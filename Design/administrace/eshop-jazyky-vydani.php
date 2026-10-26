<?php
// RAC 2026-10-22-01 | sprava jazyku vydani knih | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$viewer=current_user_row();
if (!$viewer || !rac_can($pdo,$viewer,'products.view')) {http_response_code(403);exit('Nemáte oprávnění zobrazit jazyky vydání.');}
$canEdit=rac_can($pdo,$viewer,'products.edit');
$e=static function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
$errors=[];$notice='';$csrf=csrf_token('rac_edition_languages');
try {
 $pdo->query('SELECT code FROM book_edition_languages LIMIT 0');
} catch (Throwable $ex) {http_response_code(503);exit('Nejprve spusťte SQL migraci 022_book_edition_languages.sql.');}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
 if(!$canEdit){$errors[]='Nemáte oprávnění měnit seznam jazyků.';}
 elseif(!csrf_valid('rac_edition_languages',(string)($_POST['csrf']??''))){$errors[]='Neplatný bezpečnostní token.';}
 else {
  $action=(string)($_POST['action']??'');
  try {
   if($action==='add') {
    $code=strtolower(trim((string)($_POST['code']??'')));
    $name=trim((string)($_POST['native_name']??''));
    $sort=filter_var($_POST['sort_order']??100,FILTER_VALIDATE_INT);
    if(!preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/D',$code)||strlen($code)>16)throw new RuntimeException('Jazykový kód musí odpovídat např. it, ar, es nebo pt-br.');
    if($name===''||mb_strlen($name)>120)throw new RuntimeException('Vyplňte název jazyka (max. 120 znaků).');
    if($sort===false||$sort<0||$sort>99999)throw new RuntimeException('Neplatné pořadí.');
    $st=$pdo->prepare('INSERT INTO book_edition_languages(code,native_name,sort_order,active) VALUES (?,?,?,1)');
    $st->execute([$code,$name,$sort]);
    $notice='Jazyk vydání byl přidán.';
   } elseif($action==='update') {
    $code=(string)($_POST['code']??'');
    $name=trim((string)($_POST['native_name']??''));
    $sort=filter_var($_POST['sort_order']??null,FILTER_VALIDATE_INT);
    $active=isset($_POST['active'])?1:0;
    if(!preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/D',$code)||strlen($code)>16)throw new RuntimeException('Neplatný kód jazyka.');
    if($name===''||mb_strlen($name)>120||$sort===false||$sort<0||$sort>99999)throw new RuntimeException('Neplatný název nebo pořadí.');
    if(!$active) {
     $check=$pdo->prepare('SELECT COUNT(*) FROM product_variants WHERE language_code=? AND active=1');
     $check->execute([$code]);
     if((int)$check->fetchColumn()>0)throw new RuntimeException('Jazyk má aktivní vydání. Nejprve deaktivujte prodej všech jeho vydání.');
    }
    $st=$pdo->prepare('UPDATE book_edition_languages SET native_name=?,sort_order=?,active=? WHERE code=?');
    $st->execute([$name,$sort,$active,$code]);
    $notice='Nastavení jazyka bylo uloženo.';
   } else throw new RuntimeException('Neznámá operace.');
  } catch(Throwable $ex){error_log('RAC edition language: '.$ex->getMessage());$errors[]='Změnu se nepodařilo uložit: '.$ex->getMessage();}
 }
}
$rows=$pdo->query('SELECT code,native_name,sort_order,active FROM book_edition_languages ORDER BY active DESC,sort_order,native_name,code')->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Jazyky vydání knih';$title=$pageTitle.' | RAC';
$racUsePortoLayout=true;$base='/administrace';$assets=$base;
$viewerName=(string)($viewer['username']??'Administrátor');
$currentPath='/administrace/eshop-jazyky-vydani.php';
$viewerRole=(string)($viewer['role']??'');
$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
include __DIR__.'/inc/header.class.php';
include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Jazyky vydání knih</h2></header>
<div class="row mt-4"><div class="col-12">
<p class="text-muted">Jazyky vydání jsou nezávislé na překladech webu. Zde můžete přidávat například italštinu, arabštinu nebo portugalštinu, aniž by se měnily jazykové mutace stránek.</p>
<?php if($notice!==''): ?><div class="alert alert-success"><?= $e($notice) ?></div><?php endif; ?>
<?php foreach($errors as $error): ?><div class="alert alert-danger"><?= $e($error) ?></div><?php endforeach; ?>
<?php if($canEdit): ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Přidat jazyk vydání</h2></header><div class="card-body">
<form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="add">
<div class="row g-3 align-items-end"><div class="col-md-2"><label class="form-label">Kód (ISO)</label><input class="form-control" name="code" maxlength="16" placeholder="it" required pattern="[a-zA-Z]{2,3}(-[a-zA-Z0-9]{2,8})?"></div>
<div class="col-md-5"><label class="form-label">Název v původním jazyce</label><input class="form-control" name="native_name" maxlength="120" placeholder="Italiano / العربية" required></div>
<div class="col-md-2"><label class="form-label">Pořadí</label><input class="form-control" name="sort_order" type="number" min="0" max="99999" value="100"></div>
<div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Přidat jazyk</button></div></div></form>
</div></section>
<?php endif; ?>
<section class="card"><header class="card-header"><h2 class="card-title">Dostupné jazyky vydání</h2></header><div class="card-body">
<div class="table-responsive"><table class="table table-striped table-bordered align-middle"><thead><tr><th>Kód</th><th>Název jazyka</th><th>Pořadí</th><th>Aktivní</th><th>Akce</th></tr></thead><tbody>
<?php foreach($rows as $row): $formId='edition-language-'.preg_replace('/[^a-z0-9-]/','',(string)$row['code']); ?>
<tr><td><strong><?= $e($row['code']) ?></strong></td>
<td><input class="form-control" form="<?= $e($formId) ?>" name="native_name" value="<?= $e($row['native_name']) ?>" maxlength="120" <?= $canEdit?'':'readonly' ?>></td>
<td><input class="form-control" form="<?= $e($formId) ?>" name="sort_order" type="number" min="0" max="99999" value="<?= (int)$row['sort_order'] ?>" <?= $canEdit?'':'readonly' ?>></td>
<td><input type="checkbox" form="<?= $e($formId) ?>" name="active" value="1" <?= (int)$row['active']===1?'checked':'' ?> <?= $canEdit?'':'disabled' ?> aria-label="Aktivní jazyk <?= $e($row['code']) ?>"></td>
<td><form method="post" id="<?= $e($formId) ?>"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="code" value="<?= $e($row['code']) ?>"><button class="btn btn-sm btn-primary" type="submit" <?= $canEdit?'':'disabled' ?>>Uložit</button></form></td></tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5">Žádné jazyky. Nejprve proveďte migraci 022.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="text-muted small mb-0">Vypnutí jazyka nemaže historická vydání ani objednávky. Nelze vypnout jazyk, který má právě aktivní vydání.</p>
</div></section></div></div></section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
