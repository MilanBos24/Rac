<?php
// RAC | Faktura PDF CZ/EN | 2026-10-25-23.20 | PHP 7.4+
// Jazyk urcen podle zeme odberatele ulozene na dokladu.
$racCountry = strtoupper(trim((string)($doklad['odberatel_stat'] ?? '')));
$racEnglish = !in_array($racCountry, array('', 'CZ', 'CZE', 'ČR', 'CESKO', 'ČESKO', 'CZECH REPUBLIC', 'CZECHIA'), true);
$racLabels = $racEnglish ? array(
 'invoice'=>'INVOICE', 'order'=>'Order', 'issued'=>'Issue date:', 'paid_on'=>'Payment date:',
 'customer'=>'Customer:', 'supplier'=>'Supplier:', 'id'=>'Business ID:', 'tax_id'=>'Tax ID:',
 'payment'=>'Payment details:', 'method'=>'Payment method:', 'online'=>'Online payment (Stripe)',
 'status'=>'Payment status:', 'paid'=>'Paid', 'item'=>'Item', 'quantity'=>'Quantity',
 'unit'=>'Unit price', 'total'=>'Total', 'unit_suffix'=>'pcs', 'gross'=>'Total amount',
 'received'=>'Total paid:', 'balance'=>'Balance due:', 'non_vat'=>'The supplier is not registered for VAT in the Czech Republic.',
 'paid_notice'=>'PAID – no further payment is required.',
 'footer'=>'Electronically issued invoice for order',
 'preview'=>'PREVIEW – NOT AN ISSUED INVOICE',
 'test'=>'TEST DOCUMENT – NOT AN ACCOUNTING OR TAX DOCUMENT'
) : array(
 'invoice'=>'FAKTURA', 'order'=>'Objednávka', 'issued'=>'Datum vystavení:', 'paid_on'=>'Datum úhrady:',
 'customer'=>'Odběratel:', 'supplier'=>'Dodavatel:', 'id'=>'IČO:', 'tax_id'=>'DIČ:',
 'payment'=>'Platební údaje:', 'method'=>'Způsob úhrady:', 'online'=>'Online platba (Stripe)',
 'status'=>'Stav úhrady:', 'paid'=>'Zaplaceno', 'item'=>'Položka', 'quantity'=>'Množství',
 'unit'=>'Cena / jedn.', 'total'=>'Celkem', 'unit_suffix'=>'ks', 'gross'=>'Celková hodnota',
 'received'=>'Celkem uhrazeno:', 'balance'=>'Zbývá k úhradě:', 'non_vat'=>'Dodavatel není plátcem DPH.',
 'paid_notice'=>'ZAPLACENO – žádná další platba není požadována.',
 'footer'=>'Faktura vystavena elektronicky pro objednávku',
 'preview'=>'NÁHLED – NENÍ VYSTAVENÁ FAKTURA',
 'test'=>'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD'
);
$racL = static function(string $key) use ($racLabels): string { return $racLabels[$key]; };
?>
<?php
// RAC - STEP06 | 2026-10-25 | PDF podle originalniho rozlozeni virtualni-kancelar/fakturace.
// Pouziva pouze ulozene snimky udaju - nikdy necita aktualni nastaveni dodavatele.
$h=static function($v):string{return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');};
$m=static function($v):string{return number_format((float)$v,2,',',' ').' CZK';};
?>
<!doctype html>
<html lang="<?= $racEnglish ? 'en' : 'cs' ?>"><head><meta charset="UTF-8">
<style>
@page { margin:22mm 16mm 18mm 16mm; }
body { font-family:DejaVu Sans,sans-serif; font-size:9.5pt; line-height:1.45; color:#333; background:#fff; }
.invoice { width:100%; }
.top-line { border-bottom:3px solid #212529; padding-bottom:18px; margin-bottom:24px; }
.topline-table { width:100%; border-collapse:collapse; }
.topline-table td { vertical-align:top; }
.h2 { color:#171717; font-size:25pt; line-height:1.1; font-weight:bold; letter-spacing:-.5px; margin:0 0 5px; }
.number { font-size:14pt; font-weight:bold; color:#171717; margin:0; }
.muted { color:#777; }
.dates { width:100%; border-collapse:collapse; font-size:9.5pt; }
.dates td { padding:0 0 4px 10px; white-space:nowrap; }
.dates .v { text-align:right; }
.parties { width:100%; border-collapse:collapse; table-layout:fixed; margin:18px 0 22px; }
.parties td { width:50%; vertical-align:top; padding:0 12px 0 0; }
.parties td.supplier { padding-left:28px; padding-right:0; }
.h5 { color:#171717; font-size:11pt; font-weight:bold; margin:0 0 6px; }
address { font-style:normal; line-height:1.5; margin:0; }
.payment { margin:14px 0 22px; }
.payment p { margin:0 0 4px; }
.payment .lbl { color:#171717; }
.items { width:100%; border-collapse:collapse; margin:25px 0 18px; }
.items th { background:#f3f3f3; color:#222; border-top:1px solid #ddd; border-bottom:2px solid #bbb; padding:9px 7px; font-size:8.5pt; }
.items td { border-bottom:1px solid #e6e6e6; padding:8px 7px; vertical-align:top; font-size:8.5pt; }
.items tbody tr:nth-child(even) { background:#fafafa; }
.right { text-align:right; } .center { text-align:center; }
.summary-layout { width:100%; border-collapse:collapse; table-layout:fixed; margin-top:9px; }
.summary-layout .spacer { width:40%; } .summary-layout .values { width:60%; vertical-align:top; }
.summary { width:100%; border-collapse:collapse; }
.summary td { padding:7px 8px; border-bottom:1px solid #e4e4e4; font-size:9.5pt; }
.summary .total td { border-top:2px solid #222; border-bottom:0; color:#111; font-size:12pt; font-weight:bold; padding-top:10px; }
.paid { margin-top:17px; padding:10px 13px; background:#f3f3f3; font-weight:bold; color:#171717; border-left:3px solid #212529; }
.footer { position:fixed; bottom:-7mm; left:0; right:0; color:#888; text-align:center; font-size:8pt; border-top:1px solid #eee; padding-top:6px; }
.preview { border:2px dashed #a00; padding:8px; color:#a00; text-align:center; font-weight:bold; font-size:13pt; margin-bottom:15px; }
</style></head><body>
<?php if (strpos((string)($doklad['cislo_dokladu'] ?? ''), 'TEST-') === 0): ?>
<div style="border:3px solid #b00020;color:#b00020;padding:12px;text-align:center;font-size:19px;font-weight:bold;margin-bottom:16px;">
<?= $h($racL('test')) ?>
</div>
<?php endif; ?>

<?php if(!empty($racInvoicePreview)): ?><div class="preview"><?= $h($racL('preview')) ?></div><?php endif; ?>
<div class="invoice">
<header class="top-line"><table class="topline-table"><tr><td style="width:51%">
 <h1 class="h2"><?= $h($racL('invoice')) ?></h1>
 <p class="number">#<?= $h($doklad['cislo_dokladu']) ?></p>
 <div class="muted" style="margin-top:7px"><?= $h($racL('order')) ?>: <?= $h($doklad['order_number']) ?></div>
 </td><td style="width:49%"><table class="dates">
 <tr><td><?= $h($racL('issued')) ?></td><td class="v"><?= $h(date('j. n. Y',strtotime((string)$doklad['datum_vystaveni']))) ?></td></tr>
 <tr><td><?= $h($racL('paid_on')) ?></td><td class="v"><?= $h(date('j. n. Y',strtotime((string)$doklad['datum_zaplaceni']))) ?></td></tr>
 </table></td></tr></table></header>
<table class="parties"><tr><td>
 <p class="h5"><?= $h($racL('customer')) ?></p><address>
 <strong><?= $h($doklad['odberatel_firma'] ?: $doklad['odberatel_nazev']) ?></strong><br>
 <?php if($doklad['odberatel_firma'] && $doklad['odberatel_nazev']): ?><?= $h($doklad['odberatel_nazev']) ?><br><?php endif; ?>
 <?php if($doklad['odberatel_ulice']): ?><?= $h($doklad['odberatel_ulice']) ?><br><?php endif; ?>
 <?= $h(trim((string)$doklad['odberatel_psc'].' '.(string)$doklad['odberatel_mesto'])) ?><br>
 <?= $h($doklad['odberatel_stat']) ?><br>
 <?php if($doklad['odberatel_ico']): ?><?= $h($racL('id')) ?> <?= $h($doklad['odberatel_ico']) ?><br><?php endif; ?>
 <?php if($doklad['odberatel_dic']): ?><?= $h($racL('tax_id')) ?> <?= $h($doklad['odberatel_dic']) ?><br><?php endif; ?>
 <?= $h($doklad['odberatel_email']) ?>
 </address></td><td class="supplier"><p class="h5"><?= $h($racL('supplier')) ?></p><address>
 <strong><?= $h($doklad['dodavatel_nazev']) ?></strong><br>
 <?php if($doklad['dodavatel_ulice']): ?><?= $h($doklad['dodavatel_ulice']) ?><br><?php endif; ?>
 <?= $h(trim((string)$doklad['dodavatel_psc'].' '.(string)$doklad['dodavatel_mesto'])) ?><br>
 <?= $h($doklad['dodavatel_stat']) ?><br>
 <?php if($doklad['dodavatel_ico']): ?><?= $h($racL('id')) ?> <?= $h($doklad['dodavatel_ico']) ?><br><?php endif; ?>
 <?php if($doklad['dodavatel_dic']): ?><?= $h($racL('tax_id')) ?> <?= $h($doklad['dodavatel_dic']) ?><br><?php endif; ?>
 <?= $h($doklad['dodavatel_email']) ?>
 </address></td></tr></table>
<div class="payment"><p class="h5"><?= $h($racL('payment')) ?></p>
 <p><span class="lbl"><?= $h($racL('method')) ?></span> <?= $h($racL('online')) ?></p>
 <p><span class="lbl"><?= $h($racL('status')) ?></span> <strong><?= $h($racL('paid')) ?></strong></p>
</div>
<table class="items"><thead><tr>
 <th style="width:47%;text-align:left"><?= $h($racL('item')) ?></th><th class="center" style="width:11%"><?= $h($racL('quantity')) ?></th>
 <th class="right" style="width:21%"><?= $h($racL('unit')) ?></th><th class="right" style="width:21%"><?= $h($racL('total')) ?></th>
 </tr></thead><tbody><?php foreach($polozky as $it): ?><tr>
 <td><strong><?= $h($it['nazev']) ?></strong><?php if($it['popis']): ?><div class="muted"><?= $h($it['popis']) ?></div><?php endif; ?></td>
 <td class="center"><?= $h((string)(float)$it['mnozstvi']) ?> <?= $h($racL('unit_suffix')) ?></td>
 <td class="right"><?= $h($m($it['cena_za_jednotku'])) ?></td>
 <td class="right"><?= $h($m($it['castka_celkem'])) ?></td>
 </tr><?php endforeach; ?></tbody></table>
<table class="summary-layout"><tr><td class="spacer"></td><td class="values">
 <table class="summary">
 <?php if(empty($doklad['dodavatel_platce_dph'])): ?><tr><td><?= $h($racL('non_vat')) ?></td><td class="right">—</td></tr><?php endif; ?>
 <tr><td><?= $h($racL('gross')) ?></td><td class="right"><?= $h($m($doklad['castka_celkem'])) ?></td></tr>
 <tr class="total"><td><?= $h($racL('received')) ?></td><td class="right"><?= $h($m($doklad['castka_zaplaceno'])) ?></td></tr>
 <tr><td><strong><?= $h($racL('balance')) ?></strong></td><td class="right"><strong><?= $h($m(max(0,(float)$doklad['castka_celkem']-(float)$doklad['castka_zaplaceno']))) ?></strong></td></tr>
 </table></td></tr></table>
 <div class="paid"><?= $h($racL('paid_notice')) ?></div>
</div>
<div class="footer"><?= $h($racL('footer')) ?> <?= $h($doklad['order_number']) ?>.</div>
</body></html>
