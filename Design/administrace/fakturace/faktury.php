<?php
// RAC fakturace | 2026-10-27-19.55-MENU02 | Seznam vystavenych faktur | PHP 7.4+
declare(strict_types=1);
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/auth_roles.php';
require_once __DIR__ . '/../config/permissions.php';
require_min_role('admin');

$viewer = current_user_row();
if (!$viewer) { http_response_code(403); exit('Přístup odepřen.'); }
$isSuper = strtolower((string)($viewer['role'] ?? '')) === 'superadmin';
if (!$isSuper && !rac_can($pdo, $viewer, 'orders.view')) {
    http_response_code(403);
    exit('K zobrazení faktur nemáte oprávnění.');
}

function racFakturyH($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function racFakturyDatum($value): string {
    $date = (string)$value;
    if ($date === '') return '—';
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));
    return $dt instanceof DateTimeImmutable ? $dt->format('d.m.Y') : $date;
}
function racFakturyPenize($amount, string $currency): string {
    return number_format((float)$amount, 2, ',', ' ') . ' ' . $currency;
}

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q, 'UTF-8') > 120) $q = mb_substr($q, 0, 120, 'UTF-8');
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $page === false ? 1 : (int)$page;
$perPage = 25;
$total = 0;
$rows = [];
$errors = [];

try {
    $where = '';
    $parameters = [];
    if ($q !== '') {
        $where = ' WHERE (cislo_dokladu LIKE :number OR order_number LIKE :order_number OR odberatel_nazev LIKE :customer OR odberatel_email LIKE :email)';
        $term = '%' . $q . '%';
        $parameters = [':number'=>$term, ':order_number'=>$term, ':customer'=>$term, ':email'=>$term];
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM fakturace_doklady' . $where);
    $count->execute($parameters);
    $total = (int)$count->fetchColumn();
    $lastPage = max(1, (int)ceil($total/$perPage));
    $page = min($page, $lastPage);
    $offset = ($page-1)*$perPage;
    // LIMIT/OFFSET se pouzivaji vyhradne po prevodu na cele kladne cislo.
    $sql = 'SELECT id, order_id, order_number, cislo_dokladu, datum_vystaveni, odberatel_nazev, odberatel_email, mena, castka_celkem, stav, email_odeslan_dne FROM fakturace_doklady'
        . $where . ' ORDER BY datum_vystaveni DESC, id DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
    $list = $pdo->prepare($sql);
    $list->execute($parameters);
    $rows = $list->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
    error_log('RAC fakturace seznam: ' . $ex->getMessage());
    $errors[] = 'Faktury se nepodařilo načíst. Ověř strukturu databáze (migrace 026).';
}
$pages = max(1, (int)ceil($total/$perPage));
$searchQuery = $q !== '' ? '&q=' . rawurlencode($q) : '';

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Faktury | RAC';
$pageTitle = 'Faktury';
$currentPath = '/administrace/fakturace/faktury.php';
$viewerName = (string)($viewer['username'] ?? 'Administrátor');
$viewerRole = (string)($viewer['role'] ?? '');
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$e = static function ($value): string { return racFakturyH($value); };
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
];
include __DIR__ . '/../inc/header.class.php';
include __DIR__ . '/../inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header"><h2>Vystavené faktury</h2></header>
    <div class="row pt-3"><div class="col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="mb-0 text-muted">Evidence faktur vystavených k zaplaceným objednávkám RAC.</p>
            <a href="k-vystaveni.php" class="btn btn-primary">Zaplacené objednávky k fakturaci</a>
            <a href="nastaveni.php" class="btn btn-default"><i class="fas fa-cog me-1" aria-hidden="true"></i> Nastavení fakturace</a>
        </div>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= $e($error) ?></div>
        <?php endforeach; ?>
        <div class="alert alert-info">Stripe TEST doklady se označují TEST, nejde o účetní doklady a nečerpají ostrou číselnou řadu. Automatické vystavení proběhne při ověřené TEST platbě pro tuzemskou objednávku CZK u neplátce DPH; PDF se vloží do digitálního doručovacího e-mailu.</div>
        <section class="card mb-4">
            <header class="card-header"><h2 class="card-title">Seznam faktur</h2></header>
            <div class="card-body">
                <form method="get" action="faktury.php" class="row g-2 align-items-end mb-3">
                    <div class="col-md-9">
                        <label for="faktury-q" class="form-label">Vyhledat fakturu, objednávku nebo zákazníka</label>
                        <input id="faktury-q" name="q" type="search" class="form-control" maxlength="120" value="<?= $e($q) ?>" placeholder="Číslo faktury, číslo objednávky, jméno nebo e-mail">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">Vyhledat</button>
                        <?php if ($q !== ''): ?><a class="btn btn-default" href="faktury.php">Zrušit</a><?php endif; ?>
                    </div>
                </form>
                <?php if (!$errors && !$rows): ?>
                    <div class="alert alert-info mb-0">
                        <?= $q !== '' ? 'Žádná faktura neodpovídá zadanému hledání.' : 'Zatím nejsou vystavené žádné faktury. Vystavení můžete zatím vyzkoušet ručně u ověřené zaplacené objednávky.' ?>
                    </div>
                <?php elseif (!$errors): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead><tr>
                                <th>Faktura</th><th>Vystaveno</th><th>Objednávka</th><th>Odběratel</th><th class="text-end">Částka</th><th>Stav</th><th>E-mail</th><th>PDF</th><th>Objednávka RAC</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><strong><?= $e($row['cislo_dokladu']) ?></strong><?php if (strpos((string)$row['cislo_dokladu'], 'TEST-')===0): ?><div><span class="badge bg-warning text-dark">TEST – neúčetní doklad</span></div><?php endif; ?></td>
                                    <td class="text-nowrap"><?= $e(racFakturyDatum($row['datum_vystaveni'])) ?></td>
                                    <td><?= $e($row['order_number']) ?></td>
                                    <td><?= $e($row['odberatel_nazev']) ?><div class="small text-muted"><?= $e($row['odberatel_email']) ?></div></td>
                                    <td class="text-end text-nowrap"><?= $e(racFakturyPenize($row['castka_celkem'], (string)$row['mena'])) ?></td>
                                    <td><span class="badge <?= strpos((string)$row['cislo_dokladu'], 'TEST-')===0 ? 'bg-warning text-dark' : 'bg-success' ?>"><?= strpos((string)$row['cislo_dokladu'], 'TEST-')===0 ? 'TEST – simulovaná platba' : $e($row['stav'] === 'zaplaceno' ? 'Zaplaceno' : $row['stav']) ?></span></td>
                                    <td><?= empty($row['email_odeslan_dne']) ? '<span class="text-muted">Neodesláno</span>' : $e(racFakturyDatum($row['email_odeslan_dne'])) ?></td>
                                    <td><a class="btn btn-primary btn-sm" href="faktura-pdf.php?id=<?= (int)$row['id'] ?>" target="_blank" rel="noopener">PDF</a></td><td><a class="btn btn-default btn-sm" href="/administrace/eshop-objednavka.php?id=<?= (int)$row['order_id'] ?>">Detail objednávky</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between flex-wrap align-items-center gap-2 mt-3">
                        <span class="text-muted">Celkem faktur: <?= (int)$total ?> · Strana <?= (int)$page ?> z <?= (int)$pages ?></span>
                        <?php if ($pages > 1): ?>
                            <nav aria-label="Stránkování faktur"><ul class="pagination mb-0">
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="?page=<?= max(1,$page-1) . $searchQuery ?>">Předchozí</a></li>
                                <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>"><a class="page-link" href="?page=<?= min($pages,$page+1) . $searchQuery ?>">Další</a></li>
                            </ul></nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div></div>
</section>
<?php include __DIR__ . '/../inc/footer.class.php'; ?>
