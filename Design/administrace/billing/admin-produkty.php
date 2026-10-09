<?php
declare(strict_types=1);

/**
 * RAC Billing - administrace produktu
 * verze 2026-09-16-20.45
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/auth_roles.php';

require_min_role('admin');

$viewer = current_user_row();
if (!$viewer) {
    header('Location: /logout.php');
    exit;
}

if (empty($_SESSION['csrf_billing_products'])) {
    $_SESSION['csrf_billing_products'] = bin2hex(random_bytes(32));
}

function bp_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bp_redirect(string $status = 'saved'): void
{
    header('Location: admin-produkty.php?status=' . urlencode($status));
    exit;
}

function bp_currency_decimals(string $currency): int
{
    $currency = strtoupper(trim($currency));

    // Stripe currencies without minor units.
    $zeroDecimal = array(
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY',
        'KMF', 'KRW', 'MGA', 'PYG', 'RWF',
        'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
    );

    return in_array($currency, $zeroDecimal, true) ? 0 : 2;
}

function bp_amount_to_minor(string $value, string $currency): int
{
    $value = trim(str_replace(array(' ', ','), array('', '.'), $value));

    if ($value === '' || !is_numeric($value)) {
        throw new RuntimeException('Zadejte platnou cenu.');
    }

    $amount = (float)$value;

    if ($amount < 0) {
        throw new RuntimeException('Cena nesmí být záporná.');
    }

    $decimals = bp_currency_decimals($currency);
    $multiplier = $decimals === 0 ? 1 : 100;

    return (int)round($amount * $multiplier);
}

function bp_minor_to_amount($minor, string $currency): string
{
    $minor = (int)$minor;
    $decimals = bp_currency_decimals($currency);

    if ($decimals === 0) {
        return (string)$minor;
    }

    return number_format($minor / 100, 2, '.', '');
}

function bp_product_exists(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT id FROM billing_products WHERE id = :id LIMIT 1');
    $stmt->execute(array(':id' => $id));
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

$errors = array();
$success = '';

if (isset($_GET['status'])) {
    switch ((string)$_GET['status']) {
        case 'created':
            $success = 'Produkt byl vytvořen.';
            break;
        case 'updated':
            $success = 'Produkt byl upraven.';
            break;
        case 'state':
            $success = 'Stav produktu byl změněn.';
            break;
    }
}

$form = array(
    'code' => '',
    'name' => '',
    'description' => '',
    'provider_product_id' => '',
    'provider_price_id' => '',
    'payment_type' => 'one_off',
    'amount' => '',
    'currency' => 'CZK',
    'billing_interval' => 'month',
    'billing_interval_count' => '1',
    'active' => '1',
    'sort_order' => '0',
);

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM billing_products WHERE id = :id LIMIT 1');
    $stmt->execute(array(':id' => $editId));
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editProduct) {
        $errors[] = 'Produkt nebyl nalezen.';
        $editId = 0;
    } else {
        $form = array(
            'code' => (string)$editProduct['code'],
            'name' => (string)$editProduct['name'],
            'description' => (string)($editProduct['description'] ?? ''),
            'provider_product_id' => (string)($editProduct['provider_product_id'] ?? ''),
            'provider_price_id' => (string)($editProduct['provider_price_id'] ?? ''),
            'payment_type' => (string)$editProduct['payment_type'],
            'amount' => bp_minor_to_amount(
                (int)$editProduct['amount_minor'],
                (string)$editProduct['currency']
            ),
            'currency' => strtoupper((string)$editProduct['currency']),
            'billing_interval' => (string)($editProduct['billing_interval'] ?: 'month'),
            'billing_interval_count' => (string)($editProduct['billing_interval_count'] ?: 1),
            'active' => (string)((int)$editProduct['active']),
            'sort_order' => (string)((int)$editProduct['sort_order']),
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!$csrf || !hash_equals((string)$_SESSION['csrf_billing_products'], $csrf)) {
        $errors[] = 'Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.';
    }

    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'toggle') {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0 || !bp_product_exists($pdo, $productId)) {
            $errors[] = 'Produkt nebyl nalezen.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'UPDATE billing_products
                 SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute(array(':id' => $productId));
            bp_redirect('state');
        }
    }

    if ($action === 'save') {
        $productId = (int)($_POST['product_id'] ?? 0);

        $form['code'] = trim((string)($_POST['code'] ?? ''));
        $form['name'] = trim((string)($_POST['name'] ?? ''));
        $form['description'] = trim((string)($_POST['description'] ?? ''));
        $form['provider_product_id'] = trim((string)($_POST['provider_product_id'] ?? ''));
        $form['provider_price_id'] = trim((string)($_POST['provider_price_id'] ?? ''));
        $form['payment_type'] = trim((string)($_POST['payment_type'] ?? 'one_off'));
        $form['amount'] = trim((string)($_POST['amount'] ?? ''));
        $form['currency'] = strtoupper(trim((string)($_POST['currency'] ?? 'CZK')));
        $form['billing_interval'] = trim((string)($_POST['billing_interval'] ?? 'month'));
        $form['billing_interval_count'] = trim((string)($_POST['billing_interval_count'] ?? '1'));
        $form['active'] = !empty($_POST['active']) ? '1' : '0';
        $form['sort_order'] = trim((string)($_POST['sort_order'] ?? '0'));

        if ($productId > 0 && !bp_product_exists($pdo, $productId)) {
            $errors[] = 'Upravovaný produkt nebyl nalezen.';
        }

        if ($form['code'] === '') {
            $errors[] = 'Zadejte interní kód produktu.';
        } elseif (!preg_match('/^[a-z0-9][a-z0-9_-]{1,99}$/', $form['code'])) {
            $errors[] = 'Kód produktu může obsahovat jen malá písmena, číslice, pomlčku a podtržítko.';
        }

        if ($form['name'] === '') {
            $errors[] = 'Zadejte název produktu.';
        }

        if (
            $form['provider_product_id'] === ''
            || strpos($form['provider_product_id'], 'prod_') !== 0
        ) {
            $errors[] = 'Stripe Product ID musí začínat prod_.';
        }

        if (
            $form['provider_price_id'] === ''
            || strpos($form['provider_price_id'], 'price_') !== 0
        ) {
            $errors[] = 'Stripe Price ID musí začínat price_.';
        }

        if (!in_array($form['payment_type'], array('one_off', 'subscription'), true)) {
            $errors[] = 'Neplatný typ platby.';
        }

        if (!preg_match('/^[A-Z]{3}$/', $form['currency'])) {
            $errors[] = 'Měna musí být třípísmenný ISO kód, například CZK.';
        }

        $amountMinor = 0;
        try {
            $amountMinor = bp_amount_to_minor($form['amount'], $form['currency']);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        $interval = null;
        $intervalCount = null;

        if ($form['payment_type'] === 'subscription') {
            if (!in_array($form['billing_interval'], array('day', 'week', 'month', 'year'), true)) {
                $errors[] = 'Neplatný interval předplatného.';
            }

            $intervalCount = (int)$form['billing_interval_count'];

            if ($intervalCount < 1 || $intervalCount > 36) {
                $errors[] = 'Počet intervalů musí být od 1 do 36.';
            }

            $interval = $form['billing_interval'];
        }

        $sortOrder = (int)$form['sort_order'];

        if (!$errors) {
            $sql = 'SELECT id FROM billing_products WHERE code = :code';
            $params = array(':code' => $form['code']);

            if ($productId > 0) {
                $sql .= ' AND id <> :id';
                $params[':id'] = $productId;
            }

            $sql .= ' LIMIT 1';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = 'Produkt s tímto interním kódem už existuje.';
            }
        }

        if (!$errors) {
            try {
                if ($productId > 0) {
                    /*
                     * CODE je při editaci záměrně neměnný:
                     * billing_payments a billing_subscriptions jej používají v historii.
                     */
                    $stmt = $pdo->prepare(
                        'UPDATE billing_products
                         SET name = :name,
                             description = :description,
                             provider = :provider,
                             provider_product_id = :provider_product_id,
                             provider_price_id = :provider_price_id,
                             payment_type = :payment_type,
                             amount_minor = :amount_minor,
                             currency = :currency,
                             billing_interval = :billing_interval,
                             billing_interval_count = :billing_interval_count,
                             active = :active,
                             sort_order = :sort_order,
                             updated_at = NOW()
                         WHERE id = :id'
                    );

                    $stmt->execute(array(
                        ':name' => $form['name'],
                        ':description' => $form['description'] !== '' ? $form['description'] : null,
                        ':provider' => 'stripe',
                        ':provider_product_id' => $form['provider_product_id'],
                        ':provider_price_id' => $form['provider_price_id'],
                        ':payment_type' => $form['payment_type'],
                        ':amount_minor' => $amountMinor,
                        ':currency' => $form['currency'],
                        ':billing_interval' => $interval,
                        ':billing_interval_count' => $intervalCount,
                        ':active' => (int)$form['active'],
                        ':sort_order' => $sortOrder,
                        ':id' => $productId,
                    ));

                    bp_redirect('updated');
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO billing_products (
                        code,
                        name,
                        description,
                        provider,
                        provider_product_id,
                        provider_price_id,
                        payment_type,
                        amount_minor,
                        currency,
                        billing_interval,
                        billing_interval_count,
                        active,
                        sort_order,
                        created_at,
                        updated_at
                    ) VALUES (
                        :code,
                        :name,
                        :description,
                        :provider,
                        :provider_product_id,
                        :provider_price_id,
                        :payment_type,
                        :amount_minor,
                        :currency,
                        :billing_interval,
                        :billing_interval_count,
                        :active,
                        :sort_order,
                        NOW(),
                        NOW()
                    )'
                );

                $stmt->execute(array(
                    ':code' => $form['code'],
                    ':name' => $form['name'],
                    ':description' => $form['description'] !== '' ? $form['description'] : null,
                    ':provider' => 'stripe',
                    ':provider_product_id' => $form['provider_product_id'],
                    ':provider_price_id' => $form['provider_price_id'],
                    ':payment_type' => $form['payment_type'],
                    ':amount_minor' => $amountMinor,
                    ':currency' => $form['currency'],
                    ':billing_interval' => $interval,
                    ':billing_interval_count' => $intervalCount,
                    ':active' => (int)$form['active'],
                    ':sort_order' => $sortOrder,
                ));

                bp_redirect('created');
            } catch (PDOException $e) {
                $errors[] = 'Produkt se nepodařilo uložit do databáze.';
                error_log('RAC Billing product admin SQL error: ' . $e->getMessage());
            }
        }

        if ($productId > 0) {
            $editId = $productId;
        }
    }
}

try {
    $products = $pdo->query(
        'SELECT *
         FROM billing_products
         ORDER BY sort_order ASC, id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $products = array();
    $errors[] = 'Produkty se nepodařilo načíst.';
    error_log('RAC Billing products load error: ' . $e->getMessage());
}

include __DIR__ . '/obsah/admin-produkty.class.php';