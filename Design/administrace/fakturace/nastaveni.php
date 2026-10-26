<?php
// RAC FAKTURACE NASTAVENI | 2026-10-25-STEP03-03 | PHP 7.4+
declare(strict_types=1);
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/auth_roles.php';
require_min_role('admin');

function racFaktNastH($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$racFaktDefaults = array(
    'nazev_dodavatele' => '', 'ico' => '', 'dic' => '', 'ulice' => '', 'mesto' => '',
    'psc' => '', 'stat' => 'Česká republika', 'email' => '', 'telefon' => '', 'web' => '',
    'platce_dph' => 0, 'vychozi_sazba_dph' => '0.00', 'mena' => 'CZK',
    'nazev_uctu' => '', 'typ_uctu' => 'tuzemsky', 'cislo_uctu' => '', 'kod_banky' => '', 'mena_uctu' => 'CZK', 'iban' => '', 'bic' => '',
    'rada_faktura_prefix' => 'FV', 'rada_faktura_rok' => (int)date('Y'),
    'rada_faktura_dalsi_cislo' => 1,
);
$errors = array();
$success = isset($_GET['ulozeno']) && $_GET['ulozeno'] === '1' ? 'Nastavení fakturace bylo uloženo.' : '';
$settings = $racFaktDefaults;
try {
    $stmt = $pdo->query('SELECT * FROM fakturace_nastaveni WHERE id=1 LIMIT 1');
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($record)) {
        $settings = array_merge($settings, $record);
    }
} catch (Throwable $e) {
    error_log('RAC fakturace nastaveni load: ' . $e->getMessage());
    $errors[] = 'Není možné načíst nastavení. Zkontroluj instalaci SQL migrace 026.';
}

// Samostatny session token; nezasahuje do ostatnich formularu administrace.
if (session_status() !== PHP_SESSION_ACTIVE) {
    throw new RuntimeException('Administrátorská relace není aktivní.');
}
if (!isset($_SESSION['rac_fakturace_settings_csrf'])) {
    $_SESSION['rac_fakturace_settings_csrf'] = bin2hex(random_bytes(24));
}
$csrf = (string)$_SESSION['rac_fakturace_settings_csrf'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$errors) {
    $postedToken = (string)($_POST['csrf'] ?? '');
    if ($postedToken === '' || !hash_equals($csrf, $postedToken)) {
        $errors[] = 'Neplatný bezpečnostní token, obnov stránku.';
    } else {
        $textLimits = array(
            'nazev_dodavatele' => 255, 'ico' => 20, 'dic' => 30, 'ulice' => 255,
            'mesto' => 120, 'psc' => 20, 'stat' => 120, 'email' => 190,
            'telefon' => 50, 'web' => 190, 'nazev_uctu' => 100, 'cislo_uctu' => 50, 'kod_banky' => 12, 'mena_uctu' => 3, 'iban' => 50,
            'bic' => 20, 'rada_faktura_prefix' => 20
        );
        foreach ($textLimits as $name => $maxLength) {
            $raw = $_POST[$name] ?? '';
            $value = is_string($raw) ? trim($raw) : '';
            if (mb_strlen($value, 'UTF-8') > $maxLength || preg_match('/[\r\n]/u', $value)) {
                $errors[] = 'Neplatná délka nebo obsah pole: ' . $name;
            }
            $settings[$name] = $value;
        }
        $settings['platce_dph'] = isset($_POST['platce_dph']) && $_POST['platce_dph'] === '1' ? 1 : 0;
        $rateInput = str_replace(',', '.', trim((string)($_POST['vychozi_sazba_dph'] ?? '0')));
        if (!preg_match('/^(?:\d{1,2})(?:\.\d{1,2})?$/D', $rateInput) || (float)$rateInput > 99) {
            $errors[] = 'Sazba DPH musí být číslo od 0 do 99 se dvěma desetinnými místy.';
        }
        $settings['vychozi_sazba_dph'] = $rateInput;
        $settings['mena'] = 'CZK';
        $settings['typ_uctu'] = (string)($_POST['typ_uctu'] ?? 'tuzemsky');
        if (!in_array($settings['typ_uctu'], array('tuzemsky','zahranicni'), true)) {
            $errors[] = 'Vyber platný typ bankovního účtu.';
        }
        $settings['mena_uctu'] = strtoupper(trim((string)$settings['mena_uctu']));
        if (!preg_match('/^[A-Z]{3}$/D', $settings['mena_uctu'])) {
            $errors[] = 'Měna účtu musí být třípísmenný kód, například CZK nebo EUR.';
        }
        if ($settings['kod_banky'] !== '' && !preg_match('/^[0-9]{4}$/D', $settings['kod_banky'])) {
            $errors[] = 'Kód české banky musí obsahovat přesně čtyři číslice.';
        }
        if ($settings['iban'] !== '' && !preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/D', strtoupper(str_replace(' ', '', (string)$settings['iban'])))) {
            $errors[] = 'IBAN musí mít platný formát (bez ověření kontrolního součtu).';
        }
        if ($settings['bic'] !== '' && !preg_match('/^[A-Z0-9]{8}([A-Z0-9]{3})?$/D', strtoupper((string)$settings['bic']))) {
            $errors[] = 'SWIFT/BIC musí mít 8 nebo 11 znaků.';
        }

        if ($settings['nazev_dodavatele'] === '') $errors[] = 'Vyplň název dodavatele.';
        if ($settings['stat'] === '') $errors[] = 'Vyplň stát dodavatele.';
        if ($settings['email'] !== '' && !filter_var($settings['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Zadej platný e-mail dodavatele.';
        if ($settings['web'] !== '' && !filter_var($settings['web'], FILTER_VALIDATE_URL)) $errors[] = 'Zadej platnou adresu webu včetně https://.';
        if ($settings['platce_dph'] && $settings['dic'] === '') $errors[] = 'U plátce DPH je nutné vyplnit DIČ.';
        if ($settings['rada_faktura_prefix'] === '' || !preg_match('/^[a-zA-Z0-9_-]{1,20}$/D', $settings['rada_faktura_prefix'])) {
            $errors[] = 'Prefix číselné řady může obsahovat jen písmena bez diakritiky, číslice, pomlčku a podtržítko.';
        }
        $yr = filter_var($_POST['rada_faktura_rok'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 2020, 'max_range' => 2100)));
        $nextNo = filter_var($_POST['rada_faktura_dalsi_cislo'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 99999999)));
        if ($yr === false || $yr === null) $errors[] = 'Neplatný rok číselné řady.';
        if ($nextNo === false || $nextNo === null) $errors[] = 'Neplatné další číslo faktury.';
        $settings['rada_faktura_rok'] = $yr !== false ? $yr : '';
        $settings['rada_faktura_dalsi_cislo'] = $nextNo !== false ? $nextNo : '';
        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $num = $pdo->query('SELECT COUNT(*) FROM fakturace_doklady')->fetchColumn();
                $previous = $pdo->query('SELECT rada_faktura_prefix,rada_faktura_rok,rada_faktura_dalsi_cislo FROM fakturace_nastaveni WHERE id=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
                if ((int)$num > 0 && $previous) {
                    if ($settings['rada_faktura_prefix'] !== (string)$previous['rada_faktura_prefix'] || (int)$settings['rada_faktura_rok'] !== (int)$previous['rada_faktura_rok'] || (int)$settings['rada_faktura_dalsi_cislo'] < (int)$previous['rada_faktura_dalsi_cislo']) {
                        throw new RuntimeException('Po vystavení faktur nelze změnit prefix ani rok řady nebo snížit další číslo.');
                    }
                }
                $cols = array('nazev_dodavatele','ico','dic','ulice','mesto','psc','stat','email','telefon','web','platce_dph','vychozi_sazba_dph','mena','nazev_uctu','typ_uctu','cislo_uctu','kod_banky','mena_uctu','iban','bic','rada_faktura_prefix','rada_faktura_rok','rada_faktura_dalsi_cislo');
                $args = array();
                foreach ($cols as $col) {
                    $args[] = $settings[$col] === '' && !in_array($col, array('nazev_dodavatele','stat','rada_faktura_prefix','mena'), true) ? null : $settings[$col];
                }
                $names = implode(',', array_map(static function ($c) {return '`'.$c.'`';}, $cols));
                $updates = implode(',', array_map(static function ($c) {return '`'.$c.'`=VALUES(`'.$c.'`)';}, $cols));
                $sql = 'INSERT INTO fakturace_nastaveni (`id`,'.$names.') VALUES (1,'.implode(',', array_fill(0, count($cols), '?')).') ON DUPLICATE KEY UPDATE '.$updates;
                $pdo->prepare($sql)->execute($args);
                $pdo->commit();
                header('Location: nastaveni.php?ulozeno=1', true, 303);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('RAC fakturace nastaveni save: ' . $e->getMessage());
                $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Nastavení se nepodařilo uložit. Zkontroluj strukturu tabulky a log PHP.';
            }
        }
    }
}

$racUsePortoLayout = true;
$base = '/administrace'; $assets = $base;
$title = 'Nastavení fakturace | RAC';
$pageTitle = 'Nastavení fakturace';
$currentPath = '/administrace/fakturace/nastaveni.php';
$viewer = current_user_row();
$viewerName = (string)($viewer['username'] ?? 'Administrátor');
$viewerRole = (string)($viewer['role'] ?? '');
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$e = static function ($value): string { return racFaktNastH($value); };
$menuGroups = array(
    array('Přehled', array(array('Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'))),
    array('Administrace', array(array('Jazyky', '/administrace/jazyky.php', 'icons icon-globe'))),
    array('E-shop', array(array('Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'))),
);
include __DIR__ . '/../inc/header.class.php';
include __DIR__ . '/../inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header"><h2>Nastavení fakturace</h2></header>
    <div class="row pt-3"><div class="col-12">
        <?php if ($success !== ''): ?><div class="alert alert-success"><?= $e($success) ?></div><?php endif; ?>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= $e($err) ?></div><?php endforeach; ?>
        <div class="alert alert-info">Údaje se použijí pro budoucí faktury. Změny nastavení neovlivní již vystavené doklady. Faktury se zatím automaticky nevystavují.</div>
        <form method="post" action="nastaveni.php" autocomplete="off">
            <input type="hidden" name="active_tab" id="rac_active_tab" value="<?= $e((string)($_POST['active_tab'] ?? 'dodavatel')) ?>">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <div class="tabs tabs-primary mb-4" id="rac-fakturace-tabs">
                <ul class="nav nav-tabs tabs-primary" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="tab-dodavatel" data-bs-toggle="tab" data-bs-target="#dodavatel" type="button" role="tab" aria-controls="dodavatel" aria-selected="true">Údaje dodavatele</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-dph" data-bs-toggle="tab" data-bs-target="#dph" type="button" role="tab" aria-controls="dph" aria-selected="false">DPH</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-platby" data-bs-toggle="tab" data-bs-target="#platby" type="button" role="tab" aria-controls="platby" aria-selected="false">Platební údaje</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-rady" data-bs-toggle="tab" data-bs-target="#rady" type="button" role="tab" aria-controls="rady" aria-selected="false">Číselné řady</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="dodavatel" role="tabpanel" aria-labelledby="tab-dodavatel">
                        <section class="card mb-0"><header class="card-header"><h2 class="card-title">Údaje dodavatele</h2></header>
                            <div class="card-body"><div class="row g-3">
                    <?php foreach (array(
                        array('nazev_dodavatele','Název / jméno dodavatele',6),array('ico','IČO',3),array('dic','DIČ',3),
                        array('ulice','Ulice a číslo',6),array('mesto','Město',4),array('psc','PSČ',2),
                        array('stat','Stát',6),array('email','Kontaktní e-mail',6),
                        array('telefon','Telefon',6),array('web','Web',6)
                    ) as $field): ?>
                    <div class="col-md-<?= (int)$field[2] ?>">
                        <label class="form-label" for="<?= $e($field[0]) ?>"><?= $e($field[1]) ?><?= $field[0] === 'nazev_dodavatele' ? ' *' : '' ?></label>
                        <input class="form-control" id="<?= $e($field[0]) ?>" name="<?= $e($field[0]) ?>" value="<?= $e($settings[$field[0]] ?? '') ?>" maxlength="<?= isset($textLimits[$field[0]]) ? (int)$textLimits[$field[0]] : 255 ?>" <?= $field[0] === 'nazev_dodavatele' ? 'required' : '' ?>>
                    </div>
                    <?php endforeach; ?>
                </div></div>
                        </section>
                    </div>
                    <div class="tab-pane fade" id="dph" role="tabpanel" aria-labelledby="tab-dph">
                        <section class="card mb-0"><header class="card-header"><h2 class="card-title">DPH</h2></header>
                            <div class="card-body"><div class="row g-3 align-items-end">
                    <div class="col-md-6"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="platce_dph" name="platce_dph" value="1" <?= (int)$settings['platce_dph'] === 1 ? 'checked' : '' ?>><label for="platce_dph" class="form-check-label">Dodavatel je plátcem DPH</label></div></div>
                    <div class="col-md-3"><label class="form-label" for="vychozi_sazba_dph">Výchozí sazba DPH (%)</label><input class="form-control" id="vychozi_sazba_dph" name="vychozi_sazba_dph" type="text" inputmode="decimal" value="<?= $e($settings['vychozi_sazba_dph']) ?>" required></div>
                    <div class="col-md-3"><label class="form-label">Základní měna</label><input class="form-control" value="CZK" disabled><input type="hidden" name="mena" value="CZK"></div>
                </div><p class="text-muted mb-0 mt-3">Sazba je výchozí nastavení, nikoli automatické určení DPH každé knihy. Při budoucí integraci posoudíme sazbu podle typu knihy a země zákazníka.</p></div>
                        </section>
                    </div>
                    <div class="tab-pane fade" id="platby" role="tabpanel" aria-labelledby="tab-platby">
                        <section class="card mb-0"><header class="card-header"><h2 class="card-title">Platební údaje</h2></header>
                            <div class="card-body"><div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="nazev_uctu">Název účtu</label><input class="form-control" id="nazev_uctu" name="nazev_uctu" maxlength="100" value="<?= $e($settings['nazev_uctu']) ?>" placeholder="Např. Hlavní účet"></div>
                    <div class="col-md-4"><label class="form-label" for="typ_uctu">Typ účtu</label><select class="form-select" id="typ_uctu" name="typ_uctu"><option value="tuzemsky" <?= $settings['typ_uctu']==='tuzemsky'?'selected':'' ?>>Tuzemský účet</option><option value="zahranicni" <?= $settings['typ_uctu']==='zahranicni'?'selected':'' ?>>Zahraniční účet</option></select></div>
                    <div class="col-md-4"><label class="form-label" for="mena_uctu">Měna účtu</label><select class="form-select" id="mena_uctu" name="mena_uctu"><?php foreach (array('CZK','EUR','USD','GBP','PLN') as $bankCurrency): ?><option value="<?= $e($bankCurrency) ?>" <?= $settings['mena_uctu']===$bankCurrency?'selected':'' ?>><?= $e($bankCurrency) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label" for="cislo_uctu">Číslo bankovního účtu</label><input class="form-control" id="cislo_uctu" name="cislo_uctu" maxlength="50" value="<?= $e($settings['cislo_uctu']) ?>"></div>
                    <div class="col-md-4"><label class="form-label" for="kod_banky">Kód banky</label><input class="form-control" id="kod_banky" name="kod_banky" maxlength="4" inputmode="numeric" value="<?= $e($settings['kod_banky']) ?>" placeholder="Např. 0800"></div>
                    <div class="col-md-4"><label class="form-label" for="iban">IBAN</label><input class="form-control" id="iban" name="iban" maxlength="50" value="<?= $e($settings['iban']) ?>"></div>
                    <div class="col-md-4"><label class="form-label" for="bic">SWIFT / BIC</label><input class="form-control" id="bic" name="bic" maxlength="20" value="<?= $e($settings['bic']) ?>"></div>
                </div><p class="text-muted mb-0 mt-3">Platba proběhla přes Stripe, proto na faktuře nebude výzva k další úhradě.</p></div>
                        </section>
                    </div>
                    <div class="tab-pane fade" id="rady" role="tabpanel" aria-labelledby="tab-rady">
                        <section class="card mb-0"><header class="card-header"><h2 class="card-title">Číselné řady</h2></header>
                            <div class="card-body"><div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="rada_faktura_prefix">Prefix</label><input class="form-control" id="rada_faktura_prefix" name="rada_faktura_prefix" maxlength="20" value="<?= $e($settings['rada_faktura_prefix']) ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="rada_faktura_rok">Rok</label><input class="form-control" type="number" min="2020" max="2100" id="rada_faktura_rok" name="rada_faktura_rok" value="<?= $e($settings['rada_faktura_rok']) ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="rada_faktura_dalsi_cislo">Další číslo</label><input class="form-control" type="number" min="1" id="rada_faktura_dalsi_cislo" name="rada_faktura_dalsi_cislo" value="<?= $e($settings['rada_faktura_dalsi_cislo']) ?>" required></div>
                </div><p class="text-muted mb-0 mt-3">Číselná řada se použije až při automatickém vystavování faktur. Po vystavení faktur nebude možné snížit další číslo.</p></div>
                        </section>
                    </div>
                </div>
            </div>
            <div class="text-start mb-4"><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit nastavení fakturace</button></div>
        </form>
    </div></div>
</section>
<script>
(function () {
    var tabs = document.querySelectorAll('#rac-fakturace-tabs button[data-bs-toggle="tab"]');
    var active = document.getElementById('rac_active_tab');
    if (!active) return;
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener('shown.bs.tab', function () { active.value = this.getAttribute('data-bs-target').slice(1); });
        tabs[i].addEventListener('click', function () { active.value = this.getAttribute('data-bs-target').slice(1); });
    }
    var selected = active.value;
    if (selected !== 'dodavatel' && window.bootstrap && window.bootstrap.Tab) {
        var button = document.querySelector('#rac-fakturace-tabs button[data-bs-target="#' + selected + '"]');
        if (button) new window.bootstrap.Tab(button).show();
    }
})();
</script>
<?php include __DIR__ . '/../inc/footer.class.php'; ?>
