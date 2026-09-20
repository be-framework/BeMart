<?php

declare(strict_types=1);

namespace __NAMESPACE__\Module;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreException;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\Package\AbstractAppModule;
use BEAR\QueryRepository\DevQueryRepositoryLogModule;
use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\InvokerInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\Scope;
use Symfony\Component\Cache\Adapter\AdapterInterface;

use function array_slice;
use function bin2hex;
use function count;
use function glob;
use function gmdate;
use function max;
use function microtime;
use function random_bytes;
use function rmdir;
use function sort;
use function sprintf;

use const GLOB_ONLYDIR;

/**
 * Observation context: `dev-` prefix on the app context (`dev-hal-app`, `cli-dev-hal-app`).
 *
 * A context module inherits the whole inner chain, so it renames the chain's own
 * InvokerInterface and decorates it in place. Re-providing a wrapped PackageModule here
 * registers the package pointcuts a second time and every interceptor runs twice — the log
 * shows it as a scope nested in itself.
 */
final class DevModule extends AbstractAppModule
{
    private const ORIGINAL_INVOKER = 'original_invoker';

    /**
     * Body generations retained alongside log sessions (same count passed to
     * DevQueryRepositoryLogModule below): a log's body_ref keeps resolving for as long as the
     * log itself survives, and neither is pruned without the other (issue #134).
     *
     * Matches DevQueryRepositoryLogModule's own default so aligning the two lifetimes does not
     * shorten the log history the bear-observe skill tells people to read back through.
     */
    private const int KEEP_GENERATIONS = 100;

    #[Override]
    protected function configure(): void
    {
        $bodiesRoot = $this->appMeta->logDir . '/es-bodies';
        self::pruneStaleGenerations($bodiesRoot, self::KEEP_GENERATIONS);
        // One subdirectory per request: FileBodyStore's body sequence restarts at 1 on every
        // injector build, so a directory shared across sessions would let two sessions
        // overwrite each other's numbered files. The key sorts chronologically, matching the
        // shape of DevQueryRepositoryLogModule's own session filenames.
        $bodyDir = $bodiesRoot . '/' . self::generationKey();

        $this->rename(InvokerInterface::class, self::ORIGINAL_INVOKER);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::ORIGINAL_INVOKER,
                'recordedMethods' => Recorded::class,
                // Without this entry Ray.Di ignores the #[Filtered] attribute on the parameter
                // and an application-bound filter never reaches the invoker.
                'paramsFilter' => Filtered::class,
            ])
            ->in(Scope::SINGLETON);
        // GET is not a state change, so extraction ignores it; recording it is what makes
        // reads visible in the tree.
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
        $this->bind(BodyStoreInterface::class)->toInstance(new FileBodyStore($bodyDir));
        $this->install(new EventSourcingModule());

        // The cache log module owns the writer and the shutdown flush, so the application
        // writes no flush of its own.
        $this->install(new DevQueryRepositoryLogModule($this->appMeta->logDir . '/observe', self::KEEP_GENERATIONS));
        // One request, one tree: the resource invoker records into the logger the sink flushes.
        $this->bind(SemanticLoggerInterface::class)
            ->toProvider(ObserveLoggerProvider::class)->in(Scope::SINGLETON);

        // Without persistent pools every GET is a miss and the log can never show a hit.
        $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)
            ->toProvider(DevPoolProvider::class)->in(Scope::SINGLETON);
        $this->bind(AdapterInterface::class)->annotatedWith(EtagPool::class)
            ->toProvider(DevPoolProvider::class)->in(Scope::SINGLETON);
    }

    /** Sortable per-request key: zero-padded so lexical order is chronological order. */
    private static function generationKey(): string
    {
        $now = microtime(true);
        $seconds = (int) $now;
        $micro = (int) (($now - (float) $seconds) * 1_000_000.0);

        return gmdate('Ymd-His', $seconds) . '-' . sprintf('%06d', $micro) . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Deletes body generations beyond $keep, oldest first, leaving room for the one this
     * request is about to create. Mirrors LogFileWriter::prune()'s own retention count, so a
     * body generation is never pruned while the log session that references it still exists.
     */
    private static function pruneStaleGenerations(string $bodiesRoot, int $keep): void
    {
        $generations = glob($bodiesRoot . '/*', GLOB_ONLYDIR) ?: [];
        sort($generations);
        $overflow = max(0, count($generations) - $keep + 1);
        foreach (array_slice($generations, 0, $overflow) as $stale) {
            try {
                FileBodyStore::clearDirectory($stale);
                rmdir($stale);
            } catch (BodyStoreException) {
                // A sibling process pruning the same generation concurrently is not an error.
            }
        }
    }
}
