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
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\BaseInfoStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeAdminCommand;
use MyVendor\BeMart\Be\Reason\Query\FakeAdminQuery;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeAddressStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeAdminStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeBaseInfoStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeCategoryStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeClassCategoryStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeClassNameStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeCsvColumnConfigStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeLoginHistoryStorage;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeCartCommand;
use MyVendor\BeMart\Be\Reason\Query\FakeCartQuery;
use MyVendor\BeMart\Be\Reason\Query\FakeCartStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerCommand;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerQuery;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeEmailUniquenessChecker;
use MyVendor\BeMart\Be\Reason\Query\FakeFavoriteStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeMailTemplateStorage;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeOrderCommand;
use MyVendor\BeMart\Be\Reason\Query\FakeOrderQuery;
use MyVendor\BeMart\Be\Reason\Query\FakePasswordResetTokenStorage;
use MyVendor\BeMart\Be\Reason\Query\FakePluginStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeProductClassQuery;
use MyVendor\BeMart\Be\Reason\Query\FakeProductCommand;
use MyVendor\BeMart\Be\Reason\Query\FakeProductQuery;
use MyVendor\BeMart\Be\Reason\Query\FakeProductStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeTradeLawStorage;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\PasswordResetTokenStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AddressIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\CategoryIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCategoryIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassNameIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAddressIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakeClassCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakeClassNameIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\FakeCustomerIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakeCustomerInitialPoint;
use MyVendor\BeMart\Be\Reason\Service\FakeInventoryAllocator;
use MyVendor\BeMart\Be\Reason\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Service\FakeOrderNumberGenerator;
use MyVendor\BeMart\Be\Reason\Service\FakePasswordHasher;
use MyVendor\BeMart\Be\Reason\Service\FakePaymentGateway;
use MyVendor\BeMart\Be\Reason\Service\FakePaymentMethodFactory;
use MyVendor\BeMart\Be\Reason\Service\FakePurchaseFlow;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Be\Reason\Service\OrderNumberGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentGatewayInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
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
        // Pilot 6 (doLogin): read-side Customer lookup over the same Storage.
        $this->bind(CustomerQueryInterface::class)->to(FakeCustomerQuery::class);
        $this->bind(PasswordHasherInterface::class)->to(FakePasswordHasher::class);
        $this->bind(CustomerIdGeneratorInterface::class)->to(FakeCustomerIdGenerator::class);
        $this->bind(CustomerInitialPointInterface::class)->to(FakeCustomerInitialPoint::class);

        // Reason (Pilot 5 doCheckout): Inventory + PaymentGateway + OrderNumber +
        // OrderCommand + Mailer. The Fake classes that accumulate in-memory state
        // (stock map, gateway captures, mailer captures, persisted-order map)
        // MUST be the same instance for both the Becoming chain (which resolves
        // via Interface) and the test introspection (which resolves via concrete
        // class).
        //
        // Ray.Di gotcha: `bind(Iface)->to(Impl)` is a linked binding that does
        // NOT consult `bind(Impl)->in(Singleton)`. It creates a fresh Impl
        // instance independent of the Impl binding's scope. Even declaring
        // `->in(Singleton)` on both bindings creates two separate singletons
        // (one keyed on Iface, one keyed on Impl). The only reliable way to
        // share a single instance across both lookups is `toInstance($obj)` on
        // both bindings, pointing to the same object reference. Pilot 5
        // stress-tested this — Storage classes used by Commands (which the
        // Becoming chain never resolves directly) work with the simple
        // singleton pattern, but Mailer/Gateway/Inventory hold state directly
        // and need `toInstance`.
        $this->bind(FakeFinalizedOrderStorage::class)->in(Scope::SINGLETON);
        $inventory = new FakeInventoryAllocator();
        $gateway = new FakePaymentGateway();
        $mailer = new FakeMailer();
        $this->bind(FakeInventoryAllocator::class)->toInstance($inventory);
        $this->bind(InventoryAllocatorInterface::class)->toInstance($inventory);
        $this->bind(FakePaymentGateway::class)->toInstance($gateway);
        $this->bind(PaymentGatewayInterface::class)->toInstance($gateway);
        $this->bind(FakeMailer::class)->toInstance($mailer);
        $this->bind(MailerInterface::class)->toInstance($mailer);
        $this->bind(OrderNumberGeneratorInterface::class)->to(FakeOrderNumberGenerator::class);
        $this->bind(OrderCommandInterface::class)->to(FakeOrderCommand::class);

        // Reason (Phase B Slice 6): Session for ownership checks. Default
        // binds a logged-in fixture customer ('customer-001') matching the
        // `aaaa…` happy-path pre-order so existing Pilot tests continue
        // working unchanged. Tests that need a different (or absent)
        // customer override this binding with a fresh FakeSession instance.
        $this->bind(SessionInterface::class)->toInstance(new FakeSession('customer-001'));

        // Reason (Phase B Slice 8): CSRF token validator for state-changing
        // requests. Default binds FakeCsrfToken, which validates against
        // FakeCsrfToken::TOKEN. Resource tests submit that constant as the
        // `csrfToken` body field; tests that need to exercise rejection
        // simply omit it or pass a mismatch.
        $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class)->in(Scope::SINGLETON);

        // Reason (Pilot 13 doAddFavorite): unified Query+Command for the
        // customer favorites list. Singleton so adds within a request
        // are visible to subsequent reads.
        $this->bind(FavoriteStorageInterface::class)->to(FakeFavoriteStorage::class)->in(Scope::SINGLETON);

        // Reason (Pilot 16 customer address book): unified Query+Command
        // for the address store + an opaque-id generator. Singleton on
        // storage so a request's POST is visible to the same request's
        // GET / PUT / DELETE within a single test.
        $this->bind(AddressStorageInterface::class)->to(FakeAddressStorage::class)->in(Scope::SINGLETON);
        $this->bind(AddressIdGeneratorInterface::class)->to(FakeAddressIdGenerator::class);

        // Reason (Pilot 14 doRequestPasswordReset): reset-token store +
        // mailer for the password-reset link. Singleton so a test can
        // inspect what was issued.
        $this->bind(PasswordResetTokenStorageInterface::class)
            ->to(FakePasswordResetTokenStorage::class)->in(Scope::SINGLETON);

        // Reason (Wave 4 doAdminLogin / doAdminLogout): admin role AAA
        // infrastructure — parallel firewall to the customer-side
        // SessionInterface. Mirrors EC-CUBE / Symfony's two-firewall
        // model (`admin` + `customer`): admins and customers are
        // distinct AAA principal classes, so the admin id lives behind
        // its own interface rather than overloading SessionInterface.
        //
        // Storage is Singleton so a future AdminCommand (Wave 5+) sees
        // its writes inside the same request — same CQRS convention as
        // FakeCustomerStorage. AdminSessionInterface defaults to the
        // anonymous binding (`new FakeAdminSession(null)`) to match the
        // customer-side default: tests that exercise an admin-only path
        // rebind it via override module (same `rebindSession` helper
        // pattern as ChangeResourceTest, but for AdminSessionInterface).
        $this->bind(FakeAdminStorage::class)->in(Scope::SINGLETON);
        $this->bind(AdminQueryInterface::class)->to(FakeAdminQuery::class);
        $this->bind(AdminSessionInterface::class)->toInstance(new FakeAdminSession(null));

        // Wave 8α: admin product CRUD.
        $this->bind(FakeProductStorage::class)->in(Scope::SINGLETON);
        $this->bind(ProductCommandInterface::class)->to(FakeProductCommand::class);

        // Wave 8β: admin catalog hierarchy (Category / ClassName / ClassCategory).
        $this->bind(CategoryStorageInterface::class)->to(FakeCategoryStorage::class)->in(Scope::SINGLETON);
        $this->bind(CategoryIdGeneratorInterface::class)->to(FakeCategoryIdGenerator::class);
        $this->bind(ClassNameStorageInterface::class)->to(FakeClassNameStorage::class)->in(Scope::SINGLETON);
        $this->bind(ClassNameIdGeneratorInterface::class)->to(FakeClassNameIdGenerator::class);
        $this->bind(ClassCategoryStorageInterface::class)->to(FakeClassCategoryStorage::class)->in(Scope::SINGLETON);
        $this->bind(ClassCategoryIdGeneratorInterface::class)->to(FakeClassCategoryIdGenerator::class);

        // Wave 8γ: admin member CRUD + login history.
        $this->bind(AdminCommandInterface::class)->to(FakeAdminCommand::class);
        $this->bind(AdminIdGeneratorInterface::class)->to(FakeAdminIdGenerator::class);
        $this->bind(LoginHistoryStorageInterface::class)
            ->to(FakeLoginHistoryStorage::class)->in(Scope::SINGLETON);

        // Wave 8ε: admin plugin lifecycle + simple settings. toInstance pattern (G-14).
        $pluginStorage = new FakePluginStorage();
        $baseInfoStorage = new FakeBaseInfoStorage();
        $mailTemplateStorage = new FakeMailTemplateStorage();
        $tradeLawStorage = new FakeTradeLawStorage();
        $this->bind(FakePluginStorage::class)->toInstance($pluginStorage);
        $this->bind(PluginStorageInterface::class)->toInstance($pluginStorage);
        $this->bind(FakeBaseInfoStorage::class)->toInstance($baseInfoStorage);
        $this->bind(BaseInfoStorageInterface::class)->toInstance($baseInfoStorage);
        $this->bind(FakeMailTemplateStorage::class)->toInstance($mailTemplateStorage);
        $this->bind(MailTemplateStorageInterface::class)->toInstance($mailTemplateStorage);
        $this->bind(FakeTradeLawStorage::class)->toInstance($tradeLawStorage);
        $this->bind(TradeLawStorageInterface::class)->toInstance($tradeLawStorage);

        // Wave 9ι: misc admin transitions.
        // doUpdateCsv stores the column vector per csvType. The read-side
        // pairs of goBaseInfo / goMailTemplateList / goTradeLawList reuse
        // the Wave 8ε storage bindings above. goExportCustomer reuses the
        // Wave 8β FakeCustomerStorage::search via CustomerQueryInterface.
        $this->bind(CsvColumnConfigStorageInterface::class)
            ->to(FakeCsvColumnConfigStorage::class)->in(Scope::SINGLETON);
    }
}
