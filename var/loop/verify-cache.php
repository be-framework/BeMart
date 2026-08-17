<?php

declare(strict_types=1);

/**
 * The oracle for the cache loop: exercise one flow and judge it from the log alone
 *
 * Usage: php var/loop/verify-cache.php <flow>
 *
 * A flow names a cached read and, when it has one, the write that must invalidate it. The script
 * boots the application with a real pool and recording on - neither is bound by default, so
 * without this the attributes are inert and every assertion below would pass vacuously - then
 * checks the invariants that a working cache has to satisfy:
 *
 *   1. the cold read stores something (`save_*` with `saved: true`)
 *   2. the second read is a hit
 *   3. an embedded child appears as a `depends_on` edge, and the parent's save carries the
 *      child's tag: without that the child's write cannot reach the parent
 *   4. the write's `invalidate` tags meet the parent's save tags (set intersection)
 *   5. the read after the write is a miss again - a hit here is stale content
 *   6. no entry is saved with an empty tag list, which no invalidation can ever reach
 *
 * Exit code 0 means the log proved it. Non-zero prints which invariant failed and the tree.
 */

use BEAR\QueryRepository\Log\SafeSemanticLogger;
use BEAR\QueryRepository\ProdQueryRepositoryModule;
use BEAR\QueryRepository\StorageRedisDsnModule;
use BEAR\QueryRepository\QueryRepositoryModule;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\QueryRepository\QueryRepositoryInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\Uri;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Injector;
use Ray\Di\AbstractModule;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

const FLOWS = [
    'help' => [
        'read' => 'page://self/help/about',
        'write' => null,
        'embeds' => false,
    ],
    'agent-catalog' => [
        'read' => 'app://self/agent/catalog',
        'write' => null,
        'embeds' => false,
    ],
    // The storefront list: the page is per-request (CSRF, sort, paging), the corpus it embeds is
    // the cached part. Reading the page proves the embedded resource is what fills and hits.
    'products-app' => [
        'read' => 'app://self/products',
        'write' => null,
        'embeds' => false,
    ],
    // A page that carries a CSRF token must NOT be cached; what it must do is hit the child it
    // embeds. Caching it would hand one shopper's token to the next.
    // The dependency case: master data embeds the number that moves. Purging the child is what an
    // admin write hook would do, and the parent has to fall with it.
    'product-stock' => [
        'read' => 'app://self/product?productCode=sample-001',
        'write' => null,
        'purge' => 'app://self/product/stock?productCode=sample-001',
        'embeds' => true,
    ],
    'products-page' => [
        'read' => 'page://self/products',
        'write' => null,
        'embeds' => false,
        'mode' => 'per-request',
    ],
];

$flowName = $argv[1] ?? '';
if (! isset(FLOWS[$flowName])) {
    fwrite(STDERR, sprintf("unknown flow '%s'; known: %s\n", $flowName, implode(', ', array_keys(FLOWS))));
    exit(2);
}

$flow = FLOWS[$flowName];
$logDir = dirname(__DIR__) . '/loop/log/' . $flowName;
exec('rm -rf ' . escapeshellarg($logDir));

// The oracle owns its cold state, or "cold read" is a lie
$dsnForFlush = getenv('CACHE_DSN') ?: '';
if ($dsnForFlush !== '') {
    (new Predis\Client($dsnForFlush))->flushall();
} else {
    exec('rm -rf ' . escapeshellarg(dirname(__DIR__) . '/tmp/' . (getenv('APP_CONTEXT') ?: 'eccube-sql-hal-app') . '/cache'));
}

$dsn = getenv('CACHE_DSN') ?: '';
$override = new class ($dsn) extends AbstractModule {
    public function __construct(private string $dsn)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // Pools only. Installing a log module here would bring a second QueryRepositoryModule with
        // it, and Ray.Aop accumulates pointcuts: the application already installed one through
        // PackageModule, so every cache interceptor would run twice - two lookups, two writes per
        // request, and a log that shows the same URI nested inside itself.
        $this->install($this->dsn === ''
            ? new ProdQueryRepositoryModule()
            : new StorageRedisDsnModule($this->dsn));
        // Recording on, by replacing the one binding that decides it
        $this->bind(SemanticLoggerInterface::class)->annotatedWith(CacheLog::class)
            ->toInstance(new SafeSemanticLogger(new SemanticLogger()));
    }
};

// Context is a parameter: the sql contexts talk to a database that may hold no products, the
// fake contexts carry a deterministic corpus. A cache is indifferent to which, the assertions are not.
$context = getenv('APP_CONTEXT') ?: 'eccube-sql-hal-app';
$injector = Injector::getOverrideInstance($context, $override);
$resource = $injector->getInstance(ResourceInterface::class);
$logger = $injector->getInstance(SemanticLoggerInterface::class, CacheLog::class);

/** @return list<array{type: string, context: array<string, mixed>}> */
function flatten(LogJson $log): array
{
    $flat = [];
    $walk = static function (array $node) use (&$walk, &$flat): void {
        $flat[] = ['type' => (string) $node['type'], 'context' => (array) ($node['context'] ?? [])];
        foreach ((array) ($node['open'] ?? []) as $child) {
            $walk((array) $child);
        }

        foreach ((array) ($node['events'] ?? []) as $event) {
            $walk((array) $event);
        }

        if (isset($node['close'])) {
            $walk((array) $node['close']);
        }
    };
    $tree = $log->toArray();
    foreach ((array) ($tree['open'] ?? []) as $node) {
        $walk((array) $node);
    }

    foreach ((array) ($tree['events'] ?? []) as $node) {
        $walk((array) $node);
    }

    return $flat;
}

/** @param list<array{type: string, context: array<string, mixed>}> $entries */
function typesOf(array $entries): array
{
    return array_map(static fn (array $e): string => $e['type'], $entries);
}

/** @param list<array{type: string, context: array<string, mixed>}> $entries */
function tagsOf(array $entries, string $prefix): array
{
    $tags = [];
    foreach ($entries as $entry) {
        if (! str_starts_with($entry['type'], $prefix)) {
            continue;
        }

        foreach ((array) ($entry['context']['tags'] ?? []) as $tag) {
            $tags[] = (string) $tag;
        }
    }

    return array_values(array_unique($tags));
}

/**
 * Violations already tracked upstream, so the loop fails on new ones only
 *
 * Each entry names the substring and the issue that owns it. Remove an entry when its issue
 * closes - if the defect is still there, the next run says so.
 */
const KNOWN = [
    'save_donut saved with an empty tag list' => 'bearsunday/BEAR.QueryRepository#185',
];

$violations = [];
$known = [];
$sessions = [];

$read = static function (string $label) use ($resource, $logger, $flow, &$sessions): array {
    $resource->get($flow['read']);
    $entries = flatten($logger->flush());
    $sessions[$label] = $entries;

    return $entries;
};

$cold = $read('cold read');
$types = typesOf($cold);
$mode = $flow['mode'] ?? 'cached';

if ($mode === 'per-request') {
    // 8. the response is assembled per request: nothing about it may be stored, and the child it
    // embeds has to be the thing that fills and answers
    $ownSaves = array_filter(
        $cold,
        static fn (array $e): bool => str_starts_with($e['type'], 'save_') && ($e['context']['uri'] ?? '') === $flow['read'],
    );
    if ($ownSaves !== []) {
        $violations[] = sprintf('8: %s is assembled per request, but the log stores it', $flow['read']);
    }

    $warmPerRequest = $read('second read');
    if (! in_array('cache_hit', typesOf($warmPerRequest), true)) {
        $violations[] = '8: the embedded resource is not answering from cache through this page';
    }

    foreach ($sessions as $label => $entries) {
        printf("%-18s %s\n", $label, implode(' ', typesOf($entries)));
    }

    foreach (array_unique($known) as $entry) {
        printf("KNOWN %s\n", $entry);
    }

    if ($violations === []) {
        printf("\nOK %s: nothing about the page is stored, and its child answers from cache\n", $flowName);
        exit(0);
    }

    printf("\nFAIL %s\n", $flowName);
    foreach ($violations as $violation) {
        printf("  - %s\n", $violation);
    }

    exit(1);
}

// 1. the cold read stored something
$saves = array_values(array_filter($cold, static fn (array $e): bool => str_starts_with($e['type'], 'save_')));
if ($saves === []) {
    $violations[] = '1: the cold read wrote nothing (no save_* event)';
}

foreach ($saves as $save) {
    if (($save['context']['saved'] ?? null) === false) {
        $violations[] = sprintf('1: %s reports saved: false - the pool refused the entry', $save['type']);
    }

    // 6. an entry with no tags is unreachable by any invalidation
    if (($save['context']['tags'] ?? null) === []) {
        $violation = sprintf('6: %s saved with an empty tag list - no invalidation can reach it', $save['type']);
        $issue = null;
        foreach (KNOWN as $needle => $tracked) {
            if (str_contains($violation, $needle)) {
                $issue = $tracked;
                break;
            }
        }

        $issue === null ? $violations[] = $violation : $known[] = $violation . ' [' . $issue . ']';
    }
}

// 3. an embedded child has to show up as an edge, and its tag has to be on the parent's entry
if ($flow['embeds']) {
    if (! in_array('depends_on', $types, true)) {
        $violations[] = '3: no depends_on edge - the parent is not stored under its children tags';
    }

    $childTags = [];
    foreach ($cold as $entry) {
        if ($entry['type'] !== 'depends_on') {
            continue;
        }

        foreach ((array) ($entry['context']['childTags'] ?? []) as $tag) {
            $childTags[] = (string) $tag;
        }
    }

    $parentTags = tagsOf($cold, 'save_');
    if ($childTags !== [] && array_intersect($childTags, $parentTags) === []) {
        $violations[] = sprintf(
            '3: the child tags %s are absent from the parent save tags %s',
            json_encode($childTags),
            json_encode($parentTags),
        );
    }
}

// 2. the second read is a hit
$warm = $read('warm read');
if (! in_array('cache_hit', typesOf($warm), true)) {
    $violations[] = '2: the second read is not a hit';
}

$purgeUri = $flow['purge'] ?? null;
if ($purgeUri !== null) {
    // A direct purge, the way an admin write hook would invalidate: it opens a manual_purge scope
    $injector->getInstance(QueryRepositoryInterface::class)->purge(new Uri($purgeUri));
    $purgeEntries = flatten($logger->flush());
    $sessions['purge child'] = $purgeEntries;

    $savedTags = tagsOf($cold, 'save_');
    $purgedTags = tagsOf($purgeEntries, 'invalidate');
    // 4. the purge has to reach a tag the parent was stored under
    if (array_intersect($purgedTags, $savedTags) === []) {
        $violations[] = sprintf(
            '4: purging the child invalidated %s, which does not meet the parent read tags %s',
            json_encode($purgedTags),
            json_encode($savedTags),
        );
    }

    // 5. the parent must rebuild
    $afterPurge = $read('read after purge');
    if (in_array('cache_hit', typesOf($afterPurge), true)) {
        $violations[] = '5: the parent still hits after its child was purged - stale content';
    }
}

if ($flow['write'] !== null) {
    $resource->put($flow['write']);
    $writeEntries = flatten($logger->flush());
    $sessions['write'] = $writeEntries;

    $invalidateTags = tagsOf($writeEntries, 'invalidate');
    $savedTags = tagsOf($cold, 'save_');
    // 4. the write has to invalidate a tag the parent was stored under
    if (array_intersect($invalidateTags, $savedTags) === []) {
        $violations[] = sprintf(
            '4: the write invalidated %s, which does not meet the read tags %s',
            json_encode($invalidateTags),
            json_encode($savedTags),
        );
    }

    // 5. the read after the write must rebuild
    $after = $read('read after write');
    if (in_array('cache_hit', typesOf($after), true)) {
        $violations[] = '5: the read after the write is a hit - stale content is being served';
    }
}

foreach ($sessions as $label => $entries) {
    printf("%-18s %s\n", $label, implode(' ', typesOf($entries)));
}

// 7. what the store actually did with the lifetime the log claims
if ($dsn !== '') {
    $client = new Predis\Client($dsn);
    /** @var list<string> $keys */
    $keys = $client->keys('*');
    $claimsNoExpiry = [];
    foreach ($sessions['cold read'] as $entry) {
        if (str_starts_with($entry['type'], 'save_') && in_array($entry['context']['requestedTtl'] ?? null, [null, 0], true)) {
            $claimsNoExpiry[] = $entry['type'];
        }
    }

    foreach ($keys as $key) {
        $ttl = (int) $client->ttl($key);
        if ($claimsNoExpiry !== [] && $ttl > 0) {
            // Not a defect: requestedTtl is what this package asked for, and the backend decides.
            // Worth printing because an operator reading "no expiry" still has entries expiring.
            $known[] = sprintf(
                '7: %s asked for no expiry; the store gave %s a TTL of %d seconds [backend floor]',
                implode('/', array_unique($claimsNoExpiry)),
                $key,
                $ttl,
            );
            break;
        }
    }
}

foreach (array_unique($known) as $entry) {
    printf("KNOWN %s\n", $entry);
}

if ($violations === []) {
    printf(
        "\nOK %s: the log proves store, hit%s\n",
        $flowName,
        $flow['write'] !== null || ($flow['purge'] ?? null) !== null ? ', invalidation and rebuild' : '',
    );
    exit(0);
}

printf("\nFAIL %s\n", $flowName);
foreach ($violations as $violation) {
    printf("  - %s\n", $violation);
}

exit(1);
