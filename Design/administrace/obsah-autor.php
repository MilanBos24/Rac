<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_once __DIR__ . '/config/cms.php';

require_min_role('admin');

$page = cms_get_page($pdo, 'author');

if (!$page) {
    http_response_code(500);
    die('CMS stránka "author" nebyla nalezena. Nejprve importujte database/005_cms_author.sql.');
}

$languages = cms_get_active_languages($pdo);

if (!$languages) {
    http_response_code(500);
    die('Nejsou dostupné žádné aktivní jazyky.');
}

$pageTranslations = cms_get_page_translations($pdo, (int)$page['id']);
$sections = cms_get_page_sections($pdo, (int)$page['id']);
$fieldValues = cms_get_field_translations($pdo, (int)$page['id']);

$languageIds = array();
$fieldIds = array();

foreach ($languages as $language) {
    $languageIds[(int)$language['id']] = true;
}

foreach ($sections as $section) {
    foreach ($section['fields'] as $field) {
        $fieldIds[(int)$field['id']] = true;
    }
}

$errors = array();
$ok = null;
$csrf = csrf_token('csrf_cms_author');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!csrf_valid('csrf_cms_author', $postedCsrf)) {
        $errors[] = 'Neplatný bezpečnostní token.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $postedPage = isset($_POST['page']) && is_array($_POST['page'])
                ? $_POST['page']
                : array();

            $stmtPage = $pdo->prepare(
                'INSERT INTO page_translations
                    (page_id, language_id, page_title, slug, meta_title, meta_description)
                 VALUES
                    (:page_id, :language_id, :page_title, :slug, :meta_title, :meta_description)
                 ON DUPLICATE KEY UPDATE
                    page_title = VALUES(page_title),
                    slug = VALUES(slug),
                    meta_title = VALUES(meta_title),
                    meta_description = VALUES(meta_description),
                    updated = CURRENT_TIMESTAMP'
            );

            foreach ($languages as $language) {
                $languageId = (int)$language['id'];
                $data = isset($postedPage[$languageId]) && is_array($postedPage[$languageId])
                    ? $postedPage[$languageId]
                    : array();

                $pageTitleValue = trim(isset($data['page_title']) ? (string)$data['page_title'] : '');
                $slugValue = trim(isset($data['slug']) ? (string)$data['slug'] : '');
                $metaTitle = trim(isset($data['meta_title']) ? (string)$data['meta_title'] : '');
                $metaDescription = trim(isset($data['meta_description']) ? (string)$data['meta_description'] : '');

                $stmtPage->execute(array(
                    ':page_id' => (int)$page['id'],
                    ':language_id' => $languageId,
                    ':page_title' => $pageTitleValue !== '' ? $pageTitleValue : null,
                    ':slug' => $slugValue !== '' ? $slugValue : null,
                    ':meta_title' => $metaTitle !== '' ? $metaTitle : null,
                    ':meta_description' => $metaDescription !== '' ? $metaDescription : null,
                ));
            }

            $postedFields = isset($_POST['field']) && is_array($_POST['field'])
                ? $_POST['field']
                : array();

            $stmtField = $pdo->prepare(
                'INSERT INTO page_section_field_translations
                    (field_id, language_id, value)
                 VALUES
                    (:field_id, :language_id, :value)
                 ON DUPLICATE KEY UPDATE
                    value = VALUES(value),
                    updated = CURRENT_TIMESTAMP'
            );

            foreach ($fieldIds as $fieldId => $_) {
                foreach ($languageIds as $languageId => $_language) {
                    $value = '';

                    if (
                        isset($postedFields[$fieldId]) &&
                        is_array($postedFields[$fieldId]) &&
                        isset($postedFields[$fieldId][$languageId])
                    ) {
                        $value = trim((string)$postedFields[$fieldId][$languageId]);
                    }

                    $stmtField->execute(array(
                        ':field_id' => (int)$fieldId,
                        ':language_id' => (int)$languageId,
                        ':value' => $value !== '' ? $value : null,
                    ));
                }
            }

            $pdo->commit();
            $ok = 'Obsah stránky O autorovi byl uložen.';

            $pageTranslations = cms_get_page_translations($pdo, (int)$page['id']);
            $fieldValues = cms_get_field_translations($pdo, (int)$page['id']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Obsah se nepodařilo uložit.';
        }
    }
}

$pageTitle = 'O autorovi';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';

$firstLanguageId = (int)$languages[0]['id'];
?>
<main class="admin-content">
    <div class="page-header">
        <h1>O autorovi</h1>
        <p>Textový obsah a SEO údaje podle aktivních jazykových mutací</p>
    </div>

    <?php if ($ok): ?>
        <div class="alert alert-success"><?= h($ok) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

        <section class="card cms-language-card" data-cms-card>
            <div class="card-header">
                <h2>SEO a název stránky</h2>
            </div>

            <div class="card-body">
                <div class="cms-language-tabs">
                    <?php foreach ($languages as $language): ?>
                        <?php
                            $languageId = (int)$language['id'];
                            $translation = isset($pageTranslations[$languageId])
                                ? $pageTranslations[$languageId]
                                : array();

                            $complete = trim((string)($translation['meta_title'] ?? '')) !== ''
                                && trim((string)($translation['meta_description'] ?? '')) !== '';
                        ?>
                        <button
                            class="cms-language-tab<?= $languageId === $firstLanguageId ? ' is-active' : '' ?>"
                            type="button"
                            data-cms-tab="<?= $languageId ?>"
                        >
                            <span><?= h($language['native_name']) ?></span>
                            <span class="cms-language-state <?= $complete ? 'is-complete' : 'is-missing' ?>">
                                <?= $complete ? '✓' : '!' ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($languages as $language): ?>
                    <?php
                        $languageId = (int)$language['id'];
                        $translation = isset($pageTranslations[$languageId])
                            ? $pageTranslations[$languageId]
                            : array();
                    ?>
                    <div
                        class="cms-language-panel<?= $languageId === $firstLanguageId ? ' is-active' : '' ?>"
                        data-cms-panel="<?= $languageId ?>"
                    >
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Název stránky</label>
                                <input
                                    class="form-control"
                                    type="text"
                                    name="page[<?= $languageId ?>][page_title]"
                                    value="<?= h($translation['page_title'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label>URL slug</label>
                                <input
                                    class="form-control"
                                    type="text"
                                    name="page[<?= $languageId ?>][slug]"
                                    value="<?= h($translation['slug'] ?? '') ?>"
                                    placeholder="o-autorovi"
                                >
                            </div>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Meta title</label>
                                <input
                                    class="form-control"
                                    type="text"
                                    name="page[<?= $languageId ?>][meta_title]"
                                    value="<?= h($translation['meta_title'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label>Meta description</label>
                                <textarea
                                    class="form-control"
                                    name="page[<?= $languageId ?>][meta_description]"
                                    rows="3"
                                ><?= h($translation['meta_description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php foreach ($sections as $section): ?>
            <section class="card cms-language-card" data-cms-card>
                <div class="card-header">
                    <h2><?= h($section['label']) ?></h2>
                </div>

                <div class="card-body">
                    <div class="cms-language-tabs">
                        <?php foreach ($languages as $language): ?>
                            <?php
                                $languageId = (int)$language['id'];
                                $complete = cms_section_complete($section, $languageId, $fieldValues);
                            ?>
                            <button
                                class="cms-language-tab<?= $languageId === $firstLanguageId ? ' is-active' : '' ?>"
                                type="button"
                                data-cms-tab="<?= $languageId ?>"
                            >
                                <span><?= h($language['native_name']) ?></span>
                                <span class="cms-language-state <?= $complete ? 'is-complete' : 'is-missing' ?>">
                                    <?= $complete ? '✓' : '!' ?>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($languages as $language): ?>
                        <?php $languageId = (int)$language['id']; ?>
                        <div
                            class="cms-language-panel<?= $languageId === $firstLanguageId ? ' is-active' : '' ?>"
                            data-cms-panel="<?= $languageId ?>"
                        >
                            <?php foreach ($section['fields'] as $field): ?>
                                <?php
                                    $value = isset($fieldValues[$field['id']][$languageId])
                                        ? (string)$fieldValues[$field['id']][$languageId]
                                        : '';
                                ?>
                                <div class="form-group">
                                    <label>
                                        <?= h($field['label']) ?>
                                        <?php if ($field['required']): ?>
                                            <span class="cms-required">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php if ($field['type'] === 'textarea'): ?>
                                        <textarea
                                            class="form-control"
                                            name="field[<?= (int)$field['id'] ?>][<?= $languageId ?>]"
                                            rows="6"
                                        ><?= h($value) ?></textarea>
                                    <?php else: ?>
                                        <input
                                            class="form-control"
                                            type="text"
                                            name="field[<?= (int)$field['id'] ?>][<?= $languageId ?>]"
                                            value="<?= h($value) ?>"
                                        >
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="cms-savebar">
            <div>
                <strong>O autorovi</strong>
                <span>Změny se projeví na veřejném webu po uložení.</span>
            </div>
            <button class="btn btn-primary" type="submit">Uložit obsah</button>
        </div>
    </form>
</main>

<script>
(function () {
    var cards = document.querySelectorAll('[data-cms-card]');

    cards.forEach(function (card) {
        var tabs = card.querySelectorAll('[data-cms-tab]');
        var panels = card.querySelectorAll('[data-cms-panel]');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var id = tab.getAttribute('data-cms-tab');

                tabs.forEach(function (item) {
                    item.classList.toggle('is-active', item === tab);
                });

                panels.forEach(function (panel) {
                    panel.classList.toggle(
                        'is-active',
                        panel.getAttribute('data-cms-panel') === id
                    );
                });
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/inc/footer.class.php'; ?>