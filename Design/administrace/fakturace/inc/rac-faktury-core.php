<?php
// RAC FAKTURACE STEP05-01 | 2026-10-25 | PHP 7.4+
declare(strict_types=1);

function racFakturaH($x): string { return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function racFakturaMoney($x, string $currency='CZK'): string { return number_format((float)$x, 2, ',', ' ') . ' ' . $currency; }
function racFakturaOrder(PDO $pdo, int $id, bool $lock=false): array {
    $s=$pdo->prepare('SELECT * FROM shop_orders WHERE id=? LIMIT 1'.($lock?' FOR UPDATE':''));
    $s->execute([$id]); $order=$s->fetch(PDO::FETCH_ASSOC);
    if (!$order) throw new RuntimeException('Objednávka nebyla nalezena.');
    $s=$pdo->prepare('SELECT id,title,variant_kind,variant_language_code,quantity,unit_price,line_total,currency FROM shop_order_items WHERE order_id=? ORDER BY id');
    $s->execute([$id]);$items=$s->fetchAll(PDO::FETCH_ASSOC);
    if (!$items) throw new RuntimeException('Objednávka neobsahuje položky.');
    $s=$pdo->prepare("SELECT COUNT(*) FROM shop_payments WHERE order_id=? AND status='paid'");
    $s->execute([$id]);
    if ((int)$s->fetchColumn()<1) throw new RuntimeException('Fakturu lze vystavit pouze k potvrzené platbě.');
    $s=$pdo->prepare("SELECT payment_intent_id,amount_minor,currency,environment,updated_at FROM shop_payments WHERE order_id=? AND status='paid' ORDER BY id DESC LIMIT 1");
    $s->execute([$id]);$payment=$s->fetch(PDO::FETCH_ASSOC);
    if (!$payment) throw new RuntimeException('Chybí ověřená platba.');
    $config=$pdo->query('SELECT * FROM fakturace_nastaveni WHERE id=1')->fetch(PDO::FETCH_ASSOC);
    if (!$config || trim((string)$config['nazev_dodavatele'])==='') throw new RuntimeException('Nejdříve vyplňte nastavení fakturace.');
    return ['order'=>$order,'items'=>$items,'payment'=>$payment,'config'=>$config];
}
/** Supported billing countries for domestic and EU consumer orders (ISO 3166-1 alpha-2). */
function racFakturaSupportedEuCountry(string $country): bool {
    static $eu = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'];
    return in_array(strtoupper(trim($country)), $eu, true);
}

function racFakturaCheckForIssue(array $bundle): void {
    $order=$bundle['order'];$payment=$bundle['payment'];$config=$bundle['config'];$items=$bundle['items'];
    // B2C pilot: cesky neplatce DPH, CZK settlement, CR a dalsi clenske staty EU.
    // Prodejce musi sam sledovat preshranicni limit a pripadne zmeny rezimu DPH.
    if (!in_array((string)($payment['environment']??''), ['live','test'], true)) throw new RuntimeException('Neznámý režim Stripe platby.');
    if ((int)$config['platce_dph']!==0) throw new RuntimeException('Automatická fakturace je zatím povolena jen pro neplátce DPH. U plátce je nutné doplnit správné sazby pro jednotlivé položky.');
    if (strtoupper((string)$order['currency'])!=='CZK' || strtoupper((string)$payment['currency'])!=='CZK') throw new RuntimeException('Automatická fakturace je zatím dostupná pouze pro objednávky a platby v CZK.');
    $country=strtoupper(trim((string)($order['billing_country']??'')));
    if (!racFakturaSupportedEuCountry($country)) throw new RuntimeException('Automatická fakturace je zatím určena pro odběratele z ČR a EU.');
    if (trim((string)($order['email']??''))==='') throw new RuntimeException('Chybí e-mail odběratele.');
    if (trim((string)($order['billing_name']??''))==='') throw new RuntimeException('Chybí fakturační jméno.');
    $sum=0;
    foreach ($items as $item) {
        if (strtoupper((string)$item['currency'])!=='CZK') throw new RuntimeException('Položky nejsou všechny v CZK.');
        if ((float)$item['quantity']<=0 || (float)$item['line_total']<0) throw new RuntimeException('Neplatné hodnoty položek objednávky.');
        $sum+=(int)round((float)$item['line_total']*100);
    }
    $shipping=(int)round((float)($order['shipping_price']??0)*100);
    $total=(int)round((float)($order['total']??0)*100);
    if ($sum+$shipping!==$total) throw new RuntimeException('Součet položek a dopravy neodpovídá celkové částce objednávky.');
    if ((int)$payment['amount_minor']!==$total) throw new RuntimeException('Uhrazená částka se neshoduje s objednávkou.');
    if ($total<1) throw new RuntimeException('Nulovou objednávku nelze tímto způsobem fakturovat.');
}
function racFakturaIssue(PDO $pdo,int $orderId): int {
    $pdo->beginTransaction();
    try {
        // Nastaveni zamknout jako prvni: serializuje ciselnou radu.
        $settings=$pdo->query('SELECT * FROM fakturace_nastaveni WHERE id=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
        if (!$settings) throw new RuntimeException('Nejsou uložené fakturační údaje.');
        $s=$pdo->prepare('SELECT id FROM fakturace_doklady WHERE order_id=? LIMIT 1');$s->execute([$orderId]);$existing=$s->fetchColumn();
        if ($existing) { $pdo->commit(); return (int)$existing; }
        $bundle=racFakturaOrder($pdo,$orderId,true);
        racFakturaCheckForIssue($bundle);
        $o=$bundle['order'];$pay=$bundle['payment'];$items=$bundle['items'];
        $year=(int)$settings['rada_faktura_rok'];$number=(int)$settings['rada_faktura_dalsi_cislo'];
        if($year<2020 || $number<1 || $number>99999999) throw new RuntimeException('Neplatná číselná řada.');
        $isTest=((string)$pay['environment']==='test');
        // TEST has its own deterministic id-based sequence; never consumes real invoice numbering.
        $code=$isTest ? ('TEST-'.$orderId) : ((string)$settings['rada_faktura_prefix'].$year.str_pad((string)$number,4,'0',STR_PAD_LEFT));
        $invoiceDate=(new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('Y-m-d');
        $paidDate=substr((string)($pay['updated_at']??''),0,10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$paidDate)) $paidDate=$invoiceDate;
        $customer=(string)($o['billing_name']??'');$company=(string)($o['billing_company']??'');
        $snapshot=[
            'order_id'=>$orderId,'cislo_dokladu'=>$code,'typ'=>'faktura','stav'=>'zaplaceno',
            'datum_vystaveni'=>$invoiceDate,'datum_zaplaceni'=>$paidDate,'datum_zdanitelneho_plneni'=>null,
            'mena'=>'CZK','zpusob_uhrady'=>'stripe','stripe_payment_id'=>(string)($pay['payment_intent_id']??''),
            'order_number'=>(string)$o['order_number'],
            'dodavatel_nazev'=>(string)$settings['nazev_dodavatele'],
            'dodavatel_ico'=>$settings['ico'],'dodavatel_dic'=>$settings['dic'],'dodavatel_ulice'=>$settings['ulice'],
            'dodavatel_mesto'=>$settings['mesto'],'dodavatel_psc'=>$settings['psc'],'dodavatel_stat'=>$settings['stat'],
            'dodavatel_email'=>$settings['email'],'dodavatel_telefon'=>$settings['telefon'],'dodavatel_web'=>$settings['web'],
            'dodavatel_cislo_uctu'=>$settings['cislo_uctu'],'dodavatel_iban'=>$settings['iban'],'dodavatel_bic'=>$settings['bic'],
            'dodavatel_nazev_uctu'=>$settings['nazev_uctu'],'dodavatel_typ_uctu'=>$settings['typ_uctu'],
            'dodavatel_kod_banky'=>$settings['kod_banky'],'dodavatel_mena_uctu'=>$settings['mena_uctu'],
            'dodavatel_platce_dph'=>0,
            'odberatel_nazev'=>$customer,'odberatel_firma'=>$company?:null,'odberatel_ico'=>$o['billing_company_id']??null,
            'odberatel_dic'=>$o['billing_vat_id']??null,'odberatel_ulice'=>$o['billing_street']??null,
            'odberatel_mesto'=>$o['billing_city']??null,'odberatel_psc'=>$o['billing_postal']??null,
            'odberatel_stat'=>$o['billing_country']??null,'odberatel_email'=>(string)$o['email'],
            'castka_bez_dph'=>(string)$o['total'],'castka_dph'=>'0.00','castka_celkem'=>(string)$o['total'],
            'castka_zaplaceno'=>(string)$o['total'],'poznamka'=>$isTest ? 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.' : 'Uhrazeno online prostřednictvím Stripe.'
        ];
        $cols=array_keys($snapshot);
        $stmt=$pdo->prepare('INSERT INTO fakturace_doklady (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')');
        $stmt->execute(array_values($snapshot));$invoiceId=(int)$pdo->lastInsertId();
        $line=$pdo->prepare('INSERT INTO fakturace_polozky (doklad_id,nazev,popis,mnozstvi,jednotka,cena_za_jednotku,sleva_procent,sazba_dph,castka_bez_dph,castka_dph,castka_celkem,poradi) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach($items as $idx=>$it){
            $name=(string)$it['title'];$kind=(string)$it['variant_kind'];$lang=(string)$it['variant_language_code'];
            $description=trim($kind.' · '.$lang,' ·');
            $line->execute([$invoiceId,$name,$description,(string)$it['quantity'],'ks',(string)$it['unit_price'],'0.00','0.00',(string)$it['line_total'],'0.00',(string)$it['line_total'],$idx+1]);
        }
        $shipping=(float)($o['shipping_price']??0);
        if($shipping>0){$line->execute([$invoiceId,'Doprava',(string)($o['shipping_method_name']??''),'1','služba',number_format($shipping,2,'.',''),'0.00','0.00',number_format($shipping,2,'.',''),'0.00',number_format($shipping,2,'.',''),count($items)+1]);}
        if (!$isTest) {
            $inc=$pdo->prepare('UPDATE fakturace_nastaveni SET rada_faktura_dalsi_cislo=? WHERE id=1');
            $inc->execute([$number+1]);
        }
        $pdo->commit();return $invoiceId;
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
