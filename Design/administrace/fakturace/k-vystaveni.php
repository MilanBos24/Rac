<?php
// RAC FAKTURACE STEP05-01 | vyber uhrazene objednavky | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_once __DIR__.'/../config/permissions.php';
require_once __DIR__.'/inc/rac-faktury-core.php';
require_min_role('admin');
$viewer=current_user_row();$isSuper=$viewer&&strtolower((string)($viewer['role']??''))==='superadmin';
if(!$viewer||(!$isSuper&&!rac_can($pdo,$viewer,'orders.view'))){http_response_code(403);exit('Přístup odepřen.');}
$orders=[];$errors=[];
try {
 $sql="SELECT o.id,o.order_number,o.email,o.billing_name,o.billing_country,o.total,o.currency,MAX(p.id) AS payment_id
 FROM shop_orders o INNER JOIN shop_payments p ON p.order_id=o.id AND p.status='paid'
 LEFT JOIN fakturace_doklady f ON f.order_id=o.id WHERE f.id IS NULL
 GROUP BY o.id,o.order_number,o.email,o.billing_name,o.billing_country,o.total,o.currency
 ORDER BY o.id DESC LIMIT 50";
 $orders=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $ex){error_log('RAC invoice candidates: '.$ex->getMessage());$errors[]='Nepodařilo se načíst zaplacené objednávky.';}
$racUsePortoLayout=true;$base='/administrace';$assets=$base;$title='Objednávky k fakturaci | RAC';$pageTitle=$title;
$currentPath='/administrace/fakturace/faktury.php';$viewerName=(string)($viewer['username']??'Administrátor');
$viewerRole=(string)($viewer['role']??'');$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
$e=static function($v):string{return racFakturaH($v);};$menuGroups=[];
include __DIR__.'/../inc/header.class.php';include __DIR__.'/../inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0"><header class="page-header"><h2>Zaplacené objednávky k fakturaci</h2></header><div class="row pt-3"><div class="col-12">
<a class="btn btn-default mb-3" href="faktury.php">← Zpět na faktury</a>
<div class="alert alert-info">Automatika vystavuje i TEST doklady (označení TEST, bez účetní platnosti). V seznamu jsou zaplacené objednávky bez faktury. Podmínky: CZK, česká fakturační adresa a dodavatel neplátce DPH.</div>
<?php foreach($errors as $error): ?><div class="alert alert-danger"><?= $e($error) ?></div><?php endforeach; ?>
<section class="card"><header class="card-header"><h2 class="card-title">Objednávky</h2></header><div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>Objednávka</th><th>Zákazník</th><th>Země</th><th>Uhrazeno</th><th></th></tr></thead><tbody>
<?php foreach($orders as $o): ?><tr><td><?= $e($o['order_number']) ?></td><td><?= $e($o['billing_name']) ?><div class="small text-muted"><?= $e($o['email']) ?></div></td><td><?= $e($o['billing_country']) ?></td><td><?= $e(racFakturaMoney($o['total'],(string)$o['currency'])) ?></td><td><a class="btn btn-primary btn-sm" href="vystavit.php?order_id=<?= (int)$o['id'] ?>">Zkontrolovat</a></td></tr><?php endforeach; ?>
<?php if(!$orders): ?><tr><td colspan="5">Žádné objednávky k vystavení.</td></tr><?php endif; ?></tbody></table></div></div></section>
</div></div></section><?php include __DIR__.'/../inc/footer.class.php'; ?>
