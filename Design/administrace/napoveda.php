<?php
// verze 2026-11-03-15.02
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
    'Reporting' => [
        [
            'title' => 'Jak prvotně zprovoznit Google Analytics reporting přes OAuth?',
            'steps' => [
                'V Google Cloud Console vytvořte nebo vyberte projekt určený pro reporting, například „RAC Reporting“. Jeden projekt může sloužit jako technické OAuth zázemí pro toto propojení.',
                'V projektu otevřete APIs & Services → Enabled APIs & services. Pokud chybí, přes Enable APIs and Services zapněte Google Analytics Data API a Google Analytics Admin API.',
                'Otevřete Google Auth Platform a dokončete úvodní konfiguraci. V Branding / App Information zadejte název aplikace, například „RAC Reporting“, a kontaktní e-mail.',
                'V části Audience zvolte External, pokud se budou přihlašovat běžné Google účty mimo jednu Google Workspace organizaci. V Contact Information zadejte kontaktní e-mail správce.',
                'V Google Auth Platform → Data Access klikněte na Add or remove scopes. Pokud scope není v seznamu, vložte jej přes Manually add scopes: https://www.googleapis.com/auth/analytics.readonly. Poté klikněte na Add to table a Save.',
                'Dokud je aplikace v režimu Testing, otevřete Google Auth Platform → Audience → Test users → Add users a vložte e-mailovou adresu Google účtu, kterým se budete přihlašovat. Nevkládá se URL adresa. Tento účet musí mít přístup k požadované GA4 službě.',
                'Otevřete Google Auth Platform → Clients → Create OAuth client. Jako Application type zvolte Web application a klienta pojmenujte například „RAC Reporting“.',
                'Authorized JavaScript origins můžete pro toto serverové propojení nechat prázdné. Pokud je chcete vyplnit, patří sem pouze doména bez cesty, například https://pavlakovalovska.eu. Celá cesta /administrace/reporting/... sem nepatří.',
                'Do Authorized redirect URIs vložte přesně callback adresu používanou RAC: https://pavlakovalovska.eu/administrace/reporting/reporting-nastaveni.php. Adresa se musí shodovat znak po znaku, včetně https, domény a celé cesty.',
                'Po vytvoření OAuth klienta si zkopírujte Client ID a Client Secret. Najdete je i později v Google Auth Platform → Clients → RAC Reporting. Client Secret nezveřejňujte, neposílejte e-mailem a nevkládejte do veřejného repozitáře.',
                'Pokud se Client Secret omylem objeví na screenshotu nebo jiném veřejném místě, vytvořte nový secret a původní dále nepoužívejte.',
                'V RAC otevřete Nastavení systému → Nastavení reportingu. Vložte OAuth Client ID a OAuth Client Secret a klikněte na Uložit OAuth nastavení.',
                'Klikněte na Připojit Google Analytics. Přihlaste se Google účtem, který má přístup ke klientově Google Analytics, a povolte aplikaci čtení Analytics.',
                'Po návratu do RAC se zobrazí seznam dostupných GA4 služeb. Vyberte správnou službu a klikněte na Uložit vybranou službu. Property ID se uloží automaticky.',
                'Klikněte na Otestovat připojení. Pokud test projde, OAuth i Google Analytics API fungují. Nová GA4 služba může být ještě bez dat, i když je technické propojení v pořádku.',
                'Před ostrým provozem otevřete Google Auth Platform → Audience a změňte Publishing status z Testing na In production. Pokud bylo RAC poprvé propojeno ještě v režimu Testing, je vhodné Google Analytics v RAC jednou odpojit a znovu připojit, aby se uložil nový refresh token vydaný už v produkčním režimu.'
            ],
            'url' => '/administrace/reporting/reporting-nastaveni.php',
            'link' => 'Otevřít nastavení reportingu'
        ],
        [
            'title' => 'Kde najdu OAuth Client ID a Client Secret?',
            'steps' => [
                'V Google Cloud Console vyberte projekt RAC Reporting.',
                'Otevřete Google Auth Platform → Clients.',
                'Klikněte na OAuth klienta RAC Reporting.',
                'V detailu klienta najdete Client ID a správu Client Secretu.',
                'Client Secret nevkládejte do screenshotů ani do veřejného repozitáře. Pokud byl zveřejněn, vytvořte nový.'
            ],
            'url' => '/administrace/reporting/reporting-nastaveni.php',
            'link' => 'Otevřít nastavení reportingu'
        ],
        [
            'title' => 'Jaký je rozdíl mezi Authorized JavaScript origins a Authorized redirect URIs?',
            'steps' => [
                'Authorized JavaScript origins obsahuje pouze původ webu, tedy protokol a doménu, například https://pavlakovalovska.eu. Nesmí obsahovat cestu a nemá končit lomítkem.',
                'Authorized redirect URIs obsahuje celou callback adresu, například https://pavlakovalovska.eu/administrace/reporting/reporting-nastaveni.php.',
                'Pokud celou callback adresu vložíte do JavaScript origins, Google zobrazí chybu „Invalid Origin: URIs must not contain a path or end with /“.',
                'Pokud se callback adresa neshoduje s adresou uloženou v Google Cloud, zobrazí se chyba redirect_uri_mismatch.'
            ],
            'url' => '/administrace/reporting/reporting-nastaveni.php',
            'link' => 'Otevřít nastavení reportingu'
        ],
        [
            'title' => 'Co zkontrolovat, když OAuth nebo reporting nefunguje?',
            'steps' => [
                'Při chybě Invalid Origin zkontrolujte, že celá callback cesta není vložena do Authorized JavaScript origins. Tam ponechte pouze doménu nebo pole nechte prázdné.',
                'Při chybě redirect_uri_mismatch porovnejte přesně Redirect URI v Google Cloud s adresou v RAC. Musí souhlasit protokol, doména i celá cesta.',
                'Pokud se po připojení nezobrazí žádná GA4 služba, ověřte, že přihlášený Google účet má přístup k dané Analytics službě a že je zapnuté Google Analytics Admin API.',
                'Pokud OAuth funguje, ale reporting neukazuje data, spusťte Otestovat připojení. Pokud test projde, může být GA4 pouze nová a zatím bez nasbíraných dat nebo může být problém v samotném měření na webu.',
                'Pokud Google nevrátí refresh token a RAC žádný použitelný token nemá, zrušte dřívější oprávnění aplikace u Google účtu a proveďte připojení znovu.',
                'Pokud připojení po několika dnech přestane fungovat, zkontrolujte Google Auth Platform → Audience. Pokud je aplikace stále v Testing, přepněte ji do In production a poté RAC znovu připojte.',
                'Pokud byl Client Secret zveřejněn nebo zachycen na screenshotu, vytvořte v Google Auth Platform → Clients nový secret a kompromitovaný již nepoužívejte.'
            ],
            'url' => '/administrace/reporting/reporting-nastaveni.php',
            'link' => 'Otevřít nastavení reportingu'
        ],
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
                <input class="form-control" type="search" id="helpSearch" placeholder="Například vložení knihy, fakturace, objednávky, reporting" autocomplete="off">
                <div class="small text-muted mt-2" id="helpCount" aria-live="polite"></div>
                <p id="helpEmpty" class="text-muted mt-3" hidden>Žádný návod neodpovídá vyhledávání.</p>
            </div>
        </div>
        <?php foreach ($helpCategories as $category => $topics): ?>
        <section class="card mb-4 rac-help-category">
            <div class="card-body">
                <h4 class="font-weight-bold text-dark mb-3"><?= $e($category) ?></h4>
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
