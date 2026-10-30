<?php
/** RAC | Centrum nápovědy | Porto Admin Toggles | PHP 7.4+ */
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Centrum nápovědy | RAC';
$pageTitle = 'Centrum nápovědy';
$viewer = current_user_row();
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$racNavPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/napoveda.php';

// První obsahové návody. Texty lze dále doplňovat bez změny JS/CSS.
$helpCategories = [
    'Správa knih' => [
        ['title' => 'Jak vložit novou knihu?', 'steps' => ['V levém menu otevřete E-shop → Produkty.', 'Klikněte na „Vložit produkt“.', 'Vyplňte údaje o knize a nastavte její vydání a jazykové verze.', 'Uložte změny a zkontrolujte, jak se kniha zobrazuje na webu.'], 'url' => '/administrace/eshop-produkt.php', 'link' => 'Otevřít vložení produktu'],
        ['title' => 'Jak upravit údaje existující knihy?', 'steps' => ['Otevřete E-shop → Produkty.', 'Vyhledejte požadovanou knihu.', 'Otevřete její úpravu, proveďte změny a uložte je.'], 'url' => '/administrace/eshop-produkty.php', 'link' => 'Otevřít katalog knih'],
        ['title' => 'Jak spravovat různé varianty knihy?', 'steps' => ['Otevřete konkrétní produkt v administraci.', 'V editoru zkontrolujte dostupná vydání (například tištěná kniha, e-kniha nebo audiokniha).', 'U každé varianty zkontrolujte potřebné údaje a nastavení dostupnosti.', 'Uložte produkt a zkontrolujte veřejnou stránku knihy.'], 'url' => '/administrace/eshop-produkty.php', 'link' => 'Otevřít produkty'],
        ['title' => 'Jak přidat ukázku knihy?', 'steps' => ['Otevřete editaci příslušné knihy.', 'Vyhledejte část editoru určenou pro soubory či ukázku vydání.', 'Nahrajte soubor ukázky a změny uložte.', 'Na veřejné stránce knihy ověřte otevření i stažení ukázky.'], 'url' => '/administrace/eshop-produkty.php', 'link' => 'Otevřít produkty'],
    ],
    'Objednávky a fakturace' => [
        ['title' => 'Kde najdu objednávky?', 'steps' => ['Otevřete E-shop → Objednávky.', 'Vyhledejte objednávku a otevřete její detail.', 'Zkontrolujte stav objednávky, platbu a objednané položky.'], 'url' => '/administrace/eshop-objednavky.php', 'link' => 'Otevřít objednávky'],
        ['title' => 'Kde nastavím fakturaci?', 'steps' => ['Otevřete Nastavení systému nebo odpovídající část Fakturace.', 'Zkontrolujte údaje dodavatele, číselné řady, splatnost a platební údaje.', 'Změny uložte a ověřte na testovacím dokladu.'], 'url' => null, 'link' => null],
    ],
    'Nastavení administrace' => [
        ['title' => 'Kde upravím právní dokumenty?', 'steps' => ['V administraci otevřete Právní dokumenty.', 'Vyberte dokument a požadovanou jazykovou verzi.', 'Po úpravě text uložte a ověřte jeho zobrazení na webu.'], 'url' => '/administrace/pravni-dokumenty.php', 'link' => 'Otevřít právní dokumenty'],
    ],
];
?>
<?php include __DIR__ . '/inc/header.class.php'; ?>
<?php include __DIR__ . '/inc/leve-meny.class.php'; ?>
<link rel="stylesheet" href="/administrace/css/css-custom.css?v=20261030-02">
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>Centrum nápovědy</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/dashboard.php" aria-label="Dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Nápověda</span></li>
            </ol>
        </div>
    </header>
    <div class="rac-help">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="font-weight-bold text-dark mt-0 mb-2">Jak vám můžeme pomoci?</h3>
                <p class="text-muted mb-3">Vyhledejte návod nebo otevřete některou z otázek níže.</p>
                <label for="helpSearch" class="form-label">Hledat v nápovědě</label>
                <input class="form-control" type="search" id="helpSearch" placeholder="Například vložení knihy, fakturace, objednávky" autocomplete="off">
                <div class="small text-muted mt-2" id="helpCount" aria-live="polite"></div>
                <p id="helpEmpty" class="text-muted mt-3" hidden>Žádný návod neodpovídá vyhledávání.</p>
            </div>
        </div>
        <?php foreach ($helpCategories as $category => $topics): ?>
        <section class="card mb-4 rac-help-category">
            <div class="card-body">
                <h4 class="font-weight-bold text-dark mb-3"><?= $e($category) ?></h4>
                <!-- Nativní Porto Admin Toggle plugin; inicializaci zajišťuje theme.init.js. -->
                <div class="toggle toggle-primary" data-plugin-toggle data-plugin-options="{ 'isAccordion': true }">
                    <?php foreach ($topics as $topic): ?>
                    <section class="toggle rac-help-topic" data-search="<?= $e($category . ' ' . $topic['title'] . ' ' . implode(' ', $topic['steps'])) ?>">
                        <label><?= $e($topic['title']) ?></label>
                        <div class="toggle-content">
                            <ol class="rac-help-steps">
                                <?php foreach ($topic['steps'] as $step): ?>
                                <li><?= $e($step) ?></li>
                                <?php endforeach; ?>
                            </ol>
                            <?php if (!empty($topic['url'])): ?>
                            <p class="mb-0"><a href="<?= $e($topic['url']) ?>" class="btn btn-primary btn-sm"><?= $e($topic['link']) ?></a></p>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
</section>
<script src="/administrace/js/js-custom.js?v=20261030-02" defer></script>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
