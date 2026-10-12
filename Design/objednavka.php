<?php
// RAC checkout – 2026-10-09-01 (PHP 7.4)
require_once __DIR__.'/inc/01.config.class.php';
require_once __DIR__.'/inc/02.language.class.php';
require_once __DIR__.'/inc/rac-products.php';
require_once __DIR__.'/inc/rac-cart.php';
require_once __DIR__.'/inc/rac-checkout.php';
$racPdo=racFrontendPdo(); $racCheckoutErrors=array(); $racCheckoutSuccess=null;
$racCheckoutLines=array();$racCheckoutMethods=array();$racCheckoutCurrency=null;$racCheckoutPhysical=false;$racCheckoutCountries=array();
try {
 if (!$racPdo instanceof PDO) throw new RuntimeException('Databáze není dostupná.');
 $racCheckoutLines=racCartLines($racPdo,$currentLanguage);
 if (!$racCheckoutLines) throw new RuntimeException('Košík je prázdný.');
 foreach ($racCheckoutLines as $line) {
   if ($line['variant']['kind']==='print')$racCheckoutPhysical=true;
   $currency=(string)$line['variant']['currency'];
   if ($racCheckoutCurrency!==null && $racCheckoutCurrency!==$currency)throw new RuntimeException('V košíku jsou různé měny.');
   $racCheckoutCurrency=$currency;
 }
 $racCheckoutCurrency=$racCheckoutCurrency??'CZK';
 $racCheckoutCountries=$racCheckoutPhysical?racCheckoutAvailableCountries($racPdo,$racCheckoutCurrency):array();
 $country=strtoupper((string)($_POST['shipping_country']??$_GET['country']??'CZ'));
 if ($racCheckoutPhysical && !in_array($country,$racCheckoutCountries,true))$country=$racCheckoutCountries[0]??'CZ';
 $racCheckoutMethods=$racCheckoutPhysical?racCheckoutShippingMethods($racPdo,$currentLanguage,$country,$racCheckoutCurrency):array();
 if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
  try { $racCheckoutSuccess=racCheckoutSubmit($racPdo,$racCheckoutLines,$currentLanguage,$racCheckoutCurrency,$racCheckoutPhysical); }
  catch(Throwable $e){error_log('RAC checkout: '.$e->getMessage());$racCheckoutErrors[]=$e instanceof InvalidArgumentException?$e->getMessage():'Objednávku se nepodařilo uložit. Ověřte údaje a databázové tabulky.';}
 }
} catch(Throwable $e){$racCheckoutErrors[]= $e->getMessage();}
include __DIR__.'/inc/03.head.class.php'; include __DIR__.'/inc/04.header.class.php';
include __DIR__.'/obsah/objednavka.class.php';
include __DIR__.'/inc/05.footer.class.php';include __DIR__.'/inc/06.mobile-nav.class.php';include __DIR__.'/inc/07.scripts.class.php';
