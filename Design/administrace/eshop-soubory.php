<?php
// verze 2026-10-08-faze4 / RAC sprava digitalnich souboru / PHP 7.4
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_once __DIR__ . '/config/digital_storage.php';
require_min_role('admin');

$id = max(0, (int)($_GET['id'] ?? 0));
$st = $pdo->prepare('SELECT id, slug FROM products WHERE id=?');
$st->execute(array($id));
$product = $st->fetch(PDO::FETCH_ASSOC);
if (!$product) { http_response_code(404); exit('Produkt nenalezen.'); }
$st = $pdo->prepare("SELECT id,kind FROM product_variants WHERE product_id=? AND kind IN ('ebook','audio') ORDER BY kind");
$st->execute(array($id));
$variants = $st->fetchAll(PDO::FETCH_ASSOC);
$variantMap = array();
foreach ($variants as $v) { $variantMap[(int)$v['id']] = (string)$v['kind']; }
$errors = array(); $ok = '';
$csrf = csrf_token('csrf_product_files');

function rac_file_allowed(string $kind, string $media): bool {
    return ($kind === 'ebook' && in_array($media, array('pdf','epub'), true)) ||
           ($kind === 'audio' && in_array($media, array('mp3','m4a'), true));
}
function rac_file_mime_ok(string $type, string $media): bool {
    $map = array(
        'pdf' => array('application/pdf'),
        'epub' => array('application/epub+zip', 'application/zip', 'application/octet-stream'),
        'mp3' => array('audio/mpeg', 'audio/mp3', 'application/octet-stream'),
        'm4a' => array('audio/mp4', 'audio/x-m4a', 'video/mp4', 'application/octet-stream')
    );
    return isset($map[$media]) && in_array($type, $map[$media], true);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid('csrf_product_files', (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Neplatny bezpecnostni token.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'upload') {
            $variantId = (int)($_POST['variant_id'] ?? 0);
            $purpose = (string)($_POST['purpose'] ?? '');
            $media = (string)($_POST['media_type'] ?? '');
            $file = $_FILES['digital_file'] ?? null;
            if (!isset($variantMap[$variantId]) || !in_array($purpose, array('paid','preview'), true) || !rac_file_allowed($variantMap[$variantId] ?? '', $media)) {
                $errors[] = 'Neplatna varianta nebo typ souboru.';
            }
            if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
                $errors[] = 'Nahravani souboru selhalo. Zkontrolujte limity PHP upload_max_filesize a post_max_size.';
            }
            $maxSize = 500 * 1024 * 1024; // 500 MiB; hosting muze mit nizsi limit
            if (is_array($file) && ((int)($file['size'] ?? 0) < 1 || (int)$file['size'] > $maxSize)) {
                $errors[] = 'Soubor musi mit velikost 1 B az 500 MiB.';
            }
            if (!$errors) {
                $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                if (!rac_file_mime_ok($mime, $media)) { $errors[] = 'Typ souboru neodpovida formatu.'; }
                $name = basename(str_replace('\\', '/', (string)$file['name']));
                if ($name === '' || strlen($name) > 255 || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== $media) {
                    $errors[] = 'Neplatny nazev ci pripona souboru.';
                }
                // Minimum signature verification for common masquerading files.
                $fh = fopen($file['tmp_name'], 'rb');
                $signature = $fh ? (string)fread($fh, 12) : '';
                if ($fh) { fclose($fh); }
                if ($media === 'pdf' && substr($signature, 0, 5) !== '%PDF-') { $errors[] = 'Chybna hlavicka PDF.'; }
                if ($media === 'epub' && substr($signature, 0, 2) !== 'PK') { $errors[] = 'Chybna hlavicka EPUB.'; }
                if ($media === 'mp3' && substr($signature, 0, 3) !== 'ID3' && substr($signature, 0, 1) !== "\xFF") { $errors[] = 'Chybna hlavicka MP3.'; }
                if ($media === 'm4a' && substr($signature, 4, 4) !== 'ftyp') { $errors[] = 'Chybna hlavicka M4A.'; }
            }
            if (!$errors) {
                try {
                    $dir = rac_private_storage_dir();
                    $key = bin2hex(random_bytes(24));
                    $dest = $dir . DIRECTORY_SEPARATOR . $key;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) { throw new RuntimeException('Soubor nelze ulozit.'); }
                    @chmod($dest, 0600);
                    try {
                        $pdo->beginTransaction();
                        $st = $pdo->prepare('INSERT INTO product_files (variant_id,purpose,media_type,storage_key,original_filename,mime_type,file_size) VALUES (?,?,?,?,?,?,?)');
                        $st->execute(array($variantId, $purpose, $media, $key, $name, $mime, (int)$file['size']));
                        $pdo->commit(); $ok = 'Soubor byl bezpecne ulozen.';
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) { $pdo->rollBack(); }
                        @unlink($dest); throw $e;
                    }
                } catch (Throwable $e) { error_log($e->getMessage()); $errors[] = 'Ulozeni selhalo: ' . $e->getMessage(); }
            }
        } elseif ($action === 'delete') {
            $fileId = (int)($_POST['file_id'] ?? 0);
            $st = $pdo->prepare('SELECT f.id,f.storage_key FROM product_files f JOIN product_variants v ON v.id=f.variant_id WHERE f.id=? AND v.product_id=?');
            $st->execute(array($fileId, $id)); $found = $st->fetch(PDO::FETCH_ASSOC);
            if (!$found) { $errors[] = 'Soubor nebyl nalezen.'; }
            else {
                try {
                    $dir = rac_private_storage_dir();
                    $path = $dir . DIRECTORY_SEPARATOR . $found['storage_key'];
                    // First make file unavailable; only then delete DB record; rollback restores file.
                    $trash = $dir . DIRECTORY_SEPARATOR . bin2hex(random_bytes(24)) . '.trash';
                    if (!is_file($path) || !rename($path, $trash)) { throw new RuntimeException('Soubor nelze pripravit k odstraneni.'); }
                    try {
                        $pdo->beginTransaction();
                        $st = $pdo->prepare('DELETE FROM product_files WHERE id=?');
                        $st->execute(array($fileId)); $pdo->commit();
                        @unlink($trash); $ok = 'Soubor byl odstranen.';
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) { $pdo->rollBack(); }
                        rename($trash, $path); throw $e;
                    }
                } catch (Throwable $e) { error_log($e->getMessage()); $errors[] = 'Odstraneni souboru selhalo.'; }
            }
        }
    }
}
$st = $pdo->prepare('SELECT f.id,f.variant_id,f.purpose,f.media_type,f.original_filename,f.file_size,f.created,v.kind FROM product_files f JOIN product_variants v ON v.id=f.variant_id WHERE v.product_id=? ORDER BY f.created DESC,f.id DESC');
$st->execute(array($id)); $files = $st->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Digitalni soubory produktu';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
  <div class="page-header"><h1>E-knihy a audioknihy</h1><p><?= h($product['slug']) ?> – placene soubory a ukazky</p></div>
  <p><a class="btn btn-light" href="eshop-produkt.php?id=<?= (int)$id ?>">← Zpet na produkt</a></p>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endforeach; ?>
  <?php if ($ok !== ''): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
  <section class="card"><div class="card-header"><h2>Nahrat soubor</h2></div><div class="card-body">
  <?php if (!$variants): ?><p>Nejdrive ulozte variantu e-kniha nebo audiokniha u produktu.</p><?php else: ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="upload">
    <div class="form-group"><label>Varianta</label><select class="form-control" name="variant_id" required><?php foreach ($variants as $v): ?><option value="<?= (int)$v['id'] ?>"><?= $v['kind']==='ebook'?'E-kniha':'Audiokniha' ?> (ID <?= (int)$v['id'] ?>)</option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Ucel</label><select class="form-control" name="purpose"><option value="paid">Plny placeny soubor</option><option value="preview">Verejna ukazka (verejny pristup bude doplnen pozdeji)</option></select></div>
    <div class="form-group"><label>Format</label><select class="form-control" name="media_type"><option value="pdf">PDF</option><option value="epub">EPUB</option><option value="mp3">MP3</option><option value="m4a">M4A</option></select></div>
    <div class="form-group"><label>Soubor (max. 500 MiB, dle limitu hostingu)</label><input class="form-control" type="file" name="digital_file" required accept=".pdf,.epub,.mp3,.m4a"></div>
    <button class="btn btn-primary" type="submit">Nahrat soubor</button>
  </form><?php endif; ?></div></section>
  <section class="card" style="margin-top:20px"><div class="card-header"><h2>Nahrane soubory</h2></div><div class="card-body">
  <?php if (!$files): ?><p>Zatim nejsou nahrane zadne soubory.</p><?php else: ?>
  <table style="width:100%"><thead><tr><th>Varianta</th><th>Ucel</th><th>Soubor</th><th>Velikost</th><th>Akce</th></tr></thead><tbody>
  <?php foreach ($files as $f): ?><tr><td><?= h($f['kind']) ?></td><td><?= $f['purpose']==='paid'?'Plny':'Ukazka' ?></td><td><?= h($f['original_filename']) ?> (<?= h($f['media_type']) ?>)</td><td><?= h(number_format((int)$f['file_size']/1048576,2,',',' ')) ?> MiB</td><td><form method="post" onsubmit="return confirm('Opravdu trvale odstranit soubor?')"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>"><button class="btn btn-light" type="submit">Odstranit</button></form></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div></section>
  <p style="margin-top:16px">Plne soubory nejsou verejne dostupne. Odkazy pro platící zakazniky pridame az spolu s objednavkami a overenim plateb.</p>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
