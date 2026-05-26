#!/usr/bin/env php
<?php

declare(strict_types=1);

use MyVendor\BeMart\Router\RouteTable;

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Asia/Tokyo');

$repo = dirname(__DIR__);
$eccubeSource = getenv('ECCUBE_SOURCE_DIR') ?: ($repo . '/tools/ec-cube-source');
if (! is_dir($eccubeSource . '/src/Eccube/Controller') && is_dir('/Users/akihito/git/ec-cube/src/Eccube/Controller')) {
    $eccubeSource = '/Users/akihito/git/ec-cube';
}

$controllerRoot = $eccubeSource . '/src/Eccube/Controller';
if (! is_dir($controllerRoot)) {
    fwrite(STDERR, "EC-CUBE controller source not found. Set ECCUBE_SOURCE_DIR or clone EC-CUBE into tools/ec-cube-source.\n");
    exit(1);
}

/** @return list<array{name:string,path:string,method:string,publicMethod:string,file:string,line:int,function:string}> */
function extractOriginalRoutes(string $controllerRoot): array
{
    $routes = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerRoot));
    foreach ($iterator as $fileInfo) {
        if (! $fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
            continue;
        }

        $file = $fileInfo->getPathname();
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            continue;
        }

        $pending = [];
        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $line = (string) $lines[$i];
            if (str_contains($line, '#[Route')) {
                $startLine = $i + 1;
                $attribute = trim($line);
                $parenDepth = substr_count($attribute, '(') - substr_count($attribute, ')');
                while (($parenDepth > 0 || ! str_contains($attribute, ']')) && $i + 1 < $count) {
                    $i++;
                    $nextLine = (string) $lines[$i];
                    $attribute .= ' ' . trim($nextLine);
                    $parenDepth += substr_count($nextLine, '(') - substr_count($nextLine, ')');
                }

                $parsed = parseRouteAttribute($attribute);
                if ($parsed !== null) {
                    $parsed['line'] = $startLine;
                    $parsed['file'] = substr($file, strlen($controllerRoot) + 1);
                    $pending[] = $parsed;
                }

                continue;
            }

            if ($pending !== [] && preg_match('/\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $line, $m) === 1) {
                $function = $m[1];
                foreach ($pending as $route) {
                    foreach ($route['methods'] as $method) {
                        $routes[] = [
                            'name' => $route['name'],
                            'path' => normalizeOriginalPath($route['path']),
                            'method' => $method,
                            'publicMethod' => publicHtmlMethod($method),
                            'file' => $route['file'],
                            'line' => $route['line'],
                            'function' => $function,
                        ];
                    }
                }

                $pending = [];
            }
        }
    }

    usort($routes, static fn (array $a, array $b): int => [$a['name'], $a['method'], $a['path']] <=> [$b['name'], $b['method'], $b['path']]);

    return $routes;
}

/** @return array{name:string,path:string,methods:list<string>}|null */
function parseRouteAttribute(string $attribute): array|null
{
    $name = attrStringArg($attribute, 'name');
    if ($name === null) {
        return null;
    }

    $path = attrStringArg($attribute, 'path');
    if ($path === null && preg_match('/Route\(\s*([\'\"])(.*?)\1/', $attribute, $m) === 1) {
        $path = $m[2];
    }

    if ($path === null) {
        return null;
    }

    $methods = [];
    if (preg_match('/\bmethods\s*:\s*\[([^\]]*)\]/', $attribute, $m) === 1) {
        preg_match_all('/[\'\"]([A-Z]+)[\'\"]/', $m[1], $mm);
        $methods = $mm[1];
    }

    if ($methods === []) {
        $methods = ['ANY'];
    }

    return ['name' => $name, 'path' => $path, 'methods' => array_values(array_unique($methods))];
}

function attrStringArg(string $attribute, string $name): string|null
{
    if (preg_match('/\b' . preg_quote($name, '/') . '\s*:\s*([\'\"])(.*?)\1/', $attribute, $m) === 1) {
        return $m[2];
    }

    return null;
}

function normalizeOriginalPath(string $path): string
{
    $path = str_replace('%eccube_admin_route%', 'admin', $path);
    $path = str_replace('%eccube_user_data_route%', 'user_data', $path);

    return normalizePath($path);
}

function normalizePath(string $path): string
{
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    return $path === '' ? '/' : $path;
}

function publicHtmlMethod(string $method): string
{
    return match ($method) {
        'PUT', 'DELETE' => 'POST',
        default => $method,
    };
}

/** @return array<string, list<array{name:string,method:string,path:string,resource:string,dispatch:string,alps:string,isActionRedirect:bool}>> */
function bemartRoutesByName(): array
{
    $byName = [];
    foreach (RouteTable::default()->routes as $route) {
        foreach ($route->methods as $method) {
            $byName[$route->name][] = [
                'name' => $route->name,
                'method' => $method,
                'path' => normalizePath($route->path),
                'resource' => $route->resource,
                'dispatch' => $route->dispatchMethodFor($method),
                'alps' => $route->alpsIdFor($method),
                'isActionRedirect' => str_contains($route->resource, 'action-redirect'),
            ];
        }
    }

    ksort($byName);

    return $byName;
}

/** @return array<string, array{title:string,type:string,rt:string}> */
function alpsDescriptors(string $repo): array
{
    $profile = json_decode((string) file_get_contents($repo . '/alps.json'), true, flags: JSON_THROW_ON_ERROR);
    $ids = [];
    $collect = static function (array $descriptors) use (&$collect, &$ids): void {
        foreach ($descriptors as $descriptor) {
            if (! is_array($descriptor)) {
                continue;
            }
            if (isset($descriptor['id'])) {
                $ids[(string) $descriptor['id']] = [
                    'title' => (string) ($descriptor['title'] ?? $descriptor['id']),
                    'type' => (string) ($descriptor['type'] ?? ''),
                    'rt' => (string) ($descriptor['rt'] ?? ''),
                ];
            }
            if (isset($descriptor['descriptor']) && is_array($descriptor['descriptor'])) {
                $collect($descriptor['descriptor']);
            }
        }
    };
    $collect($profile['alps']['descriptor'] ?? []);
    ksort($ids);

    return $ids;
}

/** @param list<array{name:string,path:string,method:string,publicMethod:string,file:string,line:int,function:string}> $original */
function originalByName(array $original): array
{
    $byName = [];
    foreach ($original as $route) {
        $byName[$route['name']][] = $route;
    }
    ksort($byName);

    return $byName;
}

/** @param list<array<string, mixed>> $routes */
function uniqueValues(array $routes, string $key): array
{
    $values = [];
    foreach ($routes as $route) {
        $values[(string) $route[$key]] = true;
    }
    $values = array_keys($values);
    sort($values);

    return $values;
}

/** @param list<string> $items */
function codeList(array $items): string
{
    return implode('<br>', array_map(static fn (string $value): string => '`' . escapeMd($value) . '`', $items));
}

function escapeMd(string $value): string
{
    return str_replace(['|', "\n", "\r"], ['\\|', ' ', ''], $value);
}

function routeDescription(string $name): string
{
    $special = [
        'homepage' => 'ストアフロントのトップページを表示する。',
        'product_list' => '商品一覧を表示・検索する。',
        'product_detail' => '商品詳細を表示する。',
        'product_add_cart' => '商品をカートに追加する。',
        'product_add_favorite' => '商品をお気に入りに追加する。',
        'product_delete_favorite' => '商品をお気に入りから削除する。',
        'cart' => 'カート内容を表示する。',
        'cart_handle_item' => 'カート商品の数量変更・削除を行う。',
        'cart_buystep' => '選択したカートで購入手続きへ進む。',
        'contact' => 'お問い合わせフォームを表示・送信する。',
        'contact_confirm' => 'お問い合わせ内容の確認を行う。',
        'contact_complete' => 'お問い合わせ完了を表示する。',
        'entry' => '会員登録フォームを表示・登録する。',
        'entry_complete' => '会員登録完了を表示する。',
        'entry_activate' => '仮会員登録を本会員化する。',
        'forgot' => 'パスワード再発行を受け付ける。',
        'forgot_complete' => 'パスワード再発行受付完了を表示する。',
        'forgot_reset' => 'パスワード再設定を行う。',
        'mypage_login' => '顧客ログインを表示・実行する。',
        'mypage' => 'マイページトップを表示する。',
        'mypage_change' => '会員情報変更を表示・更新する。',
        'mypage_change_complete' => '会員情報変更完了を表示する。',
        'mypage_delivery' => 'お届け先一覧を表示する。',
        'mypage_delivery_new' => 'お届け先を新規登録する。',
        'mypage_delivery_edit' => 'お届け先を編集する。',
        'mypage_delivery_delete' => 'お届け先を削除する。',
        'mypage_favorite' => 'お気に入り一覧を表示する。',
        'mypage_favorite_delete' => 'お気に入り商品を削除する。',
        'mypage_history' => '購入履歴詳細を表示する。',
        'mypage_order' => '購入履歴から再注文する。',
        'mypage_withdraw' => '退会手続きを表示・実行する。',
        'mypage_withdraw_confirm' => '退会確認を表示・実行する。',
        'mypage_withdraw_complete' => '退会完了を表示する。',
        'shopping' => '購入手続き画面を表示する。',
        'shopping_login' => '購入手続き用ログインを表示する。',
        'shopping_nonmember' => '非会員購入情報を入力する。',
        'shopping_customer' => '非会員購入者情報を更新する。',
        'shopping_shipping' => '配送先選択を表示・反映する。',
        'shopping_shipping_edit' => '配送先情報を編集する。',
        'shopping_shipping_multiple' => '複数配送先を設定する。',
        'shopping_confirm' => '注文確認を表示する。',
        'shopping_checkout' => '注文を確定する。',
        'shopping_complete' => '注文完了を表示する。',
        'shopping_error' => '購入エラーを表示する。',
        'shopping_redirect_to' => '購入フロー内の戻り先へリダイレクトする。',
        'logout' => 'ストアフロントからログアウトする。',
        'help_about' => '当サイトについてを表示する。',
        'help_guide' => 'ご利用ガイドを表示する。',
        'help_privacy' => 'プライバシーポリシーを表示する。',
        'help_agreement' => '利用規約を表示する。',
        'help_tradelaw' => '特定商取引法表示を表示する。',
        'sitemap_xml' => 'サイトマップXMLを出力する。',
        'sitemap_category_xml' => 'カテゴリサイトマップXMLを出力する。',
        'sitemap_product_xml' => '商品サイトマップXMLを出力する。',
        'sitemap_page_xml' => 'ページサイトマップXMLを出力する。',
        'user_data' => 'ユーザー作成ページを表示する。',
    ];
    if (isset($special[$name])) {
        return $special[$name];
    }

    $label = labelFromRouteName($name);
    if (str_starts_with($name, 'admin_')) {
        return '管理画面の' . $label . 'を扱う。';
    }
    if (str_starts_with($name, 'block_')) {
        return 'ストアフロントブロックの' . $label . 'を表示する。';
    }
    if (str_starts_with($name, 'install')) {
        return 'インストーラーの' . $label . 'を扱う。';
    }

    return $label . 'を扱う。';
}

function labelFromRouteName(string $name): string
{
    $terms = [
        'admin' => '',
        'homepage' => 'ダッシュボード',
        'change' => '変更',
        'password' => 'パスワード',
        'content' => 'コンテンツ管理',
        'file' => 'ファイル管理',
        'view' => '表示',
        'download' => 'ダウンロード',
        'delete' => '削除',
        'layout' => 'レイアウト',
        'preview' => 'プレビュー',
        'block' => 'ブロック',
        'cache' => 'キャッシュ',
        'css' => 'CSS',
        'js' => 'JavaScript',
        'maintenance' => 'メンテナンス',
        'news' => '新着情報',
        'page' => 'ページ',
        'customer' => '顧客',
        'delivery' => '配送',
        'order' => '受注',
        'shipping' => '出荷',
        'product' => '商品',
        'category' => 'カテゴリ',
        'class' => '規格',
        'name' => '名',
        'csv' => 'CSV',
        'import' => '取込',
        'template' => 'テンプレート',
        'split' => '分割',
        'cleanup' => 'クリーンアップ',
        'image' => '画像',
        'process' => '処理',
        'load' => '読込',
        'revert' => '取消',
        'setting' => '設定',
        'shop' => '店舗',
        'mail' => 'メール',
        'payment' => '支払方法',
        'system' => 'システム',
        'log' => 'ログ',
        'login' => 'ログイン',
        'history' => '履歴',
        'masterdata' => 'マスタデータ',
        'two' => '2',
        'factor' => '要素',
        'auth' => '認証',
        'store' => 'ストア',
        'plugin' => 'プラグイン',
        'api' => 'API',
        'owners' => 'オーナーズストア',
        'search' => '検索',
        'install' => 'インストール',
        'confirm' => '確認',
        'upgrade' => 'アップグレード',
        'schema' => 'スキーマ',
        'update' => '更新',
        'authentication' => '認証',
        'auto' => '自動',
        'new' => '新規',
        'item' => '商品',
        'cart' => 'カート',
        'sp' => 'スマホ',
        'export' => 'エクスポート',
        'edit' => '編集',
        'sort' => '並び順',
        'no' => '番号',
        'move' => '移動',
        'bulk' => '一括',
        'status' => 'ステータス',
        'visible' => '表示',
        'visibility' => '表示切替',
        'member' => 'メンバー',
        'authority' => '権限',
        'security' => 'セキュリティ',
        'tax' => '税率',
        'tradelaw' => '特商法',
        'calendar' => 'カレンダー',
        'base' => '基本',
        'info' => '情報',
        'resend' => '再送',
        'pdf' => 'PDF',
        'notify' => '通知',
        'tracking' => '追跡番号',
        'number' => '番号',
        'sale' => '売上',
        'nonstock' => '在庫切れ',
        'phpinfo' => 'PHP情報',
        'disable' => '無効化',
        'redirect' => 'リダイレクト',
        'check' => '確認',
        'step1' => 'ステップ1',
        'step2' => 'ステップ2',
        'step3' => 'ステップ3',
        'step4' => 'ステップ4',
        'step5' => 'ステップ5',
        'complete' => '完了',
    ];

    $labels = [];
    foreach (explode('_', $name) as $part) {
        if (isset($terms[$part])) {
            if ($terms[$part] !== '') {
                $labels[] = $terms[$part];
            }
            continue;
        }
        $labels[] = $part;
    }

    return implode(' / ', $labels);
}

function scopeNote(string $name): string
{
    if (str_starts_with($name, 'install')) {
        return 'インストーラー系。BeMart移植対象に含めるか判断が必要。';
    }
    if (str_contains($name, 'plugin_api') || str_contains($name, 'owners')) {
        return '外部オーナーズストア/API連携。現行方針では対象外候補。';
    }
    if (str_starts_with($name, 'admin_store_plugin')) {
        return 'プラグイン管理。現行方針では対象外候補だが管理画面導線との整合判断が必要。';
    }

    return '';
}

$original = extractOriginalRoutes($controllerRoot);
$originalByName = originalByName($original);
$bemartByName = bemartRoutesByName();
$alps = alpsDescriptors($repo);

$rows = [];
$counts = ['完了' => 0, '部分' => 0, '未実装' => 0];
foreach ($originalByName as $name => $routes) {
    $sourceLocation = $routes[0]['file'] . ':' . $routes[0]['line'];
    $function = $routes[0]['function'];
    $originalMethods = uniqueValues($routes, 'method');
    $publicMethods = uniqueValues($routes, 'publicMethod');
    $originalPaths = uniqueValues($routes, 'path');
    $bemart = $bemartByName[$name] ?? [];
    $bemartMethods = $bemart === [] ? [] : uniqueValues($bemart, 'method');
    $bemartPaths = $bemart === [] ? [] : uniqueValues($bemart, 'path');
    $alpsIds = $bemart === [] ? [] : uniqueValues($bemart, 'alps');
    $resources = $bemart === [] ? [] : uniqueValues($bemart, 'resource');

    $reasons = [];
    $status = '完了';
    if ($bemart === []) {
        $status = '未実装';
        $reasons[] = 'RouteTable未登録';
        $reasons[] = 'ALPS未割当';
    } else {
        foreach ($publicMethods as $method) {
            if (! in_array($method, $bemartMethods, true)) {
                $reasons[] = '公開method不足: ' . $method;
            }
        }
        foreach ($originalPaths as $path) {
            if (! in_array($path, $bemartPaths, true)) {
                $reasons[] = 'original path未一致';
                break;
            }
        }
        foreach ($bemart as $route) {
            if ($route['isActionRedirect']) {
                $reasons[] = 'ActionRedirect残り';
                break;
            }
        }
        foreach ($alpsIds as $alpsId) {
            if (! isset($alps[$alpsId])) {
                $reasons[] = 'ALPS descriptor欠落: ' . $alpsId;
            }
        }
        if ($reasons !== []) {
            $status = '部分';
        }
    }

    $scope = scopeNote($name);
    if ($scope !== '') {
        $reasons[] = $scope;
    }

    $counts[$status]++;
    $rows[] = [
        'name' => $name,
        'status' => $status,
        'description' => routeDescription($name),
        'originalMethods' => $originalMethods,
        'publicMethods' => $publicMethods,
        'originalPaths' => $originalPaths,
        'alpsIds' => $alpsIds,
        'bemartMethods' => $bemartMethods,
        'bemartPaths' => $bemartPaths,
        'resources' => $resources,
        'reasons' => array_values(array_unique($reasons)),
        'source' => $sourceLocation,
        'function' => $function,
    ];
}

$totalOriginalNames = count($originalByName);
$totalOriginalMethods = count($original);
$totalBemartNames = count($bemartByName);
$totalBemartMethods = array_sum(array_map('count', $bemartByName));
$actionRedirect = 0;
foreach ($bemartByName as $routes) {
    foreach ($routes as $route) {
        if ($route['isActionRedirect']) {
            $actionRedirect++;
        }
    }
}

$generatedAt = date('Y-m-d');
$md = <<<MD
# EC-CUBE original route coverage

作成日: {$generatedAt}

## 目的

EC-CUBE 4.3 の Symfony `#[Route]` を移植の source of truth とし、BeMart 側で「やったもの / やっていないもの」を route name 単位で追跡する。
この表は、画面リンククロールだけでは検出できない **original route name 欠落**、**original path 不一致**、**ALPS 未割当** を見つけるための coverage matrix である。

## Source

- original route: `{$eccubeSource}/src/Eccube/Controller/**/*.php`
- BeMart route: `/Users/akihito/git/be-bemart/src/Router/RouteTable.php`
- ALPS: `/Users/akihito/git/be-bemart/alps.json`
- regenerate: `php /Users/akihito/git/be-bemart/bin/generate-original-route-coverage.php`

## Summary

| 指標 | 件数 |
|---|---:|
| original route name | {$totalOriginalNames} |
| original method entry | {$totalOriginalMethods} |
| BeMart route name | {$totalBemartNames} |
| BeMart method entry | {$totalBemartMethods} |
| 完了 | {$counts['完了']} |
| 部分 | {$counts['部分']} |
| 未実装 | {$counts['未実装']} |
| ActionRedirect method entry | {$actionRedirect} |

## 判定ルール

- **完了**: original route name が BeMart `RouteTable` にあり、HTML公開method（PUT/DELETEはPOSTへ正規化）・original path・ALPS descriptor・Resource接続が揃っている。
- **部分**: route name はあるが、original path不一致、method不足、`ActionRedirect`、ALPS descriptor欠落のいずれかがある。
- **未実装**: original route name が BeMart `RouteTable` にない。ALPS IDも未割当として扱う。
- ALPS は URL / HTTP method を書かないため、path互換は `RouteTable` 側の責務として別に見る。

## Coverage table

| 状態 | original route name | 説明 | original method | HTML公開method | original path | ALPS ID | BeMart path | Resource | 残タスク / メモ | source |
|---|---|---|---|---|---|---|---|---|---|---|

MD;

foreach ($rows as $row) {
    $md .= '| ' . $row['status']
        . ' | `' . escapeMd($row['name']) . '`'
        . ' | ' . escapeMd($row['description'])
        . ' | ' . codeList($row['originalMethods'])
        . ' | ' . codeList($row['publicMethods'])
        . ' | ' . codeList($row['originalPaths'])
        . ' | ' . ($row['alpsIds'] === [] ? '未割当' : codeList($row['alpsIds']))
        . ' | ' . ($row['bemartPaths'] === [] ? '-' : codeList($row['bemartPaths']))
        . ' | ' . ($row['resources'] === [] ? '-' : codeList($row['resources']))
        . ' | ' . ($row['reasons'] === [] ? '-' : escapeMd(implode(' / ', $row['reasons'])))
        . ' | `' . escapeMd($row['source'] . ' ' . $row['function'] . '()') . '` |' . "\n";
}

$path = $repo . '/docs/migration/original-route-coverage.md';
if (! is_dir(dirname($path))) {
    mkdir(dirname($path), 0777, true);
}
file_put_contents($path, $md);

fprintf(STDERR, "Generated %s\n", $path);
fprintf(STDERR, "original route names=%d done=%d partial=%d missing=%d\n", $totalOriginalNames, $counts['完了'], $counts['部分'], $counts['未実装']);
