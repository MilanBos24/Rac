<?php
/**
 * RAC Billing - vzhled administrace webhooku
 * verze 2026-09-16-21.48
 */

function bwh_query(array $replace = array()): string
{
    $query = $_GET;

    foreach ($replace as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return http_build_query($query);
}
?>
<!doctype html>
<html class="fixed header-dark">
<head>
<?php include __DIR__ . '/../../inc/title.class.php'; ?>
<?php include __DIR__ . '/../../inc/meta.class.php'; ?>
<?php include __DIR__ . '/../../inc/links.class.php'; ?>
<?php include __DIR__ . '/../../inc/scripts-top.class.php'; ?>
</head>
<body>
<section class="body">
<?php include __DIR__ . '/../../inc/header.class.php'; ?>

<div class="inner-wrapper">
<?php include __DIR__ . '/../../inc/leve-meny.class.php'; ?>

<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>Platby – webhooky</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/billing/admin.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Platby</span></li>
                <li><span>Webhooky</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
        </div>
    </header>

    <div class="row align-items-center pt-2 mb-3">
        <div class="col-12 d-flex flex-wrap">
            <a href="/billing/admin.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-cog me-1"></i> Nastavení
            </a>
            <a href="/billing/admin-produkty.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-box me-1"></i> Produkty
            </a>
            <a href="/billing/admin-platby.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-credit-card me-1"></i> Platby
            </a>
            <a href="/billing/admin-predplatne.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-sync-alt me-1"></i> Předplatná
            </a>
            <a href="/billing/admin-webhooky.php" class="btn btn-primary me-2 mb-2">
                <i class="fas fa-random me-1"></i> Webhooky
            </a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= bwh_e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-success">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Zpracované</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['processed'] ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-info">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Přijaté</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['received'] ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-warning">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Ignorované</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['ignored'] ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-danger">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Chyby</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['errors'] ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row align-items-center mb-3">
        <div class="col-12">
            <form method="get"
                  action="admin-webhooky.php"
                  class="d-flex align-items-lg-center flex-column flex-lg-row">

                <label class="ws-nowrap me-lg-3 mb-2 mb-lg-0">Filtrovat:</label>

                <select name="status"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-md"
                        onchange="this.form.submit()">
                    <option value="">Všechny stavy</option>
                    <option value="processed" <?= $statusFilter === 'processed' ? 'selected' : '' ?>>Zpracováno</option>
                    <option value="received" <?= $statusFilter === 'received' ? 'selected' : '' ?>>Přijato</option>
                    <option value="ignored" <?= $statusFilter === 'ignored' ? 'selected' : '' ?>>Ignorováno</option>
                    <option value="error" <?= $statusFilter === 'error' ? 'selected' : '' ?>>Chyba</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>

                <select name="event_type"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-lg"
                        onchange="this.form.submit()">
                    <option value="">Všechny eventy</option>
                    <?php foreach ($eventTypes as $eventType): ?>
                        <option value="<?= bwh_e($eventType) ?>" <?= $typeFilter === $eventType ? 'selected' : '' ?>>
                            <?= bwh_e($eventType) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="mode"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-md"
                        onchange="this.form.submit()">
                    <option value="">Sandbox + Live</option>
                    <option value="test" <?= $modeFilter === 'test' ? 'selected' : '' ?>>Sandbox</option>
                    <option value="live" <?= $modeFilter === 'live' ? 'selected' : '' ?>>Live</option>
                </select>

                <div class="search search-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-search flex-grow-1">
                    <div class="input-group">
                        <input type="text"
                               name="q"
                               class="search-term form-control"
                               value="<?= bwh_e($search) ?>"
                               placeholder="Event ID, object ID, typ, chyba..."
                               autocomplete="off">
                        <button class="btn btn-default" type="submit" title="Hledat">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>

                <?php if ($statusFilter !== '' || $typeFilter !== '' || $modeFilter !== '' || $search !== ''): ?>
                    <a href="admin-webhooky.php"
                       class="btn btn-default mb-0"
                       title="Zrušit filtr"
                       aria-label="Zrušit filtr">
                        <i class="bx bx-x"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <section class="card">
        <header class="card-header">
            <h2 class="card-title">
                Webhook události
                <span class="text-muted ms-2" style="font-size: 14px;">
                    <?= (int)$totalRows ?> záznamů
                </span>
            </h2>
        </header>

        <div class="card-body">
            <?php if (!$events): ?>
                <p class="text-muted mb-0">Pro zvolený filtr nebyly nalezeny žádné webhook události.</p>
            <?php else: ?>
                <div class="dashboard-responsive-section">
                    <section class="card mb-3 dashboard-desktop-header">
                        <div class="card-body">
                            <div class="row form-group pb-0">
                                <div class="col-xl-2"><label>Přijato</label></div>
                                <div class="col-xl-3"><label>Typ události</label></div>
                                <div class="col-xl-2"><label>Stav / režim</label></div>
                                <div class="col-xl-2"><label>Object ID</label></div>
                                <div class="col-xl-3"><label>Event ID / chyba</label></div>
                            </div>
                        </div>
                    </section>

                    <div class="dashboard-grid">
                        <?php foreach ($events as $event): ?>
                            <section class="card mb-1 border-0 shadow-none dashboard-item-card">
                                <div class="card-body py-1">
                                    <div class="row form-group pb-0 align-items-center">
                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Přijato</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bwh_e(bwh_date($event['received_at'])) ?>">
                                            <small class="text-muted">
                                                processed: <?= bwh_e(bwh_date($event['processed_at'])) ?>
                                            </small>
                                        </div>

                                        <div class="col-xl-3 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Typ události</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bwh_e($event['event_type']) ?>">
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Stav / režim</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bwh_e(bwh_status_label((string)$event['status'])) ?>">
                                            <small class="<?= (string)$event['status'] === 'processed' ? 'text-success' : ((string)$event['status'] === 'ignored' ? 'text-warning' : 'text-muted') ?>">
                                                <?= (int)$event['livemode'] === 1 ? 'LIVE' : 'SANDBOX' ?>
                                            </small>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Object ID</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bwh_e($event['object_id'] ?: '—') ?>">
                                        </div>

                                        <div class="col-xl-3 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Event ID / chyba</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bwh_e($event['event_id']) ?>">

                                            <?php if (!empty($event['error_message'])): ?>
                                                <small class="text-danger"><?= bwh_e($event['error_message']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">bez chyby</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Stránkování webhooků">
                        <ul class="pagination justify-content-end mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= bwh_e(bwh_query(array('page' => max(1, $page - 1)))) ?>">
                                    Předchozí
                                </a>
                            </li>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link"
                                       href="?<?= bwh_e(bwh_query(array('page' => $i))) ?>">
                                        <?= (int)$i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= bwh_e(bwh_query(array('page' => min($totalPages, $page + 1)))) ?>">
                                    Další
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</section>
</div>

<?php include __DIR__ . '/../../inc/prave-meny.class.php'; ?>
</section>

<?php include __DIR__ . '/../../inc/scripts-bottom.class.php'; ?>
</body>
</html>