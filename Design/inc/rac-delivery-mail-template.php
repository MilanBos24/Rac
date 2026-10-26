<?php
// RAC-MAIL-TEMPLATE-2026-10-25-01 / PHP 7.4+
declare(strict_types=1);
function racDeliveryMailDefaults(): array {
 return [
  'subject' => 'Digitální knihy k objednávce {order_number}',
  'body' => "Dobrý den,\n\nděkujeme za objednávku {order_number}. Zakoupené digitální soubory můžete stáhnout z následujících odkazů (platnost {validity_days} dnů):\n\n{download_links}\nOdkazy nepřeposílejte třetím osobám. V případě potíží kontaktujte podporu.\n"
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
