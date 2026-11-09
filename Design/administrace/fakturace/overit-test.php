<?php
// RAC one-off read-only diagnostics: delete after test, only admin. PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_once __DIR__.'/../config/permissions.php';
require_min_role('admin');
$viewer=current_user_row();
if(!$viewer || (strtolower((string)($viewer['role']??''))!=='superadmin' && !rac_can($pdo,$viewer,'orders.view'))){http_response_code(403);exit('Přístup odepřen.');}
header('Content-Type: text/plain; charset=UTF-8');
require_once __DIR__.'/inc/rac-faktury-core.php';
require_once __DIR__.'/inc/rac-faktury-auto.php';
echo 'AUTO: '.(RAC_FAKTURY_AUTO_ENABLED?'ON':'OFF')."\n";
echo 'TEST mode: '.(is_file(__DIR__.'/inc/rac-faktury-attachment.php')?'attachment renderer found':'attachment renderer MISSING')."\n";
echo 'Webhook invoicing call: '.(strpos((string)file_get_contents(__DIR__.'/../../platebni-webhook.php'),'racFakturaAutoOnPayment(')!==false?'YES':'NO')."\n";
echo 'PDF Dompdf: '.(is_file(__DIR__.'/../libs/dompdf/autoload.inc.php')?'YES':'NO')."\n";
echo 'PDF template: '.(is_file(__DIR__.'/templates/faktura-pdf.php')?'YES':'NO')."\n";
echo 'Mail attachment support: '.(strpos((string)@file_get_contents(__DIR__.'/../inc/rac-mail.php'),'racFakturaPdfAttachment(')!==false?'YES':'NO')."\n";
