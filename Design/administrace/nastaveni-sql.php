<?php
// RAC SQL knihovna – 2026-10-20-12.40 | PHP 7.4+ | pouze superadmin
// Čte pouze lokální .sql soubory; žádné dotazy nespouští.
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$viewer=current_user_row();
if (!$viewer || strtolower((string)($viewer['role']??''))!=='superadmin') {
    http_response_code(403);
    exit('Přístup odepřen. Tato stránka je určena pouze superadminovi.');
}
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$viewerName=(string)($viewer['username']??'Superadmin');
$viewerRole=(string)($viewer['role']??'superadmin');
$roleLabel=role_label($viewerRole);
$racUsePortoLayout=true;
$base='/administrace';$assets=$base;
$pageTitle='SQL – instalační příkazy';$title='SQL knihovna | RAC';
$currentPath='/administrace/nastaveni-sql.php';
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
$folders=[
 ['title'=>'Základ systému a e-shop','path'=>__DIR__.'/database','tag'=>'RAC'],
 ['title'=>'Platební brána (Billing)','path'=>__DIR__.'/billing/database','tag'=>'Billing'],
];
$sections=[];$warnings=[];$total=0;
foreach($folders as $folder){
    $files=[];
    if(is_dir($folder['path'])) {
        foreach(glob($folder['path'].'/*.sql') ?: [] as $file) {
            if(!is_file($file)||!is_readable($file))continue;
            $size=filesize($file);
            if($size===false||$size>2*1024*1024){$warnings[]='Soubor '.basename($file).' nelze zobrazit (přes 2 MB nebo nečitelný).';continue;}
            $sql=file_get_contents($file);
            if($sql===false)continue;
            $name=basename($file);
            $files[]=['name'=>$name,'sql'=>$sql,'bytes'=>strlen($sql)];
        }
        usort($files,static function($a,$b){return strnatcasecmp($a['name'],$b['name']);});
    }else{$warnings[]='Složka '.str_replace(__DIR__.'/','',$folder['path']).' na tomto hostingu neexistuje.';}
    if($files){$sections[]=['title'=>$folder['title'],'tag'=>$folder['tag'],'files'=>$files];$total+=count($files);}
}
$allNames=[];
foreach($sections as $section){foreach($section['files'] as $file){$allNames[]=$file['name'];}}
$knownMissing=[];
foreach(['008','009','010','014','015','016','017'] as $number){
    $found=false;
    foreach($allNames as $name){if(preg_match('/^'.preg_quote($number,'/').'[_-]/',$name)){$found=true;break;}}
    if(!$found)$knownMissing[]=$number;
}
include __DIR__.'/inc/header.class.php';
include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
  <header class="page-header"><h2>SQL – instalační příkazy</h2></header>
  <div class="row mt-4"><div class="col-12">
    <div class="alert alert-info"><strong>Knihovna instalačních SQL příkazů.</strong> Zdroj jsou soubory <code>administrace/database/*.sql</code> a <code>administrace/billing/database/*.sql</code> nahrané na FTP. Stránka nic nezapisuje do databáze. Příkazy zkopíruj do phpMyAdmin a spouštěj postupně po kontrole závislostí.</div>
    <?php foreach($warnings as $warning): ?><div class="alert alert-warning"><?= $e($warning) ?></div><?php endforeach; ?>
    <?php if($knownMissing): ?><div class="alert alert-warning"><strong>Pozor:</strong> v načtených složkách nebyly nalezeny některé dříve používané číslované migrace: <?= $e(implode(', ',$knownMissing)) ?>. Bez jejich doplnění nelze tento přehled považovat za kompletní instalační sadu pro nový e-shop.</div><?php endif; ?>
    <section class="card mb-4"><header class="card-header"><h2 class="card-title">Instalační moduly (<?= (int)$total ?> SQL souborů)</h2></header>
      <div class="card-body">
      <?php if(!$sections): ?><p class="text-muted mb-0">Žádné dostupné SQL soubory nebyly nalezeny. Nahraj nejprve SQL migrace do složek database.</p><?php else: ?>
       <ul class="nav nav-tabs tabs-primary" role="tablist" id="rac-sql-tabs">
       <?php foreach($sections as $index=>$section): ?>
         <li class="nav-item" role="presentation"><button type="button" class="nav-link<?= $index===0?' active':'' ?>" id="rac-sql-tab-<?= $index ?>" data-bs-toggle="tab" data-bs-target="#rac-sql-panel-<?= $index ?>" role="tab" aria-controls="rac-sql-panel-<?= $index ?>" aria-selected="<?= $index===0?'true':'false' ?>"><?= $e($section['title']) ?> <span class="badge bg-secondary ms-1"><?= count($section['files']) ?></span></button></li>
       <?php endforeach; ?>
       </ul>
       <div class="tab-content pt-3">
       <?php foreach($sections as $index=>$section): ?>
         <div class="tab-pane fade<?= $index===0?' show active':'' ?>" id="rac-sql-panel-<?= $index ?>" role="tabpanel" aria-labelledby="rac-sql-tab-<?= $index ?>">
          <div class="row">
          <?php foreach($section['files'] as $n=>$file): $id='rac-sql-'.$index.'-'.$n; ?>
           <div class="col-12 mb-4"><section class="card card-featured card-featured-primary h-100">
             <header class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2"><h3 class="card-title mb-0"><?= $e($file['name']) ?></h3><button type="button" class="btn btn-primary btn-sm rac-copy-sql" data-target="<?= $e($id) ?>"><i class="far fa-copy me-1"></i> Kopírovat SQL</button></header>
             <div class="card-body"><p class="text-muted small mb-2">Modul: <?= $e($section['title']) ?> · <?= number_format($file['bytes'],0,',',' ') ?> bajtů</p>
             <textarea id="<?= $e($id) ?>" class="form-control font-monospace" readonly rows="12" spellcheck="false" style="font-size:12px;white-space:pre;overflow:auto;"><?= $e($file['sql']) ?></textarea>
             </div></section></div>
          <?php endforeach; ?>
          </div>
         </div>
       <?php endforeach; ?>
       </div>
      <?php endif; ?>
      </div>
    </section>
  </div></div>
</section>
<script>
document.addEventListener('click',function(event){
 var btn=event.target.closest('.rac-copy-sql');if(!btn)return;
 var el=document.getElementById(btn.getAttribute('data-target'));if(!el)return;
 var original=btn.innerHTML;
 function done(){btn.textContent='Zkopírováno';setTimeout(function(){btn.innerHTML=original;},1600);}
 if(navigator.clipboard && window.isSecureContext){
   navigator.clipboard.writeText(el.value).then(done,function(){el.focus();el.select();document.execCommand('copy');done();});
 }else{el.focus();el.select();document.execCommand('copy');done();}
});
</script>
<?php include __DIR__.'/inc/footer.class.php'; ?>
