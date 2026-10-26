<?php
// RAC Fakturace | aktivace AUTO | 2026-10-25-TEST-END2END-01 | PHP 7.4+
declare(strict_types=1);

// Vypnuti bez zasahu do webhooku: zmenit true na false.
if (!defined('RAC_FAKTURY_AUTO_ENABLED')) {
    define('RAC_FAKTURY_AUTO_ENABLED', true);
}

/**
 * Called by the existing, verified RAC Stripe shop webhook after commit.
 * Invoice core permits verified LIVE and Stripe TEST payments in CZK billed in CZ
 * where the supplier is marked non-VAT; it handles duplicates atomically.
 * Do not call this function from checkout forms or unverified callbacks.
 */
function racFakturaAutoOnPayment(PDO $pdo, int $orderId): void
{
    if (!RAC_FAKTURY_AUTO_ENABLED || $orderId <= 0) {
        return;
    }

    $service = __DIR__ . '/rac-faktury-core.php';
    if (!is_file($service)) {
        throw new RuntimeException('Fakturace: chybi rac-faktury-core.php');
    }
    require_once $service;
    racFakturaIssue($pdo, $orderId);
}
