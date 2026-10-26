<?php
/* RAC | Právní dokumenty | verze 2026-10-21-12.00 | jazykové mutace + vizuální editor + Porto */
/* Používá existující přihlášení, PDO a tabulky RAC (migrace 017). */
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');
$viewer = current_user_row();
if (!$viewer || empty($viewer['id'])) { http_response_code(403); exit('Přístup zamítnut.'); }
$adminId = (int)$viewer['id'];
$db = $pdo;
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$csrf = csrf_token('csrf_legal_documents');
$e = static function ($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
function legalRedirect(string $notice): void { header('Location: pravni-dokumenty.php?notice=' . rawurlencode($notice), true, 303); exit; }
function legalPost(PDO $db, string $sql, array $params = []): void { $s=$db->prepare($sql); $s->execute($params); }
function legalOne(PDO $db, string $sql, array $params = []): ?array { $s=$db->prepare($sql); $s->execute($params); $r=$s->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
$languages = ['cs'=>'Čeština', 'en'=>'English', 'de'=>'Deutsch', 'pl'=>'Polski', 'fr'=>'Français'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid('csrf_legal_documents', (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Neplatný bezpečnostní token.'); }
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'create_document') {
            $key = strtolower(trim((string)($_POST['document_key'] ?? '')));
            $title = trim((string)($_POST['document_title'] ?? ''));
            if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,99}$/', $key) || $title === '' || mb_strlen($title) > 255) { throw new RuntimeException('Zadejte platný identifikátor a název dokumentu.'); }
            legalPost($db,'INSERT INTO rac_legal_documents (document_key,title,description) VALUES (?,?,?)',[$key,$title,trim((string)($_POST['description']??''))]);
            legalRedirect('Dokument byl založen.');
        }
        if ($action === 'toggle_document') {
            $id = (int)($_POST['document_id'] ?? 0);
            if ($id < 1 || !legalOne($db, 'SELECT id FROM rac_legal_documents WHERE id=?', [$id])) { throw new RuntimeException('Dokument nenalezen.'); }
            legalPost($db,'UPDATE rac_legal_documents SET is_enabled = IF(is_enabled=1,0,1) WHERE id=?',[$id]);
            legalRedirect('Dostupnost dokumentu byla změněna.');
        }
        if ($action === 'save_draft') {
            $id = (int)($_POST['document_id'] ?? 0);
            $lang=(string)($_POST['language_code']??'');
            $title=trim((string)($_POST['title']??''));
            $html=(string)($_POST['content_html']??'');
            $label=trim((string)($_POST['version_label']??''));
            $versionId=(int)($_POST['version_id']??0);
            if (!isset($languages[$lang]) || $title === '' || mb_strlen($title)>255 || trim(strip_tags($html)) === '' || mb_strlen($label)>40) { throw new RuntimeException('Vyplňte platný jazyk, název a obsah.'); }
            if (!legalOne($db, 'SELECT id FROM rac_legal_documents WHERE id=?',[$id])) {throw new RuntimeException('Dokument nenalezen.');}
            // Povolit pouze neatributové formátovací značky, žádné skripty ani URL.
            $allowedTags = ['p','br','strong','b','em','i','u','h2','h3','h4','ul','ol','li','blockquote','mark','span'];
            $validationHtml = str_replace('<span class="rac-text-orange">', '<span>', $html);
            $badHtml = preg_match('~<\s*(?!/?(?:p|br|strong|b|em|i|u|h2|h3|h4|ul|ol|li|blockquote|mark|span)\b)[^>]*>~i', $validationHtml)
                || preg_match('~<\s*/?\s*(?:p|br|strong|b|em|i|u|h2|h3|h4|ul|ol|li|blockquote|mark|span)\b[^>\s/]+~i', $validationHtml)
                || preg_match('~<\s*/?\s*(?:p|br|strong|b|em|i|u|h2|h3|h4|ul|ol|li|blockquote|mark|span)\b\s+[^>]*>~i', $validationHtml)
                || preg_match('~<[^>]*\son[a-z]+\s*=~i', $validationHtml)
                || strpos($html, '<!--') !== false
                || preg_match('~<\s*/?\s*(?:script|style|svg|iframe|math|form|object|embed|textarea|meta|link)\b~i', $validationHtml);
            if ($badHtml) { throw new RuntimeException('Povoleny jsou jen formátovací HTML značky bez atributů, včetně mark a oranžové barvy písma.'); }
            $hash=hash('sha256',$html);
            if ($versionId > 0) {
                $s=$db->prepare("UPDATE rac_legal_document_versions SET title=?,content_html=?,content_sha256=?,version_label=? WHERE id=? AND document_id=? AND language_code=? AND status='draft'");
                $s->execute([$title,$html,$hash,$label?:null,$versionId,$id,$lang]);
                if ($s->rowCount() === 0 && !legalOne($db,"SELECT id FROM rac_legal_document_versions WHERE id=? AND document_id=? AND language_code=? AND status='draft'",[$versionId,$id,$lang])) { throw new RuntimeException('Publikovanou nebo archivovanou verzi nelze přepsat.'); }
            } else {
                $db->beginTransaction();
                $document = legalOne($db,'SELECT id FROM rac_legal_documents WHERE id=? FOR UPDATE',[$id]);
                if (!$document) throw new RuntimeException('Dokument nenalezen.');
                $last=legalOne($db,'SELECT COALESCE(MAX(version_number),0) AS n FROM rac_legal_document_versions WHERE document_id=? AND language_code=?',[$id,$lang]);
                legalPost($db,"INSERT INTO rac_legal_document_versions (document_id,language_code,version_number,version_label,title,content_html,content_sha256,status,created_by) VALUES (?,?,?,?,?,?,?,'draft',?)",[$id,$lang,(int)$last['n']+1,$label?:null,$title,$html,$hash,$adminId]);
                $db->commit();
            }
            legalRedirect('Návrh verze byl uložen.');
        }
        if ($action === 'publish') {
            $versionId=(int)($_POST['version_id']??0);
            $db->beginTransaction();
            $v=legalOne($db,'SELECT v.*,d.is_enabled FROM rac_legal_document_versions v JOIN rac_legal_documents d ON d.id=v.document_id WHERE v.id=? FOR UPDATE',[$versionId]);
            if (!$v || $v['status']!=='draft' || !(int)$v['is_enabled']) throw new RuntimeException('Publikovat lze jen návrh aktivního dokumentu.');
            // Uzamčení dokumentu serializuje publikování více návrhů téhož dokumentu.
            legalOne($db,'SELECT id FROM rac_legal_documents WHERE id=? FOR UPDATE',[(int)$v['document_id']]);
            legalPost($db,"UPDATE rac_legal_document_versions SET status='archived' WHERE document_id=? AND language_code=? AND status='published'",[(int)$v['document_id'],$v['language_code']]);
            legalPost($db,"UPDATE rac_legal_document_versions SET status='published',published_at=NOW(),effective_from=NOW(),published_by=? WHERE id=? AND status='draft'",[$adminId,$versionId]);
            $db->commit(); legalRedirect('Nová verze byla publikována; předchozí je archivována.');
        }
        if ($action === 'copy_version') {
            $versionId = (int)($_POST['version_id'] ?? 0);
            $db->beginTransaction();
            $v = legalOne($db, 'SELECT * FROM rac_legal_document_versions WHERE id=?', [$versionId]);
            if (!$v || !in_array($v['status'], ['published', 'archived'], true)) {
                throw new RuntimeException('Kopírovat lze pouze publikovanou nebo archivovanou verzi.');
            }
            legalOne($db, 'SELECT id FROM rac_legal_documents WHERE id=? FOR UPDATE', [(int)$v['document_id']]);
            $last = legalOne($db, 'SELECT COALESCE(MAX(version_number),0) AS n FROM rac_legal_document_versions WHERE document_id=? AND language_code=?', [(int)$v['document_id'], $v['language_code']]);
            legalPost($db, "INSERT INTO rac_legal_document_versions
                (document_id,language_code,version_number,version_label,title,content_html,content_sha256,status,created_by)
                VALUES (?,?,?,?,?,?,?,'draft',?)",
                [(int)$v['document_id'], $v['language_code'], (int)$last['n'] + 1, null, $v['title'], $v['content_html'], $v['content_sha256'], $adminId]);
            $newId = (int)$db->lastInsertId();
            $db->commit();
            header('Location: pravni-dokumenty.php?edit_version=' . $newId . '&notice=' . rawurlencode('Nový návrh byl vytvořen z předchozí verze.'), true, 303);
            exit;
        }
        if ($action === 'delete_draft') {
            $id=(int)($_POST['version_id']??0);
            legalPost($db,"DELETE FROM rac_legal_document_versions WHERE id=? AND status='draft'",[$id]);
            legalRedirect('Nepublikovaný návrh byl odstraněn.');
        }
        throw new RuntimeException('Neznámá operace.');
    } catch (Throwable $ex) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $ex instanceof PDOException ? 'Databázová operace se nezdařila. Zkontrolujte údaje a duplicity.' : $ex->getMessage();
        error_log('[RAC legal] ' . $ex->getMessage());
    }
}
$docs=$db->query('SELECT * FROM rac_legal_documents ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$versions=$db->query('SELECT v.*,d.title AS document_title FROM rac_legal_document_versions v JOIN rac_legal_documents d ON d.id=v.document_id ORDER BY v.document_id,v.language_code,v.version_number DESC')->fetchAll(PDO::FETCH_ASSOC);
$documentId=(int)($_GET['document_id']??0);
$editVersion=(int)($_GET['edit_version']??0);
$current=null;
if ($editVersion) { $current=legalOne($db,"SELECT * FROM rac_legal_document_versions WHERE id=? AND status='draft'",[$editVersion]); if($current) $documentId=(int)$current['document_id']; }
if (!$documentId && $docs) $documentId=(int)$docs[0]['id'];
$selected=null;foreach($docs as $d)if((int)$d['id']===$documentId)$selected=$d;
// Přehled dostupných mutací právě vybraného dokumentu.
$selectedLanguage=(string)($_GET['lang']??'cs');
if (!isset($languages[$selectedLanguage])) $selectedLanguage='cs';
if ($current) $selectedLanguage=(string)$current['language_code'];
$languageVersions=[];
foreach($versions as $v) {
    if ((int)$v['document_id'] !== $documentId) continue;
    $code=(string)$v['language_code'];
    if (!isset($languageVersions[$code])) $languageVersions[$code]=['published'=>null,'draft'=>null,'archived'=>null];
    if (array_key_exists((string)$v['status'], $languageVersions[$code]) && $languageVersions[$code][$v['status']]===null) {
        $languageVersions[$code][$v['status']]=$v;
    }
}


$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Právní dokumenty | RAC';
$pageTitle = 'Právní dokumenty';
$currentPath = '/administrace/pravni-dokumenty.php';
$viewerName = (string)($viewer['username'] ?? 'Administrátor');
$viewerRole = (string)($viewer['role'] ?? '');
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
        ['Právní dokumenty', '/administrace/pravni-dokumenty.php', 'icons icon-note'],
    ]],
    ['E-shop', [
        ['Doprava', '/administrace/eshop-doprava.php', 'icons icon-drawer'],
    ]],
    ['Billing', [
        ['Nastavení Stripe', '/administrace/billing/admin.php', 'el el-cogs'],
        ['Produkty', '/administrace/billing/admin-produkty.php', 'icons icon-basket'],
        ['Platby', '/administrace/billing/admin-platby.php', 'el el-credit-card'],
        ['Předplatné', '/administrace/billing/admin-predplatne.php', 'icons icon-refresh'],
        ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
        ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
    ]],
];
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Právní dokumenty</h2></header>
<div class="row pt-3 mb-4"><div class="col-12">
<div class="alert alert-info">Publikované verze jsou neměnné. Při úpravách vytvořte návrh nové verze a samostatně jej publikujte. Veřejné GDPR zobrazuje publikovanou jazykovou mutaci; checkout dosud není propojený.</div>
<?php if (isset($_GET['notice'])): ?><div class="alert alert-success"><?= $e($_GET['notice']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $e($error) ?></div><?php endif; ?>
<div class="row">
<div class="col-lg-4">
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Dokumenty</h2></header><div class="card-body">
<?php if (!$docs): ?><p>Zatím nejsou založené žádné dokumenty.</p><?php endif; ?>
<?php foreach ($docs as $d): ?>
<div class="border-bottom py-3"><a href="?document_id=<?= (int)$d['id'] ?>"><strong><?= $e($d['title']) ?></strong></a><div class="text-muted small"><?= $e($d['document_key']) ?> · <?= $d['is_enabled'] ? 'Aktivní' : 'Neaktivní' ?></div>
<form method="post" class="mt-2"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="toggle_document"><input type="hidden" name="document_id" value="<?= (int)$d['id'] ?>"><button type="submit" class="btn btn-default btn-sm"><?= $d['is_enabled'] ? 'Deaktivovat' : 'Aktivovat' ?></button></form></div>
<?php endforeach; ?></div></section>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Nový dokument</h2></header><form method="post"><div class="card-body"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="create_document">
<label class="form-label">Identifikátor</label><input class="form-control" name="document_key" placeholder="obchodni-podminky" required pattern="[a-z0-9_-]+" maxlength="100">
<label class="form-label mt-3">Název</label><input class="form-control" name="document_title" required maxlength="255">
<label class="form-label mt-3">Popis</label><textarea class="form-control" name="description" rows="2"></textarea>
</div><footer class="card-footer text-end"><button type="submit" class="btn btn-primary">Vytvořit dokument</button></footer></form></section>
</div>
<div class="col-lg-8">
<?php if ($selected): ?>
<section class="card mb-4">
  <header class="card-header"><h2 class="card-title">Jazykové mutace – <?= $e($selected['title']) ?></h2></header>
  <div class="card-body">
    <p class="text-muted mb-3">Každý jazyk se publikuje a verzuje samostatně. Chybějící publikovaný překlad veřejný web nahradí českou verzí.</p>
    <div class="row g-3">
    <?php foreach ($languages as $langCode => $langLabel):
      $state=$languageVersions[$langCode]??['published'=>null,'draft'=>null,'archived'=>null];
      $published=$state['published']; $draft=$state['draft'];
      $langUrl='?document_id='.$documentId.'&lang='.rawurlencode($langCode);
    ?>
      <div class="col-12 col-md-6 col-xl-4">
        <div class="border rounded p-3 h-100 d-flex flex-column gap-2 <?= $selectedLanguage===$langCode ? 'border-warning' : '' ?>">
          <div class="d-flex justify-content-between align-items-center"><strong><?= $e($langLabel) ?></strong><span class="badge bg-secondary"><?= $e(strtoupper($langCode)) ?></span></div>
          <?php if ($published): ?>
            <div><span class="badge bg-success">Publikováno</span> <small>v<?= (int)$published['version_number'] ?></small></div>
          <?php else: ?><span class="badge bg-secondary align-self-start">Překlad není publikován</span><?php endif; ?>
          <?php if ($draft): ?><div><span class="badge bg-warning text-dark">Rozpracovaný návrh</span> <small>v<?= (int)$draft['version_number'] ?></small></div><?php endif; ?>
          <div class="mt-auto pt-2">
            <?php if ($draft): ?>
              <a class="btn btn-primary btn-sm w-100" href="?edit_version=<?= (int)$draft['id'] ?>">Upravit návrh</a>
            <?php elseif ($published): ?>
              <form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="copy_version"><input type="hidden" name="version_id" value="<?= (int)$published['id'] ?>"><button class="btn btn-default btn-sm w-100" type="submit">Nová verze</button></form>
            <?php else: ?>
              <a class="btn btn-primary btn-sm w-100" href="<?= $e($langUrl) ?>">Přidat překlad</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</section>
<?php if ($current || !(($languageVersions[$selectedLanguage]['draft']??null) || ($languageVersions[$selectedLanguage]['published']??null))): ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title"><?= $e($selected['title']) ?> – <?= $current ? 'Úprava návrhu' : (($languageVersions[$selectedLanguage]['published']??null) ? 'Nový návrh' : 'Přidat překlad') ?></h2></header>
<form method="post"><div class="card-body"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="save_draft"><input type="hidden" name="document_id" value="<?= $documentId ?>"><input type="hidden" name="version_id" value="<?= (int)($current['id'] ?? 0) ?>">
<div class="row g-3"><div class="col-md-6"><label class="form-label">Jazyk</label><input class="form-control" value="<?= $e($languages[$selectedLanguage]) ?>" readonly><input type="hidden" name="language_code" value="<?= $e($selectedLanguage) ?>"><small class="text-muted">Jazyk vyberete v přehledu mutací výše.</small></div>
<div class="col-md-6"><label class="form-label">Označení verze (volitelné)</label><input class="form-control" name="version_label" maxlength="40" value="<?= $e($current['version_label'] ?? '') ?>" placeholder="např. 2026-10"></div>
<div class="col-12"><label class="form-label">Nadpis dokumentu</label><input class="form-control" name="title" maxlength="255" required value="<?= $e($current['title'] ?? ($selectedLanguage === 'cs' ? $selected['title'] : '')) ?>"></div>
<div class="col-12">
<label class="form-label" for="rac-legal-html">Obsah dokumentu</label>
<div class="rac-legal-editor" data-rac-legal-editor>
  <div class="rac-legal-toolbar" role="toolbar" aria-label="Formátování dokumentu">
    <select class="form-select form-select-sm rac-legal-block" aria-label="Styl odstavce">
      <option value="p">Odstavec</option><option value="h2">Nadpis 2</option><option value="h3">Nadpis 3</option><option value="h4">Nadpis 4</option><option value="blockquote">Citace</option>
    </select>
    <button type="button" class="btn btn-default btn-sm" data-command="bold" title="Tučné písmo"><strong>B</strong></button>
    <button type="button" class="btn btn-default btn-sm" data-command="italic" title="Kurzíva"><em>I</em></button>
    <button type="button" class="btn btn-default btn-sm" data-command="underline" title="Podtržení"><u>U</u></button>
    <button type="button" class="btn btn-default btn-sm" data-command="insertUnorderedList" title="Odrážkový seznam">• Seznam</button>
    <button type="button" class="btn btn-default btn-sm" data-command="insertOrderedList" title="Číslovaný seznam">1. Seznam</button>
    <button type="button" class="btn btn-default btn-sm rac-legal-highlight" title="Zvýraznit vybraný text oranžovou barvou #f5900d" aria-label="Zvýraznit vybraný text barvou #f5900d"><span style="background:#f5900d;color:#151515;padding:2px 7px;border-radius:3px;font-weight:700">A</span> Zvýraznit</button>
    <button type="button" class="btn btn-default btn-sm rac-legal-text-color" title="Změnit barvu vybraného písma na #f5900d"><span style="color:#f5900d;font-weight:700">A</span> Barva písma</button>
    <button type="button" class="btn btn-default btn-sm rac-legal-source-toggle">HTML</button>
  </div>
  <div class="rac-legal-visual" contenteditable="true" role="textbox" aria-label="Vizuální editor právního dokumentu" aria-multiline="true" spellcheck="true"></div>
  <textarea class="form-control font-monospace rac-legal-source" id="rac-legal-html" name="content_html" rows="13" required><?= $e($current['content_html'] ?? '') ?></textarea>
</div>
<div class="form-text">Vyberte text a použijte formátování. Enter vloží nový odstavec; nadpis vyberete v nabídce „Styl odstavce“. Režim HTML je volitelný. Při uložení se ponechají jen povolené formátovací značky, bez atributů. Zvýraznění použije barvu #f5900d. Odkazy zatím nejsou podporovány.</div>
</div></div>
</div><footer class="card-footer d-flex flex-wrap justify-content-end gap-2"><button class="btn btn-primary" type="submit">Uložit návrh</button><?php if ($current): ?><a class="btn btn-default" href="?document_id=<?= $documentId ?>">Zrušit úpravy</a><?php endif; ?></footer></form></section><?php else: ?><div class="alert alert-info">Pro tento jazyk použijte tlačítko „Upravit návrh“ nebo „Nová verze“ v přehledu mutací.</div><?php endif; ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Historie verzí</h2></header><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Dokument / jazyk</th><th>Verze</th><th>Stav</th><th>Vytvořeno</th><th>Akce</th></tr></thead><tbody>
<?php foreach ($versions as $v): ?><tr><td><?= $e($v['document_title']) ?><div class="text-muted small"><?= $e(strtoupper($v['language_code'])) ?></div></td><td>v<?= (int)$v['version_number'] ?><div class="small"><?= $e($v['version_label'] ?? '') ?></div></td><td><span class="badge <?= $v['status'] === 'published' ? 'bg-success' : ($v['status'] === 'draft' ? 'bg-warning text-dark' : 'bg-secondary') ?>"><?= $e($v['status']) ?></span></td><td><?= $e($v['created_at']) ?></td><td><div class="d-flex gap-2 flex-wrap">
<?php if ($v['status'] === 'draft'): ?><a class="btn btn-default btn-sm" href="?edit_version=<?= (int)$v['id'] ?>">Upravit</a><form method="post" onsubmit="return confirm('Publikovat tuto verzi? Dosavadní publikovaná verze se archivuje.');"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="publish"><input type="hidden" name="version_id" value="<?= (int)$v['id'] ?>"><button type="submit" class="btn btn-primary btn-sm">Publikovat</button></form><form method="post" onsubmit="return confirm('Smazat nepublikovaný návrh?');"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="delete_draft"><input type="hidden" name="version_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Smazat návrh</button></form>
<?php else: ?><form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="copy_version"><input type="hidden" name="version_id" value="<?= (int)$v['id'] ?>"><button type="submit" class="btn btn-default btn-sm">Vytvořit návrh z verze</button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div></div></section>
<?php endif; ?>
</div></div></div></div></section>
<style>
.rac-legal-toolbar{display:flex;gap:7px;flex-wrap:wrap;align-items:center;padding:10px;border:1px solid #ced4da;border-radius:5px 5px 0 0;background:#f3f4f6}
.rac-legal-toolbar .rac-legal-block{width:auto;min-width:150px}
.rac-legal-visual{min-height:340px;max-height:700px;overflow:auto;padding:20px;background:#fff;color:#20252a;border:1px solid #ced4da;border-top:0;border-radius:0 0 5px 5px;line-height:1.7;white-space:normal;overflow-wrap:anywhere}
.rac-legal-visual:focus{outline:2px solid #ffc107;outline-offset:-2px}
.rac-legal-visual .rac-text-orange{color:#f5900d}
.rac-legal-visual mark{background-color:#f5900d;color:#171717;padding:0 .12em;border-radius:2px}
.rac-legal-visual h2,.rac-legal-visual h3,.rac-legal-visual h4{color:#20252a;margin:24px 0 12px;line-height:1.3}
.rac-legal-visual h2{font-size:24px}.rac-legal-visual h3{font-size:20px}.rac-legal-visual h4{font-size:17px}
.rac-legal-visual p{margin:0 0 14px}.rac-legal-visual ol,.rac-legal-visual ul{padding-left:28px;margin:0 0 16px}.rac-legal-visual li{margin:4px 0}.rac-legal-visual blockquote{border-left:3px solid #ffc107;padding-left:15px}
.rac-legal-source{display:none;border-top:0;border-radius:0 0 5px 5px}
.rac-legal-editor.is-source .rac-legal-source{display:block}.rac-legal-editor.is-source .rac-legal-visual{display:none}
</style>
<script>
(function(){
'use strict';
var wrap=document.querySelector('[data-rac-legal-editor]');if(!wrap)return;
var source=wrap.querySelector('.rac-legal-source'),visual=wrap.querySelector('.rac-legal-visual'),toggle=wrap.querySelector('.rac-legal-source-toggle');
var allowed={p:1,br:1,strong:1,b:1,em:1,i:1,u:1,h2:1,h3:1,h4:1,ul:1,ol:1,li:1,blockquote:1,mark:1,span:1};
function escapeText(value){var d=document.createElement('div');d.textContent=value;return d.innerHTML;}
function plainToHtml(value){return value.replace(/\r\n?/g,'\n').split(/\n\s*\n/).map(function(part){return '<p>'+escapeText(part).replace(/\n/g,'<br>')+'</p>';}).join('');}
function cleaned(html){
 var box=document.createElement('div');box.innerHTML=html;
 function clean(node){
  if(node.nodeType===3)return document.createTextNode(node.nodeValue);
  if(node.nodeType!==1)return document.createDocumentFragment();
  var tag=node.tagName.toLowerCase();
  if(tag==='div')tag='p';else if(tag==='font'||tag==='a')tag='';
  var target=tag&&allowed[tag]?document.createElement(tag):document.createDocumentFragment();
  if(tag==='span'&&node.getAttribute('class')==='rac-text-orange'&&target.nodeType===1)target.className='rac-text-orange';
  else if(tag==='span')target=document.createDocumentFragment();
  if(tag==='br')return target;
  Array.from(node.childNodes).forEach(function(child){target.appendChild(clean(child));});
  return target;
 }
 var out=document.createElement('div');Array.from(box.childNodes).forEach(function(n){out.appendChild(clean(n));});
 return out.innerHTML.trim();
}
var starting=source.value.trim();visual.innerHTML=starting?( /<\s*(?:p|h[234]|ul|ol|li|blockquote|mark|span|br|strong|b|em|i|u)\b/i.test(starting)?cleaned(starting):plainToHtml(starting)):'<p></p>';
function sync(){source.value=cleaned(visual.innerHTML);}
wrap.querySelectorAll('[data-command]').forEach(function(btn){btn.addEventListener('mousedown',function(e){e.preventDefault();});btn.addEventListener('click',function(){visual.focus();document.execCommand(btn.getAttribute('data-command'),false,null);sync();});});
var highlighter=wrap.querySelector('.rac-legal-highlight');
highlighter.addEventListener('mousedown',function(e){e.preventDefault();});
highlighter.addEventListener('click',function(){
 if(wrap.classList.contains('is-source'))return;
 var selection=window.getSelection();
 if(!selection||selection.rangeCount===0||selection.isCollapsed)return;
 var range=selection.getRangeAt(0);
 if(!visual.contains(range.commonAncestorContainer))return;
 var mark=document.createElement('mark');
 // extractContents zachová vnořené tučné/kurzívu i při výběru přes více prvků.
 var fragment=range.extractContents();
 if(!fragment.textContent.trim()){range.insertNode(fragment);return;}
 mark.appendChild(fragment);range.insertNode(mark);
 selection.removeAllRanges();visual.normalize();sync();
});
var colorButton=wrap.querySelector('.rac-legal-text-color');
colorButton.addEventListener('mousedown',function(e){e.preventDefault();});
colorButton.addEventListener('click',function(){
 if(wrap.classList.contains('is-source'))return;
 var selection=window.getSelection();
 if(!selection||selection.rangeCount===0||selection.isCollapsed)return;
 var range=selection.getRangeAt(0);
 if(!visual.contains(range.commonAncestorContainer))return;
 var span=document.createElement('span');span.className='rac-text-orange';
 var fragment=range.extractContents();
 if(!fragment.textContent.trim()){range.insertNode(fragment);return;}
 span.appendChild(fragment);range.insertNode(span);
 selection.removeAllRanges();visual.normalize();sync();
});
wrap.querySelector('.rac-legal-block').addEventListener('change',function(){visual.focus();document.execCommand('formatBlock',false,this.value);sync();});
visual.addEventListener('paste',function(event){event.preventDefault();var text=(event.clipboardData||window.clipboardData).getData('text/plain');document.execCommand('insertText',false,text);});
visual.addEventListener('input',sync);
toggle.addEventListener('click',function(){if(wrap.classList.contains('is-source')){visual.innerHTML=cleaned(source.value);wrap.classList.remove('is-source');toggle.textContent='HTML';}else{sync();wrap.classList.add('is-source');toggle.textContent='Vizuální editor';}});
var form=wrap.closest('form');if(form)form.addEventListener('submit',function(){if(!wrap.classList.contains('is-source'))sync();else source.value=cleaned(source.value);});
})();
</script>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
