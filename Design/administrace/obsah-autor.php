<?php
/** RAC | O autorovi | Porto/BOS24 | verze 2026-10-12-23.15 */
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_once __DIR__.'/config/cms.php';
require_min_role('admin');
$page = cms_get_page($pdo, 'author');
if (!$page) { http_response_code(500); exit('CMS stránka "author" nebyla nalezena. Nejprve importujte database/005_cms_author.sql.'); }
$languages = cms_get_active_languages($pdo);
if (!$languages) { http_response_code(500); exit('Nejsou dostupné žádné aktivní jazyky.'); }
$pageTranslations = cms_get_page_translations($pdo, (int)$page['id']);
$sections = cms_get_page_sections($pdo, (int)$page['id']);
$fieldValues = cms_get_field_translations($pdo, (int)$page['id']);
$languageIds = [];
$fieldIds = [];
foreach ($languages as $language) { $languageIds[(int)$language['id']] = true; }
foreach ($sections as $section) { foreach ($section['fields'] as $field) { $fieldIds[(int)$field['id']] = true; } }
$errors = [];
$ok = null;
$csrf = csrf_token('csrf_cms_author');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    if (!csrf_valid('csrf_cms_author', $postedCsrf)) { $errors[] = 'Neplatný bezpečnostní token.'; }
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $postedPage = isset($_POST['page']) && is_array($_POST['page']) ? $_POST['page'] : [];
            foreach ($languages as $language) {
                $languageId = (int)$language['id'];
                $data = isset($postedPage[$languageId]) && is_array($postedPage[$languageId]) ? $postedPage[$languageId] : [];
                $pageTitle = trim(isset($data['page_title']) ? (string)$data['page_title'] : '');
                $metaTitle = trim(isset($data['meta_title']) ? (string)$data['meta_title'] : '');
                $metaDescription = trim(isset($data['meta_description']) ? (string)$data['meta_description'] : '');
                $stmt = $pdo->prepare('INSERT INTO page_translations (page_id, language_id, page_title, meta_title, meta_description) VALUES (:page_id, :language_id, :page_title, :meta_title, :meta_description) ON DUPLICATE KEY UPDATE page_title=VALUES(page_title), meta_title=VALUES(meta_title), meta_description=VALUES(meta_description), updated=CURRENT_TIMESTAMP');
                $stmt->execute([
                    ':page_id' => (int)$page['id'], ':language_id' => $languageId,
                    ':page_title' => $pageTitle !== '' ? $pageTitle : null,
                    ':meta_title' => $metaTitle !== '' ? $metaTitle : null,
                    ':meta_description' => $metaDescription !== '' ? $metaDescription : null,
                ]);
            }
            $postedFields = isset($_POST['field']) && is_array($_POST['field']) ? $_POST['field'] : [];
            $stmtField = $pdo->prepare('INSERT INTO page_section_field_translations (field_id, language_id, value) VALUES (:field_id, :language_id, :value) ON DUPLICATE KEY UPDATE value=VALUES(value), updated=CURRENT_TIMESTAMP');
            foreach ($fieldIds as $fieldId => $_) {
                foreach ($languageIds as $languageId => $_language) {
                    $value = '';
                    if (isset($postedFields[$fieldId]) && is_array($postedFields[$fieldId]) && isset($postedFields[$fieldId][$languageId])) { $value = trim((string)$postedFields[$fieldId][$languageId]); }
                    $stmtField->execute([':field_id'=>(int)$fieldId, ':language_id'=>(int)$languageId, ':value'=>$value!==''?$value:null]);
                }
            }
            $pdo->commit();
            $ok = 'Obsah stránky o autorovi byl uložen.';
            $pageTranslations = cms_get_page_translations($pdo, (int)$page['id']);
            $fieldValues = cms_get_field_translations($pdo, (int)$page['id']);
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = 'Obsah se nepodařilo uložit.';
        }
    }
}

// Stejné společné soubory a výchozí proměnné jako ve funkčním Billingu.
$racUsePortoLayout = true;
$title = 'O autorovi | RAC Administrace';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$viewer = function_exists('current_user_row') ? current_user_row() : null;
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/obsah-uvod.php';
$menuGroups = [
    ['Přehled', [['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],
    ['Obsah webu', [
        ['Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'],
        ['O autorovi','/administrace/obsah-autor.php','icons icon-note'],
        ['Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope'],
        ['Jazyky','/administrace/jazyky.php','icons icon-globe'],
    ]],
    ['E-shop', [['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],
    ['Billing', [
        ['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'],
        ['Produkty','/administrace/billing/admin-produkty.php','icons icon-basket'],
        ['Platby','/administrace/billing/admin-platby.php','el el-credit-card'],
        ['Předplatné','/administrace/billing/admin-predplatne.php','icons icon-refresh'],
        ['Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'],
        ['Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench'],
    ]],
    ['Administrace', [
        ['Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'],
        ['Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people'],
    ]],
];
$firstLanguageId = (int)$languages[0]['id'];
$racTabGroup = 'seo';
include __DIR__.'/inc/header.class.php';
include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header">
  <h2>O autorovi</h2>
  <div class="right-wrapper text-end">
    <ol class="breadcrumbs"><li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li><li><span>Obsah webu</span></li><li><span>O autorovi</span></li></ol>
    <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
  </div>
</header>
<p class="mb-3 mt-3">Textový obsah a SEO údaje podle aktivních jazykových mutací</p>
<?php if ($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<section class="card mb-4">
<header class="card-header"><h2 class="card-title">SEO a název stránky</h2></header>
<div class="card-body">
<div class="tabs">
<ul class="nav nav-tabs tabs-primary" role="tablist">
<?php foreach ($languages as $language):
  $languageId = (int)$language['id'];
  $translation = $pageTranslations[$languageId] ?? [];
  $complete = trim((string)($translation['meta_title'] ?? '')) !== '' && trim((string)($translation['meta_description'] ?? '')) !== '';
?>
<li class="nav-item"><button class="nav-link<?= $languageId === $firstLanguageId?' active':'' ?>" type="button" id="tab-<?= h($racTabGroup) ?>-<?= $languageId ?>" data-bs-toggle="tab" data-bs-target="#panel-<?= h($racTabGroup) ?>-<?= $languageId ?>" role="tab" aria-controls="panel-<?= h($racTabGroup) ?>-<?= $languageId ?>" aria-selected="<?= $languageId === $firstLanguageId ? 'true' : 'false' ?>"><span><?= h($language['native_name']) ?></span> <small><?= $complete?'✓':'!' ?></small></button></li>
<?php endforeach; ?>
</ul>
<div class="tab-content">
<?php foreach ($languages as $language):
  $languageId = (int)$language['id'];
  $translation = $pageTranslations[$languageId] ?? [];
?>
<div class="tab-pane fade<?= $languageId === $firstLanguageId?' show active':'' ?>" id="panel-<?= h($racTabGroup) ?>-<?= $languageId ?>" role="tabpanel" aria-labelledby="tab-<?= h($racTabGroup) ?>-<?= $languageId ?>" tabindex="0">
<div class="row form-group pb-3">
 <div class="col-lg-6 mb-3"><label class="form-label">Název stránky</label><input class="form-control" type="text" name="page[<?= $languageId ?>][page_title]" value="<?= h($translation['page_title'] ?? '') ?>"></div>
 <div class="col-lg-6 mb-3"><label class="form-label">Meta title</label><input class="form-control" type="text" name="page[<?= $languageId ?>][meta_title]" value="<?= h($translation['meta_title'] ?? '') ?>"></div>
 <div class="col-12"><label class="form-label">Meta description</label><textarea class="form-control" name="page[<?= $languageId ?>][meta_description]" rows="3"><?= h($translation['meta_description'] ?? '') ?></textarea></div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
</section>
<?php foreach ($sections as $section): $racTabGroup = 'section-' . (int)$section['id']; ?>
<section class="card mb-4">
<header class="card-header"><h2 class="card-title"><?= h($section['label']) ?></h2></header>
<div class="card-body">
<div class="tabs">
<ul class="nav nav-tabs tabs-primary" role="tablist">
<?php foreach ($languages as $language):
    $languageId = (int)$language['id'];
    $complete = cms_section_complete($section,$languageId,$fieldValues);
?>
<li class="nav-item"><button class="nav-link<?= $languageId === $firstLanguageId?' active':'' ?>" type="button" id="tab-<?= h($racTabGroup) ?>-<?= $languageId ?>" data-bs-toggle="tab" data-bs-target="#panel-<?= h($racTabGroup) ?>-<?= $languageId ?>" role="tab" aria-controls="panel-<?= h($racTabGroup) ?>-<?= $languageId ?>" aria-selected="<?= $languageId === $firstLanguageId ? 'true' : 'false' ?>"><span><?= h($language['native_name']) ?></span> <small><?= $complete?'✓':'!' ?></small></button></li>
<?php endforeach; ?>
</ul>
<div class="tab-content">
<?php foreach ($languages as $language): $languageId = (int)$language['id']; ?>
<div class="tab-pane fade<?= $languageId === $firstLanguageId?' show active':'' ?>" id="panel-<?= h($racTabGroup) ?>-<?= $languageId ?>" role="tabpanel" aria-labelledby="tab-<?= h($racTabGroup) ?>-<?= $languageId ?>" tabindex="0">
<?php foreach ($section['fields'] as $field):
    $value = isset($fieldValues[$field['id']][$languageId]) ? (string)$fieldValues[$field['id']][$languageId] : '';
?>
<div class="form-group mb-3"><label class="form-label"><?= h($field['label']) ?><?php if ($field['required']): ?> <span class="text-danger">*</span><?php endif; ?></label>
<?php if ($field['type'] === 'textarea'): ?>
<textarea class="form-control" name="field[<?= (int)$field['id'] ?>][<?= $languageId ?>]" rows="4"><?= h($value) ?></textarea>
<?php else: ?>
<input class="form-control" type="text" name="field[<?= (int)$field['id'] ?>][<?= $languageId ?>]" value="<?= h($value) ?>">
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
</section>
<?php endforeach; ?>
<section class="card"><div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
<div><strong>O autorovi</strong><div class="text-muted small">Změny se projeví na veřejném webu po uložení.</div></div>
<button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit obsah</button>
</div></section>
</form>
</section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
