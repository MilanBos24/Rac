<?php
// RAC 2026-10-22-UKAZKY-09 | Tlacitka ukazek vedle sebe a auto jazyk | PHP 7.4+
if (empty($product['id']) || !isset($pdo) || !($pdo instanceof PDO)) return;
$racPreviewMap = [];
try {
    $st = $pdo->prepare(
        "SELECT f.variant_id,f.media_type,v.kind
         FROM product_files f
         JOIN product_variants v ON v.id=f.variant_id
         WHERE v.product_id=? AND v.active=1 AND v.kind IN ('ebook','audio')
           AND f.purpose='preview' AND f.is_published=1
         ORDER BY f.id DESC"
    );
    $st->execute([(int)$product['id']]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $id = (int)$f['variant_id'];
        if (!isset($racPreviewMap[$id])) {
            $racPreviewMap[$id] = ['kind'=>(string)$f['kind'],'type'=>(string)$f['media_type']];
        }
    }
} catch (Throwable $e) {
    error_log('RAC preview buttons: '.$e->getMessage());
}
$racPreviewUi = [
 'cs'=>['read'=>'Přečíst ukázku','download'=>'Stáhnout ukázku','listen'=>'Poslechnout ukázku','close'=>'Zavřít','title'=>'Ukázka e-knihy'],
 'en'=>['read'=>'Read sample','download'=>'Download sample','listen'=>'Listen to sample','close'=>'Close','title'=>'E-book sample'],
 'de'=>['read'=>'Leseprobe ansehen','download'=>'Leseprobe herunterladen','listen'=>'Hörprobe anhören','close'=>'Schließen','title'=>'E-Book-Leseprobe'],
 'pl'=>['read'=>'Przeczytaj fragment','download'=>'Pobierz fragment','listen'=>'Posłuchaj fragmentu','close'=>'Zamknij','title'=>'Fragment e-booka'],
 'fr'=>['read'=>"Lire l'extrait",'download'=>"Télécharger l'extrait",'listen'=>"Écouter l'extrait",'close'=>'Fermer','title'=>"Extrait de l'e-book"],
 'sk'=>['read'=>'Prečítať ukážku','download'=>'Stiahnuť ukážku','listen'=>'Vypočuť ukážku','close'=>'Zavrieť','title'=>'Ukážka e-knihy']
];
$racUi = $racPreviewUi[(string)($currentLanguage??'cs')]??$racPreviewUi['cs'];
$racE = static function ($v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); };
?>
<style>
/* Spolecny vzhled tlacitek Ogency, bez globalni zmeny sablony. */
#rac-preview-actions{display:flex;align-items:center;gap:24px;flex-wrap:wrap;margin-top:22px}
#rac-preview-actions[hidden]{display:none!important}
#rac-preview-actions .rac-preview-links{display:flex;flex-direction:row;align-items:center;gap:15px;flex-wrap:wrap}
#rac-preview-actions .rac-preview-links .ogency-btn{margin:0;text-align:center;white-space:normal}
#rac-preview-actions .rac-preview-links [hidden]{display:none!important}
#rac-preview-actions .rac-preview-audio-circle{width:175px;height:175px;flex:0 0 175px;position:relative;border-radius:50%;background:var(--ogency-black2,#1d1d1d);display:flex;align-items:center;justify-content:center}
#rac-preview-actions .rac-preview-audio-circle[hidden]{display:none!important}
#rac-preview-actions .rac-preview-audio-circle .rac-preview-circle-text{position:absolute;inset:7px;color:#fff;border:1px solid rgba(255,255,255,.35);border-radius:50%;font-size:11px;text-align:center;display:flex;align-items:flex-start;justify-content:center;padding-top:16px;letter-spacing:.7px}
#rac-preview-actions .rac-preview-play{position:relative;z-index:1;cursor:pointer;background:#fff;border:0;border-radius:50%;height:64px;width:64px;color:#171717;font-size:19px;display:flex;align-items:center;justify-content:center}
#rac-preview-actions .rac-preview-play:focus-visible{outline:3px solid #d9c0f6;outline-offset:4px}
#rac-pdf-modal[hidden]{display:none!important}
#rac-pdf-modal{position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.85);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px}
#rac-pdf-modal .rac-pdf-dialog{width:min(100%,1000px);height:min(90vh,900px);display:flex;flex-direction:column;background:#fff;color:#222;box-shadow:0 16px 50px rgba(0,0,0,.35)}
#rac-pdf-modal .rac-pdf-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 14px;background:#1d1d1d;color:#fff}
#rac-pdf-modal .rac-pdf-toolbar strong{color:#fff}
#rac-pdf-modal .rac-pdf-close{border:1px solid #777;background:transparent;color:#fff;padding:7px 14px;cursor:pointer}
#rac-pdf-modal iframe{display:block;width:100%;height:100%;border:0;background:#f5f5f5}
@media(max-width:575px){#rac-preview-actions{gap:15px}#rac-preview-actions .rac-preview-links{width:100%;flex-direction:column;align-items:stretch}#rac-preview-actions .rac-preview-links .ogency-btn{width:100%}#rac-pdf-modal{padding:5px}#rac-pdf-modal .rac-pdf-dialog{height:96dvh}}
</style>
<div id="rac-preview-actions" hidden aria-label="<?= $racE($racUi['title']) ?>">
  <div id="rac-preview-audio-circle" class="rac-preview-audio-circle" hidden>
    <span class="rac-preview-circle-text"><?= $racE($racUi['listen']) ?></span>
    <button id="rac-preview-play" class="rac-preview-play" type="button" aria-label="<?= $racE($racUi['listen']) ?>" title="<?= $racE($racUi['listen']) ?>"><i class="fa fa-play" aria-hidden="true"></i></button>
    <audio id="rac-preview-audio" preload="none"></audio>
  </div>
  <div id="rac-preview-links" class="rac-preview-links" hidden>
    <button type="button" class="ogency-btn" id="rac-preview-read" hidden><?= $racE($racUi['read']) ?></button>
    <a class="ogency-btn" id="rac-preview-download" href="#" hidden><?= $racE($racUi['download']) ?></a>
  </div>
</div>
<div id="rac-pdf-modal" role="dialog" aria-modal="true" aria-label="<?= $racE($racUi['title']) ?>" hidden>
  <div class="rac-pdf-dialog">
    <div class="rac-pdf-toolbar">
      <strong><?= $racE($racUi['title']) ?></strong>
      <button type="button" class="rac-pdf-close" id="rac-pdf-close"><?= $racE($racUi['close']) ?> ×</button>
    </div>
    <iframe id="rac-pdf-frame" title="<?= $racE($racUi['title']) ?>" loading="lazy"></iframe>
  </div>
</div>
<script>
(function(){
 'use strict';
 var map=<?= json_encode($racPreviewMap,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 var variant=document.getElementById('rac-variant-select');
 var actions=document.getElementById('rac-preview-actions');
 var oldAudio=document.querySelector('.rac-product-audio-sample');
 var circle=document.getElementById('rac-preview-audio-circle');
 var play=document.getElementById('rac-preview-play');
 var audio=document.getElementById('rac-preview-audio');
 var links=document.getElementById('rac-preview-links');
 var read=document.getElementById('rac-preview-read');
 var download=document.getElementById('rac-preview-download');
 var modal=document.getElementById('rac-pdf-modal');
 var frame=document.getElementById('rac-pdf-frame');
 var close=document.getElementById('rac-pdf-close');
 if(oldAudio)oldAudio.style.display='none'; // Stary obecny prehravac neni vazany na jazyk vydani.
 if(!variant||!actions)return;
 // Tlacitka patri pod nakupni ovladani, na misto puvodni audio ukazky.
 var purchaseForm=document.getElementById('rac-cart-submit');
 if(purchaseForm&&purchaseForm.form){
   purchaseForm.form.insertAdjacentElement('afterend',actions);
 }else {
   var content=document.querySelector('.product-details__content');
   if(content)content.appendChild(actions);
 }
 document.body.appendChild(modal);
 function stopAudio(){
   audio.pause();audio.removeAttribute('src');audio.load();
   play.innerHTML='<i class="fa fa-play" aria-hidden="true"></i>';
 }
 function closePdf(){
   modal.hidden=true;frame.removeAttribute('src');
   document.body.style.overflow='';
 }
 function refresh(){
   var chosen=variant.options[variant.selectedIndex];
   var id=chosen?chosen.value:'';
   var entry=map[id];
   stopAudio();closePdf();
   actions.hidden=true;circle.hidden=true;links.hidden=true;read.hidden=true;download.hidden=true;
   if(!entry)return;
   var url='/ukazka.php?variant_id='+encodeURIComponent(id);
   if(entry.kind==='audio' && (entry.type==='mp3'||entry.type==='m4a')){
     audio.src=url;circle.hidden=false;actions.hidden=false;
   }
   if(entry.kind==='ebook' && entry.type==='pdf'){
     read.hidden=false;download.hidden=false;links.hidden=false;actions.hidden=false;
     read.setAttribute('data-url',url);
     download.href=url+'&download=1';
   }else if(entry.kind==='ebook' && entry.type==='epub'){
     download.hidden=false;links.hidden=false;actions.hidden=false;
     download.href=url+'&download=1';
   }
 }
 play.addEventListener('click',function(){
  if(!audio.src)return;
  if(audio.paused){audio.play().then(function(){play.innerHTML='<i class="fa fa-pause" aria-hidden="true"></i>';}).catch(function(){});}
  else {audio.pause();play.innerHTML='<i class="fa fa-play" aria-hidden="true"></i>';}
 });
 audio.addEventListener('ended',function(){play.innerHTML='<i class="fa fa-play" aria-hidden="true"></i>';});
 read.addEventListener('click',function(){
  var url=read.getAttribute('data-url');if(!url)return;
  frame.src=url;modal.hidden=false;
  document.body.style.overflow='hidden';close.focus();
 });
 close.addEventListener('click',closePdf);
 modal.addEventListener('click',function(e){if(e.target===modal)closePdf();});
 document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.hidden)closePdf();});
 variant.addEventListener('change',refresh);
 var editionLanguage=document.getElementById('rac-edition-language-select');
 if(editionLanguage)editionLanguage.addEventListener('change',function(){setTimeout(refresh,0);});
 // Vychodiskovy jazyk vydani = jazyk webu, pokud je dostupny;
 // jinak cestina, nasledne prvni nabizeny jazyk. Jen pri prvnim nacteni.
 var currentSiteLanguage=<?= json_encode((string)($currentLanguage ?? 'cs'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 function setInitialEditionLanguage(){
   if(!editionLanguage || !editionLanguage.options.length)return;
   var wanted='';
   var options=Array.prototype.slice.call(editionLanguage.options);
   if(options.some(function(o){return o.value===currentSiteLanguage;})) wanted=currentSiteLanguage;
   else if(options.some(function(o){return o.value==='cs';})) wanted='cs';
   else wanted=options[0].value;
   if(editionLanguage.value!==wanted){
     editionLanguage.value=wanted;
     // Aktivuje puvodni filtrovani variant i prepocteni ceny.
     editionLanguage.dispatchEvent(new Event('change',{bubbles:true}));
   }
   if(window.jQuery && window.jQuery.fn && window.jQuery.fn.selectpicker){
     window.jQuery(editionLanguage).selectpicker('refresh');
   }
 }
 // Puvodni JS detailu inicializuje vyber jako prvni dostupny;
 // tato inicializace probiha az po nem a jen jednou, bez prepisovani rucni volby.
 setInitialEditionLanguage();
 document.addEventListener('DOMContentLoaded',function(){setTimeout(refresh,0);});
 setTimeout(refresh,0);
})();
</script>
