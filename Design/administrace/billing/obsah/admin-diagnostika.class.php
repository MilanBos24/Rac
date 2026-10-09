<?php
/**
 * RAC Billing - vzhled diagnostiky
 * verze 2026-09-16-21.55
 */
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
        <h2>Platby – diagnostika</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/billing/admin.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Platby</span></li>
                <li><span>Diagnostika</span></li>
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
            <a href="/billing/admin-webhooky.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-random me-1"></i> Webhooky
            </a>
            <a href="/billing/admin-diagnostika.php" class="btn btn-primary me-2 mb-2">
                <i class="fas fa-stethoscope me-1"></i> Diagnostika
            </a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= bdiag_e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($apiTestResult !== null): ?>
        <div class="alert <?= $apiTestResult['ok'] ? 'alert-success' : 'alert-danger' ?>">
            <strong><?= $apiTestResult['ok'] ? 'Stripe API test proběhl úspěšně.' : 'Stripe API test selhal.' ?></strong>
            <?= bdiag_e($apiTestResult['message']) ?>
            <?php if ((int)$apiTestResult['http_code'] > 0): ?>
                (HTTP <?= (int)$apiTestResult['http_code'] ?>)
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <section class="card card-featured-left <?= $systemReady ? 'card-featured-success' : 'card-featured-danger' ?>">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Celkový stav</h4>
                                <div class="info">
                                    <strong class="amount"><?= $systemReady ? 'Připraven' : 'Vyžaduje kontrolu' ?></strong>
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
                                <h4 class="title">Režim</h4>
                                <div class="info">
                                    <strong class="amount"><?= $mode === 'live' ? 'LIVE' : 'SANDBOX' ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <section class="card card-featured-left <?= $billingEnabled ? 'card-featured-success' : 'card-featured-warning' ?>">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Platební systém</h4>
                                <div class="info">
                                    <strong class="amount"><?= $billingEnabled ? 'Aktivní' : 'Neaktivní' ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-primary">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Výchozí měna</h4>
                                <div class="info">
                                    <strong class="amount"><?= bdiag_e($defaultCurrency) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <section class="card h-100">
                <header class="card-header">
                    <h2 class="card-title">Server a zabezpečení</h2>
                </header>
                <div class="card-body">
                    <div class="row form-group pb-3">
                        <div class="col-md-6">
                            <label>PHP OpenSSL</label>
                            <input class="form-control" readonly value="<?= bdiag_e(bdiag_bool_label($opensslAvailable)) ?>">
                        </div>
                        <div class="col-md-6">
                            <label>PHP cURL</label>
                            <input class="form-control" readonly value="<?= bdiag_e(bdiag_bool_label($curlAvailable)) ?>">
                        </div>
                    </div>

                    <div class="row form-group pb-3">
                        <div class="col-md-6">
                            <label>Master key soubor</label>
                            <input class="form-control" readonly value="<?= $masterKeyExists ? 'Vytvořen' : 'CHYBÍ' ?>">
                        </div>
                        <div class="col-md-6">
                            <label>Master key lze načíst</label>
                            <input class="form-control" readonly value="<?= bdiag_e(bdiag_bool_label($masterKeyReadable)) ?>">
                        </div>
                    </div>

                    <div class="row form-group pb-0">
                        <div class="col-md-12">
                            <label>Base URL</label>
                            <input class="form-control" readonly value="<?= bdiag_e($baseUrl !== '' ? $baseUrl : 'Nenastaveno') ?>">
                            <small class="<?= $baseUrlValid ? 'text-success' : 'text-danger' ?>">
                                <?= $baseUrlValid ? 'HTTPS URL je platná.' : 'Base URL chybí nebo není HTTPS.' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-6 mb-4">
            <section class="card h-100">
                <header class="card-header">
                    <h2 class="card-title">Stripe konfigurace</h2>
                </header>
                <div class="card-body">
                    <div class="row form-group pb-3">
                        <div class="col-md-6">
                            <label>Sandbox API key</label>
                            <input class="form-control" readonly value="<?= bdiag_e(bdiag_masked_setting($pdo, 'stripe_test_secret_key')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label>Sandbox Webhook secret</label>
                            <input class="form-control" readonly value="<?= bdiag_e(bdiag_masked_setting($pdo, 'stripe_test_webhook_secret')) ?>">
                        </div>
                    </div>

                    <div class="row form-group pb-3">
                        <div class="col-md-6">
                            <label>Live API key</label>
                            <input class="form-control" readonly value="<?= bdiag_e(bdiag_masked_setting($pdo, 'stripe_live_secret_key')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label>Live Webhook secret</label>
                            <input class="form-control" readonly value="<?= bdiag_e(bdiag_masked_setting($pdo, 'stripe_live_webhook_secret')) ?>">
                        </div>
                    </div>

                    <div class="row form-group pb-3">
                        <div class="col-md-12">
                            <label>Webhook URL</label>
                            <input class="form-control" readonly value="<?= bdiag_e($webhookUrl !== '' ? $webhookUrl : 'Nenastaveno') ?>">
                        </div>
                    </div>

                    <form method="post" action="admin-diagnostika.php">
                        <input type="hidden" name="csrf" value="<?= bdiag_e($_SESSION['csrf_billing_diagnostics']) ?>">
                        <input type="hidden" name="action" value="test_stripe">

                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-plug me-1"></i>
                            Otestovat Stripe připojení
                        </button>

                        <small class="text-muted ms-2">
                            Test pouze načte 1 Payment Intent. Nic ve Stripe nevytváří ani nemění.
                        </small>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <section class="card mb-4">
        <header class="card-header">
            <h2 class="card-title">Databázové tabulky</h2>
        </header>

        <div class="card-body">
            <div class="dashboard-responsive-section">
                <section class="card mb-3 dashboard-desktop-header">
                    <div class="card-body">
                        <div class="row form-group pb-0">
                            <div class="col-xl-8"><label>Tabulka</label></div>
                            <div class="col-xl-4"><label>Stav</label></div>
                        </div>
                    </div>
                </section>

                <div class="dashboard-grid">
                    <?php foreach ($tableChecks as $tableName => $tableOk): ?>
                        <section class="card mb-1 border-0 shadow-none dashboard-item-card">
                            <div class="card-body py-1">
                                <div class="row form-group pb-0">
                                    <div class="col-xl-8 col-lg-12 mb-2">
                                        <label class="dashboard-mobile-label">Tabulka</label>
                                        <input class="form-control" readonly value="<?= bdiag_e($tableName) ?>">
                                    </div>
                                    <div class="col-xl-4 col-lg-12 mb-2">
                                        <label class="dashboard-mobile-label">Stav</label>
                                        <input class="form-control" readonly value="<?= $tableOk ? 'OK' : 'CHYBÍ' ?>">
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <header class="card-header">
            <h2 class="card-title">Shrnutí aktivního režimu</h2>
        </header>

        <div class="card-body">
            <div class="row form-group pb-3">
                <div class="col-md-4">
                    <label>Aktivní API key</label>
                    <input class="form-control" readonly value="<?= $activeApiConfigured ? 'Nastaven' : 'CHYBÍ' ?>">
                </div>
                <div class="col-md-4">
                    <label>Aktivní Webhook secret</label>
                    <input class="form-control" readonly value="<?= $activeWebhookConfigured ? 'Nastaven' : 'CHYBÍ' ?>">
                </div>
                <div class="col-md-4">
                    <label>Všechny DB tabulky</label>
                    <input class="form-control" readonly value="<?= $allTablesReady ? 'OK' : 'CHYBÍ' ?>">
                </div>
            </div>

            <?php if ($systemReady): ?>
                <div class="alert alert-success mb-0">
                    Konfigurace aktivního režimu je kompletní. Pro ověření skutečného přístupu ke Stripe použijte tlačítko
                    <strong>Otestovat Stripe připojení</strong>.
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-0">
                    Některá část konfigurace není připravena. Výše zkontrolujte položky označené jako CHYBA nebo CHYBÍ.
                </div>
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