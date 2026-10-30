<?php
// RAC-MAIL-TEMPLATE-2026-10-25-03 / PHP 7.4+
declare(strict_types=1);
function racDeliveryMailDefaults(): array {
 return [
  'subject' => 'Digitální knihy k objednávce {order_number}',
  'body' => "Dobrý den,

děkujeme za objednávku č. {order_number}.

Zakoupené digitální soubory si stáhnete zde:

{download_links}Odkazy mají platnost {validity_days} dní od vytvoření. Nesdílejte je s dalšími osobami.

Děkujeme za váš nákup.

----------------------------------------

Hello,

thank you for your order no. {order_number}.

You can download your purchased digital files here:

{download_links}The download links are valid for {validity_days} days from their creation. Please do not share them with anyone else.

Thank you for your purchase.
"
 ];
}
function racDeliveryMailTemplate(PDO $db): array {
 $defaults=racDeliveryMailDefaults();
 // U neprovedene migrace zachovat puvodni fungujici e-mail.
 try {
  $st=$db->query('SELECT email_subject,email_body FROM shop_delivery_email_templates WHERE id=1 LIMIT 1');
  $row=$st->fetch(PDO::FETCH_ASSOC);
  if ($row && trim((string)$row['email_subject'])!=='' && trim((string)$row['email_body'])!=='') {
   return ['subject'=>(string)$row['email_subject'],'body'=>(string)$row['email_body']];
  }
 } catch (PDOException $e) {
  // Only missing table may fall back; other database errors must surface.
  if ($e->getCode() !== '42S02' && strpos($e->getMessage(),'1146')===false) throw $e;
 }
 return $defaults;
}
function racDeliveryMailRender(string $template,array $values): string {
 return strtr($template,[
  '{order_number}'=>(string)($values['order_number']??''),
  '{validity_days}'=>(string)($values['validity_days']??'30'),
  '{download_links}'=>(string)($values['download_links']??'')
 ]);
}

/** Generate customer-facing label from the actual purchased edition language, not a file name. */
function racDeliveryEditionTitles(PDO $db,array $fileIds): array {
 $fileIds=array_values(array_unique(array_filter(array_map('intval',$fileIds),static function($id){return $id>0;})));
 if (!$fileIds) return [];
 $holders=implode(',',array_fill(0,count($fileIds),'?'));
 $sql="SELECT f.id AS file_id, f.original_filename, v.language_code, p.slug,
   COALESCE(NULLIF(t_edition.title,''),NULLIF(t_cs.title,''),NULLIF(t_en.title,''),NULLIF(p.slug,''),'Kniha') AS book_title
  FROM product_files f
  JOIN product_variants v ON v.id=f.variant_id
  JOIN products p ON p.id=v.product_id
  LEFT JOIN languages l_edition ON l_edition.code=v.language_code
  LEFT JOIN product_translations t_edition ON t_edition.product_id=p.id AND t_edition.language_id=l_edition.id
  LEFT JOIN languages l_cs ON l_cs.code='cs'
  LEFT JOIN product_translations t_cs ON t_cs.product_id=p.id AND t_cs.language_id=l_cs.id
  LEFT JOIN languages l_en ON l_en.code='en'
  LEFT JOIN product_translations t_en ON t_en.product_id=p.id AND t_en.language_id=l_en.id
  WHERE f.id IN ($holders)";
 $st=$db->prepare($sql);$st->execute($fileIds);
 $labels=[];
 foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $name=trim(preg_replace('/[\r\n]+/u',' ',(string)$row['book_title']));
  $ext=strtoupper((string)pathinfo((string)$row['original_filename'],PATHINFO_EXTENSION));
  if ($ext==='') $ext='SOUBOR';
  $labels[(int)$row['file_id']]=$name.' ('.$ext.')';
 }
 return $labels;
}
