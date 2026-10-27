<?php
// RAC checkout helpers, PHP 7.4

// RAC souhlas s okamžitým dodáním digitálního obsahu / 2026-10-20-14.04
function racCheckoutDigitalConsentVersion(): string { return '2026-10-20-v1'; }
function racCheckoutDigitalConsentText(string $lang): string {
 $texts = array(
  'cs'=>'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.',
  'en'=>'I expressly agree to access the purchased digital content before the end of the 14-day withdrawal period. I acknowledge that, once access to the digital content begins under these conditions, I lose my right to withdraw within that 14-day period.',
  'de'=>'Ich stimme ausdrücklich zu, dass mir die erworbenen digitalen Inhalte vor Ablauf der 14-tägigen Widerrufsfrist bereitgestellt werden. Mir ist bekannt, dass ich mit Beginn der Bereitstellung unter diesen Voraussetzungen mein Widerrufsrecht innerhalb dieser Frist verliere.',
  'pl'=>'Wyraźnie zgadzam się na udostępnienie zakupionych treści cyfrowych przed upływem 14 dni od zawarcia umowy. Przyjmuję do wiadomości, że z chwilą rozpoczęcia ich udostępniania na tych warunkach tracę prawo do odstąpienia od umowy w terminie 14 dni.',
  'fr'=>'Je consens expressément à recevoir le contenu numérique acheté avant la fin du délai de rétractation de 14 jours. Je reconnais qu’en cas de début de fourniture dans ces conditions, je perds mon droit de rétractation pendant ce délai.',
);
 return $texts[$lang] ?? $texts['cs'];
}
function racCheckoutDigitalConsentError(string $lang): string {
 $texts = array(
  'cs'=>'Pro objednávku digitálního obsahu potvrďte souhlas s jeho okamžitým zpřístupněním.',
  'en'=>'Please confirm your consent to immediate access to digital content.',
  'de'=>'Bitte bestätigen Sie Ihre Zustimmung zur sofortigen Bereitstellung digitaler Inhalte.',
  'pl'=>'Potwierdź zgodę na natychmiastowe udostępnienie treści cyfrowych.',
  'fr'=>'Veuillez confirmer votre consentement à la fourniture immédiate du contenu numérique.',
);
 return $texts[$lang] ?? $texts['cs'];
}
function racCheckoutDigitalConsentHeading(string $lang): string {
 $texts = array(
  'cs'=>'Povinné potvrzení pro e-knihy a audioknihy',
  'en'=>'Required confirmation for e-books and audiobooks',
  'de'=>'Erforderliche Bestätigung für E-Books und Hörbücher',
  'pl'=>'Wymagane potwierdzenie dla e-booków i audiobooków',
  'fr'=>'Confirmation requise pour les livres numériques et les livres audio',
);
 return $texts[$lang] ?? $texts['cs'];
}
function racCheckoutHasDigital(array $lines): bool {
 foreach ($lines as $line) if (($line['variant']['kind'] ?? '') !== 'print') return true;
 return false;
}

function racCheckoutAvailableCountries(PDO $pdo,string $currency): array {
 $st=$pdo->prepare("SELECT DISTINCT r.country_code FROM shipping_rates r JOIN shipping_methods m ON m.id=r.method_id WHERE m.active=1 AND r.active=1 AND r.currency=? ORDER BY r.country_code");
 $st->execute(array($currency));return $st->fetchAll(PDO::FETCH_COLUMN);
}
function racCheckoutShippingMethods(PDO $pdo,string $lang,string $country,string $currency): array {
 $ids=racShopLanguageIds($pdo,$lang);
 $st=$pdo->prepare("SELECT m.id,m.type,m.max_weight_grams,m.code,r.price,r.free_from, COALESCE(NULLIF(t1.name,''),NULLIF(t2.name,''),NULLIF(t3.name,''),m.code) AS name FROM shipping_methods m JOIN shipping_rates r ON r.method_id=m.id AND r.country_code=? AND r.currency=? AND r.active=1 LEFT JOIN shipping_method_translations t1 ON t1.method_id=m.id AND t1.language_id=? LEFT JOIN shipping_method_translations t2 ON t2.method_id=m.id AND t2.language_id=? LEFT JOIN shipping_method_translations t3 ON t3.method_id=m.id AND t3.language_id=? WHERE m.active=1 ORDER BY m.sort_order,m.id");
 $st->execute(array($country,$currency,$ids[0],$ids[1],$ids[2]));return $st->fetchAll(PDO::FETCH_ASSOC);
}
function racCheckoutField(array $post,string $name,int $max=190,bool $required=true): string {
 $value=trim((string)($post[$name]??''));
 if (($required && $value==='') || mb_strlen($value)>$max) {
  $labels=array('first_name'=>'checkout.first_name','last_name'=>'checkout.last_name','email'=>'checkout.email','phone'=>'contact.phone','shipping_name'=>'checkout.recipient_name','shipping_street'=>'checkout.street','shipping_city'=>'checkout.city','shipping_postal'=>'checkout.postal_code','shipping_country'=>'checkout.shipping_country','billing_name'=>'checkout.billing_name','billing_street'=>'checkout.street','billing_city'=>'checkout.city','billing_postal'=>'checkout.postal_code','billing_country'=>'checkout.billing_country','pickup_point'=>'checkout.pickup_location','billing_company'=>'checkout.company_name','billing_company_id'=>'checkout.company_id','billing_vat_id'=>'checkout.vat_id','note'=>'checkout.note');
  $key=$labels[$name]??'';
  $label=$key!==''?__($key):__('checkout.field_generic');
  throw new InvalidArgumentException(__('checkout.error_field',array('field'=>$label)));
 }
 return $value;
}
function racCheckoutAddress(array $post,string $prefix,bool $required): array {
 $name=racCheckoutField($post,$prefix.'_name',190,$required);
 $street=racCheckoutField($post,$prefix.'_street',190,$required);
 $city=racCheckoutField($post,$prefix.'_city',120,$required);
 $postal=racCheckoutField($post,$prefix.'_postal',30,$required);
 $country=strtoupper(racCheckoutField($post,$prefix.'_country',2,$required));
 if ($required && !preg_match('/^[A-Z]{2}$/D',$country))throw new InvalidArgumentException('Neplatná země adresy.');
 return compact('name','street','city','postal','country');
}
function racCheckoutSubmit(PDO $pdo,array $lines,string $language,string $currency,bool $physical): string {
 if (!racCartCheckToken($_POST['csrf']??null))throw new InvalidArgumentException('Platnost formuláře vypršela. Obnovte stránku.');
 $first=racCheckoutField($_POST,'first_name',100);$last=racCheckoutField($_POST,'last_name',100);
 $email=racCheckoutField($_POST,'email',190);if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Neplatný e-mail.');
 $phone=racCheckoutField($_POST,'phone',60);
 $same=isset($_POST['billing_same'])&&$_POST['billing_same']==='1';
 if ($physical) {
  $delivery=racCheckoutAddress($_POST,'shipping',true);
  if (!in_array($delivery['country'],racCheckoutAvailableCountries($pdo,$currency),true))throw new InvalidArgumentException('Pro danou zemi není dostupná doprava.');
 } else $delivery=array('name'=>$first.' '.$last,'street'=>'','city'=>'','postal'=>'','country'=>'');
 if ($same && $physical) $billing=$delivery;
 else $billing=racCheckoutAddress($_POST,'billing',true);
 $company=racCheckoutField($_POST,'billing_company',190,false);
 $companyId=racCheckoutField($_POST,'billing_company_id',50,false);
 $vatId=racCheckoutField($_POST,'billing_vat_id',50,false);
 $note=racCheckoutField($_POST,'note',2000,false);
 $sumCents=0;$containsPrint=false;
 foreach($lines as $line){
  if((string)$line['variant']['currency']!==$currency)throw new InvalidArgumentException('Různé měny v košíku nejsou podporovány.');
  $sumCents+=(int)round($line['price']*100)*(int)$line['quantity'];
  if($line['variant']['kind']==='print')$containsPrint=true;
 }
 if($containsPrint!==$physical)throw new InvalidArgumentException('Obsah košíku se změnil.');
  $hasDigital=racCheckoutHasDigital($lines);
  if($hasDigital && (string)($_POST['digital_immediate_consent']??'')!=='1')throw new InvalidArgumentException(racCheckoutDigitalConsentError($language));
  if ((string)($_POST['terms_accepted']??'')!=='1') throw new InvalidArgumentException(__('checkout.terms_required'));
 $methodId=null;$methodName=null;$type=null;$pickup=null;$shippingCents=0;
 if($physical){
  $chosen=(int)($_POST['shipping_method_id']??0);$found=null;
  foreach(racCheckoutShippingMethods($pdo,$language,$delivery['country'],$currency) as $m)if((int)$m['id']===$chosen)$found=$m;
  if(!$found)throw new InvalidArgumentException('Vyberte dostupný způsob dopravy.');
  $methodId=(int)$found['id'];$methodName=$found['name'];$type=$found['type'];
  if($type==='pickup_point')$pickup=racCheckoutField($_POST,'pickup_point',255,true);
  $free=$found['free_from'];$shippingCents=($free!==null && $sumCents>=(int)round((float)$free*100))?0:(int)round((float)$found['price']*100);
 }
 $subtotal=number_format($sumCents/100,2,'.','');$shipping=number_format($shippingCents/100,2,'.','');$total=number_format(($sumCents+$shippingCents)/100,2,'.','');
 try{
  $pdo->beginTransaction();
  // Aktualizujeme cenu a sklad pod zamkem: zabráníme přijetí neplatného množství.
  $itemData=array();
  foreach($lines as $line){
   $id=(int)$line['variant']['id'];
   $st=$pdo->prepare("SELECT v.*,p.slug FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=? AND v.active=1 AND p.status='active' FOR UPDATE");
   $st->execute(array($id));$v=$st->fetch(PDO::FETCH_ASSOC);
   if(!$v || (string)$v['currency']!==$currency)throw new InvalidArgumentException('Produkt se změnil. Obnovte košík.');
   if($v['kind']==='print' && $v['stock_quantity']!==null && (int)$v['stock_quantity']<(int)$line['quantity'])throw new InvalidArgumentException('Požadovaný počet tištěných knih není skladem.');
   if((int)round((float)racShopPrice($v)*100)!==(int)round($line['price']*100))throw new InvalidArgumentException('Cena produktu se změnila. Obnovte košík.');
    if ($v['kind']!=='print' && !$hasDigital) throw new InvalidArgumentException('Obsah košíku se změnil.');
   $itemData[]=array($v,$line);
  }
  if($physical){
   $rateCheck=$pdo->prepare('SELECT m.active AS method_active,r.active AS rate_active,r.price,r.free_from FROM shipping_methods m JOIN shipping_rates r ON r.method_id=m.id WHERE m.id=? AND r.country_code=? AND r.currency=? FOR UPDATE');
   $rateCheck->execute(array($methodId,$delivery['country'],$currency));$actual=$rateCheck->fetch(PDO::FETCH_ASSOC);
   if(!$actual || !(int)$actual['method_active'] || !(int)$actual['rate_active'])throw new InvalidArgumentException('Zvolená doprava již není dostupná.');
   $shippingCents=($actual['free_from']!==null && $sumCents>=(int)round((float)$actual['free_from']*100))?0:(int)round((float)$actual['price']*100);
   $shipping=number_format($shippingCents/100,2,'.','');$total=number_format(($sumCents+$shippingCents)/100,2,'.','');
  }
  $orderNo='RAC-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(5)));
  $stmt=$pdo->prepare('INSERT INTO shop_orders (order_number,email,phone,customer_first_name,customer_last_name,shipping_country,shipping_street,shipping_city,shipping_postal,billing_same_as_shipping,billing_name,billing_street,billing_city,billing_postal,billing_country,billing_company,billing_company_id,billing_vat_id,shipping_method_id,shipping_method_name,shipping_type,pickup_point,shipping_price,subtotal,total,currency,customer_note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $stmt->execute(array($orderNo,$email,$phone,$first,$last,$delivery['country']?:null,$delivery['street']?:null,$delivery['city']?:null,$delivery['postal']?:null,$same?1:0,$billing['name'],$billing['street'],$billing['city'],$billing['postal'],$billing['country'],$company?:null,$companyId?:null,$vatId?:null,$methodId,$methodName,$type,$pickup,$shipping,$subtotal,$total,$currency,$note?:null));
  $orderId=(int)$pdo->lastInsertId();
  $add=$pdo->prepare('INSERT INTO shop_order_items (order_id,product_id,variant_id,title,variant_kind,variant_language_code,quantity,unit_price,line_total,currency) VALUES (?,?,?,?,?,?,?,?,?,?)');
  foreach($itemData as $item){$v=$item[0];$l=$item[1];$add->execute(array($orderId,(int)$v['product_id'],(int)$v['id'],(string)$l['product']['title'],(string)$v['kind'],$v['language_code'],(int)$l['quantity'],number_format((float)$l['price'],2,'.',''),number_format((float)$l['total'],2,'.',''),$currency));}
   if ($hasDigital) {
    $consentSql=$pdo->prepare('INSERT INTO shop_order_digital_consents (order_id,consent_version,consent_language,consent_text,consent_given,consented_at) VALUES (?,?,?,?,1,NOW())');
    $consentSql->execute(array($orderId,racCheckoutDigitalConsentVersion(),in_array($language,array('cs','en','de','pl','fr'),true)?$language:'cs',racCheckoutDigitalConsentText($language)));
   }
  $pdo->commit();unset($_SESSION['rac_cart']);return $orderNo;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
