<?php
/**
 * RAC Stripe webhook – temporary network diagnostic endpoint.
 * Version: 2026-10-26-DIAG-01, PHP 7.4+
 * ONLY for a separate TEST/SANDBOX event destination.
 * Does not process orders, payments, digital deliveries or invoices.
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Record metadata only in the hosting PHP error log; do not store request body,
// event payload, payment details or Stripe signature.
$remote = (string)($_SERVER['REMOTE_ADDR'] ?? '-');
$rid = bin2hex(random_bytes(4));
error_log('RAC_STRIPE_NETWORK_DIAG request=' . $rid . ' time=' . gmdate('c') . ' ip=' . $remote . ' method=POST');
http_response_code(200);
echo 'RAC NETWORK DIAG OK ' . $rid;
