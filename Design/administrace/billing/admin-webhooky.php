<?php
// RAC servisni stranka Billing | Porto/BOS24 | 2026-10-20-11.35 | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_login();
$viewer=current_user_row();
if (!$viewer || strtolower((string)($viewer['role']??'')) !== 'superadmin') {
    http_response_code(403);
    exit('Přístup odepřen. Stránka je dostupná pouze superadminovi.');
}
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$racUsePortoLayout=true;$base='/administrace';$assets=$base;
$viewerName=(string)($viewer['username']??'Superadmin');
$viewerRole=(string)($viewer['role']??'superadmin');
$roleLabel=function_exists('role_label')?role_label($viewerRole):'Superadmin';
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
    ]],
    ['E-shop', [
        ['Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'],
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
];
$currentPath='/administrace/billing/admin-webhooky.php';
$pageTitle='Webhooky';$title='Webhooky | RAC';
$errors=[];$events=[];$stats=['processed'=>0,'received'=>0,'ignored'=>0,'errors'=>0];$total=0;
$q=trim((string)($_GET['q']??''));if(mb_strlen($q)>120)$q=mb_substr($q,0,120);
$status=(string)($_GET['status']??'');if(!in_array($status,['','processed','received','ignored','error','failed'],true))$status='';
$mode=(string)($_GET['mode']??'');if(!in_array($mode,['','test','live'],true))$mode='';
$page=max(1,(int)($_GET['page']??1));$limit=30;$pages=1;
try{
 $statsRow=$pdo->query("SELECT COUNT(*) total, COALESCE(SUM(status='processed'),0) processed, COALESCE(SUM(status='received'),0) received, COALESCE(SUM(status='ignored'),0) ignored, COALESCE(SUM(status IN ('error','failed')),0) errors FROM billing_webhook_events")->fetch(PDO::FETCH_ASSOC);
 foreach($stats as $k=>$v)$stats[$k]=(int)($statsRow[$k]??0);
 $where=[];$params=[];
 if($status!==''){$where[]='status=:status';$params[':status']=$status;}
 if($mode!=='')$where[]='livemode='.($mode==='live'?'1':'0');
 if($q!==''){$where[]='(event_id LIKE :q1 OR event_type LIKE :q2 OR object_id LIKE :q3)';foreach(['q1','q2','q3'] as $k)$params[':'.$k]='%'.$q.'%';}
 $whereSql=$where?' WHERE '.implode(' AND ',$where):'';
 $st=$pdo->prepare('SELECT COUNT(*) FROM billing_webhook_events'.$whereSql);$st->execute($params);$total=(int)$st->fetchColumn();$pages=max(1,(int)ceil($total/$limit));$page=min($page,$pages);
 $sql='SELECT id,event_id,event_type,livemode,status,object_id,error_message,received_at,processed_at FROM billing_webhook_events'.$whereSql.' ORDER BY id DESC LIMIT '.$limit.' OFFSET '.(($page-1)*$limit);
 $st=$pdo->prepare($sql);$st->execute($params);$events=$st->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $ex){error_log('RAC Billing service webhooks: '.$ex->getMessage());$errors[]='Události se nepodařilo načíst. Ověřte původní tabulku billing_webhook_events.';}
include __DIR__.'/../inc/header.class.php';include __DIR__.'/../inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0"><header class="page-header"><h2>Webhooky – servis Billing</h2></header>
<div class="row mt-4 mb-3"><div class="col-12"><p class="text-muted">Historie původního modulu Billing. Události e-shopového Stripe Checkout se evidují samostatně.</p></div></div>
<?php foreach($errors as $err):?><div class="alert alert-warning"><?= $e($err) ?></div><?php endforeach;?>
<div class="row mb-4"><?php foreach([['Zpracované',$stats['processed'],'success'],['Přijaté',$stats['received'],'info'],['Ignorované',$stats['ignored'],'warning'],['Chyby',$stats['errors'],'danger']] as $stat):?><div class="col-lg-3 col-md-6 mb-3"><section class="card card-featured-left card-featured-<?= $e($stat[2]) ?>"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title"><?= $e($stat[0]) ?></h4><div class="info"><strong class="amount"><?= (int)$stat[1] ?></strong></div></div></div></div></div></section></div><?php endforeach;?></div>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Historie webhooků</h2></header><div class="card-body">
<form method="get" class="row g-3 align-items-end mb-4"><div class="col-lg-5"><label class="form-label">Hledat ID nebo typ události</label><input class="form-control" name="q" value="<?= $e($q) ?>"></div><div class="col-lg-3"><label class="form-label">Stav</label><select class="form-select" name="status"><?php foreach([''=>'Všechny','processed'=>'Zpracováno','received'=>'Přijato','ignored'=>'Ignorováno','error'=>'Chyba','failed'=>'Selhalo'] as $k=>$v):?><option value="<?= $e($k) ?>"<?= $status===$k?' selected':'' ?>><?= $e($v) ?></option><?php endforeach;?></select></div><div class="col-lg-2"><label class="form-label">Režim</label><select class="form-select" name="mode"><?php foreach([''=>'Vše','test'=>'TEST','live'=>'LIVE'] as $k=>$v):?><option value="<?= $e($k) ?>"<?= $mode===$k?' selected':'' ?>><?= $e($v) ?></option><?php endforeach;?></select></div><div class="col-lg-2"><button class="btn btn-primary" type="submit">Filtrovat</button></div></form>
<div class="table-responsive"><table class="table table-striped table-hover align-middle"><thead><tr><th>Datum</th><th>Událost</th><th>Event ID</th><th>Režim</th><th>Stav</th><th>Chyba</th></tr></thead><tbody><?php foreach($events as $event):?><tr><td><?= $e($event['received_at']) ?></td><td><?= $e($event['event_type']) ?></td><td class="text-break"><?= $e($event['event_id']) ?></td><td><?= (int)$event['livemode']?'LIVE':'TEST' ?></td><td><?= $e(['processed'=>'Zpracováno','received'=>'Přijato','ignored'=>'Ignorováno','error'=>'Chyba','failed'=>'Selhalo'][$event['status']]??$event['status']) ?></td><td><?= $e($event['error_message']??'') ?></td></tr><?php endforeach;?><?php if(!$events):?><tr><td colspan="6" class="text-muted">Žádné události.</td></tr><?php endif;?></tbody></table></div>
<div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span class="text-muted small">Celkem <?= $total ?> záznamů</span><nav aria-label="Stránkování webhooků"><ul class="pagination mb-0"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++):?><li class="page-item<?= $i===$page?' active':'' ?>"><a class="page-link" href="?<?= $e(http_build_query(['q'=>$q,'status'=>$status,'mode'=>$mode,'page'=>$i])) ?>"><?= $i ?></a></li><?php endfor;?></ul></nav></div>
</div></section></section><?php include __DIR__.'/../inc/footer.class.php'; ?>
