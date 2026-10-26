<?php
/** RAC Billing / BOS24 Porto layout, 2026-10-14-14.22 – Stripe Sandbox API test. */
declare(strict_types=1);

// Render original form in isolation, preserving every PHP variable and handler.
ob_start();
include __DIR__ . '/admin.class.php';
$legacyHtml = ob_get_clean();

// Extract only the billing module content. Discard RAC header/sidebar/footer.
$startTag = '<div class="billing-content">';
$start = strpos($legacyHtml, $startTag);
$end = strrpos($legacyHtml, '</main>');
if ($start === false || $end === false || $end <= $start) {
    throw new RuntimeException('Nepodařilo se vyčlenit obsah Billing ze stávající šablony.');
}
$moduleHtml = substr($legacyHtml, $start + strlen($startTag), $end - ($start + strlen($startTag)));
$moduleHtml = preg_replace('~</div>\s*$~', '', $moduleHtml, 1);
$moduleHtml = str_replace("\r\n", "\n", $moduleHtml);

// Add a separate POST action using the EXISTING form's CSRF token.
// No nested forms and no change to original saving behavior.
$testButton = <<<'HTML'
<div class="row form-group pb-3">
    <div class="col-lg-12">
        <button type="submit" class="btn btn-primary" formaction="stripe-test.php" formmethod="post">
            <i class="fas fa-plug me-1"></i> Otestovat spojení se Stripe (Sandbox)
        </button>
        <small class="text-muted d-block mt-2">Test ověří jen uložený testovací API klíč. Nevytváří žádnou platbu ani nemění konfiguraci.</small>
    </div>
</div>
HTML;

// Insert right after the existing status fields, before the status explanation.
$oldNotice = 'V této první verzi administrace se údaje bezpečně ukládají do databáze, ale současný <code>bootstrap.php</code> a <code>webhook.php</code> ještě nadále používají stávající <code>config.local.php</code>. Přepojení provedeme až po ověření této administrace.';
$newNotice = 'Nastavení Stripe se načítá z tabulky <code>billing_settings</code>; citlivé údaje jsou šifrované. Webhook ověříme samostatně po vytvoření koncového bodu ve Stripe.';
$noticePos = strpos($moduleHtml, $oldNotice);
if ($noticePos !== false) {
    $moduleHtml = substr_replace($moduleHtml, $newNotice, $noticePos, strlen($oldNotice));
    $moduleHtml = str_replace('<div class="alert alert-secondary mb-0">' . "\n" . '                                ' . $newNotice,
        $testButton . "\n" . '                            <div class="alert alert-secondary mb-0">' . "\n" . '                                ' . $newNotice,
        $moduleHtml);
} else {
    // Avoid silently misplacing the action if original template changes.
    error_log('RAC Stripe test: original status notice was not found in legacy admin template.');
}

// One-time result shown after redirect; do not expose Stripe credentials or raw API replies.
if (isset($_SESSION['rac_stripe_test_result']) && is_array($_SESSION['rac_stripe_test_result'])) {
    $result = $_SESSION['rac_stripe_test_result'];
    unset($_SESSION['rac_stripe_test_result']);
    $message = htmlspecialchars((string)($result['message'] ?? ''), ENT_QUOTES, 'UTF-8');
    $alertType = !empty($result['ok']) ? 'success' : 'danger';
    $alert = '<div class="alert alert-' . $alertType . '" role="status">' . $message . '</div>';
    $moduleHtml = str_replace('<div id="tab-stav" class="tab-pane fade">',
        '<div id="tab-stav" class="tab-pane fade">' . $alert, $moduleHtml);
}

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Billing – nastavení | RAC';
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['E-shop', [
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
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
    ]],
];
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/billing/admin.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
?>
<?php include __DIR__ . '/../../inc/header.class.php'; ?>
<?php include __DIR__ . '/../../inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<div class="billing-content">
<?= $moduleHtml ?>
</div>
<?php if (isset($_GET['tab']) && $_GET['tab'] === 'stav'): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tab = document.querySelector('[data-bs-target="#tab-stav"]');
    if (tab) { tab.click(); }
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/../../inc/footer.class.php'; ?>
