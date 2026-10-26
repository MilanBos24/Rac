<?php
// RAC jednorazova obnova pristupu pro testovaci objednavku 28 | 2026-10-25-22.10 | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_min_role('superadmin');
require_once __DIR__.'/../inc/rac-digital.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(503); exit('DB není dostupná.'); }
if (session_status() !== PHP_SESSION_ACTIVE) { http_response_code(500); exit('Session není aktivní.'); }
$e = static function ($x): string { return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
if (empty($_SESSION['rac_grant28_csrf'])) { $_SESSION['rac_grant28_csrf'] = bin2hex(random_bytes(32)); }
$message = ''; $error = '';
try {
  $s=$pdo->prepare("SELECT o.id,o.order_number,p.environment,p.status FROM shop_orders o JOIN shop_payments p ON p.order_id=o.id WHERE o.id=28 AND p.environment='test' AND p.status='paid' ORDER BY p.id DESC LIMIT 1");
  $s->execute(); $order=$s->fetch(PDO::FETCH_ASSOC);
  if (!$order) throw new RuntimeException('Objednávka 28 nemá potvrzenou TEST platbu. Nic nebylo změněno.');
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals((string)$_SESSION['rac_grant28_csrf'],(string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Neplatný CSRF token.');
    $pdo->beginTransaction();
    try {
      $result=racDigitalGrant($pdo,28);
      $pdo->commit();
      $message='Obnova dokončena. Nově vytvořených či obnovených přístupů: '.(int)($result['created'] ?? 0).'.';
    } catch (Throwable $x) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $x; }
  }
  $q=$pdo->prepare("SELECT a.id,a.file_id,f.original_filename,a.expires_at,a.revoked_at FROM shop_digital_access a JOIN product_files f ON f.id=a.file_id WHERE a.order_id=? ORDER BY a.file_id");
  $q->execute([28]); $access=$q->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $x) { $error=$x->getMessage(); $access=[]; }
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>RAC | Obnova přístupů 28</title><style>body{font-family:system-ui,sans-serif;max-width:820px;margin:50px auto;padding:0 18px;line-height:1.5;color:#222}button{background:#171717;color:#fff;border:0;border-radius:5px;padding:12px 20px;cursor:pointer}table{border-collapse:collapse;width:100%}td,th{padding:10px;text-align:left;border-bottom:1px solid #ddd}.error{color:#9e1820}.ok{color:#176a32}</style></head><body>
<h1>Obnova digitálních přístupů – objednávka 28</h1>
<p>Tento jednorázový nástroj vytvoří nebo obnoví odkazy pouze pro zaplacenou testovací objednávku č. 28. Neodesílá e-mail a nevystavuje fakturu.</p>
<?php if ($error!==''): ?><p class="error"><strong>CHYBA:</strong> <?= $e($error) ?></p><?php endif; ?>
<?php if ($message!==''): ?><p class="ok"><strong><?= $e($message) ?></strong></p><?php endif; ?>
<?php if ($error==='' && isset($order)): ?>
<p><strong>Objednávka:</strong> <?= $e($order['order_number']) ?> | <strong>Platba:</strong> TEST / paid</p>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($_SESSION['rac_grant28_csrf']) ?>"><button type="submit">Vytvořit / ověřit digitální přístupy</button></form>
<h2>Aktuální přístupy: <?= count($access) ?></h2>
<table><thead><tr><th>ID souboru</th><th>Soubor</th><th>Platnost do</th><th>Zneplatněno</th></tr></thead><tbody>
<?php foreach ($access as $a): ?><tr><td><?= (int)$a['file_id'] ?></td><td><?= $e($a['original_filename']) ?></td><td><?= $e($a['expires_at']) ?></td><td><?= $a['revoked_at']===null?'Ne':'Ano' ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>
<p><strong>Po úspěšném testu soubor ze serveru smaž.</strong></p>
</body></html>
