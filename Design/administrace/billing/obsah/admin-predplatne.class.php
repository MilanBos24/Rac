<?php
/**
 * RAC Billing - vzhled administrace predplatnych
 * verze 2026-09-16-21.35
 */

function bsub_query(array $replace = array()): string
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
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RAC – Billing</title>
<link rel="stylesheet" href="../assets/css/admin.css?v=2026-10-09-billing">
</head>
<body><div class="admin-app">
<header class="admin-header"><div class="admin-brand">
<a href="../dashboard.php"><strong>RAC</strong> <span>Administrace</span></a>
</div><div class="admin-user"><a class="btn btn-light btn-sm" href="../dashboard.php">Zpět do administrace</a></div></header>
<?php include __DIR__ . '/../../inc/leve-meny.class.php'; ?>
<main class="admin-content">
<div class="card"><div class="card-body">
    <header class="page-header">
        <h2>Platby – předplatná</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="admin.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Platby</span></li>
                <li><span>Předplatná</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
        </div>
    </header>

    <div class="row align-items-center pt-2 mb-3">
        <div class="col-12 d-flex flex-wrap">
            <a href="admin.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-cog me-1"></i> Nastavení
            </a>
            <a href="admin-produkty.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-box me-1"></i> Produkty
            </a>
            <a href="admin-platby.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-credit-card me-1"></i> Platby
            </a>
            <a href="admin-predplatne.php" class="btn btn-primary me-2 mb-2">
                <i class="fas fa-sync-alt me-1"></i> Předplatná
            </a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= bsub_e($error) ?></li>
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
                                <h4 class="title">Aktivní</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['active'] ?></strong></div>
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
                                <h4 class="title">Po splatnosti</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['past_due'] ?></strong></div>
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
                                <h4 class="title">Naplánované zrušení</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['cancel_scheduled'] ?></strong></div>
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
                                <h4 class="title">Zrušená</h4>
                                <div class="info"><strong class="amount"><?= (int)$stats['canceled'] ?></strong></div>
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
                  action="admin-predplatne.php"
                  class="d-flex align-items-lg-center flex-column flex-lg-row">

                <label class="ws-nowrap me-lg-3 mb-2 mb-lg-0">Filtrovat:</label>

                <select name="status"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-md"
                        onchange="this.form.submit()">
                    <option value="">Všechny stavy</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktivní</option>
                    <option value="trialing" <?= $statusFilter === 'trialing' ? 'selected' : '' ?>>Zkušební</option>
                    <option value="past_due" <?= $statusFilter === 'past_due' ? 'selected' : '' ?>>Po splatnosti</option>
                    <option value="unpaid" <?= $statusFilter === 'unpaid' ? 'selected' : '' ?>>Nezaplaceno</option>
                    <option value="canceled" <?= $statusFilter === 'canceled' ? 'selected' : '' ?>>Zrušeno</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Čeká</option>
                </select>

                <select name="product_code"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-lg"
                        onchange="this.form.submit()">
                    <option value="">Všechny produkty</option>
                    <?php foreach ($productCodes as $productCode): ?>
                        <option value="<?= bsub_e($productCode) ?>" <?= $productFilter === $productCode ? 'selected' : '' ?>>
                            <?= bsub_e($productCode) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="renewal"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-md"
                        onchange="this.form.submit()">
                    <option value="">Všechna obnovení</option>
                    <option value="yes" <?= $renewalFilter === 'yes' ? 'selected' : '' ?>>Automaticky obnovovat</option>
                    <option value="no" <?= $renewalFilter === 'no' ? 'selected' : '' ?>>Zrušit na konci období</option>
                </select>

                <div class="search search-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-search flex-grow-1">
                    <div class="input-group">
                        <input type="text"
                               name="q"
                               class="search-term form-control"
                               value="<?= bsub_e($search) ?>"
                               placeholder="E-mail, produkt, subscription ID..."
                               autocomplete="off">
                        <button class="btn btn-default" type="submit" title="Hledat">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>

                <?php if ($statusFilter !== '' || $productFilter !== '' || $renewalFilter !== '' || $search !== ''): ?>
                    <a href="admin-predplatne.php"
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
                Předplatná
                <span class="text-muted ms-2" style="font-size: 14px;">
                    <?= (int)$totalRows ?> záznamů
                </span>
            </h2>
        </header>

        <div class="card-body">
            <?php if (!$subscriptions): ?>
                <p class="text-muted mb-0">Pro zvolený filtr nebyla nalezena žádná předplatná.</p>
            <?php else: ?>
                <div class="dashboard-responsive-section">
                    <section class="card mb-3 dashboard-desktop-header">
                        <div class="card-body">
                            <div class="row form-group pb-0">
                                <div class="col-xl-2"><label>Uživatel</label></div>
                                <div class="col-xl-2"><label>Produkt</label></div>
                                <div class="col-xl-2"><label>Cena / interval</label></div>
                                <div class="col-xl-2"><label>Stav</label></div>
                                <div class="col-xl-2"><label>Aktuální období</label></div>
                                <div class="col-xl-2"><label>Stripe Subscription ID</label></div>
                            </div>
                        </div>
                    </section>

                    <div class="dashboard-grid">
                        <?php foreach ($subscriptions as $subscription): ?>
                            <section class="card mb-1 border-0 shadow-none dashboard-item-card">
                                <div class="card-body py-1">
                                    <div class="row form-group pb-0">
                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Uživatel</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bsub_e($subscription['user_email'] ?: ($subscription['user_username'] ?: 'uživatel #' . (int)$subscription['user_id'])) ?>">
                                            <small class="text-muted">Customer: <?= bsub_e($subscription['provider_customer_id'] ?: '—') ?></small>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Produkt</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bsub_e($subscription['product_name'] ?: ($subscription['product_code'] ?: '—')) ?>">
                                            <small class="text-muted"><?= bsub_e($subscription['product_code'] ?: '—') ?></small>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Cena / interval</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bsub_e(bsub_amount((int)$subscription['amount_minor'], (string)$subscription['currency'])) ?>">
                                            <small class="text-muted"><?= bsub_e(bsub_interval_label($subscription)) ?></small>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Stav</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bsub_e(bsub_status_label((string)$subscription['status'])) ?>">

                                            <?php if ((int)$subscription['cancel_at_period_end'] === 1 && (string)$subscription['status'] !== 'canceled'): ?>
                                                <small class="text-warning">Zrušení na konci období</small>
                                            <?php elseif ((string)$subscription['status'] === 'active'): ?>
                                                <small class="text-success">Automatické obnovení: ano</small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Aktuální období</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bsub_e(bsub_date($subscription['current_period_start'])) ?>">
                                            <small class="text-muted">do <?= bsub_e(bsub_date($subscription['current_period_end'])) ?></small>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Stripe Subscription ID</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bsub_e($subscription['provider_subscription_id']) ?>">

                                            <?php if (!empty($subscription['ended_at'])): ?>
                                                <small class="text-muted">ukončeno <?= bsub_e(bsub_date($subscription['ended_at'])) ?></small>
                                            <?php elseif (!empty($subscription['cancel_at'])): ?>
                                                <small class="text-muted">zrušit <?= bsub_e(bsub_date($subscription['cancel_at'])) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Stránkování předplatných">
                        <ul class="pagination justify-content-end mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= bsub_e(bsub_query(array('page' => max(1, $page - 1)))) ?>">
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
                                       href="?<?= bsub_e(bsub_query(array('page' => $i))) ?>">
                                        <?= (int)$i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= bsub_e(bsub_query(array('page' => min($totalPages, $page + 1)))) ?>">
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

</div></div></main></div><script src="../assets/js/admin.js?v=2026-10-09-billing"></script></body></html>
