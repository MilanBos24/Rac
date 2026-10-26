<?php
// RAC | Read-only EN invoice PDF preview | 2026-10-25-EN-PREVIEW-01 | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_once __DIR__.'/../config/permissions.php';
require_min_role('admin');
$viewer = current_user_row();
if (!$viewer || strtolower((string)($viewer['role'] ?? '')) !== 'superadmin') {
    http_response_code(403);
    exit('Přístup odepřen.');
}
try {
    // Read a known existing TEST invoice; no INSERT/UPDATE/DELETE or external calls.
    $q=$pdo->prepare("SELECT * FROM fakturace_doklady WHERE order_id=30 AND cislo_dokladu='TEST-30' LIMIT 1");
    $q->execute();
    $doklad=$q->fetch(PDO::FETCH_ASSOC);
    if (!$doklad) throw new RuntimeException('TEST-30 neexistuje. Nejprve ověřte její existenci v seznamu faktur.');
    $q=$pdo->prepare('SELECT * FROM fakturace_polozky WHERE doklad_id=? ORDER BY poradi,id');
    $q->execute([(int)$doklad['id']]);
    $polozky=$q->fetchAll(PDO::FETCH_ASSOC);
    if (!$polozky) throw new RuntimeException('Doklad nemá položky.');
    // Synthetic foreign customer; original invoice remains untouched.
    $doklad['odberatel_stat']='DE';
    $doklad['odberatel_nazev']='Example Reader';
    $doklad['odberatel_firma']=null;
    $doklad['odberatel_ulice']='Example Street 12';
    $doklad['odberatel_mesto']='Berlin';
    $doklad['odberatel_psc']='10115';
    $doklad['odberatel_ico']=null;
    $doklad['odberatel_dic']=null;
    $doklad['odberatel_email']='reader@example.invalid';
    $racInvoicePreview=true;
    $autoload=__DIR__.'/../libs/dompdf/autoload.inc.php';
    $template=__DIR__.'/templates/faktura-pdf.php';
    if (!is_file($autoload)||!is_file($template)) throw new RuntimeException('Chybí knihovna Dompdf nebo šablona PDF.');
    require_once $autoload;
    ob_start();
    try { include $template; $html=(string)ob_get_clean(); }
    catch(Throwable $ex) { ob_end_clean();throw $ex; }
    $options=new \Dompdf\Options();
    $options->set('isRemoteEnabled',false);
    $options->set('isPhpEnabled',false);
    $pdf=new \Dompdf\Dompdf($options);
    $pdf->setPaper('A4');
    $pdf->loadHtml($html,'UTF-8');
    $pdf->render();
    $bytes=$pdf->output();
    if(!is_string($bytes)||strncmp($bytes,'%PDF-',5)!==0)throw new RuntimeException('Generování PDF selhalo.');
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="RAC-preview-invoice-EN.pdf"');
    header('Content-Length: '.strlen($bytes));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    echo $bytes;
} catch (Throwable $ex) {
    error_log('RAC EN invoice preview: '.$ex->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Náhled nelze vytvořit: '.$ex->getMessage();
}
