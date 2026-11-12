<?php
// verze 2026-11-03-13.31
// RAC Reporting – obsahová část odvozená z funkčního _reporting/obsah/reporting.class.php
?>
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>Reporting</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Reporting</span></li>
                <li><span>Google Analytics</span></li>
            </ol>
        </div>
    </header>

    <div class="row pt-3">
        <div class="col-12">
            <?php if ($setupMessage !== ''): ?>
                <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div><strong>Google Analytics reporting není dokončen.</strong><br><?= $e($setupMessage) ?></div>
                    <?php if (strtolower((string)$viewerRole) === 'superadmin'): ?>
                        <a class="btn btn-primary" href="/administrace/reporting/reporting-nastaveni.php">Nastavení reportingu</a>
                    <?php endif; ?>
                </div>
            <?php elseif ($reportingError !== null): ?>
                <div class="alert alert-danger d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div><strong>Google Analytics data se nepodařilo načíst.</strong><br><?= $e($reportingError) ?></div>
                    <?php if (strtolower((string)$viewerRole) === 'superadmin'): ?>
                        <a class="btn btn-default" href="/administrace/reporting/reporting-nastaveni.php">Zkontrolovat nastavení</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($gaReady && $reportingError === null): ?>
    <div class="row">
        <div class="col-12">
            <section class="card mb-4">
                <header class="card-header">
                    <div class="card-actions"><span class="badge badge-ecommerce badge-success">Právě online: <?= (int)$realtimeUsers ?></span></div>
                    <h2 class="card-title">Období reportu</h2>
                </header>
                <div class="card-body">
                    <form method="get" action="<?= $e($selfPath) ?>" class="row form-group pb-0 align-items-end">
                        <div class="col-lg-3 mb-3">
                            <label class="col-form-label">Vybrat období</label>
                            <select name="obdobi" id="reporting-obdobi" class="form-control">
                                <option value="7dni" <?= $period==='7dni'?'selected':'' ?>>Posledních 7 dní</option>
                                <option value="30dni" <?= $period==='30dni'?'selected':'' ?>>Posledních 30 dní</option>
                                <option value="tento-mesic" <?= $period==='tento-mesic'?'selected':'' ?>>Tento měsíc</option>
                                <option value="minuly-mesic" <?= $period==='minuly-mesic'?'selected':'' ?>>Minulý měsíc</option>
                                <option value="vlastni" <?= $period==='vlastni'?'selected':'' ?>>Vlastní období</option>
                            </select>
                        </div>
                        <div class="col-lg-3 mb-3 reporting-custom-date"><label class="col-form-label">Od</label><input type="date" name="od" value="<?= $e($startDate) ?>" class="form-control"></div>
                        <div class="col-lg-3 mb-3 reporting-custom-date"><label class="col-form-label">Do</label><input type="date" name="do" value="<?= $e($endDate) ?>" class="form-control"></div>
                        <div class="col-lg-3 mb-3 text-end"><button type="submit" class="btn btn-primary">Zobrazit report</button></div>
                    </form>
                    <p class="mb-0 text-muted">Zobrazené období: <strong><?= $e(reporting_format_date_cs($startDate)) ?></strong> až <strong><?= $e(reporting_format_date_cs($endDate)) ?></strong>. Porovnání: <?= $e(reporting_format_date_cs($previousStartDate)) ?> až <?= $e(reporting_format_date_cs($previousEndDate)) ?>.</p>
                </div>
            </section>
        </div>
    </div>

    <?php
    $cards=array(
        array('title'=>'Aktivní uživatelé','metric'=>'activeUsers','icon'=>'bx bx-user'),
        array('title'=>'Noví uživatelé','metric'=>'newUsers','icon'=>'bx bx-user-plus'),
        array('title'=>'Relace','metric'=>'sessions','icon'=>'bx bx-transfer'),
        array('title'=>'Zobrazení stránek','metric'=>'screenPageViews','icon'=>'bx bx-show'),
    );
    ?>
    <div class="row row-gutter-sm">
        <?php foreach($cards as $card): $metric=$card['metric']; $change=reporting_change_percent($summaryCurrent[$metric],$summaryPrevious[$metric]); $positive=$change===null||$change>=0; ?>
        <div class="col-sm-6 col-lg-3 mb-4"><section class="card card-featured-left card-featured-primary h-100"><div class="card-body"><div class="widget-summary">
            <div class="widget-summary-col widget-summary-col-icon"><div class="summary-icon bg-primary"><i class="<?= $e($card['icon']) ?>"></i></div></div>
            <div class="widget-summary-col"><div class="summary"><h4 class="title"><?= $e($card['title']) ?></h4><div class="info"><strong class="amount"><?= reporting_number($summaryCurrent[$metric]) ?></strong></div></div>
            <div class="summary-footer text-start"><?php if($change===null): ?><span class="text-muted">Bez srovnatelného základu</span><?php else: ?><span class="<?= $positive?'text-success':'text-danger' ?>"><i class="fas <?= $positive?'fa-arrow-up':'fa-arrow-down' ?>"></i> <?= reporting_number(abs($change),1) ?> %</span><span class="text-muted"> oproti předchozímu období</span><?php endif; ?></div></div>
        </div></div></section></div>
        <?php endforeach; ?>
    </div>

    <div class="row row-gutter-sm">
        <div class="col-xl-8 mb-4"><section class="card h-100"><header class="card-header"><h2 class="card-title">Vývoj návštěvnosti</h2><p class="card-subtitle">Aktivní uživatelé, relace a zobrazení stránek podle dnů</p></header><div class="card-body"><div class="reporting-chart-wrap"><canvas id="reporting-visits-chart" height="115"></canvas></div></div></section></div>
        <div class="col-xl-4 mb-4"><section class="card h-100"><header class="card-header"><h2 class="card-title">Další ukazatele</h2></header><div class="card-body">
            <div class="widget-summary mb-4"><div class="widget-summary-col"><div class="summary"><h4 class="title">Průměrná délka relace</h4><div class="info"><strong class="amount"><?= $e(reporting_seconds_label($summaryCurrent['averageSessionDuration'])) ?></strong></div></div></div></div><hr>
            <h4 class="font-weight-semibold text-color-dark text-4 mb-3">Zařízení návštěvníků</h4>
            <?php $deviceTotal=0; foreach($deviceRows as $row){$deviceTotal+=(float)($row['metricValues'][0]['value']??0);} $deviceLabels=array('desktop'=>'Počítač','mobile'=>'Mobil','tablet'=>'Tablet','smart tv'=>'Smart TV'); ?>
            <?php if(!$deviceRows): ?><p class="text-muted mb-0">Žádná data.</p><?php else: foreach($deviceRows as $row): $key=strtolower(reporting_dimension_value($row,0)); $value=(float)($row['metricValues'][0]['value']??0); $pct=$deviceTotal>0?($value/$deviceTotal)*100:0; ?>
                <div class="mb-3"><div class="d-flex justify-content-between mb-1"><span><?= $e(isset($deviceLabels[$key])?$deviceLabels[$key]:ucfirst($key)) ?></span><strong><?= reporting_number($pct,1) ?> %</strong></div><div class="progress progress-sm"><div class="progress-bar" role="progressbar" style="width:<?= min(100,max(0,$pct)) ?>%"></div></div></div>
            <?php endforeach; endif; ?>
        </div></section></div>
    </div>

    <div class="row row-gutter-sm">
        <div class="col-xl-7 mb-4"><section class="card h-100"><header class="card-header"><h2 class="card-title">Nejnavštěvovanější stránky</h2></header><div class="card-body p-0"><div class="table-responsive"><table class="table table-ecommerce-simple mb-0"><thead><tr><th>Stránka</th><th>Zobrazení</th><th>Uživatelé</th></tr></thead><tbody>
        <?php if(!$pageRows): ?><tr><td colspan="3" class="text-center text-muted py-4">Žádná data.</td></tr><?php else: foreach($pageRows as $row): ?>
            <tr><td><strong class="text-color-dark"><?= $e(reporting_dimension_value($row,1)?:'(bez názvu)') ?></strong><br><small class="text-muted"><?= $e(reporting_dimension_value($row,0)) ?></small></td><td><?= reporting_number($row['metricValues'][0]['value']??0) ?></td><td><?= reporting_number($row['metricValues'][1]['value']??0) ?></td></tr>
        <?php endforeach; endif; ?></tbody></table></div></div></section></div>
        <div class="col-xl-5 mb-4"><section class="card h-100"><header class="card-header"><h2 class="card-title">Zdroje návštěvnosti</h2></header><div class="card-body p-0"><div class="table-responsive"><table class="table table-ecommerce-simple mb-0"><thead><tr><th>Zdroj / médium</th><th>Relace</th><th>Uživatelé</th></tr></thead><tbody>
        <?php if(!$sourceRows): ?><tr><td colspan="3" class="text-center text-muted py-4">Žádná data.</td></tr><?php else: foreach($sourceRows as $row): $source=reporting_dimension_value($row,0); $medium=reporting_dimension_value($row,1); ?>
            <tr><td><strong class="text-color-dark"><?= $e($source?:'(direct)') ?></strong><br><small class="text-muted"><?= $e($medium?:'(none)') ?></small></td><td><?= reporting_number($row['metricValues'][0]['value']??0) ?></td><td><?= reporting_number($row['metricValues'][1]['value']??0) ?></td></tr>
        <?php endforeach; endif; ?></tbody></table></div></div></section></div>
    </div>
    <?php endif; ?>
</section>

<style>.reporting-chart-wrap{position:relative;min-height:320px}@media(max-width:767.98px){.reporting-chart-wrap{min-height:260px}}</style>
<?php
$chartLabels=array();$chartUsers=array();$chartSessions=array();$chartViews=array();
foreach($dailyRows as $row){$raw=reporting_dimension_value($row,0);$d=DateTime::createFromFormat('Ymd',$raw);$chartLabels[]=$d?$d->format('d.m.'):$raw;$chartUsers[]=(float)($row['metricValues'][0]['value']??0);$chartSessions[]=(float)($row['metricValues'][1]['value']??0);$chartViews[]=(float)($row['metricValues'][2]['value']??0);}
?>
<script>
(function(){
 var period=document.getElementById('reporting-obdobi'), custom=document.querySelectorAll('.reporting-custom-date');
 function toggle(){var show=period&&period.value==='vlastni';for(var i=0;i<custom.length;i++)custom[i].style.display=show?'':'none';}
 if(period){period.addEventListener('change',toggle);toggle();}
 var canvas=document.getElementById('reporting-visits-chart');if(!canvas)return;
 var labels=<?= json_encode($chartLabels,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,users=<?= json_encode($chartUsers) ?>,sessions=<?= json_encode($chartSessions) ?>,views=<?= json_encode($chartViews) ?>;
 function draw(){var ctx=canvas.getContext('2d'),ratio=window.devicePixelRatio||1,width=canvas.parentNode.clientWidth,height=Math.max(260,canvas.parentNode.clientHeight||320);canvas.width=width*ratio;canvas.height=height*ratio;canvas.style.width=width+'px';canvas.style.height=height+'px';ctx.setTransform(ratio,0,0,ratio,0,0);ctx.clearRect(0,0,width,height);var p={left:45,right:18,top:20,bottom:42},gw=width-p.left-p.right,gh=height-p.top-p.bottom,all=users.concat(sessions,views),max=Math.max.apply(null,all.concat([1]));max=Math.ceil(max/5)*5;ctx.font='12px Arial, sans-serif';ctx.lineWidth=1;ctx.strokeStyle='#e5e7eb';ctx.fillStyle='#777';for(var g=0;g<=5;g++){var y=p.top+gh-(gh*g/5);ctx.beginPath();ctx.moveTo(p.left,y);ctx.lineTo(width-p.right,y);ctx.stroke();ctx.fillText(String(Math.round(max*g/5)),4,y+4);}var sx=labels.length>1?gw/(labels.length-1):gw,ls=Math.max(1,Math.ceil(labels.length/8));for(var i=0;i<labels.length;i+=ls){var x=p.left+(labels.length>1?i*sx:gw/2);ctx.fillText(labels[i],Math.max(p.left,x-14),height-14);}function line(values,color){ctx.beginPath();ctx.strokeStyle=color;ctx.lineWidth=2;for(var j=0;j<values.length;j++){var x=p.left+(values.length>1?j*sx:gw/2),y=p.top+gh-((Number(values[j])||0)/max*gh);if(j===0)ctx.moveTo(x,y);else ctx.lineTo(x,y);}ctx.stroke();}line(users,'#0088cc');line(sessions,'#47a447');line(views,'#ed9c28');var leg=[['Aktivní uživatelé','#0088cc'],['Relace','#47a447'],['Zobrazení','#ed9c28']],lx=p.left;for(var l=0;l<leg.length;l++){ctx.fillStyle=leg[l][1];ctx.fillRect(lx,2,12,3);ctx.fillStyle='#444';ctx.fillText(leg[l][0],lx+17,8);lx+=ctx.measureText(leg[l][0]).width+42;}}
 draw();var t;window.addEventListener('resize',function(){clearTimeout(t);t=setTimeout(draw,120);});
})();
</script>
