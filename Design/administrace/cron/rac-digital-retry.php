<?php
// RAC / 2026-10-16-17.20 / Safe CLI-only recovery of paid digital orders. PHP 7.2+
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$dryRun = in_array('--dry-run', $argv, true);
$limit = 20;
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d{1,2})$/D', $arg, $m)) {
        $limit = max(1, min(50, (int)$m[1]));
    }
}
$root = dirname(__DIR__, 2);
require_once $root.'/inc/01.config.class.php';
require_once $root.'/administrace/inc/rac-digital.php';
require_once $root.'/administrace/inc/rac-mail.php';
$pdo = racFrontendPdo();
if (!$pdo instanceof PDO) { fwrite(STDERR, "RAC: Databáze není dostupná.\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function racRetryNote(PDO $db, int $id, string $state, ?string $error): void {
    $stmt = $db->prepare('INSERT INTO shop_digital_retry_log (order_id,attempts,last_result,last_error,last_attempt_at) VALUES (?,1,?,?,NOW()) ON DUPLICATE KEY UPDATE attempts=attempts+1,last_result=VALUES(last_result),last_error=VALUES(last_error),last_attempt_at=NOW()');
    $stmt->execute([$id,$state,$error === null ? null : mb_substr($error,0,500,'UTF-8')]);
}
function racRetryHasFulfillableItems(PDO $db, int $id): bool {
    $stmt=$db->prepare("SELECT COUNT(*) FROM shop_order_items i JOIN product_variants v ON v.id=i.variant_id WHERE i.order_id=? AND v.kind IN ('ebook','audio')");
    $stmt->execute([$id]);
    return (int)$stmt->fetchColumn() > 0;
}
function racRetryMailState(PDO $db, int $id): string {
    $stmt=$db->prepare("SELECT status FROM shop_digital_mail_log WHERE order_id=? ORDER BY id DESC");
    $stmt->execute([$id]);
    $statuses=$stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('accepted',$statuses,true)) return 'accepted';
    if (in_array('sending',$statuses,true)) return 'sending';
    return in_array('failed',$statuses,true) ? 'failed' : 'none';
}
function racRetryRevoked(PDO $db, int $id): bool {
    $stmt=$db->prepare("SELECT COUNT(*) FROM shop_digital_access WHERE order_id=? AND revoked_at IS NOT NULL");
    $stmt->execute([$id]);
    return (int)$stmt->fetchColumn()>0;
}
function racRetryNeedsGrants(PDO $db, int $id): bool {
    $stmt=$db->prepare("SELECT COUNT(*) FROM shop_order_items i JOIN product_variants v ON v.id=i.variant_id JOIN product_files f ON f.variant_id=v.id AND f.purpose='paid' LEFT JOIN shop_digital_access a ON a.order_id=i.order_id AND a.file_id=f.id AND a.revoked_at IS NULL AND a.expires_at>NOW() WHERE i.order_id=? AND v.kind IN ('ebook','audio') AND a.id IS NULL");
    $stmt->execute([$id]);
    return (int)$stmt->fetchColumn()>0;
}
try {
    foreach (['shop_orders','shop_payments','shop_order_items','product_files','shop_digital_access','shop_order_fulfillment','shop_digital_mail_log','shop_digital_retry_log'] as $table) {
        if (!racDigitalTable($pdo,$table)) throw new RuntimeException('Chybí tabulka '.$table.'. Proveďte migraci.');
    }
    $config=racMailConfig();
    // Even dry-run validates storage and config where possible, but never emails.
    $lock=$pdo->query("SELECT GET_LOCK('rac_digital_retry_worker_v1',0)")->fetchColumn();
    if ((int)$lock!==1) { fwrite(STDOUT,"RAC: Jiná úloha právě běží.\n"); exit(0); }
    try {
        // Only confirmed payments; newest first so a small limit does not starve fresh orders.
        // Reprocess all digital orders in case older paid orders were left unfinished.
        $stmt=$pdo->prepare("SELECT o.id,o.order_number FROM shop_orders o LEFT JOIN shop_digital_retry_log r ON r.order_id=o.id WHERE EXISTS (SELECT 1 FROM shop_payments p WHERE p.order_id=o.id AND p.status='paid') AND EXISTS (SELECT 1 FROM shop_order_items i JOIN product_variants v ON v.id=i.variant_id WHERE i.order_id=o.id AND v.kind IN ('ebook','audio')) ORDER BY COALESCE(r.last_attempt_at,'1970-01-01') ASC,o.id DESC LIMIT ".(int)$limit);
        $stmt->execute();
        $orders=$stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts=['checked'=>0,'granted'=>0,'emailed'=>0,'skipped'=>0,'errors'=>0];
        foreach ($orders as $order) {
            $id=(int)$order['id']; ++$counts['checked'];
            try {
                if (!racDigitalPaid($pdo,$id) || !racRetryHasFulfillableItems($pdo,$id)) {++$counts['skipped'];continue;}
                if (racRetryRevoked($pdo,$id)) { ++$counts['skipped']; continue; } // Do not reverse admin revocations.
                $mailState=racRetryMailState($pdo,$id);
                $needsGrant=racRetryNeedsGrants($pdo,$id);
                // Avoid mailing where any accepted/manual or in-flight delivery is already recorded.
                $maySend= $mailState==='none' || $mailState==='failed';
                if (!$needsGrant && !$maySend) {++$counts['skipped'];continue;}
                if ($dryRun) {
                    fwrite(STDOUT, 'Objednávka '. $order['order_number'] . ': odkazy '.($needsGrant?'zkontrolovat/doplnit':'existují').', e-mail '.($maySend?'k prověření':'neopakovat')."\n");
                    continue;
                }
                $pdo->beginTransaction();
                $grant=racDigitalGrant($pdo,$id);
                $pdo->commit();
                if ($grant['created']>0) ++$counts['granted'];
                $state='granted';
                if ($maySend && !empty($config['automatic_enabled'])) {
                    // Preserve source=webhook for existing deduplication in rac-mail.php.
                    $sent=racDigitalSmtpEmail($pdo,$id,'webhook');
                    if ($sent) { ++$counts['emailed']; $state='mail_accepted'; }
                } elseif ($maySend) {
                    $state='mail_disabled';
                }
                racRetryNote($pdo,$id,$state,null);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                ++$counts['errors'];
                error_log('RAC digital retry order '.$id.': '.$e->getMessage());
                if (!$dryRun) { try {racRetryNote($pdo,$id,'error',$e->getMessage());} catch(Throwable $ignored) {} }
            }
        }
        fwrite(STDOUT, 'RAC retry: '.json_encode($counts,JSON_UNESCAPED_UNICODE).($dryRun?' [DRY RUN]':'')."\n");
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('rac_digital_retry_worker_v1')");
    }
} catch (Throwable $e) {
    fwrite(STDERR,"RAC retry chyba: ".$e->getMessage()."\n");
    exit(1);
}
