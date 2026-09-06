<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\QueryRepository\Expiry;
use BEAR\QueryRepository\Log\SafeSemanticLogger;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Resource\App\Agent\Catalog;
use MyVendor\BeMart\Resource\App\Agent\Product as AgentProduct;
use MyVendor\BeMart\Resource\App\Product;
use MyVendor\BeMart\Resource\App\Products;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * The agent reads the same corpus a shopper does, so an admin edit has to reach its copy
 *
 * The tag answers an admin edit and the TTL is the floor under the stock number each body copies,
 * which no write path announces. Both are asserted here: the read after the invalidation, and the
 * lifetime the copy is allowed to have.
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

    /** @return array<string, array{class-string, class-string}> */
    public static function copies(): array
    {
        return [
            'catalog of products' => [Catalog::class, Products::class],
            'product detail' => [AgentProduct::class, Product::class],
        ];
    }

    /**
     * @param class-string $copy
     * @param class-string $original
     */
    #[DataProvider('copies')]
    public function testTheAgentCopyDoesNotOutliveTheOneItDuplicates(string $copy, string $original): void
    {
        $expiry = Injector::getInstance('cli-fake-hal-app')->getInstance(Expiry::class);

        self::assertLessThanOrEqual(
            self::lifetime($original, $expiry),
            self::lifetime($copy, $expiry),
            'the agent answers with a stock number the storefront has already stopped serving',
        );
    }

    /** @param class-string $class */
    private static function lifetime(string $class, Expiry $expiry): int
    {
        $cacheable = (new ReflectionClass($class))->getAttributes(Cacheable::class)[0]->newInstance();

        return $cacheable->expirySecond > 0 ? $cacheable->expirySecond : $expiry->getTime($cacheable->expiry);
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
