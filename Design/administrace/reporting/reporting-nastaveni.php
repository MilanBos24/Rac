<?php
// verze 2026-11-03-15.02
// RAC Reporting – nastavení Google Analytics přes OAuth 2.0 | PHP 7.4+
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/auth_roles.php';
require_once __DIR__ . '/google-analytics-oauth.class.php';
require_once __DIR__ . '/google-analytics-data.class.php';

$viewer = current_user_row();
if (!$viewer) {
    header('Location: /administrace/index.php');
    exit;
}
if (strtolower((string)($viewer['role'] ?? '')) !== 'superadmin') {
    header('Location: /administrace/access-denied.php');
    exit;
}

function racReportingH($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function racReportingRedirectUri(): string
{
    $host = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : '';
    if ($host === '' || !preg_match('/^[a-z0-9.\-:\[\]]+$/i', $host)) {
        throw new RuntimeException('Nelze určit adresu tohoto webu pro OAuth callback.');
    }
    $forwarded = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || $forwarded === 'https';
    return ($https ? 'https' : 'http') . '://' . $host . '/administrace/reporting/reporting-nastaveni.php';
}

function racReportingConfigFile(): string
{
    return __DIR__ . '/private/google-oauth-config.php';
}

function racReportingLoadConfig(): ?array
{
    $file = racReportingConfigFile();
    if (!is_file($file) || !is_readable($file)) {
        return null;
    }
    $config = require $file;
    return is_array($config) ? $config : null;
}

function racReportingSaveConfig(array $config): void
{
    $dir = dirname(racReportingConfigFile());
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        throw new RuntimeException('Nepodařilo se vytvořit chráněnou složku reporting/private.');
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n", LOCK_EX);
    }
    $content = "<?php\n// Vygenerováno administrací RAC – neupravovat ručně, pokud to není nutné.\nreturn " . var_export($config, true) . ";\n";
    $tmp = racReportingConfigFile() . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $content, LOCK_EX) === false) {
        throw new RuntimeException('OAuth konfiguraci se nepodařilo zapsat. Zkontrolujte práva složky reporting/private.');
    }
    @chmod($tmp, 0600);
    if (!@rename($tmp, racReportingConfigFile())) {
        @unlink($tmp);
        throw new RuntimeException('OAuth konfiguraci se nepodařilo dokončit.');
    }
    @chmod(racReportingConfigFile(), 0600);
}

function racReportingConnection(PDO $pdo): ?array
{
    $stmt = $pdo->query('SELECT * FROM reporting_google_analytics WHERE id = 1 LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function racReportingAccessToken(PDO $pdo, GoogleAnalyticsOAuth $oauth): string
{
    $accessToken = isset($_SESSION['rac_ga_access_token']) ? (string)$_SESSION['rac_ga_access_token'] : '';
    $expiresAt = (int)($_SESSION['rac_ga_access_token_expires'] ?? 0);
    if ($accessToken !== '' && time() < ($expiresAt - 60)) {
        return $accessToken;
    }

    $connection = racReportingConnection($pdo);
    if (!$connection || empty($connection['refresh_token'])) {
        throw new RuntimeException('Google Analytics ještě není propojeno.');
    }
    $refreshToken = $oauth->decrypt((string)$connection['refresh_token']);
    $tokens = $oauth->refreshAccessToken($refreshToken);
    $accessToken = isset($tokens['access_token']) ? trim((string)$tokens['access_token']) : '';
    if ($accessToken === '') {
        throw new RuntimeException('Google nevrátil nový access token.');
    }
    $_SESSION['rac_ga_access_token'] = $accessToken;
    $_SESSION['rac_ga_access_token_expires'] = time() + max(60, (int)($tokens['expires_in'] ?? 3600));
    return $accessToken;
}

$errors = array();
$success = '';
$dbReady = true;
try {
    $pdo->query('SELECT id FROM reporting_google_analytics LIMIT 1');
} catch (Throwable $e) {
    $dbReady = false;
    $errors[] = 'Chybí databázová tabulka reportingu. Spusťte nejprve SQL soubor 027_reporting_google_analytics.sql.';
}

$redirectUri = '';
try {
    $redirectUri = racReportingRedirectUri();
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$config = racReportingLoadConfig();
$oauth = null;
if ($config) {
    try {
        $oauth = new GoogleAnalyticsOAuth($config);
    } catch (Throwable $e) {
        $errors[] = 'Uložená OAuth konfigurace není platná: ' . $e->getMessage();
    }
}

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$csrf = csrf_token('rac_reporting_settings');

if ($action === 'save-oauth' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_valid('rac_reporting_settings', (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.';
    } else {
        $clientId = trim((string)($_POST['client_id'] ?? ''));
        $postedSecret = trim((string)($_POST['client_secret'] ?? ''));
        $oldSecret = $config && isset($config['client_secret']) ? (string)$config['client_secret'] : '';
        $clientSecret = $postedSecret !== '' ? $postedSecret : $oldSecret;

        if ($clientId === '' || strlen($clientId) > 500) {
            $errors[] = 'Vyplňte platné OAuth Client ID.';
        }
        if ($clientSecret === '' || strlen($clientSecret) > 500) {
            $errors[] = 'Vyplňte OAuth Client Secret.';
        }
        if ($redirectUri === '') {
            $errors[] = 'Nelze uložit nastavení bez platné Redirect URI.';
        }

        if (!$errors) {
            try {
                $encryptionKey = $config && !empty($config['encryption_key'])
                    ? (string)$config['encryption_key']
                    : base64_encode(random_bytes(32));
                $newConfig = array(
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
                    'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
                    'token_endpoint' => 'https://oauth2.googleapis.com/token',
                    'encryption_key' => $encryptionKey,
                );
                racReportingSaveConfig($newConfig);
                $_SESSION['rac_reporting_flash_ok'] = 'OAuth nastavení bylo uloženo.';
                header('Location: /administrace/reporting/reporting-nastaveni.php');
                exit;
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

if ($action === 'connect') {
    try {
        if (!$dbReady) {
            throw new RuntimeException('Nejprve nainstalujte SQL tabulku reportingu.');
        }
        if (!$oauth) {
            throw new RuntimeException('Nejprve uložte OAuth Client ID a Client Secret.');
        }
        $_SESSION['rac_ga_oauth_state'] = bin2hex(random_bytes(32));
        header('Location: ' . $oauth->getAuthorizationUrl((string)$_SESSION['rac_ga_oauth_state']));
        exit;
    } catch (Throwable $e) {
        $_SESSION['rac_reporting_flash_err'] = $e->getMessage();
        header('Location: /administrace/reporting/reporting-nastaveni.php');
        exit;
    }
}

if ($action === 'callback' || isset($_GET['code']) || isset($_GET['error'])) {
    try {
        if (!$dbReady || !$oauth) {
            throw new RuntimeException('Reporting není připraven pro OAuth callback.');
        }
        if (isset($_GET['error'])) {
            throw new RuntimeException('Google přístup nepovolil: ' . (string)$_GET['error']);
        }
        $state = trim((string)($_GET['state'] ?? ''));
        $expectedState = (string)($_SESSION['rac_ga_oauth_state'] ?? '');
        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            throw new RuntimeException('Neplatný OAuth bezpečnostní token.');
        }
        unset($_SESSION['rac_ga_oauth_state']);
        $code = trim((string)($_GET['code'] ?? ''));
        if ($code === '') {
            throw new RuntimeException('Google nevrátil autorizační kód.');
        }
        $tokens = $oauth->exchangeAuthorizationCode($code);
        $accessToken = isset($tokens['access_token']) ? trim((string)$tokens['access_token']) : '';
        $refreshToken = isset($tokens['refresh_token']) ? trim((string)$tokens['refresh_token']) : '';
        if ($accessToken === '') {
            throw new RuntimeException('Google nevrátil access token.');
        }
        if ($refreshToken === '') {
            $existing = racReportingConnection($pdo);
            if (!$existing || empty($existing['refresh_token'])) {
                throw new RuntimeException('Google nevrátil refresh token. Zrušte dřívější oprávnění aplikace u Google účtu a propojení spusťte znovu.');
            }
            $refreshToken = $oauth->decrypt((string)$existing['refresh_token']);
        }
        $encrypted = $oauth->encrypt($refreshToken);
        $stmt = $pdo->prepare(
            'INSERT INTO reporting_google_analytics (id, refresh_token, connected_at, updated_at) '
            . 'VALUES (1, :refresh_token, NOW(), NOW()) '
            . 'ON DUPLICATE KEY UPDATE refresh_token = VALUES(refresh_token), google_account_name = NULL, google_account_id = NULL, property_id = NULL, property_name = NULL, connected_at = NOW(), updated_at = NOW()'
        );
        $stmt->execute(array(':refresh_token' => $encrypted));
        $_SESSION['rac_ga_access_token'] = $accessToken;
        $_SESSION['rac_ga_access_token_expires'] = time() + max(60, (int)($tokens['expires_in'] ?? 3600));
        header('Location: /administrace/reporting/reporting-nastaveni.php?action=properties');
        exit;
    } catch (Throwable $e) {
        $_SESSION['rac_reporting_flash_err'] = 'Google Analytics se nepodařilo propojit: ' . $e->getMessage();
        header('Location: /administrace/reporting/reporting-nastaveni.php');
        exit;
    }
}

$properties = array();
$showProperties = false;
if ($action === 'properties') {
    try {
        if (!$dbReady || !$oauth) {
            throw new RuntimeException('Nejprve dokončete OAuth nastavení a připojení Google účtu.');
        }
        $accessToken = racReportingAccessToken($pdo, $oauth);
        $summaries = $oauth->listAccountSummaries($accessToken);
        foreach ($summaries as $account) {
            $accountResource = (string)($account['account'] ?? '');
            $accountId = preg_replace('~^accounts/~', '', $accountResource);
            $accountName = (string)($account['displayName'] ?? $accountResource);
            foreach (($account['propertySummaries'] ?? array()) as $property) {
                $propertyResource = (string)($property['property'] ?? '');
                $propertyId = preg_replace('~^properties/~', '', $propertyResource);
                if ($propertyId === '') {
                    continue;
                }
                $properties[] = array(
                    'account_id' => $accountId,
                    'account_name' => $accountName,
                    'property_id' => $propertyId,
                    'property_name' => (string)($property['displayName'] ?? $propertyResource),
                );
            }
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!csrf_valid('rac_reporting_settings', (string)($_POST['csrf'] ?? ''))) {
                throw new RuntimeException('Neplatný bezpečnostní token.');
            }
            $selectedId = trim((string)($_POST['property_id'] ?? ''));
            $selected = null;
            foreach ($properties as $property) {
                if (hash_equals((string)$property['property_id'], $selectedId)) {
                    $selected = $property;
                    break;
                }
            }
            if (!$selected) {
                throw new RuntimeException('Vybraná GA4 služba není dostupná.');
            }
            $stmt = $pdo->prepare(
                'UPDATE reporting_google_analytics SET google_account_name=:account_name, google_account_id=:account_id, property_id=:property_id, property_name=:property_name, updated_at=NOW() WHERE id=1 LIMIT 1'
            );
            $stmt->execute(array(
                ':account_name' => $selected['account_name'],
                ':account_id' => $selected['account_id'],
                ':property_id' => $selected['property_id'],
                ':property_name' => $selected['property_name'],
            ));
            $_SESSION['rac_reporting_flash_ok'] = 'Google Analytics bylo propojeno se službou „' . $selected['property_name'] . '“.';
            header('Location: /administrace/reporting/reporting-nastaveni.php');
            exit;
        }
        $showProperties = true;
    } catch (Throwable $e) {
        $errors[] = 'Seznam GA4 služeb se nepodařilo načíst: ' . $e->getMessage();
    }
}

if ($action === 'test' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (!csrf_valid('rac_reporting_settings', (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token.');
        }
        if (!$oauth) {
            throw new RuntimeException('OAuth konfigurace není dokončena.');
        }
        $connection = racReportingConnection($pdo);
        if (!$connection || empty($connection['property_id'])) {
            throw new RuntimeException('Nejprve vyberte GA4 službu.');
        }
        $accessToken = racReportingAccessToken($pdo, $oauth);
        $client = new GoogleAnalyticsDataClient((string)$connection['property_id'], $accessToken);
        $client->runReport(array(
            'dateRanges' => array(array('startDate' => '7daysAgo', 'endDate' => 'today')),
            'metrics' => array(array('name' => 'activeUsers')),
            'limit' => 1,
        ));
        $_SESSION['rac_reporting_flash_ok'] = 'Připojení funguje. Google Analytics Data API odpovědělo správně.';
    } catch (Throwable $e) {
        $_SESSION['rac_reporting_flash_err'] = 'Test připojení selhal: ' . $e->getMessage();
    }
    header('Location: /administrace/reporting/reporting-nastaveni.php');
    exit;
}

if ($action === 'disconnect' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (!csrf_valid('rac_reporting_settings', (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Neplatný bezpečnostní token.');
        }
        if ($dbReady) {
            $connection = racReportingConnection($pdo);
            if ($connection && $oauth && !empty($connection['refresh_token'])) {
                try {
                    $oauth->revokeToken($oauth->decrypt((string)$connection['refresh_token']));
                } catch (Throwable $ignored) {
                    // Lokální odpojení provedeme i při již neplatném Google tokenu.
                }
            }
            $pdo->exec('DELETE FROM reporting_google_analytics WHERE id = 1');
        }
        unset($_SESSION['rac_ga_access_token'], $_SESSION['rac_ga_access_token_expires']);
        $_SESSION['rac_reporting_flash_ok'] = 'Google Analytics bylo odpojeno.';
    } catch (Throwable $e) {
        $_SESSION['rac_reporting_flash_err'] = 'Odpojení se nezdařilo: ' . $e->getMessage();
    }
    header('Location: /administrace/reporting/reporting-nastaveni.php');
    exit;
}

if (!empty($_SESSION['rac_reporting_flash_ok'])) {
    $success = (string)$_SESSION['rac_reporting_flash_ok'];
    unset($_SESSION['rac_reporting_flash_ok']);
}
if (!empty($_SESSION['rac_reporting_flash_err'])) {
    $errors[] = (string)$_SESSION['rac_reporting_flash_err'];
    unset($_SESSION['rac_reporting_flash_err']);
}

$config = racReportingLoadConfig();
$connection = null;
if ($dbReady) {
    try {
        $connection = racReportingConnection($pdo);
    } catch (Throwable $e) {
        $errors[] = 'Stav propojení se nepodařilo načíst: ' . $e->getMessage();
    }
}

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Nastavení reportingu | RAC';
$pageTitle = 'Nastavení reportingu';
$currentPath = '/administrace/reporting/reporting-nastaveni.php';
$viewerName = (string)($viewer['username'] ?? 'Superadmin');
$viewerRole = (string)($viewer['role'] ?? 'superadmin');
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$e = static function ($value): string { return racReportingH($value); };
$menuGroups = array(
    array('Přehled', array(array('Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'))),
    array('E-shop', array(array('Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'))),
);

include dirname(__DIR__) . '/inc/header.class.php';
include dirname(__DIR__) . '/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>Nastavení reportingu</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Nastavení systému</span></li>
                <li><span>Reporting</span></li>
            </ol>
        </div>
    </header>

    <div class="row mt-4"><div class="col-12">
        <?php if ($success !== ''): ?><div class="alert alert-success"><?= $e($success) ?></div><?php endif; ?>
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= $e($error) ?></div><?php endforeach; ?>

        <div class="alert alert-info">
            Reporting používá <strong>Google Analytics Data API přes OAuth 2.0</strong>. Klient nepotřebuje servisní účet ani JSON klíč. OAuth aplikace je společná technická konfigurace systému; následně se pouze přihlásí Google účet s přístupem ke konkrétní GA4 službě.
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <section class="card mb-4">
                    <header class="card-header"><h2 class="card-title">1. OAuth aplikace Google</h2></header>
                    <div class="card-body">
                        <p class="text-muted">V Google Cloud vytvořte OAuth 2.0 Client ID typu <strong>Web application</strong>. Aktivujte Google Analytics Data API a Google Analytics Admin API. Níže uvedenou Redirect URI vložte mezi autorizované redirect URI aplikace.</p>
                        <form method="post" action="reporting-nastaveni.php?action=save-oauth" autocomplete="off">
                            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                            <div class="mb-3">
                                <label class="form-label" for="ga-client-id">OAuth Client ID</label>
                                <input class="form-control" id="ga-client-id" name="client_id" maxlength="500" required value="<?= $e((string)($config['client_id'] ?? '')) ?>" placeholder="...apps.googleusercontent.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="ga-client-secret">OAuth Client Secret</label>
                                <input class="form-control" id="ga-client-secret" name="client_secret" type="password" maxlength="500" autocomplete="new-password" placeholder="<?= $config ? 'Ponechte prázdné pro zachování uloženého secretu' : 'Zadejte Client Secret' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="ga-redirect-uri">Authorized redirect URI</label>
                                <div class="input-group">
                                    <input class="form-control font-monospace" id="ga-redirect-uri" readonly value="<?= $e($redirectUri) ?>">
                                    <button class="btn btn-default" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('ga-redirect-uri').value)"><i class="far fa-copy"></i></button>
                                </div>
                                <div class="form-text">Tato adresa musí být v Google Cloud zadána přesně tak, jak je uvedena výše.</div>
                            </div>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit OAuth nastavení</button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-xl-5">
                <section class="card mb-4">
                    <header class="card-header"><h2 class="card-title">2. Google Analytics služba</h2></header>
                    <div class="card-body">
                        <?php if (!$config): ?>
                            <div class="alert alert-warning mb-0">Nejprve uložte OAuth Client ID a Client Secret.</div>
                        <?php elseif (!$dbReady): ?>
                            <div class="alert alert-warning mb-0">Nejprve nainstalujte SQL tabulku reportingu.</div>
                        <?php elseif (!$connection): ?>
                            <p>Google účet zatím není propojen.</p>
                            <a class="btn btn-primary" href="reporting-nastaveni.php?action=connect"><i class="fab fa-google me-1"></i> Připojit Google Analytics</a>
                        <?php elseif (empty($connection['property_id'])): ?>
                            <p>Google účet je autorizován, ale ještě není vybrána GA4 služba.</p>
                            <a class="btn btn-primary" href="reporting-nastaveni.php?action=properties">Vybrat GA4 službu</a>
                        <?php else: ?>
                            <dl class="row mb-3">
                                <dt class="col-5">Účet</dt><dd class="col-7"><?= $e((string)$connection['google_account_name']) ?></dd>
                                <dt class="col-5">Služba</dt><dd class="col-7"><?= $e((string)$connection['property_name']) ?></dd>
                                <dt class="col-5">Property ID</dt><dd class="col-7"><code><?= $e((string)$connection['property_id']) ?></code></dd>
                            </dl>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-default" href="reporting-nastaveni.php?action=properties">Změnit službu</a>
                                <form method="post" action="reporting-nastaveni.php?action=test" class="d-inline">
                                    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                    <button class="btn btn-success" type="submit">Otestovat připojení</button>
                                </form>
                                <form method="post" action="reporting-nastaveni.php?action=disconnect" class="d-inline" onsubmit="return confirm('Opravdu odpojit Google Analytics?');">
                                    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                    <button class="btn btn-danger" type="submit">Odpojit</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>

        <?php if ($showProperties): ?>
        <section class="card mb-4">
            <header class="card-header"><h2 class="card-title">Vyberte Google Analytics 4 službu</h2><p class="card-subtitle">Zobrazeny jsou služby dostupné právě připojenému Google účtu.</p></header>
            <div class="card-body">
                <?php if (!$properties): ?>
                    <div class="alert alert-warning mb-0">Google účet nemá dostupnou žádnou GA4 službu, nebo není aktivní Google Analytics Admin API.</div>
                <?php else: ?>
                    <form method="post" action="reporting-nastaveni.php?action=properties">
                        <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                        <div class="row g-3">
                            <?php foreach ($properties as $index => $property): $id = 'ga-property-' . (int)$index; ?>
                            <div class="col-lg-6">
                                <label class="border rounded p-3 d-block h-100" for="<?= $e($id) ?>" style="cursor:pointer;">
                                    <input class="form-check-input me-2" type="radio" name="property_id" id="<?= $e($id) ?>" value="<?= $e($property['property_id']) ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                    <strong><?= $e($property['property_name']) ?></strong><br>
                                    <span class="text-muted ms-4"><?= $e($property['account_name']) ?> · Property ID <?= $e($property['property_id']) ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary" type="submit">Uložit vybranou službu</button></div>
                    </form>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

    </div></div>
</section>
<?php include dirname(__DIR__) . '/inc/footer.class.php'; ?>
