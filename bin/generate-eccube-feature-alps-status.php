#!/usr/bin/env php
<?php

declare(strict_types=1);

use Aura\Router\Map;
use Aura\Router\RouterContainer;

require dirname(__DIR__) . '/vendor/autoload.php';

$repo = dirname(__DIR__);
$alps = json_decode((string) file_get_contents($repo . '/alps.json'), true, flags: JSON_THROW_ON_ERROR);
$container = new RouterContainer();
/** @var callable(Map): null $routesBuilder */
$routesBuilder = require $repo . '/config/aura-routes.php';
$container->setMapBuilder($routesBuilder);
$routes = $container->getMap()->getRoutes();
$browserVerificationPath = $repo . '/docs/eccube-feature-browser-verification.json';
$browserVerificationDocument = is_file($browserVerificationPath)
    ? json_decode((string) file_get_contents($browserVerificationPath), true, flags: JSON_THROW_ON_ERROR)
    : [];
$browserVerificationGlobal = is_array($browserVerificationDocument) ? $browserVerificationDocument : [];
/** @var array<string, array<string, mixed>> $browserVerifications */
$browserVerifications = isset($browserVerificationGlobal['targets']) && is_array($browserVerificationGlobal['targets'])
    ? $browserVerificationGlobal['targets']
    : [];

/** @var array<string, array{title:string,type?:string,rt?:string}> $descriptorById */
$descriptorById = [];

$collect = static function (array $descriptors) use (&$collect, &$descriptorById): void {
    foreach ($descriptors as $descriptor) {
        if (! is_array($descriptor) || ! isset($descriptor['id'])) {
            continue;
        }

        $id = (string) $descriptor['id'];
        $descriptorById[$id] = [
            'title' => (string) ($descriptor['title'] ?? $id),
            'type' => isset($descriptor['type']) ? (string) $descriptor['type'] : null,
            'rt' => isset($descriptor['rt']) ? (string) $descriptor['rt'] : null,
        ];

        if (isset($descriptor['descriptor']) && is_array($descriptor['descriptor'])) {
            $collect($descriptor['descriptor']);
        }
    }
};

$collect($alps['alps']['descriptor'] ?? []);

$rows = [];
$uniqueRouteNames = [];
$alpsMapped = 0;
$alpsMissing = 0;
$implemented = 0;
$actionRedirect = 0;
$methodEntries = 0;
$difficultyCounts = ['Easy' => 0, 'Normal' => 0, 'Hard' => 0, 'Super Hard' => 0];
$fallbackDifficultyCounts = ['Easy' => 0, 'Normal' => 0, 'Hard' => 0, 'Super Hard' => 0];

$kebabToPascal = static function (string $segment): string {
    if ($segment === '') {
        return '';
    }

    return str_replace(' ', '', ucwords(str_replace('-', ' ', $segment)));
};

$resourceFileFor = static function (string $resource) use ($repo, $kebabToPascal): string|null {
    $prefix = 'page://self';
    if (! str_starts_with($resource, $prefix)) {
        return null;
    }

    $path = trim(substr($resource, strlen($prefix)), '/');
    if ($path === '') {
        return $repo . '/src/Resource/Page/Index.php';
    }

    $segments = array_map($kebabToPascal, explode('/', $path));

    return $repo . '/src/Resource/Page/' . implode('/', $segments) . '.php';
};

$sourceEvidenceFor = static function (string $source): string {
    $evidence = [];
    if (preg_match('/Phase\\s+\\d+\\s+stub|Phase\\s+[A-Z]\\s+stub|STUB|stubbed|INTENTIONALLY NOT/i', $source) === 1) {
        $evidence[] = 'source: stub明記';
    }

    if (preg_match('/deferred/i', $source) === 1) {
        $evidence[] = 'source: deferred注記';
    }

    if (preg_match('/CSV|text\\/csv|import|export/i', $source) === 1) {
        $evidence[] = 'source: CSV/import/export';
    }

    if (preg_match('/PDF|application\\/pdf|TCPDF/i', $source) === 1) {
        $evidence[] = 'source: PDF';
    }

    if (preg_match('/PluginManager|プラグイン|plugin/i', $source) === 1) {
        $evidence[] = 'source: plugin lifecycle';
    }

    if (preg_match('/payment|stock|checkout|pre-order|注文確定|在庫|決済/i', $source) === 1) {
        $evidence[] = 'source: checkout/order side effects';
    }

    if (preg_match('/\\bmail\\b|Mailer|notify|通知|メール/i', $source) === 1) {
        $evidence[] = 'source: mail/notification';
    }

    if (preg_match('/CsrfProtected|Unauthorized|AdminSession|Session/i', $source) === 1) {
        $evidence[] = 'source: auth/csrf boundary';
    }

    return $evidence === [] ? 'source: Resource実装確認済み' : implode(' / ', array_unique($evidence));
};

/** @param list<string> $needles */
$containsAny = static function (string $haystack, array $needles): bool {
    foreach ($needles as $needle) {
        if ($needle !== '' && str_contains($haystack, $needle)) {
            return true;
        }
    }

    return false;
};

$browserVerificationKeyFor = static fn (string $routeName, string $method): string => $routeName . ' ' . strtoupper($method);

/** @var array<string, array{strategy:string,reason:string}> $hardActionRedirectAudit */
$hardActionRedirectAudit = [
    'admin_change_password POST' => [
        'strategy' => 'native',
        'reason' => '管理者パスワード変更。credential更新、再認証、CSRF、session境界をBe/BEAR側で安全側に実装する。',
    ],
    'admin_content_cache POST' => [
        'strategy' => 'adapter',
        'reason' => 'キャッシュ削除はruntime副作用を境界adapterへ隔離する。',
    ],
    'admin_content_css POST' => [
        'strategy' => 'adapter',
        'reason' => 'CSS更新は公開ファイル副作用を境界adapterへ隔離する。',
    ],
    'admin_content_js POST' => [
        'strategy' => 'adapter',
        'reason' => 'JavaScript更新は公開ファイル副作用を境界adapterへ隔離する。',
    ],
    'admin_content_maintenance POST' => [
        'strategy' => 'adapter',
        'reason' => 'メンテナンス切替は運用状態ファイル/runtime副作用を境界adapterへ隔離する。',
    ],
    'admin_product_class_category_export GET' => [
        'strategy' => 'adapter',
        'reason' => '規格分類CSV exportはEC-CUBE互換フォーマットとdownload境界をadapter化する。',
    ],
    'admin_product_class_category_export POST' => [
        'strategy' => 'adapter',
        'reason' => '規格分類CSV exportはEC-CUBE互換フォーマットとdownload境界をadapter化する。',
    ],
    'admin_product_class_name_export GET' => [
        'strategy' => 'adapter',
        'reason' => '規格名CSV exportはEC-CUBE互換フォーマットとdownload境界をadapter化する。',
    ],
    'admin_product_class_name_export POST' => [
        'strategy' => 'adapter',
        'reason' => '規格名CSV exportはEC-CUBE互換フォーマットとdownload境界をadapter化する。',
    ],
    'admin_product_csv_class_category GET' => [
        'strategy' => 'adapter',
        'reason' => '規格分類CSV exportはEC-CUBE互換フォーマットとdownload境界をadapter化する。',
    ],
    'admin_product_csv_class_category POST' => [
        'strategy' => 'adapter',
        'reason' => '規格分類CSV importはアップロード/検証/永続化副作用をadapter化する。',
    ],
    'admin_product_csv_class_name GET' => [
        'strategy' => 'adapter',
        'reason' => '規格名CSV exportはEC-CUBE互換フォーマットとdownload境界をadapter化する。',
    ],
    'admin_product_csv_class_name POST' => [
        'strategy' => 'adapter',
        'reason' => '規格名CSV importはアップロード/検証/永続化副作用をadapter化する。',
    ],
    'admin_setting_system_masterdata POST' => [
        'strategy' => 'adapter',
        'reason' => '任意マスタ選択は対象スキーマ差分と破壊的更新リスクをadapter境界で扱う。',
    ],
    'admin_setting_system_masterdata_edit POST' => [
        'strategy' => 'adapter',
        'reason' => '任意マスタ更新は対象スキーマ差分と破壊的更新リスクをadapter境界で扱う。',
    ],
    'admin_setting_system_security POST' => [
        'strategy' => 'native',
        'reason' => 'セキュリティ設定はSymfony互換runtimeではなくBe/BEAR側の安全側動作として実装する。',
    ],
    'admin_store_template POST' => [
        'strategy' => 'adapter',
        'reason' => 'template選択はファイル配置/asset切替副作用をadapter化する。',
    ],
    'admin_store_template_delete POST' => [
        'strategy' => 'adapter',
        'reason' => 'template削除はファイル配置/asset削除副作用をadapter化する。',
    ],
    'admin_store_template_download POST' => [
        'strategy' => 'adapter',
        'reason' => 'template downloadはファイルI/Oとdownload境界をadapter化する。',
    ],
    'admin_store_template_install POST' => [
        'strategy' => 'adapter',
        'reason' => 'template追加はアップロード/展開/公開asset配置副作用をadapter化する。',
    ],
    'admin_two_factor_auth POST' => [
        'strategy' => 'native',
        'reason' => '二要素認証確認はsession/認証境界をBe/BEAR側で安全側に実装する。',
    ],
    'admin_two_factor_auth_set POST' => [
        'strategy' => 'native',
        'reason' => '二要素認証設定はsecret保存/再認証境界をBe/BEAR側で安全側に実装する。',
    ],
];

/**
 * @param array<string, mixed>|null $entry
 * @param array<string, mixed>      $global
 * @return array{status:string,browser:string,url:string,checkedAt:string,evidence:string}
 */
$browserVerificationFor = static function (array|null $entry, array $global): array {
    if ($entry === null) {
        return [
            'status' => '未対象',
            'browser' => '',
            'url' => '',
            'checkedAt' => '',
            'evidence' => '',
        ];
    }

    $status = isset($entry['status']) && is_scalar($entry['status']) ? (string) $entry['status'] : '未確認';
    if (! in_array($status, ['確認済み', '未確認'], true)) {
        $status = '未確認';
    }

    $browser = isset($entry['browser']) && is_scalar($entry['browser'])
        ? (string) $entry['browser']
        : (isset($global['browser']) && is_scalar($global['browser']) ? (string) $global['browser'] : '');
    $checkedAt = isset($entry['checkedAt']) && is_scalar($entry['checkedAt'])
        ? (string) $entry['checkedAt']
        : (isset($global['checkedAt']) && is_scalar($global['checkedAt']) ? (string) $global['checkedAt'] : '');

    return [
        'status' => $status,
        'browser' => $browser,
        'url' => isset($entry['url']) && is_scalar($entry['url']) ? (string) $entry['url'] : '',
        'checkedAt' => $checkedAt,
        'evidence' => isset($entry['evidence']) && is_scalar($entry['evidence']) ? (string) $entry['evidence'] : '',
    ];
};

/**
 * @param array<string, mixed> $defaults
 */
$actionRedirectEvidenceFor = static function (array $defaults): string {
    $evidence = ['route: ActionRedirect安全退避'];
    if (isset($defaults['returnTo']) && is_scalar($defaults['returnTo'])) {
        $evidence[] = 'returnTo: ' . (string) $defaults['returnTo'];
    }

    $evidence[] = 'ActionRedirect.phpのstub注記は難易度判定から除外';

    return implode(' / ', $evidence);
};

/**
 * Human-audited migration difficulty.
 *
 * The judgement uses the intended EC-CUBE transition (route + ALPS id/title),
 * then reads the concrete target Resource source only when the route reaches a
 * real Resource. ActionRedirect is a generic safety fallback; its own stub
 * docblock must not make every fallback route look Hard.
 *
 * @return array{level:'Easy'|'Normal'|'Hard'|'Super Hard',strategy:string,reason:string,evidence:string}
 */
$migrationAssessmentFor = static function (
    string $routeName,
    string $method,
    string $alpsId,
    string $alpsTitle,
    string $alpsType,
    string $resource,
    string $dispatch,
    array $defaults,
    string $source,
    bool $sourceExists,
    ) use ($sourceEvidenceFor, $containsAny, $actionRedirectEvidenceFor, $hardActionRedirectAudit): array {
    /** @var array<string, mixed> $defaults */
    $transitionSubject = strtolower($alpsId . ' ' . $alpsTitle . ' ' . $alpsType);
    $featureSubject = strtolower($routeName . ' ' . $transitionSubject . ' ' . $method . ' ' . $dispatch);
    $isActionRedirect = str_contains($resource, 'action-redirect');
    $evidence = $isActionRedirect
        ? $actionRedirectEvidenceFor($defaults)
        : ($sourceExists ? $sourceEvidenceFor($source) : 'source: Resource未発見（route metadataのみ）');
    $isGet = $method === 'GET' || $dispatch === 'get';
    $hasBlockingStubEvidence = ! $isActionRedirect
        && preg_match('/phase\\s+\\d+\\s+stub|phase\\s+[a-z]\\s+stub|stubbed|intentionally not|FLAGGED:/i', $source) === 1;
    $isViewFallback = $isActionRedirect
        && $isGet
        && str_starts_with($alpsId, 'go')
        && ! $containsAny($transitionSubject, ['csv', 'export', 'import', 'pdf', 'download', 'install'])
        && (
            str_contains($alpsTitle, '見る')
            || str_contains($alpsTitle, '一覧')
            || str_contains($alpsTitle, '詳細')
            || str_contains($alpsTitle, '画面')
            || str_contains(strtolower($alpsId), 'list')
        );

    if ($isViewFallback) {
        return [
            'level' => 'Normal',
            'strategy' => 'native',
            'reason' => 'GETの安全退避。実態は一覧/詳細画面へ戻すナビゲーションで、既存Resourceへの接続で解消できる。',
            'evidence' => $evidence,
        ];
    }

    $auditKey = $routeName . ' ' . $method;
    if ($isActionRedirect && isset($hardActionRedirectAudit[$auditKey])) {
        return [
            'level' => 'Hard',
            'strategy' => $hardActionRedirectAudit[$auditKey]['strategy'],
            'reason' => $hardActionRedirectAudit[$auditKey]['reason'],
            'evidence' => $evidence . ' / audit: Issue #24 Hard ActionRedirect再分類',
        ];
    }

    $normalFallbackActions = [
        'doSortNoMove',
        'doUpdateCalendar',
        'doDeleteCalendarHoliday',
        'doCreateCalendarHoliday',
        'doDeleteMailTemplate',
        'doUpdateOrderStatusList',
        'doUpdateTradeLaw',
    ];
    if ($isActionRedirect && in_array($alpsId, $normalFallbackActions, true)) {
        return [
            'level' => 'Normal',
            'strategy' => 'native',
            'reason' => '既存管理画面の標準的なCRUD/並び替え/設定更新。ActionRedirect未接続だが互換runtime級ではない。',
            'evidence' => $evidence,
        ];
    }

    if ($routeName === 'admin_change_password' || $alpsId === 'doChangePassword') {
        return [
            'level' => 'Hard',
            'strategy' => 'native',
            'reason' => '管理者パスワード変更。credential更新、再認証、CSRF、session境界を安全側で確認する必要がある。',
            'evidence' => $evidence,
        ];
    }

    if (str_contains($routeName, 'admin_setting_system_masterdata')) {
        return [
            'level' => 'Hard',
            'strategy' => 'adapter',
            'reason' => '任意マスタテーブルの選択/更新。対象スキーマ差分、入力型、管理権限、破壊的更新の安全性確認が必要。',
            'evidence' => $evidence,
        ];
    }

    if ($alpsId === 'doInstallPlugin') {
        return [
            'level' => 'Super Hard',
            'strategy' => 'legacy compatibility',
            'reason' => 'EC-CUBE完全互換ではdownload/unzip/composer/migration/cache/PluginManagerまで必要。現行Resourceもinstall stubを明記。',
            'evidence' => $evidence,
        ];
    }

    if (str_contains($routeName, 'owners_search')) {
        return [
            'level' => 'Super Hard',
            'strategy' => 'out-of-scope',
            'reason' => 'Owners Store連携とplugin marketplace画面。現行移行ではplugin install/search subtreeをスコープ外にしている。',
            'evidence' => $evidence,
        ];
    }

    if (str_contains($featureSubject, 'plugin')) {
        $level = $isGet ? 'Normal' : 'Hard';
        $strategy = $isGet ? 'native' : 'legacy compatibility';
        $reason = $isGet
            ? '現行はdtb_plugin相当のregistry projection表示で完結。ただし完全互換runtimeは別設計。'
            : 'enable/disable/uninstallは現行flag操作で実装済みだが、完全互換ではPluginManager callback、proxy/cache再生成、schema/assets処理が残る。';

        return [
            'level' => $level,
            'strategy' => $strategy,
            'reason' => $reason,
            'evidence' => $evidence,
        ];
    }

    if ($routeName === 'admin_order_export_pdf') {
        return [
            'level' => 'Hard',
            'strategy' => 'legacy compatibility',
            'reason' => 'PDF帳票はEC-CUBE互換のTCPDF/FPDIテンプレート描画を隔離service経由で扱う。Be/BEAR本体は小さなinterfaceにのみ依存する。PilotではResource到達・headers・%PDF-実体出力まで完了し、EC-CUBE完全忠実度（帳票レイアウト、dtb_order_pdf保存設定、複数配送テンプレート再現）は意図的に後続残差として残す。',
            'evidence' => $evidence . ' / pilot: Issue #24 PDF legacy compatibility / residual: fidelity intentionally incomplete',
        ];
    }

    if (
        str_contains($featureSubject, 'csv')
        || str_contains($featureSubject, 'import')
        || str_contains($featureSubject, 'export')
        || str_contains($featureSubject, 'pdf')
    ) {
        return [
            'level' => 'Hard',
            'strategy' => 'adapter',
            'reason' => 'CSV/PDF/streaming/downloadはEC-CUBE互換フォーマット、ファイルI/O、バルク処理、テストfixtureの確認が必要。',
            'evidence' => $evidence,
        ];
    }

    if (
        str_starts_with($routeName, 'admin_order_')
        || str_starts_with($routeName, 'admin_shipping_')
        || str_contains($featureSubject, 'checkout')
        || str_contains($featureSubject, 'confirmorder')
        || str_contains($featureSubject, 'createorder')
        || str_contains($featureSubject, 'tracking')
        || str_contains($featureSubject, 'shippingnotify')
        || str_contains($featureSubject, 'notify_mail')
    ) {
        return [
            'level' => 'Hard',
            'strategy' => 'adapter',
            'reason' => '注文・配送・決済・在庫・通知は複数副作用と失敗分岐を持つ。完全移植では外部境界と再実行安全性の確認が必要。',
            'evidence' => $evidence,
        ];
    }

    if (
        (str_contains($featureSubject, 'mail') || str_contains($featureSubject, 'notify'))
        && ! str_contains($featureSubject, 'mailtemplate')
        && ! str_contains($alpsTitle, 'メールテンプレート')
    ) {
        return [
            'level' => 'Hard',
            'strategy' => 'adapter',
            'reason' => 'メール本文生成、テンプレート、送信副作用を伴う。Mailer境界とEC-CUBE互換文面の確認が必要。',
            'evidence' => $evidence,
        ];
    }

    if (
        str_contains($routeName, 'admin_content_cache')
        || str_contains($routeName, 'admin_content_css')
        || str_contains($routeName, 'admin_content_js')
        || str_contains($routeName, 'admin_content_maintenance')
    ) {
        return [
            'level' => 'Hard',
            'strategy' => 'adapter',
            'reason' => 'CSS/JSファイル、キャッシュ、メンテナンス状態などアプリ運用ファイルへの副作用を伴う。',
            'evidence' => $evidence,
        ];
    }

    if ($hasBlockingStubEvidence) {
        return [
            'level' => 'Hard',
            'strategy' => 'adapter',
            'reason' => 'Resource/Be実装がstubまたはdeferredを明記している。到達面はあるが業務処理の本実装が残る。',
            'evidence' => $evidence,
        ];
    }

    if (str_contains($routeName, 'admin_store_template')) {
        return [
            'level' => $isGet ? 'Normal' : 'Hard',
            'strategy' => 'adapter',
            'reason' => $isGet
                ? 'テンプレート管理表示は通常のadmin画面。ただしtemplate実体はファイル配置物。'
                : 'テンプレート選択/追加/削除/ダウンロードはファイル配置と公開assetの副作用を伴う。',
            'evidence' => $evidence,
        ];
    }

    if (str_contains($featureSubject, 'security') || str_contains($featureSubject, 'twofactor') || str_contains($featureSubject, 'two_factor')) {
        return [
            'level' => 'Hard',
            'strategy' => 'native',
            'reason' => 'セキュリティ/2FA設定は失敗時UXよりも安全側動作が優先。session・CSRF・認証状態の境界確認が必要。',
            'evidence' => $evidence,
        ];
    }

    if ($isActionRedirect) {
        return [
            'level' => 'Normal',
            'strategy' => 'native',
            'reason' => '現在は安全退避で到達面だけ確保。完全化には入力と副作用の再接続が必要だが、互換runtime級ではない。',
            'evidence' => $evidence,
        ];
    }

    if ($isGet) {
        $easyRoutes = [
            'homepage',
            'block_cart',
            'product_list',
            'product_detail',
            'help_about',
            'help_guide',
            'help_agreement',
            'help_privacy',
            'help_tradelaw',
            'contact_complete',
            'forgot_complete',
            'entry_complete',
        ];

        if (in_array($routeName, $easyRoutes, true)) {
            return [
                'level' => 'Easy',
                'strategy' => 'native',
                'reason' => '参照/静的表示中心。Resource + Query + Twigの直線的な移植で完結する。',
                'evidence' => $evidence,
            ];
        }

        return [
            'level' => $routeName === 'admin_homepage' ? 'Hard' : 'Normal',
            'strategy' => 'native',
            'reason' => $routeName === 'admin_homepage'
                ? '管理ダッシュボード/ドリルダウンは複数集計・チャート・権限付き表示を含む。'
                : 'フォーム表示、admin/mypage認証、または複数Entity projectionを含む通常移植。',
            'evidence' => $evidence,
        ];
    }

    return [
        'level' => 'Normal',
        'strategy' => 'native',
        'reason' => '標準的な状態変更。Be Input/Final、CSRF、AUTHZ、単純な永続化を確認すれば移植可能。',
        'evidence' => $evidence,
    ];
};

foreach ($routes as $route) {
    $uniqueRouteNames[$route->name] = true;
    /** @var mixed $methods */
    $methods = $route->extras['bemart']['methods'] ?? [];
    if (! is_array($methods)) {
        $methods = [];
    }

    foreach (array_keys($methods) as $method) {
        $methodEntries++;
        /** @var mixed $metadata */
        $metadata = $methods[$method] ?? [];
        if (! is_array($metadata)) {
            $metadata = [];
        }

        $alpsId = (string) ($metadata['alpsId'] ?? '');
        $hasAlpsDescriptor = isset($descriptorById[$alpsId]);
        $descriptor = $descriptorById[$alpsId] ?? ['title' => '(missing)', 'type' => null, 'rt' => null];
        $resource = (string) ($metadata['resource'] ?? '');
        $dispatch = (string) ($metadata['dispatchMethod'] ?? strtolower((string) $method));
        $isActionRedirect = str_contains($resource, 'action-redirect');
        $alpsStatus = $hasAlpsDescriptor ? '対応済み' : '未対応';
        $implementationStatus = $isActionRedirect ? '安全退避(ActionRedirect)' : '実装済み';
        $implementationDetail = $isActionRedirect
            ? 'Aura route extrasはActionRedirectへ到達。業務処理Resourceへの接続は後続タスク。'
            : 'Aura route extrasから具体Resourceへ到達。';
        $resourceFile = $resourceFileFor($resource);
        $resourceSource = $resourceFile !== null && is_file($resourceFile) ? (string) file_get_contents($resourceFile) : '';
        $assessment = $migrationAssessmentFor(
            $route->name,
            (string) $method,
            $alpsId,
            (string) $descriptor['title'],
            (string) ($descriptor['type'] ?? ''),
            $resource,
            $dispatch,
            isset($metadata['defaults']) && is_array($metadata['defaults']) ? $metadata['defaults'] : [],
            $resourceSource,
            $resourceSource !== '',
        );
        $browserVerification = $browserVerificationFor(
            $browserVerifications[$browserVerificationKeyFor((string) $route->name, (string) $method)] ?? null,
            $browserVerificationGlobal,
        );

        if ($hasAlpsDescriptor) {
            $alpsMapped++;
        } else {
            $alpsMissing++;
        }

        if ($isActionRedirect) {
            $actionRedirect++;
        } else {
            $implemented++;
        }
        $difficultyCounts[$assessment['level']]++;
        if ($isActionRedirect) {
            $fallbackDifficultyCounts[$assessment['level']]++;
        }

        $rows[] = [
            'route' => $route->name,
            'method' => $method,
            'path' => $route->path,
            'alpsId' => $alpsId,
            'alpsTitle' => $descriptor['title'],
            'alpsType' => $descriptor['type'] ?? '',
            'resource' => $resource,
            'dispatch' => $dispatch,
            'alpsStatus' => $alpsStatus,
            'implementationStatus' => $implementationStatus,
            'implementationDetail' => $implementationDetail,
            'difficulty' => $assessment['level'],
            'migrationStrategy' => $assessment['strategy'],
            'migrationReason' => $assessment['reason'],
            'migrationEvidence' => $assessment['evidence'],
            'browserVerification' => $browserVerification,
        ];
    }
}

usort($rows, static fn (array $a, array $b): int => [$a['route'], $a['method'], $a['path']] <=> [$b['route'], $b['method'], $b['path']]);

$h = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$date = date('Y-m-d');
$totalRoutes = count($routes);
$totalNames = count($uniqueRouteNames);

$tableRows = '';
foreach ($rows as $row) {
    $alpsClass = $row['alpsStatus'] === '対応済み' ? 'alps-ok' : 'alps-missing';
    $implementationClass = $row['implementationStatus'] === '実装済み' ? 'impl-ok' : 'impl-fallback';
    $difficultyClass = str_replace(' ', '-', strtolower((string) $row['difficulty']));
    $strategyClass = str_replace(' ', '_', str_replace('-', '_', (string) $row['migrationStrategy']));
    /** @var array{status:string,browser:string,url:string,checkedAt:string,evidence:string} $verification */
    $verification = $row['browserVerification'];
    $verificationClass = match ($verification['status']) {
        '確認済み' => 'browser-done',
        '未確認' => 'browser-unchecked',
        default => 'browser-not-target',
    };
    $verificationCell = '<span class="status ' . $verificationClass . '">' . $h($verification['status']) . '</span>';
    if ($verification['browser'] !== '') {
        $verificationCell .= '<br><small>browser: ' . $h($verification['browser']) . '</small>';
    }
    if ($verification['url'] !== '') {
        $verificationCell .= '<br><small><a href="' . $h($verification['url']) . '">' . $h($verification['url']) . '</a></small>';
    }
    if ($verification['checkedAt'] !== '') {
        $verificationCell .= '<br><small>checked: ' . $h($verification['checkedAt']) . '</small>';
    }
    if ($verification['evidence'] !== '') {
        $verificationCell .= '<br><small>' . $h($verification['evidence']) . '</small>';
    }

    $tableRows .= '<tr data-alps-status="' . $h($row['alpsStatus']) . '" data-implementation-status="' . $h($row['implementationStatus']) . '" data-difficulty="' . $h($row['difficulty']) . '" data-strategy="' . $h($row['migrationStrategy']) . '" data-browser-verification="' . $h($verification['status']) . '">'
        . '<td><code>' . $h($row['route']) . '</code></td>'
        . '<td><span class="method">' . $h($row['method']) . '</span></td>'
        . '<td><code>' . $h($row['path']) . '</code></td>'
        . '<td><span class="status ' . $alpsClass . '">' . $h($row['alpsStatus']) . '</span></td>'
        . '<td><code>' . $h($row['alpsId']) . '</code></td>'
        . '<td>' . $h($row['alpsTitle']) . '</td>'
        . '<td>' . $h($row['alpsType']) . '</td>'
        . '<td><span class="status ' . $implementationClass . '">' . $h($row['implementationStatus']) . '</span><br><small>' . $h($row['implementationDetail']) . '</small></td>'
        . '<td>' . $verificationCell . '</td>'
        . '<td><span class="difficulty ' . $h($difficultyClass) . '">' . $h($row['difficulty']) . '</span> '
        . '<span class="strategy ' . $h($strategyClass) . '">' . $h($row['migrationStrategy']) . '</span><br>'
        . $h($row['migrationReason']) . '<br><small>' . $h($row['migrationEvidence']) . '</small></td>'
        . '<td><code>' . $h($row['resource']) . '</code><br><small>method: ' . $h($row['dispatch']) . '</small></td>'
        . '</tr>' . PHP_EOL;
}

$difficultyCards = '';
foreach (['Easy', 'Normal', 'Hard', 'Super Hard'] as $level) {
    $difficultyCards .= '<div class="card"><strong>' . $h((string) $difficultyCounts[$level]) . '</strong>難易度 ' . $h($level) . '</div>' . PHP_EOL;
}

$fallbackDifficultyCards = '';
foreach (['Easy', 'Normal', 'Hard', 'Super Hard'] as $level) {
    if ($fallbackDifficultyCounts[$level] === 0) {
        continue;
    }

    $fallbackDifficultyCards .= '<div class="card"><strong>' . $h((string) $fallbackDifficultyCounts[$level]) . '</strong>安全退避 ' . $h($level) . '</div>' . PHP_EOL;
}

$html = <<<HTML
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EC-CUBE機能リスト ↔ ALPS ID ↔ BeMart実装状態</title>
  <style>
    :root { color-scheme: light; --fg:#172033; --muted:#5c667a; --line:#d8dee9; --ok:#0a7f42; --warn:#9a5a00; --bad:#b42318; --bg:#ffffff; --soft:#f6f8fb; --easy:#246bce; --normal:#657000; --hard:#b24a00; --super-hard:#a12642; }
    body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: var(--fg); background: var(--bg); }
    header { padding: 32px 40px 20px; background: linear-gradient(135deg, #f6f8fb, #eef4ff); border-bottom: 1px solid var(--line); }
    main { padding: 24px 40px 48px; }
    h1 { margin: 0 0 8px; font-size: 28px; }
    p { line-height: 1.65; }
    .meta { color: var(--muted); margin: 0; }
    .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 24px 0; }
    .card { border: 1px solid var(--line); border-radius: 12px; padding: 16px; background: var(--soft); }
    .card strong { display:block; font-size: 26px; margin-bottom: 4px; }
    .toolbar { display:flex; gap: 12px; flex-wrap: wrap; align-items:center; margin: 20px 0; }
    input, select { font: inherit; padding: 8px 10px; border: 1px solid var(--line); border-radius: 8px; }
    input { min-width: min(520px, 100%); flex: 1; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { border: 1px solid var(--line); padding: 8px 10px; vertical-align: top; }
    th { position: sticky; top: 0; z-index: 1; background: #edf2fb; text-align: left; }
    code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; word-break: break-all; }
    small { color: var(--muted); }
    .method { display:inline-block; min-width: 42px; text-align:center; padding: 2px 6px; border-radius: 999px; background:#e9edf5; font-weight: 700; }
    .status { font-weight: 700; }
    .status.alps-ok, .status.impl-ok, .status.browser-done { color: var(--ok); }
    .status.alps-missing { color: var(--bad); }
    .status.impl-fallback, .status.browser-unchecked { color: var(--warn); }
    .status.browser-not-target { color: var(--muted); }
    .difficulty { display:inline-block; min-width: 74px; text-align:center; padding: 2px 7px; border-radius: 999px; color: white; font-weight: 800; }
    .difficulty.easy { background: var(--easy); }
    .difficulty.normal { background: var(--normal); }
    .difficulty.hard { background: var(--hard); }
    .difficulty.super-hard { background: var(--super-hard); }
    .strategy { display:inline-block; margin-left: 4px; padding: 1px 6px; border-radius: 6px; background:#eef2f8; font-size: 12px; font-weight: 700; }
    .strategy.legacy_compatibility { background:#fff1f4; color:#a12642; }
    .strategy.adapter { background:#fff7e6; color:#8a5a00; }
    .strategy.out_of_scope { background:#f4f4f5; color:#555; }
    .legend { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 8px 16px; margin: 16px 0 20px; padding: 14px; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
    .legend div { line-height: 1.55; }
    .note { border-left: 4px solid #7aa2ff; padding: 10px 14px; background: #f5f8ff; }
    @media (max-width: 900px) { header, main { padding-left: 16px; padding-right: 16px; } table { font-size: 12px; } }
  </style>
</head>
<body>
<header>
  <h1>EC-CUBE機能リスト ↔ ALPS ID ↔ BeMart実装状態</h1>
  <p class="meta">Generated {$date} from Aura route extras and <code>alps.json</code>.</p>
</header>
<main>
  <p class="note">この表はEC-CUBE route名を「機能リスト」として扱い、各HTTP methodごとに対応するALPS descriptor、BeMart側の到達状態、移植難易度を示します。難易度はroute + ALPS ID/titleで表される<strong>本来の遷移</strong>を主根拠にし、具体Resourceへ到達している行だけResourceソースを補助根拠にします。<code>ActionRedirect</code> は汎用安全退避なので、そのResource自身のstub注記は難易度判定から除外します。実装状態は「Aura routeの到達先」であり、完全なEC-CUBE互換完了を意味するものではありません。ブラウザ確認は <code>docs/eccube-feature-browser-verification.json</code> に記録されたCodex内ブラウザでの到達/送信確認です。</p>
  <section class="cards" aria-label="summary">
    <div class="card"><strong>{$totalNames}</strong>EC-CUBE route names</div>
    <div class="card"><strong>{$totalRoutes}</strong>Aura routes</div>
    <div class="card"><strong>{$methodEntries}</strong>method entries</div>
    <div class="card"><strong>{$alpsMapped}</strong>ALPS対応済み</div>
    <div class="card"><strong>{$alpsMissing}</strong>ALPS未対応</div>
    <div class="card"><strong>{$implemented}</strong>実装済み</div>
    <div class="card"><strong>{$actionRedirect}</strong>安全退避(ActionRedirect)</div>
{$difficultyCards}
{$fallbackDifficultyCards}
  </section>
  <section class="legend" aria-label="difficulty legend">
    <div><span class="difficulty easy">Easy</span> 静的/参照中心。Resource + Query + Twigの直線移植。</div>
    <div><span class="difficulty normal">Normal</span> 一覧/詳細へのGET退避、Form、CSRF、AUTHZ、単純CRUD、admin/mypage投影。</div>
    <div><span class="difficulty hard">Hard</span> CSV/PDF、注文/配送/決済/メール送信、security、runtime/file副作用、互換性境界。</div>
    <div><span class="difficulty super-hard">Super Hard</span> Plugin install、Owners Store、Symfony/EC-CUBE互換runtime級。</div>
    <div><span class="strategy native">native</span> Be/BEARに直接移植。</div>
    <div><span class="strategy adapter">adapter</span> ファイル/外部副作用を境界adapterへ隔離。</div>
    <div><span class="strategy legacy_compatibility">legacy compatibility</span> 既存EC-CUBE/Symfony互換レイヤーを隔離して扱う。</div>
    <div><span class="strategy out_of_scope">out-of-scope</span> 現行移行スコープ外。</div>
  </section>
  <div class="toolbar">
    <input id="q" type="search" placeholder="route / ALPS ID / title / resource を検索" aria-label="filter table">
    <select id="alpsStatus" aria-label="ALPS status filter">
      <option value="">ALPS対応: すべて</option>
      <option value="対応済み">対応済み</option>
      <option value="未対応">未対応</option>
    </select>
    <select id="implementationStatus" aria-label="implementation status filter">
      <option value="">すべて</option>
      <option value="実装済み">実装済み</option>
      <option value="安全退避(ActionRedirect)">安全退避(ActionRedirect)</option>
    </select>
    <select id="difficulty" aria-label="difficulty filter">
      <option value="">難易度すべて</option>
      <option value="Easy">Easy</option>
      <option value="Normal">Normal</option>
      <option value="Hard">Hard</option>
      <option value="Super Hard">Super Hard</option>
    </select>
    <select id="strategy" aria-label="strategy filter">
      <option value="">方針すべて</option>
      <option value="native">native</option>
      <option value="adapter">adapter</option>
      <option value="legacy compatibility">legacy compatibility</option>
      <option value="out-of-scope">out-of-scope</option>
    </select>
    <select id="browserVerification" aria-label="browser verification filter">
      <option value="">ブラウザ確認すべて</option>
      <option value="未対象">未対象</option>
      <option value="未確認">未確認</option>
      <option value="確認済み">確認済み</option>
    </select>
  </div>
  <table id="features">
    <thead>
      <tr>
        <th>EC-CUBE route</th>
        <th>HTTP</th>
        <th>BeMart path</th>
        <th>ALPS対応</th>
        <th>ALPS ID</th>
        <th>ALPS title</th>
        <th>type</th>
        <th>実装状態</th>
        <th>ブラウザ確認</th>
        <th>移植難易度 / 方針 / 根拠</th>
        <th>Resource</th>
      </tr>
    </thead>
    <tbody>
{$tableRows}    </tbody>
  </table>
</main>
<script>
const q = document.getElementById('q');
const alpsStatus = document.getElementById('alpsStatus');
const implementationStatus = document.getElementById('implementationStatus');
const difficulty = document.getElementById('difficulty');
const strategy = document.getElementById('strategy');
const browserVerification = document.getElementById('browserVerification');
const rows = [...document.querySelectorAll('#features tbody tr')];
function applyFilter() {
  const needle = q.value.trim().toLowerCase();
  const a = alpsStatus.value;
  const i = implementationStatus.value;
  const d = difficulty.value;
  const p = strategy.value;
  const b = browserVerification.value;
  for (const row of rows) {
    const text = row.innerText.toLowerCase();
    const okText = !needle || text.includes(needle);
    const okAlpsStatus = !a || row.dataset.alpsStatus === a;
    const okImplementationStatus = !i || row.dataset.implementationStatus === i;
    const okDifficulty = !d || row.dataset.difficulty === d;
    const okStrategy = !p || row.dataset.strategy === p;
    const okBrowserVerification = !b || row.dataset.browserVerification === b;
    row.style.display = okText && okAlpsStatus && okImplementationStatus && okDifficulty && okStrategy && okBrowserVerification ? '' : 'none';
  }
}
q.addEventListener('input', applyFilter);
alpsStatus.addEventListener('change', applyFilter);
implementationStatus.addEventListener('change', applyFilter);
difficulty.addEventListener('change', applyFilter);
strategy.addEventListener('change', applyFilter);
browserVerification.addEventListener('change', applyFilter);
</script>
</body>
</html>
HTML;

$outputPath = $repo . '/docs/eccube-feature-alps-status.html';
if (file_put_contents($outputPath, $html) === false) {
    fwrite(STDERR, "Failed to write {$outputPath}" . PHP_EOL);
    exit(1);
}

fprintf(STDERR, "Generated docs/eccube-feature-alps-status.html (%d rows)\n", count($rows));
