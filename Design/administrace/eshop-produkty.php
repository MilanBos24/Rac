<?php
// verze 2026-10-08-12.01 / RAC eshop produkty
// PHP 7.4

declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');

$search = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? '');
$allowedStatuses = array('draft', 'active', 'hidden', 'sold_out');
if (!in_array($status, $allowedStatuses, true)) { $status = ''; }
$where = array(); $params = array();
if ($status !== '') { $where[] = 'p.status = :status'; $params[':status'] = $status; }
if ($search !== '') {
    $where[] = '(p.slug LIKE :search OR t.title LIKE :search2 OR p.author LIKE :search3)';
    $params[':search'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
}
$sql = 'SELECT p.id, p.slug, p.author, p.status, p.featured, p.sort_order, p.updated,
        COALESCE(NULLIF(t.title, \'\'), p.slug) AS title,
        (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS variant_count
        FROM products p
        LEFT JOIN languages l ON l.is_default = 1
        LEFT JOIN product_translations t ON t.product_id = p.id AND t.language_id = l.id';
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.sort_order ASC, p.id DESC LIMIT 300';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$statuses = array('draft'=>'Koncept', 'active'=>'Aktivní', 'hidden'=>'Skrytý', 'sold_out'=>'Vyprodáno');
$pageTitle = 'Eshop – Produkty';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
  <div class="page-header"><h1>Produkty</h1><p>Správa knih a prodejních variant</p></div>
  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Produkt byl uložen.</div><?php endif; ?>
  <section class="card">
    <div class="card-header"><h2>Katalog knih</h2></div>
    <div class="card-body">
      <p><a class="btn btn-primary" href="eshop-produkt.php">＋ Vložit nový produkt</a></p>
      <form method="get" class="grid grid-3" style="margin:18px 0;">
        <div class="form-group"><label>Vyhledat</label><input class="form-control" type="search" name="q" value="<?= h($search) ?>" placeholder="Název, autor, slug"></div>
        <div class="form-group"><label>Stav</label><select class="form-control" name="status"><option value="">Všechny</option><?php foreach ($statuses as $key=>$label): ?><option value="<?= h($key) ?>"<?= $status===$key?' selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>&nbsp;</label><button class="btn btn-primary" type="submit">Filtrovat</button> <a class="btn btn-light" href="eshop-produkty.php">Zrušit filtr</a></div>
      </form>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
          <thead><tr style="text-align:left;border-bottom:1px solid #ddd;"><th style="padding:12px 8px;">Název</th><th>Autor</th><th>Stav</th><th>Variant</th><th>Pořadí</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($products as $product): ?>
            <tr style="border-bottom:1px solid #eee;"><td style="padding:12px 8px;"><strong><?= h($product['title']) ?></strong><div style="font-size:12px;color:#777;"><?= h($product['slug']) ?></div></td>
            <td><?= h($product['author'] ?? '') ?></td><td><?= h($statuses[$product['status']] ?? $product['status']) ?></td><td><?= (int)$product['variant_count'] ?></td><td><?= (int)$product['sort_order'] ?></td><td><a class="btn btn-light btn-sm" href="eshop-produkt.php?id=<?= (int)$product['id'] ?>">Upravit</a></td></tr>
          <?php endforeach; ?>
          <?php if (!$products): ?><tr><td colspan="6" style="padding:20px 8px;">Žádné produkty nenalezeny.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
