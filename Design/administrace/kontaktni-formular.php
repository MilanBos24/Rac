<?php
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');
$errors = array();
$success = '';
$tabValue = (string)($_POST['tab'] ?? $_GET['tab'] ?? 'settings');
$tab = in_array($tabValue, array('settings', 'security', 'subjects'), true) ? $tabValue : 'settings';
$csrf = csrf_token('rac_contact_form_settings');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_valid('rac_contact_form_settings', (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Neplatný bezpečnostní token.';
    } elseif ($tab === 'settings') {
        $email = trim((string)($_POST['recipient_email'] ?? ''));
        $senderEmail = trim((string)($_POST['sender_email'] ?? ''));
        $senderName = trim((string)($_POST['sender_name'] ?? ''));
        $emailSubject = trim((string)($_POST['email_subject'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors[] = 'Zadejte platnou doručovací e-mailovou adresu.';
        }
        if ($senderEmail !== '' && (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL) || strlen($senderEmail) > 254)) {
            $errors[] = 'Adresa odesílatele musí být platný e-mail nebo zůstat prázdná.';
        }
        if ($senderName === '' || mb_strlen($senderName) > 120 || preg_match('/[\\r\\n]/', $senderName)) {
            $errors[] = 'Zadejte platný název odesílatele (max. 120 znaků).';
        }
        if ($emailSubject === '' || mb_strlen($emailSubject) > 180 || preg_match('/[\\r\\n]/', $emailSubject)) {
            $errors[] = 'Zadejte platný předmět e-mailu (max. 180 znaků).';
        }
        if (!$errors) {
            try {
                $stmt = $pdo->prepare('INSERT INTO contact_form_settings (id, recipient_email, sender_email, sender_name, email_subject) VALUES (1, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE recipient_email=VALUES(recipient_email), sender_email=VALUES(sender_email), sender_name=VALUES(sender_name), email_subject=VALUES(email_subject)');
                $stmt->execute(array($email, $senderEmail !== '' ? $senderEmail : null, $senderName, $emailSubject));
                $success = 'E-mailové nastavení bylo uloženo.';
            } catch (Throwable $ex) {
                error_log('RAC contact mail settings: ' . $ex->getMessage());
                $errors[] = 'Nastavení nelze uložit. Ověřte SQL migraci 012_contact_mail_settings.sql.';
            }
        }
    } elseif ($tab === 'subjects') {
        $action = (string)($_POST['action'] ?? 'add');
        try {
            if ($action === 'delete') {
                $id = filter_var($_POST['subject_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id < 1) { $errors[] = 'Neplatné ID předmětu.'; }
                else {
                    $stmt = $pdo->prepare('DELETE FROM contact_form_subjects WHERE id = ?');
                    $stmt->execute(array($id));
                    $success = 'Předmět byl odstraněn.';
                }
            } else {
                $labels = array();
                foreach (array('cs','sk','pl','en','de','fr') as $code) {
                    $value = trim((string)($_POST['label_' . $code] ?? ''));
                    if (strlen($value) > 150 || preg_match('/[\\r\\n]/', $value)) { $errors[] = 'Název předmětu je příliš dlouhý nebo neplatný.'; }
                    $labels[$code] = $value;
                }
                if ($labels['cs'] === '') { $errors[] = 'Český název předmětu je povinný.'; }
                if (!$errors) {
                    $stmt = $pdo->prepare('INSERT INTO contact_form_subjects (label_cs,label_sk,label_pl,label_en,label_de,label_fr) VALUES (?,?,?,?,?,?)');
                    $stmt->execute(array_values($labels));
                    $success = 'Nový předmět byl přidán.';
                }
            }
        } catch (Throwable $ex) {
            error_log('RAC contact subjects admin: ' . $ex->getMessage());
            $errors[] = 'Předměty nelze spravovat. Ověřte instalaci 011_contact_subjects.sql.';
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
$senderEmail = '';
$senderName = 'RAC – kontaktní formulář';
$emailSubject = 'Zpráva z kontaktního formuláře';
$blocked = array();
$subjects = array();
try {
    $mailSettings = $pdo->query('SELECT recipient_email, sender_email, sender_name, email_subject FROM contact_form_settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    if ($mailSettings) {
        $email = (string)$mailSettings['recipient_email'];
        $senderEmail = (string)($mailSettings['sender_email'] ?? '');
        $senderName = (string)($mailSettings['sender_name'] ?? 'RAC – kontaktní formulář');
        $emailSubject = (string)($mailSettings['email_subject'] ?? 'Zpráva z kontaktního formuláře');
    }
    $blocked = $pdo->query('SELECT ip_address, created_at FROM contact_blocked_ips ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $errors[] = 'Databázové tabulky nejsou dostupné. Nejprve importujte 007_contact_form.sql.'; }
try { $subjects = $pdo->query('SELECT * FROM contact_form_subjects ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $ex) { $errors[] = 'Pro vlastní předměty importujte 011_contact_subjects.sql.'; }
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
            <li class="nav-item"><a class="nav-link<?= $tab === 'subjects' ? ' active' : '' ?>" href="kontaktni-formular.php?tab=subjects">Předměty</a></li>
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
                                            <small class="text-muted">Sem budou zprávy doručovány. Při použití tlačítka Odpovědět se použije adresa návštěvníka (Reply-To).</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-8">
                                        <div class="form-group mb-3">
                                            <label class="form-label" for="sender_email">Odesílatel (From) – e-mail</label>
                                            <input class="form-control" id="sender_email" type="email" name="sender_email" maxlength="254" placeholder="<?= $e($email) ?>" value="<?= $e($senderEmail) ?>">
                                            <small class="text-muted">Nepovinné. Pokud zůstane prázdné, použije se doručovací e-mail. Adresu musí povolovat váš poštovní server.</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-8">
                                        <div class="form-group mb-3">
                                            <label class="form-label" for="sender_name">Jméno odesílatele</label>
                                            <input class="form-control" id="sender_name" type="text" name="sender_name" maxlength="120" required value="<?= $e($senderName) ?>">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-8">
                                        <div class="form-group mb-3">
                                            <label class="form-label" for="email_subject">Nadpis / předmět doručeného e-mailu</label>
                                            <input class="form-control" id="email_subject" type="text" name="email_subject" maxlength="180" required value="<?= $e($emailSubject) ?>">
                                            <small class="text-muted">Za tento nadpis se doplní vybraná kategorie dotazu.</small>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" type="submit">Uložit nastavení</button>
                            </form>
                        </div>
                    </section>
                <?php elseif ($tab === 'security'): ?>
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
                <?php else: ?>
                    <section class="card mb-0">
                        <header class="card-header"><h2 class="card-title">Předměty kontaktního formuláře</h2></header>
                        <div class="card-body">
                            <p class="text-muted">Povinné systémové předměty jsou „Dotaz k tvorbě“ a „Ostatní“. Nelze je odstranit. Nové předměty se přidávají pro všechny jazykové verze, prázdné překlady používají češtinu.</p>
                            <form method="post" action="kontaktni-formular.php?tab=subjects">
                                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="tab" value="subjects">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                <?php foreach (array('cs'=>'Čeština *','sk'=>'Slovenština','pl'=>'Polština','en'=>'Angličtina','de'=>'Němčina','fr'=>'Francouzština') as $code => $languageLabel): ?>
                                    <div class="col-lg-4 col-md-6 mb-3"><label class="form-label" for="label_<?= $e($code) ?>"><?= $e($languageLabel) ?></label>
                                    <input id="label_<?= $e($code) ?>" name="label_<?= $e($code) ?>" class="form-control" maxlength="150" <?= $code === 'cs' ? 'required' : '' ?>></div>
                                <?php endforeach; ?>
                                </div>
                                <button type="submit" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">+ Přidat předmět</button>
                            </form>
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered table-striped"><thead><tr><th>Český název</th><th>Anglický název</th><th>Akce</th></tr></thead><tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr><td><?= $e($subject['label_cs']) ?></td><td><?= $e($subject['label_en']) ?></td><td>
                                  <form method="post" action="kontaktni-formular.php?tab=subjects">
                                    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                    <input type="hidden" name="tab" value="subjects">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="subject_id" value="<?= (int)$subject['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Smazat</button>
                                  </form>
                                </td></tr>
                                <?php endforeach; ?>
                                <?php if (!$subjects): ?><tr><td colspan="3">Zatím nejsou přidány žádné další předměty.</td></tr><?php endif; ?>
                                </tbody></table>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
