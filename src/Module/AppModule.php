<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Module\AppMetaModule;
use BEAR\Package\PackageModule;
use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use Be\Framework\Module\BeModule;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeCartCommand;
use MyVendor\BeMart\Be\Reason\Query\FakeCartQuery;
use MyVendor\BeMart\Be\Reason\Query\FakeCartStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerCommand;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeEmailUniquenessChecker;
use MyVendor\BeMart\Be\Reason\Query\FakeOrderQuery;
use MyVendor\BeMart\Be\Reason\Query\FakeProductClassQuery;
use MyVendor\BeMart\Be\Reason\Query\FakeProductQuery;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCustomerIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakeCustomerInitialPoint;
use MyVendor\BeMart\Be\Reason\Service\FakePasswordHasher;
use MyVendor\BeMart\Be\Reason\Service\FakePaymentMethodFactory;
use MyVendor\BeMart\Be\Reason\Service\FakePurchaseFlow;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use Ray\Di\Scope;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new PackageModule());
        // PackageModule does not bind @AppName by itself; BEAR\Package\Module
        // factory normally overrides it. Install explicitly so tests can use
        // `new Injector(new AppModule(...))` without the factory.
        $this->override(new AppMetaModule($this->appMeta));

        // Be Framework: BecomingInterface, SemanticLogger, semantic validator, Been provider.
        $this->install(new BeModule('MyVendor\\BeMart\\Be\\Semantic'));

        // Always-on semantic logging: wrap Be's Becoming with DevBecoming so
        // every request writes var/log/bemart.json. The DevSemanticLogger
        // singleton is what DevBecoming flushes after each metamorphosis.
        $this->bind(Becoming::class);
        $this->bind(BecomingInterface::class)->toProvider(DevBecomingProvider::class);
        $this->bind(SemanticLoggerInterface::class)
            ->toProvider(DevSemanticLoggerProvider::class)
            ->in(Scope::SINGLETON);

        // Reason (Pilot 1): Product Query → fixture-backed fake.
        $this->bind(ProductQueryInterface::class)->to(FakeProductQuery::class)->in(Scope::SINGLETON);

        // Reason (Pilot 2 doAddCartItem): ProductClass + Cart fakes.
        // FakeCartStorage is a singleton so the Command's writes are visible
        // to the Query within the same request (and within a single test).
        $this->bind(ProductClassQueryInterface::class)->to(FakeProductClassQuery::class)->in(Scope::SINGLETON);
        $this->bind(FakeCartStorage::class)->in(Scope::SINGLETON);
        $this->bind(CartQueryInterface::class)->to(FakeCartQuery::class);
        $this->bind(CartCommandInterface::class)->to(FakeCartCommand::class);

        // Reason (Pilot 3 doConfirmOrder): Order Query + PurchaseFlow + PaymentMethod factory fakes.
        $this->bind(OrderQueryInterface::class)->to(FakeOrderQuery::class)->in(Scope::SINGLETON);
        $this->bind(PurchaseFlowInterface::class)->to(FakePurchaseFlow::class);
        $this->bind(PaymentMethodFactoryInterface::class)->to(FakePaymentMethodFactory::class);

        // Reason (Pilot 4 doRegisterCustomer): Customer Storage shared by
        // EmailUniquenessChecker (read) and CustomerCommand (write). The
        // storage MUST be a Singleton so the Command's writes are visible
        // to the uniqueness check within the same request/test.
        $this->bind(FakeCustomerStorage::class)->in(Scope::SINGLETON);
        $this->bind(EmailUniquenessCheckerInterface::class)->to(FakeEmailUniquenessChecker::class);
        $this->bind(CustomerCommandInterface::class)->to(FakeCustomerCommand::class);
        $this->bind(PasswordHasherInterface::class)->to(FakePasswordHasher::class);
        $this->bind(CustomerIdGeneratorInterface::class)->to(FakeCustomerIdGenerator::class);
        $this->bind(CustomerInitialPointInterface::class)->to(FakeCustomerInitialPoint::class);
    }
}
