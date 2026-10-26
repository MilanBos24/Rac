<?php
// RAC FAKTURACE STEP05-01 | pristupne jen administratorum | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_once __DIR__.'/../config/permissions.php';
require_once __DIR__.'/inc/rac-faktury-core.php';
require_min_role('admin');
$viewer=current_user_row();$isSuper=$viewer&&strtolower((string)($viewer['role']??''))==='superadmin';
if(!$viewer||(!$isSuper&&!rac_can($pdo,$viewer,'orders.view'))){http_response_code(403);exit('Přístup odepřen.');}
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(400);exit('Neplatné ID faktury.');}
$s=$pdo->prepare('SELECT * FROM fakturace_doklady WHERE id=? LIMIT 1');$s->execute([(int)$id]);$doklad=$s->fetch(PDO::FETCH_ASSOC);
if(!$doklad){http_response_code(404);exit('Faktura nenalezena.');}
$s=$pdo->prepare('SELECT * FROM fakturace_polozky WHERE doklad_id=? ORDER BY poradi,id');$s->execute([(int)$id]);$polozky=$s->fetchAll(PDO::FETCH_ASSOC);
if(!$polozky){http_response_code(409);exit('Faktura nemá položky.');}
$autoload=__DIR__.'/../libs/dompdf/autoload.inc.php';
if(!is_file($autoload)){http_response_code(503);exit('Chybí knihovna Dompdf.');}
require_once $autoload;
ob_start();
include __DIR__.'/templates/faktura-pdf.php';
$html=(string)ob_get_clean();
$options=new \Dompdf\Options();$options->set('isRemoteEnabled',false);$options->set('isPhpEnabled',false);
$dompdf=new \Dompdf\Dompdf($options);$dompdf->setPaper('A4');$dompdf->loadHtml($html,'UTF-8');$dompdf->render();
$download=(string)($_GET['download']??'')==='1';
$filename=preg_replace('/[^A-Za-z0-9_-]/','',(string)$doklad['cislo_dokladu']);
$dompdf->stream($filename.'.pdf',['Attachment'=>$download?1:0]);
