<?php
// RAC - Diagnostika kurzů ECB | 2026-10-22-FX-DIAG-02-CA | PHP 7.4+
// Jen administrátor. Bez přepisování kurzů, změny cen nebo konfigurace PHP.
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');

function racFxDiagH($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function racFxDiagResult(string $label, string $status, string $detail): array {
    return ['label'=>$label,'status'=>$status,'detail'=>$detail];
}
$checks = [];
$caFile = dirname(__DIR__) . '/inc/certs/rac-ca-bundle.pem';
$caOk = is_file($caFile) && is_readable($caFile) && filesize($caFile) > 10000;
$checks[] = racFxDiagResult('RAC CA certifikaty', $caOk ? 'ok' : 'error', $caOk ? 'Vlastni duveryhodny CA bundle je dostupny.' : 'Soubor inc/certs/rac-ca-bundle.pem chybi nebo jej PHP nedokaze precist.');
$fxPath = dirname(__DIR__).'/inc/rac-fx-display.php';
$checks[] = racFxDiagResult('Modul kurzů', is_file($fxPath) ? 'ok' : 'error',
    is_file($fxPath) ? 'Soubor inc/rac-fx-display.php existuje.' : 'Soubor inc/rac-fx-display.php chybí.');
$checks[] = racFxDiagResult('PHP', 'info', PHP_VERSION);
$hasCurl = function_exists('curl_init');
$allowStreams = filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN);
$checks[] = racFxDiagResult('cURL', $hasCurl ? 'ok' : 'warning', $hasCurl ? 'cURL je dostupné.' : 'cURL není dostupné.');
$checks[] = racFxDiagResult('allow_url_fopen', $allowStreams ? 'ok' : 'warning', $allowStreams ? 'HTTPS stream může být dostupný.' : 'Vypnuto.');
$checks[] = racFxDiagResult('OpenSSL', extension_loaded('openssl') ? 'ok' : 'warning', extension_loaded('openssl') ? 'Rozšíření je dostupné.' : 'Rozšíření není dostupné.');

$cache = rtrim(sys_get_temp_dir(), '/\\') . '/rac_ecb_display_rates_v1.json';
$cacheDir = dirname($cache);
$checks[] = racFxDiagResult('Adresář mezipaměti', is_dir($cacheDir) && is_writable($cacheDir) ? 'ok' : 'error',
    (is_dir($cacheDir) && is_writable($cacheDir)) ? 'Dočasný adresář je zapisovatelný.' : 'Dočasný adresář není zapisovatelný.');
if (is_file($cache)) {
    $old = json_decode((string)@file_get_contents($cache), true);
    if (is_array($old) && isset($old['CZK'], $old['USD'], $old['fetched']) &&
        is_numeric($old['CZK']) && is_numeric($old['USD']) && is_numeric($old['fetched'])) {
        $age = time() - (int)$old['fetched'];
        $plausible = (float)$old['CZK'] > 5 && (float)$old['CZK'] < 100 && (float)$old['USD'] > 0.2 && (float)$old['USD'] < 4;
        $fresh = $age >= 0 && $age < 86400;
        $checks[] = racFxDiagResult('Uložené kurzy', !$plausible ? 'error' : ($fresh ? 'ok' : 'warning'),
            'Poslední uložení: '.date('d.m.Y H:i:s', (int)$old['fetched']).' (čas serveru). '.
            'EUR = '.(string)$old['CZK'].' CZK; EUR = '.(string)$old['USD'].' USD.'.
            ($fresh ? ' Mezipaměť je aktuální.' : ' Mezipaměť je starší než 24 hodin.'));
    } else {
        $checks[] = racFxDiagResult('Uložené kurzy', 'error', 'Soubor mezipaměti existuje, ale nemá očekávaný obsah.');
    }
} else {
    $checks[] = racFxDiagResult('Uložené kurzy', 'warning', 'Soubor mezipaměti dosud neexistuje.');
}

// Poskytne vlastní měření sítě, bez modifikace cache. Jen na výslovné vyžádání administrátorem.
$network = [];
$rates = [];
$run = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'test';
if ($run) {
    if (!function_exists('csrf_valid') || !csrf_valid('rac_fx_diag', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        $checks[] = racFxDiagResult('Spuštění testu', 'error', 'Neplatný bezpečnostní token.');
        $run = false;
    }
}
if ($run && $caOk) {
    $urls = [
        'ECB XML' => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
        'ECB CSV' => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.csv',
    ];
    foreach ($urls as $label=>$url) {
        $body = false; $error = ''; $http = 0;
        if ($hasCurl) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>2,
                    CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>6, CURLOPT_SSL_VERIFYPEER=>true,
                    CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_CAINFO=>$caFile, CURLOPT_USERAGENT=>'RAC-FX-Diagnostics/1.0'
                ]);
                $body = curl_exec($ch);
                $http = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                $error = (string)curl_error($ch);
                curl_close($ch);
            } else {$error='Nelze inicializovat cURL.';}
        } elseif ($allowStreams) {
            $ctx = stream_context_create(['http'=>['timeout'=>6,'follow_location'=>1,'max_redirects'=>2,'user_agent'=>'RAC-FX-Diagnostics/1.0'],
                                          'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'cafile'=>$caFile]]);
            $body = @file_get_contents($url, false, $ctx);
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $line) { if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $m)) $http=(int)$m[1]; }
            }
            if ($body === false) {$error='HTTPS stream selhal (podrobnosti hledejte v PHP error logu).';}
        } else {$error='Není dostupné cURL ani allow_url_fopen.';}
        $valid = is_string($body) && strlen($body) > 10 && strlen($body) < 100000 && $http === 200;
        if (!$valid) {
            $network[] = racFxDiagResult($label, 'error', 'HTTP '.$http.'; '.($error!==''?$error:'Neplatná nebo prázdná odpověď.'));
            continue;
        }
        $czk = null; $usd = null;
        if ($label === 'ECB XML') {
            if (preg_match_all('~<Cube\b[^>]*\bcurrency\s*=\s*["\']([A-Z]{3})["\'][^>]*\brate\s*=\s*["\']([0-9.]+)["\']~i', $body, $hits, PREG_SET_ORDER)) {
                foreach ($hits as $hit) { if (strtoupper($hit[1])==='CZK') $czk=(float)$hit[2]; if (strtoupper($hit[1])==='USD') $usd=(float)$hit[2]; }
            }
        } else {
            $lines = preg_split('/\r\n|\n|\r/', trim($body));
            if (count($lines) >= 2) {
                $head = str_getcsv($lines[0]); $values = str_getcsv($lines[1]);
                foreach ($head as $i=>$code) {if (trim($code)==='CZK' && isset($values[$i])) $czk=(float)$values[$i]; if (trim($code)==='USD' && isset($values[$i])) $usd=(float)$values[$i];}
            }
        }
        $good = $czk !== null && $usd !== null && $czk > 5 && $czk < 100 && $usd > .2 && $usd < 4;
        $network[] = racFxDiagResult($label, $good ? 'ok' : 'error',
            'HTTP '.$http.'; velikost odpovědi '.strlen($body).' B. '.
            ($good ? 'Kurzy úspěšně rozpoznány.' : 'Odpověď dorazila, ale současný parser z ní nevyčetl oba kurzy.'));
        if ($good) {$rates[$label] = ['CZK'=>$czk,'USD'=>$usd,'eurPrice'=>199/$czk,'usdPrice'=>199*$usd/$czk];}
    }
}
if ($run && !$caOk) {$network[] = racFxDiagResult('ECB TLS', 'error', 'Nejprve nahrajte CA bundle do inc/certs/.');}
$viewer = current_user_row();
$viewerName = (string)($viewer['username'] ?? 'Administrátor');
$viewerRole = (string)($viewer['role'] ?? '');
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$racUsePortoLayout=true;
$base='/administrace';$assets=$base;
$pageTitle='Diagnostika kurzů ECB';$title=$pageTitle.' | RAC';
$currentPath='/administrace/rac-diagnostika-kurzu.php';
$menuGroups=[];
$e=static function($value): string {return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');};
include __DIR__.'/inc/header.class.php';
include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Diagnostika kurzů ECB</h2></header>
<div class="row pt-3"><div class="col-12 col-xl-10">
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Kontrola prostředí a mezipaměti</h2></header><div class="card-body">
<p class="text-muted">Diagnostika pouze čte konfiguraci a testuje připojení. Nemění ceny, objednávky ani uložené kurzy.</p>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Kontrola</th><th>Stav</th><th>Výsledek</th></tr></thead><tbody>
<?php foreach($checks as $item): ?>
<tr><td><?= racFxDiagH($item['label']) ?></td><td><span class="badge bg-<?= $item['status']==='ok'?'success':($item['status']==='error'?'danger':($item['status']==='warning'?'warning':'secondary')) ?>"><?= racFxDiagH(['ok'=>'OK','error'=>'CHYBA','warning'=>'UPOZORNĚNÍ','info'=>'INFO'][$item['status']]??$item['status']) ?></span></td><td><?= racFxDiagH($item['detail']) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<form method="post"><input type="hidden" name="csrf" value="<?= racFxDiagH(csrf_token('rac_fx_diag')) ?>"><input type="hidden" name="action" value="test"><button type="submit" class="btn btn-primary"><i class="fas fa-sync me-1"></i> Otestovat připojení k ECB</button></form>
</div></section>
<?php if ($run): ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Výsledek síťového testu</h2></header><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Zdroj</th><th>Stav</th><th>Výsledek</th></tr></thead><tbody>
<?php foreach($network as $item): ?><tr><td><?= racFxDiagH($item['label']) ?></td><td><?= $item['status']==='ok'?'OK':'CHYBA' ?></td><td><?= racFxDiagH($item['detail']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php foreach($rates as $label=>$r): ?>
<p><strong><?= racFxDiagH($label) ?>:</strong> 1 EUR = <?= number_format($r['CZK'],4,',',' ') ?> CZK; 1 EUR = <?= number_format($r['USD'],4,',',' ') ?> USD. Příklad 199,00 CZK (≈ <?= number_format($r['eurPrice'],2,',',' ') ?> € | ≈ <?= number_format($r['usdPrice'],2,',',' ') ?> $)</p>
<?php endforeach; ?>
</div></section>
<?php endif; ?>
<div class="alert alert-info">Pokud síťový test dopadne úspěšně, ale cena na webu stále nemá přepočet, zkontrolujeme napojení <code>obsah/produkt.class.php</code> na <code>inc/rac-fx-display.php</code>. Pokud selže obojí, pošlete mi z této stránky stav a text chyby.</div>
</div></div></section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
