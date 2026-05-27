#!/usr/bin/env php
<?php

declare(strict_types=1);

use MyVendor\BeMart\Router\AlpsRouteMap;
use MyVendor\BeMart\Router\RouteTable;

require dirname(__DIR__) . '/vendor/autoload.php';

$repo = dirname(__DIR__);
$alps = json_decode((string) file_get_contents($repo . '/alps.json'), true, flags: JSON_THROW_ON_ERROR);

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

foreach (RouteTable::default()->routes as $route) {
    $uniqueRouteNames[$route->name] = true;
    foreach ($route->methods as $method) {
        $methodEntries++;
        $alpsId = AlpsRouteMap::forRouteMethod($route, $method);
        $hasAlpsDescriptor = isset($descriptorById[$alpsId]);
        $descriptor = $descriptorById[$alpsId] ?? ['title' => '(missing)', 'type' => null, 'rt' => null];
        $isActionRedirect = str_contains($route->resource, 'action-redirect');
        $alpsStatus = $hasAlpsDescriptor ? '対応済み' : '未対応';
        $implementationStatus = $isActionRedirect ? '安全退避(ActionRedirect)' : '実装済み';
        $implementationDetail = $isActionRedirect
            ? 'RouteTableはActionRedirectへ到達。業務処理Resourceへの接続は後続タスク。'
            : 'RouteTableから具体Resourceへ到達。';

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

        $rows[] = [
            'route' => $route->name,
            'method' => $method,
            'path' => $route->path,
            'alpsId' => $alpsId,
            'alpsTitle' => $descriptor['title'],
            'alpsType' => $descriptor['type'] ?? '',
            'resource' => $route->resource,
            'dispatch' => $route->dispatchMethodFor($method),
            'alpsStatus' => $alpsStatus,
            'implementationStatus' => $implementationStatus,
            'implementationDetail' => $implementationDetail,
        ];
    }
}

usort($rows, static fn (array $a, array $b): int => [$a['route'], $a['method'], $a['path']] <=> [$b['route'], $b['method'], $b['path']]);

$h = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$date = date('Y-m-d');
$totalRoutes = count(RouteTable::default()->routes);
$totalNames = count($uniqueRouteNames);

$tableRows = '';
foreach ($rows as $row) {
    $alpsClass = $row['alpsStatus'] === '対応済み' ? 'alps-ok' : 'alps-missing';
    $implementationClass = $row['implementationStatus'] === '実装済み' ? 'impl-ok' : 'impl-fallback';
    $tableRows .= '<tr data-alps-status="' . $h($row['alpsStatus']) . '" data-implementation-status="' . $h($row['implementationStatus']) . '">'
        . '<td><code>' . $h($row['route']) . '</code></td>'
        . '<td><span class="method">' . $h($row['method']) . '</span></td>'
        . '<td><code>' . $h($row['path']) . '</code></td>'
        . '<td><span class="status ' . $alpsClass . '">' . $h($row['alpsStatus']) . '</span></td>'
        . '<td><code>' . $h($row['alpsId']) . '</code></td>'
        . '<td>' . $h($row['alpsTitle']) . '</td>'
        . '<td>' . $h($row['alpsType']) . '</td>'
        . '<td><span class="status ' . $implementationClass . '">' . $h($row['implementationStatus']) . '</span><br><small>' . $h($row['implementationDetail']) . '</small></td>'
        . '<td><code>' . $h($row['resource']) . '</code><br><small>method: ' . $h($row['dispatch']) . '</small></td>'
        . '</tr>' . PHP_EOL;
}

$html = <<<HTML
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EC-CUBE機能リスト ↔ ALPS ID ↔ BeMart実装状態</title>
  <style>
    :root { color-scheme: light; --fg:#172033; --muted:#5c667a; --line:#d8dee9; --ok:#0a7f42; --warn:#9a5a00; --bad:#b42318; --bg:#ffffff; --soft:#f6f8fb; }
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
    .status.alps-ok, .status.impl-ok { color: var(--ok); }
    .status.alps-missing { color: var(--bad); }
    .status.impl-fallback { color: var(--warn); }
    .note { border-left: 4px solid #7aa2ff; padding: 10px 14px; background: #f5f8ff; }
    @media (max-width: 900px) { header, main { padding-left: 16px; padding-right: 16px; } table { font-size: 12px; } }
  </style>
</head>
<body>
<header>
  <h1>EC-CUBE機能リスト ↔ ALPS ID ↔ BeMart実装状態</h1>
  <p class="meta">Generated {$date} from <code>RouteTable</code>, <code>AlpsRouteMap</code>, and <code>alps.json</code>.</p>
</header>
<main>
  <p class="note">この表はEC-CUBE route名を「機能リスト」として扱い、各HTTP methodごとに対応するALPS descriptorとBeMart側の到達状態を示します。URLやHTTP methodはALPSではなくRouteTableの責務です。</p>
  <section class="cards" aria-label="summary">
    <div class="card"><strong>{$totalNames}</strong>EC-CUBE route names</div>
    <div class="card"><strong>{$totalRoutes}</strong>RouteTable entries</div>
    <div class="card"><strong>{$methodEntries}</strong>method entries</div>
    <div class="card"><strong>{$alpsMapped}</strong>ALPS対応済み</div>
    <div class="card"><strong>{$alpsMissing}</strong>ALPS未対応</div>
    <div class="card"><strong>{$implemented}</strong>実装済み</div>
    <div class="card"><strong>{$actionRedirect}</strong>安全退避(ActionRedirect)</div>
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
const rows = [...document.querySelectorAll('#features tbody tr')];
function applyFilter() {
  const needle = q.value.trim().toLowerCase();
  const a = alpsStatus.value;
  const i = implementationStatus.value;
  for (const row of rows) {
    const text = row.innerText.toLowerCase();
    const okText = !needle || text.includes(needle);
    const okAlpsStatus = !a || row.dataset.alpsStatus === a;
    const okImplementationStatus = !i || row.dataset.implementationStatus === i;
    row.style.display = okText && okAlpsStatus && okImplementationStatus ? '' : 'none';
  }
}
q.addEventListener('input', applyFilter);
alpsStatus.addEventListener('change', applyFilter);
implementationStatus.addEventListener('change', applyFilter);
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
