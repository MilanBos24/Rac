<?php
/**
 * RAC Billing - vzhled administrace
 * verze 2026-09-16-20.33
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
        <h2>Platby – nastavení</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/billing/admin.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Platby</span></li>
                <li><span>Nastavení</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
        </div>
    </header>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= billing_admin_e($success) ?></div>
    <?php endif; ?>

    <?php if (!$tableReady): ?>
        <div class="alert alert-warning">
            <strong>Databáze platební administrace ještě není připravena.</strong>
            Importujte soubor <code>/billing/database/billing-settings.sql</code>.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= billing_admin_e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="admin.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= billing_admin_e($_SESSION['csrf_billing_admin']) ?>">

        <div class="tabs">
            <ul class="nav nav-tabs tabs-primary">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-target="#tab-zaklad" data-bs-toggle="tab" type="button">Základní nastavení</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-target="#tab-sandbox" data-bs-toggle="tab" type="button">Stripe Sandbox</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-target="#tab-live" data-bs-toggle="tab" type="button">Stripe Live</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-target="#tab-stav" data-bs-toggle="tab" type="button">Stav systému</button>
                </li>
            </ul>

            <div class="tab-content">
                <div id="tab-zaklad" class="tab-pane fade show active">
                    <section class="card mb-0">
                        <header class="card-header">
                            <h2 class="card-title">Základní nastavení plateb</h2>
                        </header>
                        <div class="card-body">
                            <div class="row form-group pb-3">
                                <div class="col-lg-3">
                                    <label class="form-label">Platební systém</label>
                                    <div class="checkbox-custom checkbox-default mt-2">
                                        <input type="checkbox" id="billing_enabled" name="billing_enabled" value="1" <?= billing_admin_bool($values['billing_enabled']) ? 'checked' : '' ?>>
                                        <label for="billing_enabled">Aktivní</label>
                                    </div>
                                    <small class="text-muted">Přepínač využijeme po přepojení billing modulu na tuto administraci.</small>
                                </div>

                                <div class="col-lg-3">
                                    <label class="form-label" for="mode">Aktivní režim</label>
                                    <select class="form-control" name="mode" id="mode">
                                        <option value="test" <?= $values['mode'] === 'test' ? 'selected' : '' ?>>Sandbox / Test</option>
                                        <option value="live" <?= $values['mode'] === 'live' ? 'selected' : '' ?>>Live / Ostrý provoz</option>
                                    </select>
                                </div>

                                <div class="col-lg-3">
                                    <label class="form-label" for="default_currency">Výchozí měna</label>
                                    <input class="form-control" type="text" maxlength="3" name="default_currency" id="default_currency" value="<?= billing_admin_e($values['default_currency']) ?>" placeholder="CZK">
                                </div>

                                <div class="col-lg-3">
                                    <label class="form-label">Webhook URL</label>
                                    <input class="form-control" type="text" readonly value="<?= billing_admin_e($webhookUrl !== '' ? $webhookUrl : 'Doplní se po zadání Base URL') ?>">
                                </div>
                            </div>

                            <div class="row form-group pb-3">
                                <div class="col-lg-12">
                                    <label class="form-label" for="base_url">Base URL billing modulu</label>
                                    <input class="form-control" type="url" name="base_url" id="base_url" value="<?= billing_admin_e($values['base_url']) ?>" placeholder="https://domena.cz/billing" required>
                                    <small class="text-muted">Bez koncového lomítka. Například https://domena.cz/billing</small>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div id="tab-sandbox" class="tab-pane fade">
                    <section class="card mb-0">
                        <header class="card-header">
                            <h2 class="card-title">Stripe Sandbox</h2>
                        </header>
                        <div class="card-body">
                            <div class="alert alert-info">
                                U citlivých údajů se stávající hodnota nikdy neposílá zpět do HTML. Prázdné pole při uložení znamená <strong>ponechat současnou hodnotu</strong>.
                            </div>

                            <div class="row form-group pb-3">
                                <div class="col-lg-4">
                                    <label class="form-label">Restricted / Secret API key</label>
                                    <input class="form-control" type="password" name="stripe_test_secret_key" value="" autocomplete="new-password" placeholder="<?= billing_admin_e($maskedSecrets['stripe_test_secret_key']) ?>">
                                    <small class="text-muted">rk_test_… nebo sk_test_…</small>
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">Publishable key</label>
                                    <input class="form-control" type="password" name="stripe_test_publishable_key" value="" autocomplete="new-password" placeholder="<?= billing_admin_e($maskedSecrets['stripe_test_publishable_key']) ?>">
                                    <small class="text-muted">pk_test_…; pro hosted Checkout zatím není nutný.</small>
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">Webhook signing secret</label>
                                    <input class="form-control" type="password" name="stripe_test_webhook_secret" value="" autocomplete="new-password" placeholder="<?= billing_admin_e($maskedSecrets['stripe_test_webhook_secret']) ?>">
                                    <small class="text-muted">whsec_…</small>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div id="tab-live" class="tab-pane fade">
                    <section class="card mb-0">
                        <header class="card-header">
                            <h2 class="card-title">Stripe Live</h2>
                        </header>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                Ostré klíče vyplňte až ve chvíli, kdy budete připraveni přejít z testovacího režimu do ostrého provozu.
                            </div>

                            <div class="row form-group pb-3">
                                <div class="col-lg-4">
                                    <label class="form-label">Restricted / Secret API key</label>
                                    <input class="form-control" type="password" name="stripe_live_secret_key" value="" autocomplete="new-password" placeholder="<?= billing_admin_e($maskedSecrets['stripe_live_secret_key']) ?>">
                                    <small class="text-muted">rk_live_… nebo sk_live_…</small>
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">Publishable key</label>
                                    <input class="form-control" type="password" name="stripe_live_publishable_key" value="" autocomplete="new-password" placeholder="<?= billing_admin_e($maskedSecrets['stripe_live_publishable_key']) ?>">
                                    <small class="text-muted">pk_live_…</small>
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">Webhook signing secret</label>
                                    <input class="form-control" type="password" name="stripe_live_webhook_secret" value="" autocomplete="new-password" placeholder="<?= billing_admin_e($maskedSecrets['stripe_live_webhook_secret']) ?>">
                                    <small class="text-muted">whsec_…</small>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div id="tab-stav" class="tab-pane fade">
                    <section class="card mb-0">
                        <header class="card-header">
                            <h2 class="card-title">Stav systému</h2>
                        </header>
                        <div class="card-body">
                            <div class="row form-group pb-3">
                                <div class="col-lg-3">
                                    <label>Tabulka billing_settings</label>
                                    <input class="form-control" readonly value="<?= $tableReady ? 'OK' : 'CHYBÍ' ?>">
                                </div>

                                <div class="col-lg-3">
                                    <label>Šifrovací master key</label>
                                    <input class="form-control" readonly value="<?= billing_master_key_exists() ? 'Vytvořen' : 'Zatím nevytvořen' ?>">
                                </div>

                                <div class="col-lg-3">
                                    <label>Aktivní režim</label>
                                    <input class="form-control" readonly value="<?= $values['mode'] === 'live' ? 'LIVE' : 'SANDBOX' ?>">
                                </div>

                                <div class="col-lg-3">
                                    <label>Platební systém</label>
                                    <input class="form-control" readonly value="<?= billing_admin_bool($values['billing_enabled']) ? 'Aktivní' : 'Neaktivní' ?>">
                                </div>
                            </div>

                            <div class="row form-group pb-3">
                                <div class="col-lg-4">
                                    <label>Sandbox API key</label>
                                    <input class="form-control" readonly value="<?= billing_admin_e($maskedSecrets['stripe_test_secret_key']) ?>">
                                </div>
                                <div class="col-lg-4">
                                    <label>Sandbox Webhook secret</label>
                                    <input class="form-control" readonly value="<?= billing_admin_e($maskedSecrets['stripe_test_webhook_secret']) ?>">
                                </div>
                                <div class="col-lg-4">
                                    <label>Live API key</label>
                                    <input class="form-control" readonly value="<?= billing_admin_e($maskedSecrets['stripe_live_secret_key']) ?>">
                                </div>
                            </div>

                            <div class="alert alert-secondary mb-0">
                                V této první verzi administrace se údaje bezpečně ukládají do databáze, ale současný <code>bootstrap.php</code> a <code>webhook.php</code> ještě nadále používají stávající <code>config.local.php</code>. Přepojení provedeme až po ověření této administrace.
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body text-end">
                <button class="btn btn-primary" type="submit" <?= !$tableReady ? 'disabled' : '' ?>><i class="fas fa-save me-1"></i> Uložit nastavení</button>
            </div>
        </div>
    </form>
</section>
</div>

<?php include __DIR__ . '/../../inc/prave-meny.class.php'; ?>
</section>

<?php include __DIR__ . '/../../inc/scripts-bottom.class.php'; ?>
</body>
</html>