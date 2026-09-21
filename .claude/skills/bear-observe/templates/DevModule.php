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
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\Scope;
use Symfony\Component\Cache\Adapter\AdapterInterface;

use function array_slice;
use function bin2hex;
use function count;
use function glob;
use function gmdate;
use function is_dir;
use function is_file;
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
    /**
     * Ownership marker FileBodyStore writes into each directory it manages; its own copy of this
     * name is private, so pruning re-states it rather than miscounting a directory it does not own.
     */
    private const string BODY_STORE_MARKER = '.bear-es-bodies';

    #[Override]
    protected function configure(): void
    {
        $bodiesRoot = $this->appMeta->logDir . '/es-bodies';
        self::pruneStaleGenerations($bodiesRoot, self::KEEP_GENERATIONS);
        // One subdirectory per stored body set: FileBodyStore's sequence restarts at 1 on every
        // injector build, so a directory shared across sessions would let two sessions overwrite
        // each other's numbered files. The key sorts chronologically, matching the shape of
        // DevQueryRepositoryLogModule's own session filenames.
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
        // Deferred, not `new FileBodyStore($bodyDir)`: that constructor creates its directory
        // eagerly, and configure() runs before routing knows the request method. A request that
        // records nothing (an OPTIONS preflight never reaches the recorded methods, and
        // LogFileWriter::write() returns early when nothing was opened) would then add a
        // generation without adding a log, letting the body window advance past the log window
        // until a retained log's body_ref points at a pruned generation. Creating the directory
        // only when a body is stored makes generations strictly rarer than log sessions.
        $this->bind(BodyStoreInterface::class)->toInstance(
            new class ($bodyDir) implements BodyStoreInterface {
                private FileBodyStore|null $store = null;

                public function __construct(private readonly string $dir)
                {
                }

                public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
                {
                    $this->store ??= new FileBodyStore($this->dir);

                    return ($this->store)($request, $ro);
                }
            },
        );
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
     * Deletes body generations beyond $keep, oldest first.
     *
     * Shares its retention count with LogFileWriter::prune(). A generation is only created when a
     * body is actually stored, which requires a recorded request, which writes a log session — so
     * generations are always rarer than logs and the body window spans at least as far back as the
     * log window. A retained log's `body_ref` therefore still resolves. The converse does not hold
     * and is not claimed: a crashed process can leave a generation whose log was never written,
     * and that generation is pruned on count alone.
     */
    private static function pruneStaleGenerations(string $bodiesRoot, int $keep): void
    {
        // Only directories carrying FileBodyStore's ownership marker. Counting anything else
        // toward the cap over-prunes: a foreign directory that sorts newer than the generations
        // (any lowercase name does — ASCII puts letters after digits) inflates the overflow while
        // the oldest-first slice stays entirely ours, so one stray `es-bodies/tmp` permanently
        // costs one real generation and shrinks the body window below the log window.
        $generations = [];
        foreach (glob($bodiesRoot . '/*', GLOB_ONLYDIR) ?: [] as $candidate) {
            if (! is_file($candidate . '/' . self::BODY_STORE_MARKER)) {
                continue;
            }

            $generations[] = $candidate;
        }

        sort($generations);
        $overflow = max(0, count($generations) - $keep);
        foreach (array_slice($generations, 0, $overflow) as $stale) {
            try {
                FileBodyStore::clearDirectory($stale);
            } catch (BodyStoreException) {
                // Skipped, not reported: a sibling process pruning the same generation wins the
                // race, and a directory without FileBodyStore's ownership marker is refused on
                // purpose. Neither is this module's to fix, and an unowned directory is left
                // where it is rather than deleted.
                continue;
            }

            // The directory is empty by now, so removing it is cosmetic: leaving one behind
            // orphans nothing, because the log that referenced its bodies is pruned too. A
            // concurrent prune can win the race between these two calls, so an unchecked
            // rmdir() would emit a warning for a state that is already the desired one.
            if (! is_dir($stale)) {
                continue;
            }

            @rmdir($stale);
        }
    }
}
