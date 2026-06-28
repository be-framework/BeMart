<?php

declare(strict_types=1);

/**
 * Load the IdeaStore themed catalog (categories + products + SKUs + images)
 * into BeMart's dtb_* tables, so the IdeaStore storefront design has matching
 * data (収納/台所/家具… categories that the header/category nav search by name).
 *
 * Source: /Users/akihito/git/IdeaStore/data/generated/{categories.json,products.jsonl}
 * Target: eccubedb (DATABASE_URL or 127.0.0.1 root). utf8mb4 enforced.
 *
 * Replaces only catalog tables; masters/customers/orders are left intact
 * (order_item stores a product_name snapshot, so order history survives).
 *
 *   php sql/seed/load-idea-catalog.php [limit]
 */

// IdeaStore generated catalog dir. Defaults to the in-repo copy under
// sql/seed/idea-catalog/ (self-contained). Override with IDEA_DATA_DIR.
$ideaDir = getenv('IDEA_DATA_DIR') ?: (__DIR__ . '/idea-catalog/generated');
$limit = isset($argv[1]) ? (int) $argv[1] : 0; // 0 = all

if (! is_file($ideaDir . '/products.jsonl')) {
    fwrite(STDERR, "load-idea-catalog: IdeaStore data not found at {$ideaDir} — skipping (set IDEA_DATA_DIR)\n");
    exit(0);
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: 'eccubedb';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
]);

$now = '2026-06-28 00:00:00';

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['dtb_product_image', 'dtb_product_stock', 'dtb_product_class', 'dtb_product_category', 'dtb_product', 'dtb_category'] as $t) {
    $pdo->exec("TRUNCATE TABLE {$t}");
}

// --- categories ---
$cats = json_decode((string) file_get_contents($ideaDir . '/categories.json'), true);
$slugToId = [];
$ci = 0;
$insCat = $pdo->prepare(
    'INSERT INTO dtb_category (id, category_name, hierarchy, sort_no, create_date, update_date, discriminator_type)
     VALUES (?, ?, 1, ?, ?, ?, ?)',
);
foreach ($cats as $c) {
    $ci++;
    $slugToId[$c['slug']] = $ci;
    $insCat->execute([$ci, $c['name'], $ci, $now, $now, 'category']);
}
echo 'categories: ' . $ci . "\n";

// --- products + class + stock + category link + image ---
$insP = $pdo->prepare(
    'INSERT INTO dtb_product (id, name, product_status_id, description_detail, search_word, note, create_date, update_date, discriminator_type)
     VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?)',
);
$insPC = $pdo->prepare(
    'INSERT INTO dtb_product_class (id, product_id, product_code, price01, price02, stock, stock_unlimited, sale_type_id, visible, create_date, update_date, discriminator_type)
     VALUES (?, ?, ?, ?, ?, ?, 0, 1, 1, ?, ?, ?)',
);
$insStock = $pdo->prepare(
    'INSERT INTO dtb_product_stock (id, product_class_id, stock, create_date, update_date, discriminator_type)
     VALUES (?, ?, ?, ?, ?, ?)',
);
$insPCat = $pdo->prepare(
    'INSERT INTO dtb_product_category (product_id, category_id, discriminator_type) VALUES (?, ?, ?)',
);
$insImg = $pdo->prepare(
    'INSERT INTO dtb_product_image (id, product_id, file_name, sort_no, create_date, discriminator_type) VALUES (?, ?, ?, 0, ?, ?)',
);

$fh = fopen($ideaDir . '/products.jsonl', 'r');
$pid = 0;
$pdo->beginTransaction();
while (($line = fgets($fh)) !== false) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    $p = json_decode($line, true);
    $pid++;
    if ($limit > 0 && $pid > $limit) {
        $pid--;
        break;
    }

    $status = ($p['status'] ?? 'visible') === 'visible' ? 1 : 2;
    $tags = isset($p['tags']) && is_array($p['tags']) ? implode(' ', $p['tags']) : '';
    // category name is included so the header/category nav keyword search (name=台所) matches.
    $searchWord = trim(($p['categoryName'] ?? '') . ' ' . ($p['material'] ?? '') . ' ' . ($p['color'] ?? '') . ' ' . $tags);
    $price = (int) ($p['price02'] ?? $p['price'] ?? 0);
    $price01 = (int) ($p['price'] ?? $price);
    $stock = (int) ($p['stock'] ?? 0);
    $code = (string) ($p['productCode'] ?? ('IDEA' . $pid));
    $img = ltrim((string) ($p['image'] ?? ''), '/'); // store without leading slash

    $insP->execute([$pid, $p['productName'] ?? $p['name'], $status, $p['description'] ?? null, $searchWord, $now, $now, 'product']);
    $insPC->execute([$pid, $pid, $code, $price01, $price, $stock, $now, $now, 'product_class']);
    $insStock->execute([$pid, $pid, $stock, $now, $now, 'product_stock']);
    $catId = $slugToId[$p['categorySlug'] ?? ''] ?? 1;
    $insPCat->execute([$pid, $catId, 'product_category']);
    if ($img !== '') {
        $insImg->execute([$pid, $pid, $img, $now, 'product_image']);
    }

    if ($pid % 500 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        echo 'products: ' . $pid . "\n";
    }
}
$pdo->commit();
fclose($fh);
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

echo 'DONE products: ' . $pid . "\n";
