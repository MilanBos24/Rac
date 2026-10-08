<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';

require_min_role('admin');

$errors = array();
$ok = null;

$csrf = csrf_token('csrf_languages');

function language_code_normalize(string $code): string
{
    return strtolower(trim($code));
}

function language_code_valid(string $code): bool
{
    return (bool)preg_match('/^[a-z]{2,8}(?:-[a-z0-9]{2,8})*$/', $code);
}

function language_exists(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('
        SELECT id, code, name, native_name, locale, flag_path, is_default, active, sort_order
        FROM languages
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(array(':id' => $id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function language_count(PDO $pdo): int
{
    return (int)$pdo->query('SELECT COUNT(*) FROM languages')->fetchColumn();
}

function next_language_order(PDO $pdo): int
{
    $max = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM languages')->fetchColumn();
    return $max + 10;
}


function language_flag_upload(array $file, array &$errors): ?string
{
    $error = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = 'Nahrávání vlajky se nepodařilo.';
        return null;
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Nahraný soubor vlajky není platný upload.';
        return null;
    }

    $size = isset($file['size']) ? (int)$file['size'] : 0;

    if ($size <= 0 || $size > 1024 * 1024) {
        $errors[] = 'Vlajka může mít maximálně 1 MB.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);

    $allowed = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    );

    if (!isset($allowed[$mime])) {
        $errors[] = 'Vlajka musí být JPG, PNG nebo WEBP.';
        return null;
    }

    $dir = __DIR__ . '/uploads/languages';

    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        $errors[] = 'Nepodařilo se vytvořit složku uploads/languages.';
        return null;
    }

    $filename = 'lang_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $absolute = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $absolute)) {
        $errors[] = 'Vlajku se nepodařilo uložit.';
        return null;
    }

    @chmod($absolute, 0644);

    return 'uploads/languages/' . $filename;
}

function language_flag_delete(?string $path): void
{
    $path = trim((string)$path);

    if ($path === '' || strpos($path, 'uploads/languages/') !== 0) {
        return;
    }

    $absolute = __DIR__ . '/' . $path;

    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

function language_flag_admin_url(?string $path): string
{
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    return $path;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!csrf_valid('csrf_languages', $postedCsrf)) {
        $errors[] = 'Neplatný bezpečnostní token.';
    }

    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

    if (!$errors && $action === 'add') {
        $code = language_code_normalize(isset($_POST['code']) ? (string)$_POST['code'] : '');
        $name = trim(isset($_POST['name']) ? (string)$_POST['name'] : '');
        $nativeName = trim(isset($_POST['native_name']) ? (string)$_POST['native_name'] : '');
        $locale = trim(isset($_POST['locale']) ? (string)$_POST['locale'] : '');
        $active = isset($_POST['active']) ? 1 : 0;
        $makeDefault = isset($_POST['is_default']) ? 1 : 0;

        $sortRaw = trim(isset($_POST['sort_order']) ? (string)$_POST['sort_order'] : '');
        $sortOrder = $sortRaw === '' ? next_language_order($pdo) : max(0, (int)$sortRaw);

        $flagPath = null;

        if (isset($_FILES['flag']) && is_array($_FILES['flag'])) {
            $flagPath = language_flag_upload($_FILES['flag'], $errors);
        }

        if (!language_code_valid($code)) {
            $errors[] = 'Kód jazyka musí mít tvar např. cs, en, fr nebo pt-br.';
        }

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Zadejte název jazyka pro administraci (max. 100 znaků).';
        }

        if ($nativeName === '' || mb_strlen($nativeName) > 100) {
            $errors[] = 'Zadejte vlastní název jazyka (max. 100 znaků).';
        }

        if ($locale !== '' && mb_strlen($locale) > 20) {
            $errors[] = 'Locale může mít maximálně 20 znaků.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM languages WHERE code = :code LIMIT 1');
            $stmt->execute(array(':code' => $code));

            if ($stmt->fetch()) {
                $errors[] = 'Jazyk s tímto kódem už existuje.';
            }
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();

                if ($makeDefault) {
                    $pdo->exec('UPDATE languages SET is_default = 0');
                    $active = 1;
                }

                $stmt = $pdo->prepare('
                    INSERT INTO languages
                    (code, name, native_name, locale, flag_path, is_default, active, sort_order)
                    VALUES
                    (:code, :name, :native_name, :locale, :flag_path, :is_default, :active, :sort_order)
                ');
                $stmt->execute(array(
                    ':code' => $code,
                    ':name' => $name,
                    ':native_name' => $nativeName,
                    ':locale' => $locale !== '' ? $locale : null,
                    ':flag_path' => $flagPath,
                    ':is_default' => $makeDefault,
                    ':active' => $active,
                    ':sort_order' => $sortOrder,
                ));

                $pdo->commit();
                $ok = 'Jazyková mutace byla přidána.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($flagPath !== null) {
                    language_flag_delete($flagPath);
                }

                $errors[] = 'Jazyk se nepodařilo uložit.';
            }
        }
    }

    if (!$errors && $action === 'update') {
        $id = isset($_POST['language_id']) ? (int)$_POST['language_id'] : 0;
        $language = $id > 0 ? language_exists($pdo, $id) : null;

        if (!$language) {
            $errors[] = 'Jazyk nebyl nalezen.';
        } else {
            $code = language_code_normalize(isset($_POST['code']) ? (string)$_POST['code'] : '');
            $name = trim(isset($_POST['name']) ? (string)$_POST['name'] : '');
            $nativeName = trim(isset($_POST['native_name']) ? (string)$_POST['native_name'] : '');
            $locale = trim(isset($_POST['locale']) ? (string)$_POST['locale'] : '');
            $sortOrder = max(0, (int)(isset($_POST['sort_order']) ? $_POST['sort_order'] : 0));
            $active = isset($_POST['active']) ? 1 : 0;
            $removeFlag = isset($_POST['remove_flag']) ? 1 : 0;
            $newFlagPath = null;

            if (isset($_FILES['flag']) && is_array($_FILES['flag'])) {
                $newFlagPath = language_flag_upload($_FILES['flag'], $errors);
            }

            if (!language_code_valid($code)) {
                $errors[] = 'Kód jazyka není platný.';
            }

            if ($name === '' || mb_strlen($name) > 100) {
                $errors[] = 'Název jazyka není platný.';
            }

            if ($nativeName === '' || mb_strlen($nativeName) > 100) {
                $errors[] = 'Vlastní název jazyka není platný.';
            }

            if ($locale !== '' && mb_strlen($locale) > 20) {
                $errors[] = 'Locale může mít maximálně 20 znaků.';
            }

            if ((int)$language['is_default'] === 1 && $active !== 1) {
                $active = 1;
                $errors[] = 'Výchozí jazyk nelze deaktivovat. Nejprve nastavte jiný výchozí jazyk.';
            }

            if (!$errors) {
                $stmt = $pdo->prepare('
                    SELECT id
                    FROM languages
                    WHERE code = :code AND id != :id
                    LIMIT 1
                ');
                $stmt->execute(array(
                    ':code' => $code,
                    ':id' => $id,
                ));

                if ($stmt->fetch()) {
                    $errors[] = 'Jiná jazyková mutace už tento kód používá.';
                }
            }

            if (!$errors) {
                $oldFlagPath = isset($language['flag_path']) ? trim((string)$language['flag_path']) : '';
                $effectiveFlagPath = $oldFlagPath;

                if ($removeFlag) {
                    $effectiveFlagPath = '';
                }

                if ($newFlagPath !== null) {
                    $effectiveFlagPath = $newFlagPath;
                }

                try {
                    $stmt = $pdo->prepare('
                        UPDATE languages
                        SET code = :code,
                            name = :name,
                            native_name = :native_name,
                            locale = :locale,
                            flag_path = :flag_path,
                            active = :active,
                            sort_order = :sort_order
                        WHERE id = :id
                        LIMIT 1
                    ');
                    $stmt->execute(array(
                        ':code' => $code,
                        ':name' => $name,
                        ':native_name' => $nativeName,
                        ':locale' => $locale !== '' ? $locale : null,
                        ':flag_path' => $effectiveFlagPath !== '' ? $effectiveFlagPath : null,
                        ':active' => $active,
                        ':sort_order' => $sortOrder,
                        ':id' => $id,
                    ));

                    if (
                        $oldFlagPath !== '' &&
                        ($removeFlag || ($newFlagPath !== null && $newFlagPath !== $oldFlagPath))
                    ) {
                        language_flag_delete($oldFlagPath);
                    }

                    $ok = 'Jazyková mutace byla upravena.';
                } catch (Throwable $e) {
                    if ($newFlagPath !== null) {
                        language_flag_delete($newFlagPath);
                    }

                    $errors[] = 'Změny se nepodařilo uložit.';
                }
            } elseif ($newFlagPath !== null) {
                language_flag_delete($newFlagPath);
            }
        }
    }

    if (!$errors && $action === 'set_default') {
        $id = isset($_POST['language_id']) ? (int)$_POST['language_id'] : 0;
        $language = $id > 0 ? language_exists($pdo, $id) : null;

        if (!$language) {
            $errors[] = 'Jazyk nebyl nalezen.';
        } else {
            try {
                $pdo->beginTransaction();

                $pdo->exec('UPDATE languages SET is_default = 0');

                $stmt = $pdo->prepare('
                    UPDATE languages
                    SET is_default = 1, active = 1
                    WHERE id = :id
                    LIMIT 1
                ');
                $stmt->execute(array(':id' => $id));

                $pdo->commit();
                $ok = 'Výchozí jazyk byl změněn na ' . $language['native_name'] . '.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Výchozí jazyk se nepodařilo změnit.';
            }
        }
    }

    if (!$errors && $action === 'delete') {
        $id = isset($_POST['language_id']) ? (int)$_POST['language_id'] : 0;
        $language = $id > 0 ? language_exists($pdo, $id) : null;

        if (!$language) {
            $errors[] = 'Jazyk nebyl nalezen.';
        } elseif ((int)$language['is_default'] === 1) {
            $errors[] = 'Výchozí jazyk nelze smazat. Nejprve nastavte jinou výchozí mutaci.';
        } elseif (language_count($pdo) <= 1) {
            $errors[] = 'Poslední jazykovou mutaci nelze smazat.';
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM languages WHERE id = :id LIMIT 1');
                $stmt->execute(array(':id' => $id));

                if (!empty($language['flag_path'])) {
                    language_flag_delete((string)$language['flag_path']);
                }

                $ok = 'Jazyková mutace byla smazána.';
            } catch (PDOException $e) {
                $errors[] = 'Jazyk nelze smazat, protože je k němu navázaný obsah. Místo smazání jej deaktivujte.';
            }
        }
    }
}

$languages = $pdo->query('
    SELECT id, code, name, native_name, locale, flag_path, is_default, active, sort_order, created, updated
    FROM languages
    ORDER BY sort_order ASC, id ASC
')->fetchAll(PDO::FETCH_ASSOC);

$activeCount = 0;
$defaultLanguage = null;

foreach ($languages as $language) {
    if ((int)$language['active'] === 1) {
        $activeCount++;
    }

    if ((int)$language['is_default'] === 1) {
        $defaultLanguage = $language;
    }
}

$pageTitle = 'Jazyky';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
    <div class="page-header">
        <h1>Jazyky</h1>
        <p>Správa jazykových mutací webu RAC</p>
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

    <div class="grid grid-3">
        <div class="stat-card">
            <div class="label">Jazykových mutací</div>
            <div class="value"><?= count($languages) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Aktivních</div>
            <div class="value"><?= $activeCount ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Výchozí jazyk</div>
            <div class="value" style="font-size:22px;">
                <?= $defaultLanguage ? h($defaultLanguage['native_name']) : '—' ?>
            </div>
        </div>
    </div>

    <section class="card" style="margin-top:22px;">
        <div class="card-header">
            <h2>Přidat jazykovou mutaci</h2>
        </div>

        <form method="post" enctype="multipart/form-data">
            <div class="card-body">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="add">

                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Kód jazyka</label>
                        <input class="form-control" type="text" name="code" maxlength="32" required placeholder="např. fr">
                        <div class="form-help">Použije se v URL a systému. Např. cs, en, fr, pt-br.</div>
                    </div>

                    <div class="form-group">
                        <label>Název v administraci</label>
                        <input class="form-control" type="text" name="name" maxlength="100" required placeholder="Francouzština">
                    </div>

                    <div class="form-group">
                        <label>Vlastní název jazyka</label>
                        <input class="form-control" type="text" name="native_name" maxlength="100" required placeholder="Français">
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Locale</label>
                        <input class="form-control" type="text" name="locale" maxlength="20" placeholder="fr_FR">
                        <div class="form-help">Volitelné. Později pro datumy, formátování apod.</div>
                    </div>

                    <div class="form-group">
                        <label>Vlajka</label>
                        <input class="form-control" type="file" name="flag" accept="image/jpeg,image/png,image/webp">
                        <div class="form-help">Volitelné. JPG, PNG nebo WEBP; max. 1 MB. Pokud není nahraná, web může použít vestavěnou CSS vlajku pro známý kód.</div>
                    </div>

                    <div class="form-group">
                        <label>Pořadí</label>
                        <input class="form-control" type="number" name="sort_order" min="0" step="1" placeholder="automaticky">

                        <label style="font-weight:400;margin-top:10px;">
                            <input type="checkbox" name="active" value="1" checked>
                            Aktivní
                        </label>
                        <label style="font-weight:400;">
                            <input type="checkbox" name="is_default" value="1">
                            Nastavit jako výchozí
                        </label>
                    </div>
                </div>
            </div>

            <div class="card-footer" style="text-align:right;">
                <button class="btn btn-primary" type="submit">Přidat jazyk</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Aktuální jazykové mutace</h2>
        </div>

        <div class="card-body">
            <?php if (!$languages): ?>
                <div class="alert alert-info">Nejsou založeny žádné jazyky.</div>
            <?php else: ?>
                <div class="language-list">
                    <?php foreach ($languages as $language): ?>
                        <div class="language-row<?= (int)$language['active'] !== 1 ? ' is-inactive' : '' ?>">
                            <div class="language-summary">
                                <div class="language-code">
                                    <?= h(strtoupper((string)$language['code'])) ?>
                                </div>
                                <div>
                                    <h3 class="language-name">
                                        <span><?= h($language['native_name']) ?></span>
                                        <?php if (!empty($language['flag_path'])): ?>
                                            <img
                                                class="language-flag-inline"
                                                src="<?= h(language_flag_admin_url((string)$language['flag_path'])) ?>"
                                                alt="<?= h($language['native_name']) ?>"
                                            >
                                        <?php endif; ?>
                                    </h3>
                                    <p>
                                        <?= h($language['name']) ?>
                                        <?php if (!empty($language['locale'])): ?>
                                            · <?= h($language['locale']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="language-badges">
                                    <?php if ((int)$language['is_default'] === 1): ?>
                                        <span class="badge badge-primary">Výchozí</span>
                                    <?php endif; ?>

                                    <?php if ((int)$language['active'] === 1): ?>
                                        <span class="badge badge-success">Aktivní</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">Neaktivní</span>
                                    <?php endif; ?>

                                    <span class="badge badge-muted">Pořadí <?= (int)$language['sort_order'] ?></span>
                                </div>
                            </div>

                            <details class="language-edit">
                                <summary class="language-edit-toggle">
                                    <span>Upravit</span>
                                    <span class="language-edit-icon" aria-hidden="true">✎</span>
                                </summary>

                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="language_id" value="<?= (int)$language['id'] ?>">

                                    <div class="grid grid-3">
                                        <div class="form-group">
                                            <label>Kód</label>
                                            <input class="form-control" type="text" name="code" maxlength="32" required value="<?= h($language['code']) ?>">
                                        </div>

                                        <div class="form-group">
                                            <label>Název v administraci</label>
                                            <input class="form-control" type="text" name="name" maxlength="100" required value="<?= h($language['name']) ?>">
                                        </div>

                                        <div class="form-group">
                                            <label>Vlastní název</label>
                                            <input class="form-control" type="text" name="native_name" maxlength="100" required value="<?= h($language['native_name']) ?>">
                                        </div>
                                    </div>

                                    <div class="grid grid-3">
                                        <div class="form-group">
                                            <label>Locale</label>
                                            <input class="form-control" type="text" name="locale" maxlength="20" value="<?= h($language['locale']) ?>">
                                        </div>

                                        <div class="form-group">
                                            <label>Vlajka</label>
                                            <?php if (!empty($language['flag_path'])): ?>
                                                <div class="language-current-flag">
                                                    <img src="<?= h(language_flag_admin_url((string)$language['flag_path'])) ?>" alt="">
                                                    <label style="font-weight:400;">
                                                        <input type="checkbox" name="remove_flag" value="1">
                                                        Odstranit vlastní vlajku
                                                    </label>
                                                </div>
                                            <?php endif; ?>
                                            <input class="form-control" type="file" name="flag" accept="image/jpeg,image/png,image/webp">
                                            <div class="form-help">Nahráním nové vlajky se původní nahradí.</div>
                                        </div>

                                        <div class="form-group">
                                            <label>Pořadí</label>
                                            <input class="form-control" type="number" name="sort_order" min="0" step="1" value="<?= (int)$language['sort_order'] ?>">

                                            <label style="font-weight:400;margin-top:10px;">
                                                <input
                                                    type="checkbox"
                                                    name="active"
                                                    value="1"
                                                    <?= (int)$language['active'] === 1 ? 'checked' : '' ?>
                                                    <?= (int)$language['is_default'] === 1 ? 'disabled' : '' ?>
                                                >
                                                Aktivní
                                            </label>

                                            <?php if ((int)$language['is_default'] === 1): ?>
                                                <input type="hidden" name="active" value="1">
                                                <div class="form-help">Výchozí jazyk musí zůstat aktivní.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="inline-actions">
                                        <button class="btn btn-primary" type="submit">Uložit změny</button>
                                    </div>
                                </form>

                                <div class="language-danger-zone">
                                    <?php if ((int)$language['is_default'] !== 1): ?>
                                        <form method="post">
                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                            <input type="hidden" name="action" value="set_default">
                                            <input type="hidden" name="language_id" value="<?= (int)$language['id'] ?>">
                                            <button class="btn btn-light" type="submit">Nastavit jako výchozí</button>
                                        </form>

                                        <form method="post" onsubmit="return confirm('Opravdu chcete jazykovou mutaci <?= h($language['native_name']) ?> smazat? Pokud k ní budou navázané překlady, smazání nebude povoleno.');">
                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="language_id" value="<?= (int)$language['id'] ?>">
                                            <button class="btn btn-danger" type="submit">Smazat jazyk</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="form-help">Výchozí jazyk nelze smazat. Nejprve nastavte jiný výchozí jazyk.</div>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="alert alert-info">
        <strong>Poznámka:</strong>
        V této fázi spravuje stránka databázový seznam jazyků.
        V dalším kroku napojíme veřejný web na tabulku <code>languages</code>,
        aby aktivace, pořadí a nové jazykové mutace automaticky ovlivnily přepínač jazyků na webu.
    </div>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>