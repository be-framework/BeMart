<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\QueryRepository\Log\SafeSemanticLogger;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Injector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * The agent reads the same corpus a shopper does, so an admin edit has to reach its copy
 *
 * A TTL would pass a store-and-hit assertion and leave the staleness in place, which is why the
 * read after the invalidation is the one that matters here.
 *
 * The pool comes from here because no context binds one: every context leaves the framework
 * default in place, a `NullAdapter` that answers every lookup with a miss, so this is the only
 * place the suite sees a cache hit at all.
 */
final class AgentCorpusCacheTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function agentReads(): array
    {
        return [
            'catalog' => ['app://self/agent/catalog?limit=3'],
            'product' => ['app://self/agent/product?productCode=sample-001'],
        ];
    }

    #[DataProvider('agentReads')]
    public function testTheAgentCopyIsCachedAndFallsWithTheCorpusTag(string $uri): void
    {
        // Pools and the log only. Installing a storage module here would bring a second
        // QueryRepositoryModule with it, and Ray.Aop accumulates pointcuts: every cache
        // interceptor the application already has would run twice.
        $override = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)->toInstance(new ArrayAdapter());
                $this->bind(SemanticLoggerInterface::class)->annotatedWith(CacheLog::class)
                    ->toInstance(new SafeSemanticLogger(new SemanticLogger()));
            }
        };
        $injector = Injector::getOverrideInstance('cli-fake-hal-app', $override);
        $resource = $injector->getInstance(ResourceInterface::class);
        $logger = $injector->getInstance(SemanticLoggerInterface::class, CacheLog::class);

        $resource->get($uri);
        self::assertContains('save_value', self::types($logger->flush()), 'the cold read stored nothing');

        $resource->get($uri);
        self::assertContains('cache_hit', self::types($logger->flush()), 'the second read is not a hit');

        $injector->getInstance(ProductCacheInvalidatorInterface::class)->invalidateCorpus();
        $logger->flush();

        $resource->get($uri);
        self::assertContains('cache_miss', self::types($logger->flush()), 'the agent kept its copy of a corpus that changed');
    }

    /** @return list<string> */
    private static function types(LogJson $log): array
    {
        $types = [];
        $walk = static function (array $node) use (&$walk, &$types): void {
            $types[] = (string) $node['type'];
            foreach (['open', 'events'] as $key) {
                foreach ((array) ($node[$key] ?? []) as $child) {
                    $walk((array) $child);
                }
            }

            if (isset($node['close'])) {
                $walk((array) $node['close']);
            }
        };

        $tree = $log->toArray();
        foreach (['open', 'events'] as $key) {
            foreach ((array) ($tree[$key] ?? []) as $node) {
                $walk((array) $node);
            }
        }

        return $types;
    }
}
