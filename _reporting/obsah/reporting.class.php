<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>Reporting</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="dashboard.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Google Analytics</span></li>
                <li><a role="menuitem" tabindex="-1" href="logout.php"><i class="bx bx-power-off"></i> Logout</a></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
        </div>
    </header>

    <div class="row pt-2">
        <div class="col-12">
            <section class="card mb-4">
                <header class="card-header">
                    <div class="card-actions">
                        <span class="badge badge-ecommerce badge-success">
                            Právě online: <?= (int)$realtimeUsers ?>
                        </span>
                    </div>
                    <h2 class="card-title">Období reportu</h2>
                </header>
                <div class="card-body">
                    <form method="get" action="<?= htmlspecialchars($selfPath, ENT_QUOTES, 'UTF-8') ?>" class="row form-group pb-0 align-items-end">
                        <div class="col-lg-3 mb-3">
                            <label class="col-form-label">Vybrat období</label>
                            <select name="obdobi" id="reporting-obdobi" class="form-control">
                                <option value="7dni" <?= $period === '7dni' ? 'selected' : '' ?>>Posledních 7 dní</option>
                                <option value="30dni" <?= $period === '30dni' ? 'selected' : '' ?>>Posledních 30 dní</option>
                                <option value="tento-mesic" <?= $period === 'tento-mesic' ? 'selected' : '' ?>>Tento měsíc</option>
                                <option value="minuly-mesic" <?= $period === 'minuly-mesic' ? 'selected' : '' ?>>Minulý měsíc</option>
                                <option value="vlastni" <?= $period === 'vlastni' ? 'selected' : '' ?>>Vlastní období</option>
                            </select>
                        </div>
                        <div class="col-lg-3 mb-3 reporting-custom-date">
                            <label class="col-form-label">Od</label>
                            <input type="date" name="od" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>" class="form-control">
                        </div>
                        <div class="col-lg-3 mb-3 reporting-custom-date">
                            <label class="col-form-label">Do</label>
                            <input type="date" name="do" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>" class="form-control">
                        </div>
                        <div class="col-lg-3 mb-3 text-end">
                            <button type="submit" class="btn btn-primary">Zobrazit report</button>
                        </div>
                    </form>
                    <p class="mb-0 text-muted">
                        Zobrazené období: <strong><?= htmlspecialchars(reporting_format_date_cs($startDate), ENT_QUOTES, 'UTF-8') ?></strong>
                        až <strong><?= htmlspecialchars(reporting_format_date_cs($endDate), ENT_QUOTES, 'UTF-8') ?></strong>.
                        Porovnání: <?= htmlspecialchars(reporting_format_date_cs($previousStartDate), ENT_QUOTES, 'UTF-8') ?> až <?= htmlspecialchars(reporting_format_date_cs($previousEndDate), ENT_QUOTES, 'UTF-8') ?>.
                    </p>
                </div>
            </section>
        </div>
    </div>

    <?php if ($reportingError !== null): ?>
        <div class="alert alert-danger">
            <strong>Google Analytics data se nepodařilo načíst.</strong><br>
            <?= htmlspecialchars($reportingError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php else: ?>
        <?php
        $cards = [
            ['title' => 'Aktivní uživatelé', 'metric' => 'activeUsers', 'icon' => 'bx bx-user'],
            ['title' => 'Noví uživatelé', 'metric' => 'newUsers', 'icon' => 'bx bx-user-plus'],
            ['title' => 'Relace', 'metric' => 'sessions', 'icon' => 'bx bx-transfer'],
            ['title' => 'Zobrazení stránek', 'metric' => 'screenPageViews', 'icon' => 'bx bx-show'],
        ];
        ?>
        <div class="row row-gutter-sm">
            <?php foreach ($cards as $card): ?>
                <?php
                $metric = $card['metric'];
                $change = reporting_change_percent($summaryCurrent[$metric], $summaryPrevious[$metric]);
                $isPositive = $change === null || $change >= 0;
                ?>
                <div class="col-sm-6 col-lg-3 mb-4">
                    <section class="card card-featured-left card-featured-primary h-100">
                        <div class="card-body">
                            <div class="widget-summary">
                                <div class="widget-summary-col widget-summary-col-icon">
                                    <div class="summary-icon bg-primary"><i class="<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                                </div>
                                <div class="widget-summary-col">
                                    <div class="summary">
                                        <h4 class="title"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                        <div class="info">
                                            <strong class="amount"><?= reporting_number($summaryCurrent[$metric]) ?></strong>
                                        </div>
                                    </div>
                                    <div class="summary-footer text-start">
                                        <?php if ($change === null): ?>
                                            <span class="text-muted">Bez srovnatelného základu</span>
                                        <?php else: ?>
                                            <span class="<?= $isPositive ? 'text-success' : 'text-danger' ?>">
                                                <i class="fas <?= $isPositive ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                                <?= reporting_number(abs($change), 1) ?> %
                                            </span>
                                            <span class="text-muted"> oproti předchozímu období</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row row-gutter-sm">
            <div class="col-xl-8 mb-4">
                <section class="card h-100">
                    <header class="card-header">
                        <h2 class="card-title">Vývoj návštěvnosti</h2>
                        <p class="card-subtitle">Aktivní uživatelé, relace a zobrazení stránek podle dnů</p>
                    </header>
                    <div class="card-body">
                        <div class="reporting-chart-wrap">
                            <canvas id="reporting-visits-chart" height="115"></canvas>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-xl-4 mb-4">
                <section class="card h-100">
                    <header class="card-header"><h2 class="card-title">Další ukazatele</h2></header>
                    <div class="card-body">
                        <div class="widget-summary mb-4">
                            <div class="widget-summary-col">
                                <div class="summary">
                                    <h4 class="title">Průměrná délka relace</h4>
                                    <div class="info"><strong class="amount"><?= htmlspecialchars(reporting_seconds_label($summaryCurrent['averageSessionDuration']), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h4 class="font-weight-semibold text-color-dark text-4 mb-3">Zařízení návštěvníků</h4>
                        <?php
                        $deviceTotal = 0;
                        foreach ($deviceRows as $deviceRow) {
                            $deviceTotal += isset($deviceRow['metricValues'][0]['value']) ? (float)$deviceRow['metricValues'][0]['value'] : 0;
                        }
                        $deviceLabels = ['desktop' => 'Počítač', 'mobile' => 'Mobil', 'tablet' => 'Tablet', 'smart tv' => 'Smart TV'];
                        ?>
                        <?php if (!$deviceRows): ?>
                            <p class="text-muted mb-0">Žádná data.</p>
                        <?php else: ?>
                            <?php foreach ($deviceRows as $deviceRow): ?>
                                <?php
                                $deviceKey = strtolower(reporting_dimension_value($deviceRow, 0));
                                $deviceValue = isset($deviceRow['metricValues'][0]['value']) ? (float)$deviceRow['metricValues'][0]['value'] : 0;
                                $devicePercent = $deviceTotal > 0 ? ($deviceValue / $deviceTotal) * 100 : 0;
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?= htmlspecialchars(isset($deviceLabels[$deviceKey]) ? $deviceLabels[$deviceKey] : ucfirst($deviceKey), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= reporting_number($devicePercent, 1) ?> %</strong>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar" role="progressbar" style="width: <?= min(100, max(0, $devicePercent)) ?>%" aria-valuenow="<?= (int)$devicePercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>

        <div class="row row-gutter-sm">
            <div class="col-xl-7 mb-4">
                <section class="card h-100">
                    <header class="card-header"><h2 class="card-title">Nejnavštěvovanější stránky</h2></header>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-ecommerce-simple mb-0">
                                <thead><tr><th>Stránka</th><th>Zobrazení</th><th>Uživatelé</th><th>Prům. relace</th></tr></thead>
                                <tbody>
                                <?php if (!$pageRows): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">Žádná data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pageRows as $row): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-color-dark"><?= htmlspecialchars(reporting_dimension_value($row, 1) ?: '(bez názvu)', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars(reporting_dimension_value($row, 0), ENT_QUOTES, 'UTF-8') ?></small>
                                            </td>
                                            <td><?= reporting_number(isset($row['metricValues'][0]['value']) ? $row['metricValues'][0]['value'] : 0) ?></td>
                                            <td><?= reporting_number(isset($row['metricValues'][1]['value']) ? $row['metricValues'][1]['value'] : 0) ?></td>
                                            <td><?= htmlspecialchars(reporting_seconds_label(isset($row['metricValues'][2]['value']) ? $row['metricValues'][2]['value'] : 0), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-xl-5 mb-4">
                <section class="card h-100">
                    <header class="card-header"><h2 class="card-title">Zdroje návštěvnosti</h2></header>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-ecommerce-simple mb-0">
                                <thead><tr><th>Zdroj / médium</th><th>Relace</th><th>Uživatelé</th></tr></thead>
                                <tbody>
                                <?php if (!$sourceRows): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">Žádná data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($sourceRows as $row): ?>
                                        <?php
                                        $source = reporting_dimension_value($row, 0);
                                        $medium = reporting_dimension_value($row, 1);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong class="text-color-dark"><?= htmlspecialchars($source ?: '(direct)', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($medium ?: '(none)', ENT_QUOTES, 'UTF-8') ?></small>
                                            </td>
                                            <td><?= reporting_number(isset($row['metricValues'][0]['value']) ? $row['metricValues'][0]['value'] : 0) ?></td>
                                            <td><?= reporting_number(isset($row['metricValues'][1]['value']) ? $row['metricValues'][1]['value'] : 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    <?php endif; ?>
</section>

<style>
.reporting-chart-wrap { position: relative; min-height: 320px; }
@media (max-width: 767.98px) { .reporting-chart-wrap { min-height: 260px; } }
</style>

<?php
$chartLabels = [];
$chartUsers = [];
$chartSessions = [];
$chartViews = [];
foreach ($dailyRows as $row) {
    $rawDate = reporting_dimension_value($row, 0);
    $dateObject = DateTime::createFromFormat('Ymd', $rawDate);
    $chartLabels[] = $dateObject ? $dateObject->format('d.m.') : $rawDate;
    $chartUsers[] = isset($row['metricValues'][0]['value']) ? (float)$row['metricValues'][0]['value'] : 0;
    $chartSessions[] = isset($row['metricValues'][1]['value']) ? (float)$row['metricValues'][1]['value'] : 0;
    $chartViews[] = isset($row['metricValues'][2]['value']) ? (float)$row['metricValues'][2]['value'] : 0;
}
?>
<script>
(function () {
    var periodSelect = document.getElementById('reporting-obdobi');
    var customFields = document.querySelectorAll('.reporting-custom-date');
    function toggleCustomDates() {
        var show = periodSelect && periodSelect.value === 'vlastni';
        for (var i = 0; i < customFields.length; i++) {
            customFields[i].style.display = show ? '' : 'none';
        }
    }
    if (periodSelect) {
        periodSelect.addEventListener('change', toggleCustomDates);
        toggleCustomDates();
    }

    var canvas = document.getElementById('reporting-visits-chart');
    if (!canvas) return;

    var labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var users = <?= json_encode($chartUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var sessions = <?= json_encode($chartSessions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var views = <?= json_encode($chartViews, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function drawChart() {
        var ctx = canvas.getContext('2d');
        var ratio = window.devicePixelRatio || 1;
        var width = canvas.parentNode.clientWidth;
        var height = Math.max(260, canvas.parentNode.clientHeight || 320);
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, width, height);

        var padding = {left: 45, right: 18, top: 20, bottom: 42};
        var graphW = width - padding.left - padding.right;
        var graphH = height - padding.top - padding.bottom;
        var all = users.concat(sessions, views);
        var max = Math.max.apply(null, all.concat([1]));
        max = Math.ceil(max / 5) * 5;

        ctx.font = '12px Arial, sans-serif';
        ctx.lineWidth = 1;
        ctx.strokeStyle = '#e5e7eb';
        ctx.fillStyle = '#777';
        for (var g = 0; g <= 5; g++) {
            var y = padding.top + graphH - (graphH * g / 5);
            ctx.beginPath(); ctx.moveTo(padding.left, y); ctx.lineTo(width - padding.right, y); ctx.stroke();
            ctx.fillText(String(Math.round(max * g / 5)), 4, y + 4);
        }

        var stepX = labels.length > 1 ? graphW / (labels.length - 1) : graphW;
        var labelStep = Math.max(1, Math.ceil(labels.length / 8));
        for (var i = 0; i < labels.length; i += labelStep) {
            var x = padding.left + (labels.length > 1 ? i * stepX : graphW / 2);
            ctx.fillText(labels[i], Math.max(padding.left, x - 14), height - 14);
        }

        function line(values, strokeStyle) {
            ctx.beginPath();
            ctx.strokeStyle = strokeStyle;
            ctx.lineWidth = 2;
            for (var j = 0; j < values.length; j++) {
                var x = padding.left + (values.length > 1 ? j * stepX : graphW / 2);
                var y = padding.top + graphH - ((Number(values[j]) || 0) / max * graphH);
                if (j === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            }
            ctx.stroke();
        }

        line(users, '#0088cc');
        line(sessions, '#47a447');
        line(views, '#ed9c28');

        var legends = [
            ['Aktivní uživatelé', '#0088cc'],
            ['Relace', '#47a447'],
            ['Zobrazení', '#ed9c28']
        ];
        var lx = padding.left;
        for (var l = 0; l < legends.length; l++) {
            ctx.fillStyle = legends[l][1];
            ctx.fillRect(lx, 2, 12, 3);
            ctx.fillStyle = '#444';
            ctx.fillText(legends[l][0], lx + 17, 8);
            lx += ctx.measureText(legends[l][0]).width + 42;
        }
    }

    drawChart();
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(drawChart, 120);
    });
})();
</script>
