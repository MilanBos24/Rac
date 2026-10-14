<?php
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');
$errors = array();
$success = '';
$tab = ((string)($_POST['tab'] ?? $_GET['tab'] ?? 'settings') === 'security') ? 'security' : 'settings';
$csrf = csrf_token('rac_contact_form_settings');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_valid('rac_contact_form_settings', (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Neplatný bezpečnostní token.';
    } elseif ($tab === 'settings') {
        $email = trim((string)($_POST['recipient_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors[] = 'Zadejte platnou e-mailovou adresu.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO contact_form_settings (id, recipient_email) VALUES (1, ?) ON DUPLICATE KEY UPDATE recipient_email = VALUES(recipient_email)');
                $stmt->execute(array($email));
                $success = 'Cílový e-mail byl uložen.';
            } catch (Throwable $e) { error_log($e->getMessage()); $errors[] = 'Nastavení nelze uložit. Ověřte import SQL 007_contact_form.sql.'; }
        }
    } else {
        $ip = trim((string)($_POST['ip_address'] ?? ''));
        $action = (string)($_POST['action'] ?? 'add');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $errors[] = 'Zadejte platnou IPv4 nebo IPv6 adresu.';
        } else {
            try {
                if ($action === 'delete') {
                    $stmt = $pdo->prepare('DELETE FROM contact_blocked_ips WHERE ip_address = ?');
                } else {
                    $stmt = $pdo->prepare('INSERT IGNORE INTO contact_blocked_ips (ip_address) VALUES (?)');
                }
                $stmt->execute(array($ip));
                $success = $action === 'delete' ? 'Blokace odstraněna.' : 'IP adresa byla zablokována.';
            } catch (Throwable $e) { error_log($e->getMessage()); $errors[] = 'Blokované adresy nelze změnit. Ověřte import SQL.'; }
        }
    }
}
$email = '';
$blocked = array();
try {
    $email = (string)$pdo->query('SELECT recipient_email FROM contact_form_settings WHERE id = 1')->fetchColumn();
    $blocked = $pdo->query('SELECT ip_address, created_at FROM contact_blocked_ips ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $errors[] = 'Databázové tabulky nejsou dostupné. Nejprve importujte 007_contact_form.sql.'; }
// RAC / BOS24 Porto – administrace kontaktního formulare, 2026-10-14
$pageTitle = 'Kontaktní formulář';
$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Kontaktní formulář | RAC';
$viewer = current_user_row();
$viewerName = (string)($viewer['username'] ?? 'Administrátor');
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
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
        ['Kontaktní formulář', '/administrace/kontaktni-formular.php', 'icons icon-envelope-letter'],
    ]],
    ['E-shop', [
        ['Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'],
        ['Sklad knih', '/administrace/eshop-sklad.php', 'icons icon-layers'],
        ['Doprava', '/administrace/eshop-doprava.php', 'icons icon-drawer'],
    ]],
    ['Platební brána', [
        ['Nastavení Stripe', '/administrace/billing/admin.php', 'el el-cogs'],
        ['Platby', '/administrace/billing/admin-platby.php', 'el el-credit-card'],
        ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
        ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
    ]],
];
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/kontaktni-formular.php';
$viewerRole = (string)($viewer['role'] ?? '');
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>Kontaktní formulář</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Obsah webu</span></li>
                <li><span>Kontaktní formulář</span></li>
            </ol>
        </div>
    </header>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success mt-3"><?= $e($success) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger mt-3"><?= $e($error) ?></div>
    <?php endforeach; ?>

    <div class="tabs mt-3">
        <ul class="nav nav-tabs tabs-primary">
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'settings' ? ' active' : '' ?>" href="kontaktni-formular.php?tab=settings">Nastavení</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'security' ? ' active' : '' ?>" href="kontaktni-formular.php?tab=security">Bezpečnost</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active">
                <?php if ($tab === 'settings'): ?>
                    <section class="card mb-0">
                        <header class="card-header"><h2 class="card-title">Nastavení kontaktního formuláře</h2></header>
                        <div class="card-body">
                            <form method="post" action="kontaktni-formular.php?tab=settings">
                                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="tab" value="settings">
                                <div class="row">
                                    <div class="col-lg-6 col-md-8">
                                        <div class="form-group mb-3">
                                            <label class="form-label" for="recipient_email">E-mail pro doručování zpráv</label>
                                            <input class="form-control" id="recipient_email" type="email" name="recipient_email" maxlength="254" required value="<?= $e($email) ?>">
                                            <small class="text-muted">Na tuto adresu budou zasílány zprávy z webu. Odpovídat lze přímo na e-mail návštěvníka (Reply-To).</small>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" type="submit">Uložit nastavení</button>
                            </form>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="card mb-0">
                        <header class="card-header"><h2 class="card-title">Bezpečnost kontaktního formuláře</h2></header>
                        <div class="card-body">
                            <form method="post" action="kontaktni-formular.php?tab=security" class="row g-3 align-items-end mb-4">
                                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="tab" value="security">
                                <input type="hidden" name="action" value="add">
                                <div class="col-lg-5 col-md-7">
                                    <label class="form-label" for="ip_address">Blokovat IP adresu (IPv4 nebo IPv6)</label>
                                    <input class="form-control" id="ip_address" type="text" name="ip_address" maxlength="45" placeholder="192.0.2.1" required>
                                </div>
                                <div class="col-lg-3 col-md-5">
                                    <button class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" type="submit">+ Blokovat IP</button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead><tr><th>Blokovaná IP adresa</th><th>Datum přidání</th><th>Akce</th></tr></thead>
                                    <tbody>
                                    <?php if (!$blocked): ?>
                                        <tr><td colspan="3">Zatím nejsou blokovány žádné IP adresy.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($blocked as $item): ?>
                                        <tr>
                                            <td><?= $e($item['ip_address']) ?></td>
                                            <td><?= $e($item['created_at']) ?></td>
                                            <td>
                                                <form method="post" action="kontaktni-formular.php?tab=security">
                                                    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                                    <input type="hidden" name="tab" value="security">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="ip_address" value="<?= $e($item['ip_address']) ?>">
                                                    <button class="btn btn-danger btn-sm" type="submit">Odblokovat</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted mt-3 mb-0">Další bezpečnostní nastavení přidáme později.</p>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
