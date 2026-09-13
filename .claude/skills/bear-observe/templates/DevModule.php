<?php

declare(strict_types=1);

namespace __NAMESPACE__\Module;

use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\Package\AbstractAppModule;
use BEAR\QueryRepository\DevQueryRepositoryLogModule;
use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\InvokerInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\Scope;
use Symfony\Component\Cache\Adapter\AdapterInterface;

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

    protected function configure(): void
    {
        $bodyDir = $this->appMeta->logDir . '/es-bodies';
        FileBodyStore::clearDirectory($bodyDir);

        $this->rename(InvokerInterface::class, self::ORIGINAL_INVOKER);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::ORIGINAL_INVOKER,
                'recordedMethods' => Recorded::class,
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
