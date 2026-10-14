<?php
// RAC E-shop produkty | Porto/BOS24 | 2026-10-12-22.25 | PHP 7.4
// Zpracování dotazu a SQL převzato z původní funkční verze.
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
$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Eshop – Produkty | RAC';
$viewer = current_user_row();
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$menuGroups = [
    ['Přehled', [['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt']]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['E-shop', [
        ['Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'],
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
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/eshop-produkty.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
?>
<?php include __DIR__ . '/inc/header.class.php'; ?>
<?php include __DIR__ . '/inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>E-shop – Produkty</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>E-shop</span></li><li><span>Produkty</span></li>
            </ol>
        </div>
    </header>
    <div class="row mt-4"><div class="col-12">
        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Produkt byl uložen.</div><?php endif; ?>
        <section class="card mb-4">
            <header class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="card-title mb-0">Katalog knih</h2>
                <a class="btn btn-primary" href="eshop-produkt.php"><i class="fas fa-plus me-1"></i> Vložit nový produkt</a>
            </header>
            <div class="card-body">
                <form method="get" action="eshop-produkty.php" class="row g-3 align-items-end mb-4">
                    <div class="col-12 col-md-5">
                        <label for="product_q" class="form-label">Vyhledat</label>
                        <input id="product_q" class="form-control" type="search" name="q" value="<?= h($search) ?>" placeholder="Název, autor, slug">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="product_status" class="form-label">Stav</label>
                        <select id="product_status" class="form-select" name="status">
                            <option value="">Všechny</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= h($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Filtrovat</button>
                        <a class="btn btn-default" href="eshop-produkty.php">Zrušit filtr</a>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Název</th><th>Autor</th><th>Stav</th><th class="text-center">Varianty</th><th class="text-center">Pořadí</th><th class="text-end">Akce</th></tr></thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?= h($product['title']) ?></strong><div class="small text-muted"><?= h($product['slug']) ?></div></td>
                                <td><?= h($product['author'] ?? '') ?></td>
                                <td><?php
                                    $badge = $product['status'] === 'active' ? 'success' : ($product['status'] === 'sold_out' ? 'danger' : 'secondary');
                                ?><span class="badge bg-<?= $badge ?>"><?= h($statuses[$product['status']] ?? $product['status']) ?></span></td>
                                <td class="text-center"><?= (int)$product['variant_count'] ?></td>
                                <td class="text-center"><?= (int)$product['sort_order'] ?></td>
                                <td class="text-end"><a class="btn btn-default btn-sm" href="eshop-produkt.php?id=<?= (int)$product['id'] ?>"><i class="fas fa-edit me-1"></i> Upravit</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$products): ?><tr><td colspan="6" class="text-center py-4 text-muted">Žádné produkty nenalezeny.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div></div>
</section>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
