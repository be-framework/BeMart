<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\NullBodyStore;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\Resource\SemanticLogLinker;
use BEAR\EventSourcing\Resource\SensitiveParamsFilter;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\LinkerInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\ObserveModule;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Records every resource request, and each request with links as the link_request they nest in, into the given
 * logger, with a signed-in admin and a guest customer.
 *
 * Wraps the module it is given, as ObserveModule does, so the renames reach that module's invoker and linker.
 */
final class StoryModule extends AbstractModule
{
    public const string ADMIN_ID = 'ad000000000000000000000000000001';
    private const string ORIGINAL_INVOKER = 'original_invoker';
    private const string ORIGINAL_LINKER = 'original_linker';

    public function __construct(
        private readonly SemanticLoggerInterface $logger,
        AbstractModule $module,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->rename(InvokerInterface::class, self::ORIGINAL_INVOKER);
        $this->rename(LinkerInterface::class, self::ORIGINAL_LINKER);
        $this->bind(LinkerInterface::class)
            ->toConstructor(SemanticLogLinker::class, ['linker' => self::ORIGINAL_LINKER, 'recordedMethods' => Recorded::class])
            ->in(Scope::SINGLETON);
        $this->bind(ParamsFilterInterface::class)->annotatedWith(Filtered::class)
            ->toInstance(new SensitiveParamsFilter(ObserveModule::EXTRA_CREDENTIALS));
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::ORIGINAL_INVOKER,
                'recordedMethods' => Recorded::class,
                'paramsFilter' => Filtered::class,
            ])
            ->in(Scope::SINGLETON);
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
        $this->bind(BodyStoreInterface::class)->to(NullBodyStore::class);
        $this->install(new EventSourcingModule());
        $this->bind(SemanticLoggerInterface::class)->toInstance($this->logger);
        // TestModule's DevBecoming flushes the logger after each becoming, which would split a story's tree.
        $this->bind(BecomingInterface::class)->to(Becoming::class);
        $this->bind(CustomerSession::class)->toInstance(new FakeSession(null));
        $this->bind(AdminSession::class)->toInstance(new FakeAdminSession(self::ADMIN_ID));
    }
}
