<?php
/**
 * RAC Billing - vzhled administrace plateb
 * verze 2026-09-16-21.18
 */

function bpay_query(array $replace = array()): string
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
        <h2>Platby – přehled</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="admin.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Platby</span></li>
                <li><span>Přehled</span></li>
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
            <a href="admin-platby.php" class="btn btn-primary me-2 mb-2">
                <i class="fas fa-credit-card me-1"></i> Platby
            </a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= bpay_e($error) ?></li>
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
                                <h4 class="title">Zaplacené</h4>
                                <div class="info">
                                    <strong class="amount"><?= (int)$stats['paid_count'] ?></strong>
                                </div>
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
                                <h4 class="title">Selhané</h4>
                                <div class="info">
                                    <strong class="amount"><?= (int)$stats['failed_count'] ?></strong>
                                </div>
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
                                <h4 class="title">Čekající</h4>
                                <div class="info">
                                    <strong class="amount"><?= (int)$stats['pending_count'] ?></strong>
                                </div>
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
                                <h4 class="title">Platby předplatného</h4>
                                <div class="info">
                                    <strong class="amount"><?= (int)$stats['subscription_count'] ?></strong>
                                </div>
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
                  action="admin-platby.php"
                  class="d-flex align-items-lg-center flex-column flex-lg-row">

                <label class="ws-nowrap me-lg-3 mb-2 mb-lg-0">Filtrovat:</label>

                <select name="status"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-md"
                        onchange="this.form.submit()">
                    <option value="">Všechny stavy</option>
                    <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Zaplaceno</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Selhalo</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Čeká</option>
                    <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Vráceno</option>
                    <option value="canceled" <?= $statusFilter === 'canceled' ? 'selected' : '' ?>>Zrušeno</option>
                </select>

                <select name="type"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-md"
                        onchange="this.form.submit()">
                    <option value="">Všechny typy</option>
                    <option value="one_off" <?= $typeFilter === 'one_off' ? 'selected' : '' ?>>Jednorázové</option>
                    <option value="subscription" <?= $typeFilter === 'subscription' ? 'selected' : '' ?>>Předplatné</option>
                </select>

                <select name="currency"
                        class="form-control select-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-filter-md"
                        onchange="this.form.submit()">
                    <option value="">Všechny měny</option>
                    <?php foreach ($currencies as $currency): ?>
                        <option value="<?= bpay_e($currency) ?>" <?= $currencyFilter === $currency ? 'selected' : '' ?>>
                            <?= bpay_e($currency) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="search search-style-1 me-lg-2 mb-2 mb-lg-0 bos-toolbar-search flex-grow-1">
                    <div class="input-group">
                        <input type="text"
                               name="q"
                               class="search-term form-control"
                               value="<?= bpay_e($search) ?>"
                               placeholder="Produkt, e-mail, invoice, session..."
                               autocomplete="off">
                        <button class="btn btn-default" type="submit" title="Hledat">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>

                <?php if ($statusFilter !== '' || $typeFilter !== '' || $currencyFilter !== '' || $search !== ''): ?>
                    <a href="admin-platby.php"
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
                Platby
                <span class="text-muted ms-2" style="font-size: 14px;">
                    <?= (int)$totalRows ?> záznamů
                </span>
            </h2>
        </header>

        <div class="card-body">
            <?php if (!$payments): ?>
                <p class="text-muted mb-0">Pro zvolený filtr nebyly nalezeny žádné platby.</p>
            <?php else: ?>
                <div class="dashboard-responsive-section">
                    <section class="card mb-3 dashboard-desktop-header">
                        <div class="card-body">
                            <div class="row form-group pb-0">
                                <div class="col-xl-2"><label>Datum / uživatel</label></div>
                                <div class="col-xl-2"><label>Produkt</label></div>
                                <div class="col-xl-2"><label>Typ / stav</label></div>
                                <div class="col-xl-2"><label>Částka</label></div>
                                <div class="col-xl-2"><label>Stripe doklad</label></div>
                                <div class="col-xl-2"><label>Subscription / session</label></div>
                            </div>
                        </div>
                    </section>

                    <div class="dashboard-grid">
                        <?php foreach ($payments as $payment): ?>
                            <section class="card mb-1 border-0 shadow-none dashboard-item-card">
                                <div class="card-body py-1">
                                    <div class="row form-group pb-0">
                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Datum / uživatel</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bpay_e(bpay_date($payment['created_at'])) ?>">
                                            <small class="text-muted">
                                                <?php if (!empty($payment['user_email'])): ?>
                                                    <?= bpay_e($payment['user_email']) ?>
                                                <?php elseif (!empty($payment['user_username'])): ?>
                                                    <?= bpay_e($payment['user_username']) ?>
                                                <?php elseif (!empty($payment['user_id'])): ?>
                                                    uživatel #<?= (int)$payment['user_id'] ?>
                                                <?php else: ?>
                                                    bez uživatele
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Produkt</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bpay_e($payment['product_code'] ?: '—') ?>">
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Typ / stav</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bpay_e(bpay_type_label((string)$payment['payment_type'])) ?>">
                                            <small class="<?= $payment['status'] === 'paid' ? 'text-success' : ($payment['status'] === 'failed' ? 'text-danger' : 'text-muted') ?>">
                                                <?= bpay_e(bpay_status_label((string)$payment['status'])) ?>
                                            </small>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Částka</label>
                                            <input class="form-control font-weight-bold"
                                                   readonly
                                                   value="<?= bpay_e(bpay_amount((int)$payment['amount_minor'], (string)$payment['currency'])) ?>">
                                            <?php if (!empty($payment['paid_at'])): ?>
                                                <small class="text-muted">zaplaceno <?= bpay_e(bpay_date($payment['paid_at'])) ?></small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Stripe doklad</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bpay_e($payment['provider_invoice_id'] ?: ($payment['provider_payment_intent_id'] ?: '—')) ?>">
                                        </div>

                                        <div class="col-xl-2 col-lg-12 mb-2">
                                            <label class="dashboard-mobile-label">Subscription / session</label>
                                            <input class="form-control"
                                                   readonly
                                                   value="<?= bpay_e($payment['provider_subscription_id'] ?: ($payment['provider_checkout_session_id'] ?: '—')) ?>">
                                        </div>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Stránkování plateb">
                        <ul class="pagination justify-content-end mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= bpay_e(bpay_query(array('page' => max(1, $page - 1)))) ?>">
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
                                       href="?<?= bpay_e(bpay_query(array('page' => $i))) ?>">
                                        <?= (int)$i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= bpay_e(bpay_query(array('page' => min($totalPages, $page + 1)))) ?>">
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
