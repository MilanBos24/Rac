<?php
// RAC Eshop doprava - 2026-10-09-01 - PHP 7.4
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
$pageTitle='Eshop – Doprava';
include __DIR__.'/inc/header.class.php';
include __DIR__.'/inc/leve-meny.class.php';
?>
<main class="admin-content">
 <div class="page-header"><h1>Eshop – Doprava</h1><p>Správa přepravních metod a ceníků podle země</p></div>
 <?php if ($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
 <?php if ($errors): ?><div class="alert alert-danger"><ul><?php foreach($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
 <section class="card">
  <div class="card-header"><h2><?= $editing ? 'Upravit dopravu' : 'Přidat dopravu' ?></h2></div>
  <div class="card-body">
   <form method="post">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="grid grid-3">
     <div class="form-group"><label>Kód</label><input class="form-control" name="code" required maxlength="60" value="<?= h($editing['code'] ?? '') ?>" placeholder="zasilkovna_vydejni"></div>
     <div class="form-group"><label>Typ dopravy</label><select class="form-control" name="type"><?php foreach($types as $value=>$label): ?><option value="<?= h($value) ?>" <?= ($editing['type'] ?? '')===$value?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
     <div class="form-group"><label>Pořadí</label><input class="form-control" type="number" name="sort_order" value="<?= h($editing['sort_order'] ?? 100) ?>"></div>
    </div>
    <div class="grid grid-2">
     <div class="form-group"><label>Max. hmotnost (g, volitelné)</label><input class="form-control" type="number" min="1" name="max_weight_grams" value="<?= h($editing['max_weight_grams'] ?? '') ?>"></div>
     <div class="form-group"><label><input type="checkbox" name="active" value="1" <?= !$editing || (int)$editing['active']===1?'checked':'' ?>> Aktivní metoda</label></div>
    </div>
    <h3>Texty podle jazyka</h3>
    <?php foreach($languages as $language): $langId=(int)$language['id']; ?>
    <div class="grid grid-2">
     <div class="form-group"><label>Název – <?= h($language['native_name']) ?></label><input class="form-control" maxlength="190" name="name[<?= $langId ?>]" value="<?= h($translations[$langId]['name'] ?? '') ?>" <?= $langId===(int)$languages[0]['id']?'required':'' ?>></div>
     <div class="form-group"><label>Popis – <?= h($language['native_name']) ?></label><input class="form-control" name="description[<?= $langId ?>]" value="<?= h($translations[$langId]['description'] ?? '') ?>"></div>
    </div>
    <?php endforeach; ?>
    <h3>Ceník pro jednu zemi</h3>
    <p>Další země přidáš po uložení metody; stávající sazby jsou uvedeny níže.</p>
    <div class="grid grid-3">
     <div class="form-group"><label>Země (ISO 2)</label><input class="form-control" name="country_code" maxlength="2" value="<?= h($formRate['country_code']) ?>" required></div>
     <div class="form-group"><label>Měna</label><input class="form-control" name="currency" maxlength="3" value="<?= h($formRate['currency']) ?>" required></div>
     <div class="form-group"><label>Cena</label><input class="form-control" name="price" value="<?= h($formRate['price']) ?>" required></div>
    </div>
    <div class="grid grid-2"><div class="form-group"><label>Doprava zdarma od (nepovinné)</label><input class="form-control" name="free_from" value="<?= h($formRate['free_from'] ?? '') ?>" placeholder="např. 1500"></div></div>
    <div class="card-footer"><button type="submit" class="btn btn-primary">Uložit dopravu</button> <a href="eshop-doprava.php" class="btn btn-light">Nová metoda</a></div>
   </form>
   <?php if ($rates): ?><h3>Uložené sazby</h3><table class="table"><thead><tr><th>Země</th><th>Měna</th><th>Cena</th><th>Zdarma od</th></tr></thead><tbody><?php foreach($rates as $r): ?><tr><td><?= h($r['country_code']) ?></td><td><?= h($r['currency']) ?></td><td><?= h($r['price']) ?></td><td><?= h($r['free_from'] ?? '—') ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
 </section>
 <section class="card" style="margin-top:24px"><div class="card-header"><h2>Způsoby dopravy</h2></div><div class="card-body"><table class="table"><thead><tr><th>Pořadí</th><th>Název</th><th>Typ</th><th>Stav</th><th></th></tr></thead><tbody>
 <?php foreach($methods as $m): ?><tr><td><?= (int)$m['sort_order'] ?></td><td><?= h($m['display_name'] ?: $m['code']) ?></td><td><?= h($types[$m['type']] ?? $m['type']) ?></td><td><?= (int)$m['active']===1?'Aktivní':'Vypnuto' ?></td><td><a class="btn btn-light" href="eshop-doprava.php?edit=<?= (int)$m['id'] ?>">Upravit / sazby</a></td></tr><?php endforeach; ?>
 </tbody></table></div></section>
</main>
<?php include __DIR__.'/inc/footer.class.php'; ?>
