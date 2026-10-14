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

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Správa jazyků | RAC';
$viewer = current_user_row();
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
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
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/jazyky.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;

$pageTitle = 'Jazyky';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
  <header class="page-header">
    <h2>Správa jazyků</h2>
    <div class="right-wrapper text-end"><ol class="breadcrumbs"><li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li><li><span>Jazyky</span></li></ol></div>
  </header>
  <div class="row pt-3 mb-4">
    <div class="col-lg-4 col-md-6 mb-3">
      <section class="card card-featured-left card-featured-primary h-100">
        <div class="card-body">
          <div class="widget-summary">
            <div class="widget-summary-col">
              <div class="summary">
                <h4 class="title">Jazykové mutace</h4>
                <div class="info"><strong class="amount"><?= count($languages) ?></strong></div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
      <section class="card card-featured-left card-featured-success h-100">
        <div class="card-body">
          <div class="widget-summary">
            <div class="widget-summary-col">
              <div class="summary">
                <h4 class="title">Aktivní mutace</h4>
                <div class="info"><strong class="amount"><?= $activeCount ?></strong></div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
      <section class="card card-featured-left card-featured-warning h-100">
        <div class="card-body">
          <div class="widget-summary">
            <div class="widget-summary-col">
              <div class="summary">
                <h4 class="title">Výchozí jazyk</h4>
                <div class="info"><strong class="amount"><?= $defaultLanguage ? h($defaultLanguage['native_name']) : '—' ?></strong></div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
  <?php if ($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="tabs tabs-primary mb-4">
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#rac-lang-list" role="tab" aria-controls="rac-lang-list" aria-selected="true">Přehled jazyků</button></li>
      <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#rac-lang-new" role="tab" aria-controls="rac-lang-new" aria-selected="false">Nový jazyk</button></li>
    </ul>
    <div class="tab-content">
      <div id="rac-lang-list" class="tab-pane fade show active" role="tabpanel">
        <section class="card"><header class="card-header"><h2 class="card-title">Aktuální jazykové mutace</h2></header><div class="card-body">
          <?php if (!$languages): ?><div class="alert alert-info">Zatím nejsou založeny žádné jazyky.</div><?php endif; ?>
          <div class="row g-3">
          <?php foreach ($languages as $language): $lid=(int)$language['id']; $isDefault=(int)$language['is_default']===1; $isActive=(int)$language['active']===1; ?>
            <div class="col-12"><section class="card border <?= $isActive ? '' : 'opacity-75' ?>">
              <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                  <span class="badge bg-secondary fs-6"><?= h(strtoupper((string)$language['code'])) ?></span>
                  <?php if (!empty($language['flag_path'])): ?><img src="<?= h(language_flag_admin_url((string)$language['flag_path'])) ?>" alt="" style="width:32px;height:22px;object-fit:cover;border-radius:3px"><?php endif; ?>
                  <div class="me-auto"><strong><?= h($language['native_name']) ?></strong><div class="small text-muted"><?= h($language['name']) ?><?= !empty($language['locale']) ? ' · '.h($language['locale']) : '' ?></div></div>
                  <?php if ($isDefault): ?><span class="badge bg-primary">Výchozí</span><?php endif; ?>
                  <span class="badge <?= $isActive?'bg-success':'bg-secondary' ?>"><?= $isActive?'Aktivní':'Neaktivní' ?></span>
                  <span class="badge bg-light text-dark">Pořadí <?= (int)$language['sort_order'] ?></span>
                  <button class="btn btn-default btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#rac-lang-edit-<?= $lid ?>" aria-expanded="false" aria-controls="rac-lang-edit-<?= $lid ?>"><i class="fas fa-edit me-1"></i> Upravit</button>
                </div>
                <div class="collapse mt-3" id="rac-lang-edit-<?= $lid ?>"><div class="border-top pt-3">
                  <form method="post" enctype="multipart/form-data" class="mb-3">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="language_id" value="<?= $lid ?>">
                    <div class="row g-3">
                      <div class="col-12 col-md-4"><label class="form-label" for="code-<?= $lid ?>">Kód jazyka</label><input class="form-control" id="code-<?= $lid ?>" type="text" name="code" maxlength="32" required value="<?= h($language['code']) ?>"></div>
                      <div class="col-12 col-md-4"><label class="form-label" for="name-<?= $lid ?>">Název v administraci</label><input class="form-control" id="name-<?= $lid ?>" type="text" name="name" maxlength="100" required value="<?= h($language['name']) ?>"></div>
                      <div class="col-12 col-md-4"><label class="form-label" for="native-<?= $lid ?>">Vlastní název</label><input class="form-control" id="native-<?= $lid ?>" type="text" name="native_name" maxlength="100" required value="<?= h($language['native_name']) ?>"></div>
                      <div class="col-12 col-md-4"><label class="form-label" for="locale-<?= $lid ?>">Locale</label><input class="form-control" id="locale-<?= $lid ?>" type="text" name="locale" maxlength="20" value="<?= h($language['locale']) ?>"></div>
                      <div class="col-12 col-md-4"><label class="form-label" for="order-<?= $lid ?>">Pořadí</label><input class="form-control" id="order-<?= $lid ?>" type="number" name="sort_order" min="0" step="1" required value="<?= (int)$language['sort_order'] ?>"></div>
                      <div class="col-12 col-md-4"><label class="form-label" for="flag-<?= $lid ?>">Nová vlajka</label><input class="form-control" id="flag-<?= $lid ?>" type="file" name="flag" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG, PNG, WEBP do 1 MB</small></div>
                      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" id="active-<?= $lid ?>" value="1" <?= $isActive?'checked':'' ?> <?= $isDefault?'checked disabled':'' ?>><label class="form-check-label" for="active-<?= $lid ?>">Aktivní jazyk<?= $isDefault?' (výchozí jazyk nelze deaktivovat)':'' ?></label></div>
                      <?php if ($isDefault): ?><input type="hidden" name="active" value="1"><?php endif; ?>
                      <?php if (!empty($language['flag_path'])): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="removeflag-<?= $lid ?>" name="remove_flag" value="1"><label class="form-check-label" for="removeflag-<?= $lid ?>">Odstranit stávající vlajku</label></div><?php endif; ?></div>
                    </div>
                    <div class="text-end mt-3"><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit změny</button></div>
                  </form>
                  <div class="d-flex flex-wrap gap-2">
                    <?php if (!$isDefault): ?>
                      <form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="set_default"><input type="hidden" name="language_id" value="<?= $lid ?>"><button type="submit" class="btn btn-default btn-sm">Nastavit jako výchozí</button></form>
                      <form method="post" onsubmit="return confirm('Opravdu smazat tento jazyk?');"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="language_id" value="<?= $lid ?>"><button type="submit" class="btn btn-danger btn-sm">Smazat jazyk</button></form>
                    <?php else: ?><small class="text-muted">Pro smazání nejprve nastavte jiný výchozí jazyk.</small><?php endif; ?>
                  </div>
                </div></div>
              </div>
            </section></div>
          <?php endforeach; ?>
          </div>
        </div></section>
      </div>
      <div id="rac-lang-new" class="tab-pane fade" role="tabpanel">
        <section class="card"><header class="card-header"><h2 class="card-title">Přidat jazykovou mutaci</h2></header>
          <form method="post" enctype="multipart/form-data"><div class="card-body">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="add">
            <div class="row g-3">
              <div class="col-12 col-md-4"><label class="form-label" for="rac-new-code">Kód jazyka</label><input class="form-control" id="rac-new-code" type="text" name="code" maxlength="32" required placeholder="fr"><small class="text-muted">Např. cs, en, fr, pt-br</small></div>
              <div class="col-12 col-md-4"><label class="form-label" for="rac-new-name">Název v administraci</label><input class="form-control" id="rac-new-name" type="text" name="name" maxlength="100" required placeholder="Francouzština"></div>
              <div class="col-12 col-md-4"><label class="form-label" for="rac-new-native">Vlastní název</label><input class="form-control" id="rac-new-native" type="text" name="native_name" maxlength="100" required placeholder="Français"></div>
              <div class="col-12 col-md-4"><label class="form-label" for="rac-new-locale">Locale</label><input class="form-control" id="rac-new-locale" type="text" name="locale" maxlength="20" placeholder="fr_FR"></div>
              <div class="col-12 col-md-4"><label class="form-label" for="rac-new-flag">Vlajka</label><input class="form-control" id="rac-new-flag" type="file" name="flag" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG, PNG, WEBP do 1 MB</small></div>
              <div class="col-12 col-md-4"><label class="form-label" for="rac-new-order">Pořadí</label><input class="form-control" id="rac-new-order" type="number" name="sort_order" min="0" step="1" placeholder="Automaticky"></div>
              <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" id="rac-new-active" value="1" checked><label class="form-check-label" for="rac-new-active">Aktivní</label></div><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_default" id="rac-new-default" value="1"><label class="form-check-label" for="rac-new-default">Nastavit jako výchozí</label></div></div>
            </div>
          </div><footer class="card-footer text-end"><button class="btn btn-primary" type="submit">Přidat jazyk</button></footer></form>
        </section>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
