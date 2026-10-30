<?php /* RAC napoveda widget | verze 2026-10-30-01; pouze čtení */ ?>
<style>
#racHelpOpen{position:fixed;right:22px;bottom:22px;z-index:1050;border:0;border-radius:40px;padding:12px 18px;background:#ffc107;color:#202020;box-shadow:0 5px 20px rgba(0,0,0,.23);font-weight:600}
#racHelpPanel{position:fixed;right:22px;bottom:80px;z-index:1050;width:min(360px,calc(100vw - 32px));background:#fff;color:#242424;border:1px solid #ddd;border-radius:12px;box-shadow:0 12px 36px rgba(0,0,0,.22);padding:18px}
#racHelpPanel[hidden]{display:none!important}
@media(max-width:576px){#racHelpOpen{right:16px;bottom:16px}#racHelpPanel{right:16px;bottom:72px}}
</style>
<button type="button" id="racHelpOpen" aria-expanded="false" aria-controls="racHelpPanel" aria-label="Otevřít nápovědu"><i class="fas fa-question-circle" aria-hidden="true"></i> Nápověda</button>
<div id="racHelpPanel" hidden role="region" aria-label="Rychlá nápověda">
<div class="d-flex justify-content-between align-items-center mb-3"><strong>Jak vám můžeme pomoci?</strong><button type="button" id="racHelpClose" class="btn btn-sm btn-default" aria-label="Zavřít">×</button></div>
<form action="/administrace/napoveda.php" method="get"><label class="form-label" for="racHelpQuery">Co potřebujete vyřešit?</label><input type="search" class="form-control mb-2" id="racHelpQuery" name="q" placeholder="Např. fakturace, nová kniha"><button class="btn btn-primary w-100" type="submit">Vyhledat návod</button></form>
<a class="d-inline-block mt-3" href="/administrace/napoveda.php">Všechny návody</a>
</div>
<script>
(function(){var trigger=document.getElementById('racHelpOpen'),panel=document.getElementById('racHelpPanel'),close=document.getElementById('racHelpClose');if(!trigger||!panel)return;function toggle(open){panel.hidden=!open;trigger.setAttribute('aria-expanded',String(open));if(open)document.getElementById('racHelpQuery').focus();}trigger.addEventListener('click',function(){toggle(panel.hidden);});close.addEventListener('click',function(){toggle(false);trigger.focus();});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!panel.hidden)toggle(false);});})();
</script>
