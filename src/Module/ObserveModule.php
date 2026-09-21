<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreException;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\Resource\SensitiveParamsFilter;
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
use function is_dir;
use function max;
use function microtime;
use function random_bytes;
use function rmdir;
use function sort;
use function sprintf;

use const GLOB_ONLYDIR;

/**
 * Observation context: `observe-` prefix on the app context (`cli-observe-fake-hal-app`).
 *
 * Folded from the bear-observe skill's generated DevModule.php.observe template. It stays out
 * of DevModule on purpose: DevBecoming flushes the semantic logger after every becoming, which
 * would cut a request's tree in two, so this observation chain never includes `dev`.
 *
 * A context module inherits the whole inner chain, so it renames the chain's own
 * InvokerInterface and decorates it in place. Re-providing a wrapped PackageModule here
 * registers the package pointcuts a second time and every interceptor runs twice — the log
 * shows it as a scope nested in itself.
 *
 * BeMart adds two of its own credential names to the library's default filter (bound as the
 * fallback): the library's SensitiveParamsFilter deliberately does not match a generic `key`
 * suffix (an idempotencyKey is domain input, not a secret), so `resetKey` (the single-use
 * password-reset token) and `authKey` (the TOTP shared secret carried during two-factor setup)
 * are passed as extra credential substrings. Each field's key still reaches the log; only its
 * value is replaced with `SensitiveParamsFilter::FILTERED`.
 *
 * That filter only reaches request params. `SemanticLogInvoker` hands the full, unfiltered
 * response to `BodyStoreInterface`, so `{@see ExcludedResponseBodyStore}` never persists a
 * `page://` response body at all — that HTML-rendering layer is where a form embeds a credential
 * for prefill (see its own docblock); the nested `app://` data layer a page wraps still is.
 */
final class ObserveModule extends AbstractAppModule
{
    /**
     * Credential substrings the library's default filter deliberately does not match.
     *
     * Single source for the policy: `EventSourcingExtractionTest` builds its filter from this
     * constant, so shortening the list here makes that test's redaction assertions fail.
     */
    public const array EXTRA_CREDENTIALS = ['resetKey', 'authKey'];

    /**
     * Body generations retained alongside log sessions (same count passed to
     * DevQueryRepositoryLogModule below): a log's body_ref keeps resolving for as long as the
     * log itself survives, and neither is pruned without the other (issue #134).
     *
     * Matches DevQueryRepositoryLogModule's own default so aligning the two lifetimes does not
     * shorten the log history the bear-observe skill tells people to read back through.
     */
    public const int KEEP_GENERATIONS = 100;

    private const ORIGINAL_INVOKER = 'original_invoker';

    #[Override]
    protected function configure(): void
    {
        $bodiesRoot = $this->appMeta->logDir . '/es-bodies';
        self::pruneStaleGenerations($bodiesRoot, self::KEEP_GENERATIONS);
        // One subdirectory per stored body set: FileBodyStore's sequence restarts at 1 on every
        // injector build, so a directory shared across sessions would let two sessions overwrite
        // each other's numbered files. The key sorts chronologically, matching the shape of
        // DevQueryRepositoryLogModule's own session filenames. DeferredFileBodyStore creates it
        // only if a body is actually stored, which is what keeps generations rarer than logs.
        $bodyDir = $bodiesRoot . '/' . self::generationKey();

        $this->bind(ParamsFilterInterface::class)->annotatedWith(Filtered::class)
            ->toInstance(new SensitiveParamsFilter(self::EXTRA_CREDENTIALS));
        $this->rename(InvokerInterface::class, self::ORIGINAL_INVOKER);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::ORIGINAL_INVOKER,
                'recordedMethods' => Recorded::class,
                'paramsFilter' => Filtered::class,
            ])
            ->in(Scope::SINGLETON);
        // GET is not a state change, so extraction ignores it; recording it is what makes
        // reads visible in the tree.
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
        $this->bind(BodyStoreInterface::class)
            ->toInstance(new ExcludedResponseBodyStore(new DeferredFileBodyStore($bodyDir)));
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
     * body is actually stored ({@see DeferredFileBodyStore}), which requires a recorded request,
     * which writes a log session — so generations are always rarer than logs and the body window
     * spans at least as far back as the log window. A retained log's `body_ref` therefore still
     * resolves. The converse does not hold and is not claimed: a crashed process can leave a
     * generation whose log was never written, and that generation is pruned on count alone.
     */
    private static function pruneStaleGenerations(string $bodiesRoot, int $keep): void
    {
        $generations = glob($bodiesRoot . '/*', GLOB_ONLYDIR) ?: [];
        sort($generations);
        $overflow = max(0, count($generations) - $keep);
        foreach (array_slice($generations, 0, $overflow) as $stale) {
            try {
                FileBodyStore::clearDirectory($stale);
            } catch (BodyStoreException) {
                // A sibling process pruning the same generation concurrently is not an error.
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
