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
$pageTitle = 'Kontaktní formulář';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
 <div class="page-header"><h1>Kontaktní formulář</h1><p>Nastavení doručování zpráv a ochrana formuláře</p></div>
 <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
 <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
 <section class="card">
 <div class="card-body">
  <div class="cms-language-tabs">
   <a class="cms-language-tab<?= $tab === 'settings' ? ' is-active' : '' ?>" href="?tab=settings">Nastavení</a>
   <a class="cms-language-tab<?= $tab === 'security' ? ' is-active' : '' ?>" href="?tab=security">Bezpečnost</a>
  </div>
  <?php if ($tab === 'settings'): ?>
    <h2>Cílový e-mail</h2>
    <form method="post">
     <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
     <input type="hidden" name="tab" value="settings">
     <div class="form-group"><label for="recipient_email">E-mail pro doručování zpráv</label>
       <input id="recipient_email" class="form-control" name="recipient_email" type="email" required maxlength="254" value="<?= h($email) ?>">
       <small>Na tuto adresu budou doručovány všechny zprávy z webového formuláře. Odpovídat lze přímo odesílateli přes Reply-To.</small>
     </div><button type="submit" class="btn btn-primary">Uložit nastavení</button>
    </form>
  <?php else: ?>
    <h2>Blokované IP adresy</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="tab" value="security">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label for="ip_address">IP adresa (IPv4 nebo IPv6)</label><input id="ip_address" class="form-control" name="ip_address" placeholder="192.0.2.1" required></div>
      <button class="btn btn-primary" type="submit">Blokovat IP</button>
    </form>
    <table class="table"><thead><tr><th>IP adresa</th><th>Datum</th><th>Akce</th></tr></thead><tbody>
    <?php foreach ($blocked as $item): ?>
      <tr><td><?= h($item['ip_address']) ?></td><td><?= h($item['created_at']) ?></td><td>
       <form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="tab" value="security"><input type="hidden" name="action" value="delete"><input type="hidden" name="ip_address" value="<?= h($item['ip_address']) ?>"><button type="submit" class="btn btn-sm btn-danger">Odblokovat</button></form>
      </td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <p>Další bezpečnostní funkce připravíme později.</p>
  <?php endif; ?>
 </div></section>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
