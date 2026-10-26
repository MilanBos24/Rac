<?php
// RAC invoice attachment renderer | 2026-10-25-STEP08-01 | PHP 7.4+
declare(strict_types=1);
/** @return array{filename:string,data:string}|null */
function racFakturaPdfAttachment(PDO $db,int $orderId): ?array {
 if($orderId<=0) return null;
 $s=$db->prepare("SELECT * FROM fakturace_doklady WHERE order_id=? AND typ='faktura' AND stav='zaplaceno' LIMIT 1");
 $s->execute([$orderId]);$doklad=$s->fetch(PDO::FETCH_ASSOC);
 if(!$doklad)return null; // Not issued / ineligible order. TEST-* records are permitted and watermarked.
 $s=$db->prepare('SELECT * FROM fakturace_polozky WHERE doklad_id=? ORDER BY poradi,id');
 $s->execute([(int)$doklad['id']]);$polozky=$s->fetchAll(PDO::FETCH_ASSOC);
 if(!$polozky)throw new RuntimeException('Faktura neobsahuje položky.');
 $autoload=__DIR__.'/../../libs/dompdf/autoload.inc.php';
 $template=__DIR__.'/../templates/faktura-pdf.php';
 if(!is_file($autoload)||!is_file($template))throw new RuntimeException('Chybí knihovna Dompdf nebo šablona faktury.');
 require_once $autoload;
 $racInvoicePreview=false;
 ob_start();
 try { include $template; $html=(string)ob_get_clean(); }
 catch(Throwable $e){ob_end_clean();throw $e;}
 $options=new \Dompdf\Options();
 $options->set('isRemoteEnabled',false);$options->set('isPhpEnabled',false);
 $pdf=new \Dompdf\Dompdf($options);
 $pdf->setPaper('A4');$pdf->loadHtml($html,'UTF-8');$pdf->render();
 $bytes=$pdf->output();
 if(!is_string($bytes)||strncmp($bytes,'%PDF-',5)!==0||strlen($bytes)>7*1024*1024)throw new RuntimeException('Generování PDF selhalo nebo je PDF příliš velké.');
 $name=preg_replace('/[^A-Za-z0-9_-]/','',(string)$doklad['cislo_dokladu']);
 if($name==='')$name='faktura-'.(int)$doklad['id'];
 return ['filename'=>$name.'.pdf','data'=>$bytes];
}
