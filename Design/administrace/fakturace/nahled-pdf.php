<?php
// RAC FAKTURACE STEP05-01 | PDF nahled BEZ vystaveni faktury | PHP 7.4+
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
$id=filter_var($_GET['order_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(400);exit('Neplatná objednávka.');}
try{$bundle=racFakturaOrder($pdo,(int)$id);}catch(Throwable $e){http_response_code(409);exit(racFakturaH($e->getMessage()));}
$o=$bundle['order'];$c=$bundle['config'];$pay=$bundle['payment'];
$invoiceDate=date('Y-m-d');$paidDate=substr((string)($pay['updated_at']??''),0,10);
$doklad=[
 'cislo_dokladu'=>'NÁHLED','order_number'=>$o['order_number'],
 'dodavatel_nazev'=>$c['nazev_dodavatele'],'dodavatel_ulice'=>$c['ulice'],'dodavatel_psc'=>$c['psc'],
 'dodavatel_mesto'=>$c['mesto'],'dodavatel_stat'=>$c['stat'],'dodavatel_ico'=>$c['ico'],
 'dodavatel_dic'=>$c['dic'],'dodavatel_email'=>$c['email'],
 'odberatel_nazev'=>$o['billing_name'],'odberatel_firma'=>$o['billing_company']??'',
 'odberatel_ulice'=>$o['billing_street']??'','odberatel_psc'=>$o['billing_postal']??'',
 'odberatel_mesto'=>$o['billing_city']??'','odberatel_stat'=>$o['billing_country']??'',
 'odberatel_ico'=>$o['billing_company_id']??'', 'odberatel_dic'=>$o['billing_vat_id']??'',
 'odberatel_email'=>$o['email'],'datum_vystaveni'=>$invoiceDate,
 'datum_zaplaceni'=>preg_match('/^\d{4}-\d{2}-\d{2}$/D',$paidDate)?$paidDate:$invoiceDate,
 'dodavatel_platce_dph'=>(int)$c['platce_dph'],'castka_zaplaceno'=>$o['total']
];
$polozky=[];foreach($bundle['items'] as $item){$polozky[]=['nazev'=>$item['title'],'popis'=>$item['variant_kind'].' · '.$item['variant_language_code'], 'mnozstvi'=>$item['quantity'],'cena_za_jednotku'=>$item['unit_price'],'castka_celkem'=>$item['line_total']];}
if((float)($o['shipping_price']??0)>0)$polozky[]=['nazev'=>'Doprava','popis'=>$o['shipping_method_name']??'','mnozstvi'=>1,'cena_za_jednotku'=>$o['shipping_price'],'castka_celkem'=>$o['shipping_price']];
$autoload=__DIR__.'/../libs/dompdf/autoload.inc.php';if(!is_file($autoload)){http_response_code(503);exit('Chybí Dompdf.');}require_once $autoload;
$racInvoicePreview=true;ob_start();include __DIR__.'/templates/faktura-pdf.php';$html=ob_get_clean();
$options=new \Dompdf\Options();$options->set('isRemoteEnabled',false);$options->set('isPhpEnabled',false);
$pdf=new \Dompdf\Dompdf($options);$pdf->setPaper('A4');$pdf->loadHtml((string)$html,'UTF-8');$pdf->render();$pdf->stream('nahled-objednavky-'.$id.'.pdf',['Attachment'=>0]);
