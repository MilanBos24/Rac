<?php
// RAC kosik, verze 2026-10-08-15.00, PHP 7.4
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (headers_sent()) { throw new RuntimeException('Session must start before HTML output'); }
    session_start();
}
function racCartToken(): string {
    if (empty($_SESSION['rac_cart_csrf'])) { $_SESSION['rac_cart_csrf'] = bin2hex(random_bytes(24)); }
    return $_SESSION['rac_cart_csrf'];
}
function racCartCheckToken($token): bool {
    return is_string($token) && hash_equals(racCartToken(), $token);
}
function racCartStored(): array {
    return isset($_SESSION['rac_cart']) && is_array($_SESSION['rac_cart']) ? $_SESSION['rac_cart'] : array();
}
function racCartFindVariant(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT v.id,v.product_id,v.kind,v.language_code,v.price,v.sale_price,v.sale_starts_at,v.sale_ends_at,v.currency,v.stock_quantity,p.slug
      FROM product_variants v JOIN products p ON p.id=v.product_id
      WHERE v.id=? AND v.active=1 AND p.status='active' LIMIT 1");
    $stmt->execute(array($id)); $row=$stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}
function racCartMax(array $variant): int {
    return $variant['kind']==='print' && $variant['stock_quantity']!==null ? min(99,(int)$variant['stock_quantity']) : 99;
}
function racCartHandlePost(PDO $pdo): ?string {
    if (!racCartCheckToken($_POST['csrf'] ?? null)) { return 'Neplatný bezpečnostní token. Obnovte stránku.'; }
    $action=(string)($_POST['action'] ?? '');
    $id=filter_var($_POST['variant_id'] ?? null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
    if (!$id) { return 'Neplatná varianta knihy.'; }
    $cart=racCartStored(); $key=(string)$id;
    if ($action === 'remove') { unset($cart[$key]); $_SESSION['rac_cart']=$cart; return null; }
    if ($action !== 'add' && $action !== 'update') { return 'Neplatná operace.'; }
    $variant=racCartFindVariant($pdo,(int)$id);
    if (!$variant) { return 'Vybraná kniha již není dostupná.'; }
    $qty=filter_var($_POST['quantity'] ?? null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1,'max_range'=>99)));
    if ($qty===false || $qty===null) { return 'Počet kusů musí být od 1 do 99.'; }
    $max=racCartMax($variant);
    $target=$action==='add' ? (int)($cart[$key] ?? 0)+$qty : $qty;
    if ($max<1 || $target>$max) { return 'Požadovaný počet kusů není skladem.'; }
    $cart[$key]=$target; $_SESSION['rac_cart']=$cart; return null;
}
function racCartLines(PDO $pdo,string $language): array {
    $lines=array(); $stored=racCartStored();
    foreach ($stored as $key=>$quantity) {
        $id=filter_var($key,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
        if (!$id) { continue; }
        $variant=racCartFindVariant($pdo,(int)$id);
        if (!$variant) { continue; }
        $product=racShopProduct($pdo,(string)$variant['slug'],$language);
        if (!$product) { continue; }
        $images=racShopImages($pdo,(int)$product['id'],(string)($variant['language_code'] ?: $language));
        $quantity=max(1,min(99,(int)$quantity));
        $price=(float)racShopPrice($variant);
        $lines[]=array('variant'=>$variant,'product'=>$product,'quantity'=>$quantity,
          'max'=>racCartMax($variant),'price'=>$price,'total'=>$price*$quantity,
          'image'=>isset($images[0]) ? racShopImageUrl($images[0]['file_path']) : '');
    }
    return $lines;
}
