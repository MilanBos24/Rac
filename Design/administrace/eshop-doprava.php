<?php
// RAC Eshop doprava - Porto design 2026-10-12; business logic 2026-10-09-01 PHP 7.4
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_once __DIR__ . '/config/cms.php';
require_min_role('admin');

$errors = array(); $ok = null;
$csrf = csrf_token('csrf_eshop_shipping');
$languages = cms_get_active_languages($pdo);
if (!$languages) { die('Nejsou dostupné aktivní jazyky.'); }
$languageIds = array_map(static function($row) { return (int)$row['id']; }, $languages);
$types = array('pickup_point'=>'Výdejní místo / box','address'=>'Na adresu','personal'=>'Osobní odběr');
$selectedId = isset($_GET['edit']) ? max(0,(int)$_GET['edit']) : 0;
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!csrf_valid('csrf_eshop_shipping', (string)($_POST['csrf'] ?? ''))) {
  $errors[] = 'Neplatný CSRF token.';
 } else {
  $id = max(0,(int)($_POST['id'] ?? 0));
  $code = strtolower(trim((string)($_POST['code'] ?? '')));
  $type = (string)($_POST['type'] ?? '');
  $sort = (int)($_POST['sort_order'] ?? 100);
  $active = isset($_POST['active']) ? 1 : 0;
  $maxWeightInput = trim((string)($_POST['max_weight_grams'] ?? ''));
  $maxWeight = $maxWeightInput === '' ? null : filter_var($maxWeightInput, FILTER_VALIDATE_INT, array('options'=>array('min_range'=>1)));
  $country = strtoupper(trim((string)($_POST['country_code'] ?? 'CZ')));
  $currency = strtoupper(trim((string)($_POST['currency'] ?? 'CZK')));
  $priceStr = str_replace(',', '.', trim((string)($_POST['price'] ?? '')));
  $freeStr = str_replace(',', '.', trim((string)($_POST['free_from'] ?? '')));
  $names = isset($_POST['name']) && is_array($_POST['name']) ? $_POST['name'] : array();
  $descriptions = isset($_POST['description']) && is_array($_POST['description']) ? $_POST['description'] : array();
  $defaultLanguageId = (int)$languages[0]['id'];
  if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,59}$/D', $code)) $errors[] = 'Kód musí mít 2–60 znaků: malá písmena, čísla, pomlčka nebo podtržítko.';
  if (!isset($types[$type])) $errors[] = 'Neplatný typ dopravy.';
  if ($maxWeight === false) $errors[] = 'Maximální hmotnost musí být kladné celé číslo.';
  if (!preg_match('/^[A-Z]{2}$/D', $country)) $errors[] = 'Země musí být dvoupísmenný ISO kód.';
  if (!preg_match('/^[A-Z]{3}$/D', $currency)) $errors[] = 'Měna musí mít tři písmena.';
  if (!is_numeric($priceStr) || (float)$priceStr < 0 || (float)$priceStr > 99999999 || !preg_match('/^\d+(\.\d{1,2})?$/D', $priceStr)) $errors[] = 'Neplatná cena dopravy.';
  if ($freeStr !== '' && (!is_numeric($freeStr) || (float)$freeStr < 0 || !preg_match('/^\d+(\.\d{1,2})?$/D', $freeStr))) $errors[] = 'Neplatný limit dopravy zdarma.';
  if (trim((string)($names[$defaultLanguageId] ?? '')) === '') $errors[] = 'Vyplň název ve výchozím jazyce.';
  foreach ($names as $name) if (mb_strlen(trim((string)$name)) > 190) $errors[] = 'Název nesmí přesáhnout 190 znaků.';
  if (!$errors) {
   try {
    $pdo->beginTransaction();
    if ($id > 0) {
     $check = $pdo->prepare('SELECT id FROM shipping_methods WHERE id=? FOR UPDATE');
     $check->execute(array($id));
     if (!$check->fetchColumn()) throw new RuntimeException('Doprava nebyla nalezena.');
     $st = $pdo->prepare('UPDATE shipping_methods SET code=?, type=?, active=?, sort_order=?, max_weight_grams=? WHERE id=?');
     $st->execute(array($code,$type,$active,$sort,$maxWeight,$id));
    } else {
     $st=$pdo->prepare('INSERT INTO shipping_methods (code,type,active,sort_order,max_weight_grams) VALUES (?,?,?,?,?)');
     $st->execute(array($code,$type,$active,$sort,$maxWeight));
     $id=(int)$pdo->lastInsertId();
    }
    $tr=$pdo->prepare('INSERT INTO shipping_method_translations (method_id,language_id,name,description) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description)');
    foreach($languageIds as $langId) {
     $name=trim((string)($names[$langId] ?? ''));
     if ($name === '') continue;
     $description=trim((string)($descriptions[$langId] ?? ''));
     $tr->execute(array($id,$langId,$name,$description === '' ? null : $description));
    }
    $rate=$pdo->prepare('INSERT INTO shipping_rates (method_id,country_code,currency,price,free_from,active) VALUES (?,?,?,?,?,1) ON DUPLICATE KEY UPDATE price=VALUES(price),free_from=VALUES(free_from),active=1');
    $rate->execute(array($id,$country,$currency,$priceStr,$freeStr === '' ? null : $freeStr));
    $pdo->commit();
    $selectedId=$id;
    $ok='Způsob dopravy byl uložen.';
   } catch(Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('RAC shipping save: '.$e->getMessage());
    $errors[]='Uložení se nezdařilo. Zkontrolujte unikátnost kódu a import SQL migrace.';
   }
  }
 }
}
try {
 $methods=$pdo->query('SELECT m.*, (SELECT name FROM shipping_method_translations t WHERE t.method_id=m.id ORDER BY t.language_id LIMIT 1) AS display_name FROM shipping_methods m ORDER BY m.sort_order,m.id')->fetchAll(PDO::FETCH_ASSOC);
 if ($selectedId) {
  $st=$pdo->prepare('SELECT * FROM shipping_methods WHERE id=?'); $st->execute(array($selectedId)); $editing=$st->fetch(PDO::FETCH_ASSOC);
 }
 $translations=array(); $rates=array();
 if ($editing) {
  $st=$pdo->prepare('SELECT * FROM shipping_method_translations WHERE method_id=?'); $st->execute(array($selectedId));
  foreach($st->fetchAll(PDO::FETCH_ASSOC) as $row) $translations[(int)$row['language_id']]=$row;
  $st=$pdo->prepare('SELECT * FROM shipping_rates WHERE method_id=? ORDER BY country_code,currency'); $st->execute(array($selectedId)); $rates=$st->fetchAll(PDO::FETCH_ASSOC);
 }
} catch(Throwable $e) {
 error_log('RAC shipping load: '.$e->getMessage());
 http_response_code(500); die('Modul dopravy nelze načíst. Importujte database/009_eshop_shipping.sql.');
}
$formRate = array('country_code'=>'CZ','currency'=>'CZK','price'=>'0.00','free_from'=>'');
foreach ($rates as $existingRate) {
 if ($existingRate['country_code'] === 'CZ' && $existingRate['currency'] === 'CZK') { $formRate=$existingRate; break; }
}

$pageTitle = 'Eshop – Doprava';
$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Eshop – Doprava | RAC';
$viewer = current_user_row();
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['E-shop', [
        ['Doprava', '/administrace/eshop-doprava.php', 'icons icon-drawer'],
    ]],
    ['Billing', [
        ['Nastavení Stripe', '/administrace/billing/admin.php', 'el el-cogs'],
        ['Produkty', '/administrace/billing/admin-produkty.php', 'icons icon-basket'],
        ['Platby', '/administrace/billing/admin-platby.php', 'el el-credit-card'],
        ['Předplatné', '/administrace/billing/admin-predplatne.php', 'icons icon-refresh'],
        ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
        ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
    ]],
];
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/sprava-admin.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;

?>
<?php include __DIR__ . '/inc/header.class.php'; ?>
<?php include __DIR__ . '/inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Eshop – Doprava</h2><div class="right-wrapper text-end"><ol class="breadcrumbs"><li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li><li><span>E-shop</span></li><li><span>Doprava</span></li></ol></div></header>
<div class="row mt-4"><div class="col-12">
<?php if ($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="card mb-4">
<header class="card-header"><h2 class="card-title"><?= $editing ? 'Upravit dopravu' : 'Přidat dopravu' ?></h2></header>
<form method="post" action="" autocomplete="off">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
<div class="card-body">
<div class="row g-3 mb-3">
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="ship_code">Kód</label><input id="ship_code" class="form-control" name="code" required maxlength="60" value="<?= h($editing['code'] ?? '') ?>" placeholder="zasilkovna_vydejni"></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="ship_type">Typ dopravy</label><select id="ship_type" class="form-select" name="type"><?php foreach($types as $value=>$label): ?><option value="<?= h($value) ?>" <?= ($editing['type'] ?? '')===$value?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="ship_order">Pořadí</label><input id="ship_order" class="form-control" type="number" name="sort_order" value="<?= h($editing['sort_order'] ?? 100) ?>"></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="ship_weight">Max. hmotnost (g, volitelné)</label><input id="ship_weight" class="form-control" type="number" min="1" name="max_weight_grams" value="<?= h($editing['max_weight_grams'] ?? '') ?>"></div>
<div class="col-12 col-md-6 col-xl-4 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" id="ship_active" type="checkbox" name="active" value="1" <?= !$editing || (int)$editing['active']===1?'checked':'' ?>><label class="form-check-label" for="ship_active">Aktivní metoda</label></div></div>
</div>
<h3 class="h5 mt-4 mb-3">Texty podle jazyka</h3>
<?php foreach($languages as $language): $langId=(int)$language['id']; ?>
<div class="row g-3 mb-3">
<div class="col-12 col-lg-6"><label class="form-label" for="ship_name_<?= $langId ?>">Název – <?= h($language['native_name']) ?></label><input id="ship_name_<?= $langId ?>" class="form-control" maxlength="190" name="name[<?= $langId ?>]" value="<?= h($translations[$langId]['name'] ?? '') ?>" <?= $langId===(int)$languages[0]['id']?'required':'' ?>></div>
<div class="col-12 col-lg-6"><label class="form-label" for="ship_desc_<?= $langId ?>">Popis – <?= h($language['native_name']) ?></label><input id="ship_desc_<?= $langId ?>" class="form-control" name="description[<?= $langId ?>]" value="<?= h($translations[$langId]['description'] ?? '') ?>"></div>
</div>
<?php endforeach; ?>
<h3 class="h5 mt-4 mb-2">Ceník pro jednu zemi</h3><p class="text-muted">Další země přidáš po uložení metody; stávající sazby jsou uvedeny níže.</p>
<div class="row g-3">
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="ship_country">Země (ISO 2)</label><input id="ship_country" class="form-control" name="country_code" maxlength="2" value="<?= h($formRate['country_code']) ?>" required></div>
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="ship_currency">Měna</label><input id="ship_currency" class="form-control" name="currency" maxlength="3" value="<?= h($formRate['currency']) ?>" required></div>
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="ship_price">Cena</label><input id="ship_price" class="form-control" name="price" value="<?= h($formRate['price']) ?>" required></div>
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="ship_free">Doprava zdarma od</label><input id="ship_free" class="form-control" name="free_from" value="<?= h($formRate['free_from'] ?? '') ?>" placeholder="např. 1500"></div>
</div>
<?php if ($rates): ?><h3 class="h5 mt-4">Uložené sazby</h3><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Země</th><th>Měna</th><th>Cena</th><th>Zdarma od</th></tr></thead><tbody><?php foreach($rates as $r): ?><tr><td><?= h($r['country_code']) ?></td><td><?= h($r['currency']) ?></td><td><?= h($r['price']) ?></td><td><?= h($r['free_from'] ?? '—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<footer class="card-footer text-end"><a href="eshop-doprava.php" class="btn btn-default me-2">Nová metoda</a><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Uložit dopravu</button></footer>
</form>
</section>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Způsoby dopravy</h2></header><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Pořadí</th><th>Název</th><th>Typ</th><th>Stav</th><th class="text-end">Úprava</th></tr></thead><tbody>
<?php foreach($methods as $m): ?><tr><td><?= (int)$m['sort_order'] ?></td><td><?= h($m['display_name'] ?: $m['code']) ?></td><td><?= h($types[$m['type']] ?? $m['type']) ?></td><td><?php if((int)$m['active']===1): ?><span class="badge bg-success">Aktivní</span><?php else: ?><span class="badge bg-secondary">Vypnuto</span><?php endif; ?></td><td class="text-end"><a class="btn btn-default btn-sm" href="eshop-doprava.php?edit=<?= (int)$m['id'] ?>"><i class="fas fa-edit me-1"></i> Upravit / sazby</a></td></tr><?php endforeach; ?>
</tbody></table></div></div></section>
</div></div>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
