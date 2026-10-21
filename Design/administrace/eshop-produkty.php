<?php
// RAC E-shop produkty | Porto/BOS24 | 2026-10-20-11.00 (RBAC 5B) | PHP 7.4
// Zpracování dotazu a SQL převzato z původní funkční verze.
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');

$viewer = current_user_row();
$racCanEditProducts = $viewer !== null && rac_can($pdo, $viewer, 'products.edit');

$search = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? '');
$allowedStatuses = array('draft', 'active', 'hidden', 'sold_out');
if (!in_array($status, $allowedStatuses, true)) $status = '';
$perPageChoices = array(12, 24, 48, 96);
$perPage = filter_var($_GET['per_page'] ?? 12, FILTER_VALIDATE_INT);
if (!in_array($perPage, $perPageChoices, true)) $perPage = 12;
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
if ($page === false || $page < 1) $page = 1;
$where = array();
$params = array();
if ($status !== '') {
    $where[] = 'p.status = :status';
    $params[':status'] = $status;
}
if ($search !== '') {
    $where[] = '(p.slug LIKE :search OR t.title LIKE :search2 OR p.author LIKE :search3)';
    $params[':search'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
}
$fromSql = ' FROM products p
    LEFT JOIN languages l ON l.is_default = 1
    LEFT JOIN product_translations t ON t.product_id = p.id AND t.language_id = l.id';
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare('SELECT COUNT(DISTINCT p.id)' . $fromSql . $whereSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$pageCount = max(1, (int)ceil($totalProducts / $perPage));
$page = min($page, $pageCount);
$offset = ($page - 1) * $perPage;
$sql = 'SELECT p.id, p.slug, p.author, p.status, p.featured, p.sort_order, p.updated,
        COALESCE(NULLIF(t.title, \'\'), p.slug) AS title,
        (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.active = 1) AS variant_count'
        . $fromSql . $whereSql . ' ORDER BY p.sort_order ASC, p.id DESC LIMIT :row_limit OFFSET :row_offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $name => $value) $stmt->bindValue($name, $value, PDO::PARAM_STR);
$stmt->bindValue(':row_limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':row_offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$statuses = array('draft'=>'Koncept','active'=>'Aktivní','hidden'=>'Skrytý','sold_out'=>'Vyprodáno');
$pageUrl = static function (int $targetPage) use ($search, $status, $perPage): string {
    return 'eshop-produkty.php?' . http_build_query(array_filter(array(
        'q' => $search,
        'status' => $status,
        'per_page' => $perPage,
        'page' => $targetPage
    ), static function ($value, $key) {
        return !($value === '' || ($key === 'page' && $value === 1));
    }, ARRAY_FILTER_USE_BOTH));
};

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
                <?php if ($racCanEditProducts): ?>
                <a class="btn btn-primary" href="eshop-produkt.php"><i class="fas fa-plus me-1"></i> Vložit produkt</a>
                <?php endif; ?>
            </header>
            <div class="card-body">
                <form method="get" action="eshop-produkty.php" class="row g-3 align-items-end mb-4">
                    <div class="col-12 col-md-4">
                        <label for="product_q" class="form-label">Vyhledat</label>
                        <input id="product_q" class="form-control" type="search" name="q" value="<?= h($search) ?>" placeholder="Název, autor, slug">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="product_status" class="form-label">Stav</label>
                        <select id="product_status" class="form-select" name="status">
                            <option value="">Všechny</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= h($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="product_per_page" class="form-label">Zobrazit knih</label>
                        <select id="product_per_page" class="form-select" name="per_page">
                            <?php foreach ($perPageChoices as $choice): ?>
                                <option value="<?= $choice ?>"<?= $perPage === $choice ? ' selected' : '' ?>><?= $choice ?></option>
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
                        <thead><tr><th>Název</th><th>Autor</th><th>Stav</th><th class="text-center">Varianty</th><th class="text-center">Pořadí</th><?php if ($racCanEditProducts): ?><th class="text-end">Akce</th><?php endif; ?></tr></thead>
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
                                <?php if ($racCanEditProducts): ?><td class="text-end"><a class="btn btn-default btn-sm" href="eshop-produkt.php?id=<?= (int)$product['id'] ?>"><i class="fas fa-edit me-1"></i> Upravit</a></td><?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$products): ?><tr><td colspan="<?= $racCanEditProducts ? 6 : 5 ?>" class="text-center py-4 text-muted">Žádné produkty nenalezeny.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="text-muted small">
                        <?php if ($totalProducts > 0): ?>Zobrazeno <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalProducts) ?> z <?= $totalProducts ?> knih<?php else: ?>0 knih<?php endif; ?>
                    </div>
                    <?php if ($pageCount > 1): ?>
                    <nav aria-label="Stránkování produktů"><ul class="pagination mb-0">
                        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= $e($pageUrl(max(1, $page - 1))) ?>" aria-label="Předchozí stránka">&laquo;</a></li>
                        <?php
                        $pages = array(1, $pageCount);
                        for ($i = max(1, $page - 2); $i <= min($pageCount, $page + 2); $i++) $pages[] = $i;
                        $pages = array_values(array_unique($pages));
                        sort($pages, SORT_NUMERIC);
                        $previous = 0;
                        foreach ($pages as $number):
                            if ($previous > 0 && $number > $previous + 1):
                        ?><li class="page-item disabled" aria-hidden="true"><span class="page-link">…</span></li><?php endif; ?>
                        <li class="page-item<?= $number === $page ? ' active' : '' ?>"><a class="page-link" href="<?= $e($pageUrl($number)) ?>"<?= $number === $page ? ' aria-current="page"' : '' ?>><?= $number ?></a></li>
                        <?php $previous = $number; endforeach; ?>
                        <li class="page-item<?= $page >= $pageCount ? ' disabled' : '' ?>"><a class="page-link" href="<?= $e($pageUrl(min($pageCount, $page + 1))) ?>" aria-label="Další stránka">&raquo;</a></li>
                    </ul></nav>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div></div>
</section>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
