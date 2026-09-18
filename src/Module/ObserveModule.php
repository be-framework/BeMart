<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
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
    private const ORIGINAL_INVOKER = 'original_invoker';

    #[Override]
    protected function configure(): void
    {
        $bodyDir = $this->appMeta->logDir . '/es-bodies';
        FileBodyStore::clearDirectory($bodyDir);

        $this->bind(ParamsFilterInterface::class)->annotatedWith(Filtered::class)
            ->toInstance(new SensitiveParamsFilter(['resetKey', 'authKey']));
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
            ->toInstance(new ExcludedResponseBodyStore(new FileBodyStore($bodyDir)));
        $this->install(new EventSourcingModule());

        // The cache log module owns the writer and the shutdown flush, so the application
        // writes no flush of its own.
        $this->install(new DevQueryRepositoryLogModule($this->appMeta->logDir . '/observe'));
        // One request, one tree: the resource invoker records into the logger the sink flushes.
        $this->bind(SemanticLoggerInterface::class)
            ->toProvider(ObserveLoggerProvider::class)->in(Scope::SINGLETON);

        // Without persistent pools every GET is a miss and the log can never show a hit.
        $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)
            ->toProvider(DevPoolProvider::class)->in(Scope::SINGLETON);
        $this->bind(AdapterInterface::class)->annotatedWith(EtagPool::class)
            ->toProvider(DevPoolProvider::class)->in(Scope::SINGLETON);
    }
}
