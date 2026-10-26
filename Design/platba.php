<?php
declare(strict_types=1);
/**
 * RAC – platba objednávky / Stripe Checkout
 * Verze: 2026-10-15-03 (Sandbox, PHP 7.4+)
 * Původní databázové návaznosti shop_orders/shop_payments jsou zachovány.
 * Live platby jsou v této vývojové verzi výslovně zakázány.
 */
require_once __DIR__ . '/inc/01.config.class.php';
require_once __DIR__ . '/inc/02.language.class.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = racFrontendPdo();
$h = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$label = static function (string $key, string $fallback): string {
    $translated = __($key);
    return is_string($translated) && $translated !== '' && $translated !== $key ? $translated : $fallback;
};

$orderNumber = (string)($_SESSION['rac_payment_order'] ?? '');
$nonce = (string)($_SESSION['rac_payment_nonce'] ?? '');
$order = null;
$errors = array();
$paymentReady = false;
$isSandbox = false;
$stripeMode = 'test';
$returnState = (string)($_GET['return'] ?? '');
// Stav uhrazení se vždy bere z databáze, nikoli z parametru return=success.
$isPaid = false;

if ($pdo instanceof PDO && $orderNumber !== '') {
    try {
        $stmt = $pdo->prepare('SELECT id, order_number, status, email, total, currency FROM shop_orders WHERE order_number = ? LIMIT 1');
        $stmt->execute(array($orderNumber));
        $order = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($order) {
            require_once __DIR__ . '/administrace/billing/settings.php';
            if (billing_settings_table_ready($pdo)) {
                $stripeMode = strtolower(trim((string)billing_setting_get($pdo, 'mode', 'test')));
                $isSandbox = ($stripeMode === 'test');
                $paymentReady = $isSandbox && (string)billing_setting_get($pdo, 'billing_enabled', '0') === '1';
            }
        }
    } catch (Throwable $ex) {
        error_log('RAC payment load: ' . $ex->getMessage());
        $errors[] = $label('payment.unavailable', 'Platební brána je momentálně nedostupná.');
    }
}
if ($order) {
    $isPaid = ((string)$order['status'] === 'paid');
}
if (!$order) {
    $errors[] = 'K platbě zatím není vybraná žádná objednávka. Nejprve vložte knihu do košíku a dokončete objednávkový formulář.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$order || $nonce === '' || !hash_equals($nonce, (string)($_POST['csrf'] ?? ''))) {
        $errors[] = $label('payment.invalid_request', 'Požadavek nelze ověřit. Obnovte stránku.');
    } elseif ((string)$order['status'] !== 'awaiting_payment') {
        $errors[] = $label('payment.invalid_request', 'Objednávku již nelze touto cestou zaplatit.');
    } elseif (!$paymentReady || !$isSandbox) {
        $errors[] = $label('payment.unavailable', 'Platby jsou nyní dostupné pouze v testovacím prostředí.');
    } else {
        try {
            $secretKey = trim((string)billing_setting_get($pdo, 'stripe_test_secret_key', ''));
            if (!preg_match('/^(sk|rk)_test_/', $secretKey)) {
                throw new RuntimeException('Sandbox API key is missing or invalid.');
            }
            if (!function_exists('curl_init')) {
                throw new RuntimeException('PHP cURL is unavailable.');
            }
            $currency = strtolower(trim((string)$order['currency']));
            if (!preg_match('/^[a-z]{3}$/', $currency)) {
                throw new RuntimeException('Invalid currency code.');
            }
            // Původní objednávkový modul RAC zatím používá CZK a dvě desetinná místa.
            // Pro další měny předem doplníme správná Stripe pravidla minor units.
            if ($currency !== 'czk') {
                throw new RuntimeException('This checkout version only supports CZK order totals.');
            }
            $amount = (int)round((float)$order['total'] * 100);
            if ($amount < 50 || $amount > 99999999) {
                throw new RuntimeException('Invalid Stripe amount.');
            }
            $configuredBaseUrl = trim((string)billing_setting_get($pdo, 'base_url', ''));
            $parsed = parse_url($configuredBaseUrl);
            if (!is_array($parsed) || ($parsed['scheme'] ?? '') !== 'https' || empty($parsed['host'])) {
                throw new RuntimeException('Billing Base URL must be HTTPS.');
            }
            $origin = 'https://' . $parsed['host'] . (isset($parsed['port']) ? ':' . (int)$parsed['port'] : '');
            $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
            $configuredHost = strtolower((string)$parsed['host'] . (isset($parsed['port']) ? ':' . (int)$parsed['port'] : ''));
            if (!hash_equals($configuredHost, $host)) {
                throw new RuntimeException('Checkout host differs from configured billing domain.');
            }
            $basePath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/platba.php'))), '/');
            if ($basePath === '.' || $basePath === '/') {
                $basePath = '';
            }
            $successUrl = $origin . $basePath . '/platba.php?return=success&session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $origin . $basePath . '/platba.php?return=cancel';

            $params = array(
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'line_items[0][price_data][currency]' => $currency,
                'line_items[0][price_data][unit_amount]' => (string)$amount,
                'line_items[0][price_data][product_data][name]' => 'RAC – ' . $orderNumber,
                'line_items[0][quantity]' => '1',
                'customer_email' => (string)$order['email'],
                'client_reference_id' => $orderNumber,
                'metadata[integration]' => 'rac-shop',
                'metadata[shop_order_id]' => (string)$order['id'],
                'metadata[order_number]' => $orderNumber,
                'payment_intent_data[metadata][shop_order_id]' => (string)$order['id'],
            );
            $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $secretKey,
                    'Content-Type: application/x-www-form-urlencoded',
                    // Opakovaný POST stejné objednávky nezakládá nové Checkout Sessions.
                    'Idempotency-Key: rac-shop-test-' . hash('sha256', (string)$order['id'] . ':' . $nonce),
                ),
                CURLOPT_POSTFIELDS => http_build_query($params, '', '&', PHP_QUERY_RFC3986),
            ));
            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            $stripe = is_string($response) ? json_decode($response, true) : null;
            if ($httpCode < 200 || $httpCode >= 300 || !is_array($stripe)
                || empty($stripe['id']) || empty($stripe['url'])
                || strpos((string)$stripe['url'], 'https://checkout.stripe.com/') !== 0
                || empty($stripe['livemode']) === false) {
                throw new RuntimeException('Stripe Checkout creation failed: HTTP ' . $httpCode . ' ' . $curlError);
            }
            $stmt = $pdo->prepare('SELECT id FROM shop_payments WHERE checkout_session_id = ? LIMIT 1');
            $stmt->execute(array((string)$stripe['id']));
            if (!$stmt->fetchColumn()) {
                $stmt = $pdo->prepare('INSERT INTO shop_payments (order_id, provider, environment, status, checkout_session_id, amount_minor, currency) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute(array((int)$order['id'], 'stripe', 'test', 'pending', (string)$stripe['id'], $amount, strtoupper($currency)));
            }
            // Pro další pokus po návratu ze Stripe vytvoříme nový idempotentní pokus.
            $_SESSION['rac_payment_nonce'] = bin2hex(random_bytes(24));
            header('Cache-Control: no-store, private');
            header('Location: ' . (string)$stripe['url'], true, 303);
            exit;
        } catch (Throwable $ex) {
            error_log('RAC Stripe Checkout: ' . $ex->getMessage());
            $errors[] = $label('payment.unavailable', 'Platbu se nepodařilo zahájit. Zkuste to prosím znovu.');
        }
    }
}

header('Cache-Control: no-store, private');
include __DIR__ . '/inc/03.head.class.php';
include __DIR__ . '/inc/04.header.class.php';
?>
<section class="page-header">
  <div class="page-header__bg"></div><div class="page-header__overlay"></div>
  <div class="container">
    <ul class="page-header__breadcrumb list-unstyled">
      <li><a href="<?= $h(racUrl('index.php')) ?>"><?= $h($label('breadcrumb.home', 'Úvod')) ?></a></li>
      <li><span><?= $h($label('payment.title', 'Platba objednávky')) ?></span></li>
    </ul>
    <h2 class="page-header__title"><?= $h($label('payment.title', 'Platba objednávky')) ?></h2>
  </div>
</section>
<style>
/* RAC – platební stránka, 2026-10-15-03 */
.rac-pay-wrap{padding:44px 0 75px}
.rac-pay-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:28px;align-items:start}
.rac-pay-panel{background:var(--ogency-black2,#202020);padding:32px;color:#fff;min-width:0}
.rac-pay-panel h2,.rac-pay-panel h3{color:#fff;margin:0 0 16px}
.rac-pay-muted{color:#ccc;font-size:15px;line-height:1.7}
.rac-pay-eyebrow{display:inline-flex;align-items:center;gap:8px;color:var(--ogency-base,#f0c040);font-weight:700;font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:13px}
.rac-pay-method{display:flex;align-items:center;gap:16px;border:1px solid rgba(255,255,255,.16);padding:17px 18px;margin:12px 0;min-width:0}
.rac-pay-method i{color:var(--ogency-base,#f0c040);font-size:24px;width:32px;text-align:center;flex-shrink:0}
.rac-pay-method strong{display:block;color:#fff;font-size:16px}
.rac-pay-method span{display:block;color:#c7c7c7;font-size:13px;margin-top:3px;line-height:1.5}
.rac-pay-line{display:flex;justify-content:space-between;gap:18px;padding:15px 0;border-bottom:1px solid rgba(255,255,255,.15);align-items:baseline}
.rac-pay-line span{color:#c9c9c9}
.rac-pay-line strong{color:#fff;text-align:right;overflow-wrap:anywhere}
.rac-pay-line--total{border-bottom:0;margin-top:12px}
.rac-pay-line--total strong{font-size:26px;color:var(--ogency-base,#f0c040)}
.rac-pay-actions{display:flex;flex-wrap:wrap;gap:14px;margin-top:24px}
.rac-pay-actions form{margin:0}
.rac-pay-actions .ogency-btn{border:0;cursor:pointer;white-space:normal;text-align:center}
.rac-pay-secondary{background:transparent;border:1px solid rgba(255,255,255,.3);display:inline-flex;align-items:center;justify-content:center;min-height:58px;padding:14px 20px;color:#fff;font-weight:700;text-decoration:none}
.rac-pay-secondary:hover{color:var(--ogency-base,#f0c040)}
.rac-pay-note{border-top:1px solid rgba(255,255,255,.14);margin-top:25px;padding-top:16px;font-size:13px;color:#bcbcbc;line-height:1.6}
.rac-pay-alert{margin-bottom:18px}
@media(max-width:991px){.rac-pay-grid{grid-template-columns:1fr}.rac-pay-wrap{padding:25px 0 55px}}
@media(max-width:575px){.rac-pay-panel{padding:22px 17px}.rac-pay-actions,.rac-pay-actions form,.rac-pay-actions .ogency-btn,.rac-pay-secondary{width:100%}.rac-pay-actions{flex-direction:column}.rac-pay-line--total strong{font-size:22px}}
</style>
<section class="checkout-page rac-pay-wrap">
  <div class="container">
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-danger rac-pay-alert" role="alert"><?= $h($error) ?></div>
    <?php endforeach; ?>
    <?php if ($order): ?>
      <div class="rac-pay-grid">
        <div class="rac-pay-panel">
          <span class="rac-pay-eyebrow"><i class="fas fa-shield-alt" aria-hidden="true"></i> Stripe Checkout</span>
          <?php if ($isPaid): ?>
            <h2><i class="fas fa-check-circle" aria-hidden="true" style="color:var(--ogency-base,#f0c040)"></i> Platba byla úspěšně dokončena</h2>
            <div class="alert alert-success rac-pay-alert" role="status">Děkujeme za objednávku. Platba byla potvrzena a objednávka je zaplacená.</div>
            <p class="rac-pay-muted">Číslo a rekapitulaci objednávky najdete v přehledu vedle tohoto potvrzení.</p>
          <?php else: ?>
            <h2><?= $h($label('checkout.payment', 'Způsob platby')) ?></h2>
            <p class="rac-pay-muted">Platební metodu bezpečně vyberete v dalším kroku na stránce Stripe. Nabídka závisí na zemi, zařízení a měně platby.</p>
            <div class="rac-pay-method"><i class="far fa-credit-card" aria-hidden="true"></i><div><strong>Platební karta</strong><span>Visa, Mastercard a další podporované karty</span></div></div>
            <div class="rac-pay-method"><i class="fas fa-mobile-alt" aria-hidden="true"></i><div><strong>Digitální peněženky</strong><span>Apple Pay nebo Google Pay, pokud jsou dostupné</span></div></div>
            <div class="rac-pay-method"><i class="fas fa-globe" aria-hidden="true"></i><div><strong>Další platební možnosti</strong><span>Stripe může nabídnout místní platební metody a přepočet ceny do místní měny</span></div></div>
          <?php endif; ?>
          <?php if ($isSandbox): ?>
            <div class="alert alert-info rac-pay-alert"><strong>TESTOVACÍ REŽIM</strong> – žádná skutečná platba ani stržení peněz.</div>
          <?php else: ?>
            <div class="alert alert-warning rac-pay-alert">Platební brána pro tuto verzi není aktivní. Testování probíhá pouze v Sandboxu.</div>
          <?php endif; ?>
          <?php if (!$isPaid && $returnState === 'success'): ?>
            <div class="alert alert-info rac-pay-alert"><?= $h($label('payment.pending_confirmation', 'Platba byla odeslána. Čekáme na potvrzení od Stripe.')) ?></div>
          <?php elseif (!$isPaid && $returnState === 'cancel'): ?>
            <div class="alert alert-warning rac-pay-alert"><?= $h($label('payment.cancelled', 'Platba byla zrušena. Můžete ji zkusit znovu.')) ?></div>
          <?php endif; ?>
          <?php if (!$isPaid): ?><p class="rac-pay-note">Úspěšné zaplacení objednávky potvrzuje server prostřednictvím ověřeného Stripe webhooku. Samotný návrat z platební stránky neznamená, že už byla platba zaúčtována.</p><?php endif; ?>
        </div>
        <aside class="rac-pay-panel" aria-label="<?= $h($label('payment.summary', 'Shrnutí objednávky')) ?>">
          <h3><?= $h($label('payment.summary', 'Shrnutí objednávky')) ?></h3>
          <div class="rac-pay-line"><span><?= $h($label('checkout.order_number', 'Objednávka')) ?></span><strong><?= $h($orderNumber) ?></strong></div>
          <div class="rac-pay-line"><span>Měna objednávky</span><strong><?= $h(strtoupper((string)$order['currency'])) ?></strong></div>
          <div class="rac-pay-line rac-pay-line--total"><span><?= $h($label('cart.total', 'Celkem')) ?></span><strong><?= $h(number_format((float)$order['total'], 2, ',', ' ')) ?> <?= $h(strtoupper((string)$order['currency'])) ?></strong></div>
          <p class="rac-pay-muted">Konečnou měnu a částku případného přepočtu uvidíte na Stripe Checkout před potvrzením platby.</p>
          <div class="rac-pay-actions">
            <?php if ($isPaid): ?>
              <div class="alert alert-success rac-pay-alert" role="status"><i class="fas fa-check-circle" aria-hidden="true"></i> Stav objednávky: zaplaceno.</div>
            <?php elseif (!$paymentReady): ?>
              <div class="alert alert-warning rac-pay-alert" role="status">Platba zatím není dostupná. V administraci zkontrolujte <strong>Platební brána → Základní nastavení → Platební systém: Aktivní</strong> a režim <strong>Sandbox / Test</strong>.</div>
            <?php elseif ((string)$order['status'] !== 'awaiting_payment'): ?>
              <div class="alert alert-info rac-pay-alert" role="status">Objednávka nyní není ve stavu čekajícím na platbu. Aktuální stav: <?= $h((string)$order['status']) ?>.</div>
            <?php elseif ($nonce === ''): ?>
              <div class="alert alert-warning rac-pay-alert" role="status">Platba nemůže pokračovat, protože vypršelo ověření objednávky. Vraťte se do košíku a vytvořte novou objednávku.</div>
            <?php else: ?>
              <form method="post" id="rac-stripe-payment-form" action="<?= $h(racUrl('platba.php')) ?>">
                <input type="hidden" name="csrf" value="<?= $h($nonce) ?>">
                <button class="ogency-btn" type="submit"><?= $h($label('payment.pay_now', 'Pokračovat k platbě')) ?></button>
              </form>
              <?php if (($_GET['start'] ?? '') === '1'): ?>
              <p class="rac-pay-muted" role="status"><?= $h($label('payment.redirecting', 'Přesměrování na zabezpečenou platbu Stripe…')) ?></p>
              <script>(function(){var f=document.getElementById('rac-stripe-payment-form');if(f){f.submit();}})();</script>
              <?php endif; ?>
            <?php endif; ?>
            <a class="rac-pay-secondary" href="<?= $h(racUrl('index.php')) ?>"><?= $h($label('breadcrumb.home', 'Úvod')) ?></a>
          </div>
        </aside>
      </div>
    <?php else: ?>
      <div class="rac-pay-panel" style="max-width:780px;margin:0 auto">
        <h2>Pro pokračování k platbě vytvořte objednávku</h2>
        <p class="rac-pay-muted">Platební stránku nelze použít samostatně. Vložte knihu do košíku, vyplňte údaje v objednávce a po jejím dokončení se sem automaticky vrátíte.</p>
        <div class="rac-pay-actions">
          <a class="ogency-btn" href="<?= $h(racUrl('kosik.php')) ?>">Přejít do košíku</a>
          <a class="rac-pay-secondary" href="<?= $h(racUrl('index.php')) ?>"><?= $h($label('breadcrumb.home', 'Úvod')) ?></a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php
include __DIR__ . '/inc/05.footer.class.php';
include __DIR__ . '/inc/06.mobile-nav.class.php';
include __DIR__ . '/inc/07.scripts.class.php';
