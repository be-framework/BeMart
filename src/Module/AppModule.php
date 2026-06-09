<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Module\AppMetaModule;
use BEAR\Package\PackageModule;
use BEAR\Resource\Module\JsonSchemaModule;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use Be\Framework\Module\BeModule;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Be\Reason\Provider\AddressIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\AdminIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\BlockIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\CategoryIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\ClassCategoryIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\ClassNameIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\CustomerIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\DeliveryIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\NewsIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\OrderNoProvider;
use MyVendor\BeMart\Be\Reason\Provider\PageIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\PaymentMethodAdminIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\ResetKeyProvider;
use MyVendor\BeMart\Be\Reason\Provider\TagIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\TaxRuleIdProvider;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistry;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface;
use MyVendor\BeMart\Be\Reason\Query\Factory\AdminFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\CartFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\CustomerFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\FinalizedOrderFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\OrderFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\OrderHistoryFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\OrderItemFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\ProductClassFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\ProductFactory;
use MyVendor\BeMart\Be\Reason\Query\Factory\TemplateFactory;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\DefaultPaymentMethodFactory;
use MyVendor\BeMart\Be\Reason\Service\DefaultPurchaseFlow;
use MyVendor\BeMart\Be\Reason\Service\FixedCustomerInitialPoint;
use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface;
use MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface;
use MyVendor\BeMart\Be\Reason\Service\NativePasswordHasher;
use MyVendor\BeMart\Be\Reason\Service\NoopInventoryAllocator;
use MyVendor\BeMart\Be\Reason\Service\NoopMailer;
use MyVendor\BeMart\Be\Reason\Service\NoopPaymentGateway;
use MyVendor\BeMart\Be\Reason\Service\OrderPdfCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentGatewayInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Compatibility\Eccube\EccubeCacheClearer;
use MyVendor\BeMart\Compatibility\Eccube\EccubeClassCsvCompatibility;
use MyVendor\BeMart\Compatibility\Eccube\EccubeCustomizeAssetWriter;
use MyVendor\BeMart\Compatibility\Eccube\EccubeMaintenanceMode;
use MyVendor\BeMart\Compatibility\Eccube\EccubeMasterDataWriter;
use MyVendor\BeMart\Compatibility\Eccube\EccubeSecurityConfigWriter;
use MyVendor\BeMart\Compatibility\Eccube\EccubeTemplateCompatibility;
use MyVendor\BeMart\Compatibility\Eccube\EccubeTwoFactorAuth;
use MyVendor\BeMart\Compatibility\Eccube\OrderPdfCompatibilityService;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Interceptor\CsrfProtectedInterceptor;
use MyVendor\BeMart\Provide\Transfer\DownloadResponder;
use Ray\Di\Scope;
use Ray\WebFormModule\FormFactory;

/**
 * Shared application module.
 *
 * This module is intentionally production-neutral: it installs BEAR/Be
 * framework infrastructure and bindings that are valid in every context, but
 * it does not bind Fake Reasons, dev logging, sessions, CSRF adapters, or SQL.
 * Context modules compose those concerns explicitly.
 */
final class AppModule extends AbstractAppModule
{
    /** @var list<class-string> */
    private const REASON_PROVIDERS = [
        AddressIdProvider::class,
        AdminIdProvider::class,
        BlockIdProvider::class,
        CategoryIdProvider::class,
        ClassCategoryIdProvider::class,
        ClassNameIdProvider::class,
        CustomerIdProvider::class,
        DeliveryIdProvider::class,
        NewsIdProvider::class,
        OrderNoProvider::class,
        PageIdProvider::class,
        PaymentMethodAdminIdProvider::class,
        ResetKeyProvider::class,
        TagIdProvider::class,
        TaxRuleIdProvider::class,
    ];

    /** @var list<class-string> */
    private const MEDIA_QUERY_FACTORIES = [
        AdminFactory::class,
        CartFactory::class,
        CustomerFactory::class,
        FinalizedOrderFactory::class,
        OrderFactory::class,
        OrderHistoryFactory::class,
        OrderItemFactory::class,
        ProductClassFactory::class,
        ProductFactory::class,
        TemplateFactory::class,
    ];

    protected function configure(): void
    {
        $this->install(new PackageModule());
        $this->override(new AppErrorModule());
        $this->override(new CanonicalResourceRouterModule());
        $this->bind(TransferInterface::class)->to(DownloadResponder::class);
        // PackageModule does not bind @AppName by itself; BEAR\Package\Module
        // factory normally overrides it. Install explicitly so tests can use
        // `new Injector(new *Module(...))` without the factory.
        $this->override(new AppMetaModule($this->appMeta));

        $this->install(
            new JsonSchemaModule(
                $this->appMeta->appDir . '/var/json_schema',
                $this->appMeta->appDir . '/var/json_validate',
            ),
        );

        $this->bindPriorityInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(CsrfProtected::class),
            [CsrfProtectedInterceptor::class],
        );

        // Be Framework: BecomingInterface, SemanticLogger, semantic validator,
        // Been provider. Dev/Test contexts override logging with DevLoggingModule;
        // prod keeps BeModule's plain Becoming/SemanticLogger bindings.
        $this->install(new BeModule('MyVendor\\BeMart\\Be\\Semantic'));

        $this->bind(PasswordHasherInterface::class)->to(NativePasswordHasher::class);
        $this->bind(HtmlAdminLoginChallengeAdapter::class);

        // Production-safe defaults for external domain services. FakeModule
        // overrides these with deterministic recording fakes; prod keeps the
        // same explicit bindings so every Be Final can be resolved without
        // leaking test doubles into production.
        $this->bind(InventoryAllocatorInterface::class)->to(NoopInventoryAllocator::class)->in(Scope::SINGLETON);
        $this->bind(PaymentGatewayInterface::class)->to(NoopPaymentGateway::class)->in(Scope::SINGLETON);
        $this->bind(MailerInterface::class)->to(NoopMailer::class)->in(Scope::SINGLETON);
        $this->bind(CustomerInitialPointInterface::class)->to(FixedCustomerInitialPoint::class)->in(Scope::SINGLETON);
        $this->bind(PurchaseFlowInterface::class)->to(DefaultPurchaseFlow::class)->in(Scope::SINGLETON);
        $this->bind(PaymentMethodFactoryInterface::class)->to(DefaultPaymentMethodFactory::class)->in(Scope::SINGLETON);
        $this->bind(OrderPdfCompatibilityInterface::class)->to(OrderPdfCompatibilityService::class)->in(Scope::SINGLETON);
        $this->bind(TwoFactorAuthInterface::class)->to(EccubeTwoFactorAuth::class)->in(Scope::SINGLETON);
        $this->bind(SecurityConfigWriterInterface::class)->to(EccubeSecurityConfigWriter::class)->in(Scope::SINGLETON);
        $this->bind(CacheClearerInterface::class)->to(EccubeCacheClearer::class)->in(Scope::SINGLETON);
        $this->bind(CustomizeAssetWriterInterface::class)->to(EccubeCustomizeAssetWriter::class)->in(Scope::SINGLETON);
        $this->bind(MaintenanceModeInterface::class)->to(EccubeMaintenanceMode::class)->in(Scope::SINGLETON);
        $this->bind(MasterDataWriterInterface::class)->to(EccubeMasterDataWriter::class)->in(Scope::SINGLETON);
        $this->bind(ClassCsvCompatibilityInterface::class)->to(EccubeClassCsvCompatibility::class)->in(Scope::SINGLETON);
        $this->bind(TemplateCompatibilityInterface::class)->to(EccubeTemplateCompatibility::class)->in(Scope::SINGLETON);

        // Shared registry over master storage interfaces. The storage
        // implementations come from the active persistence module (Fake or SQL).
        $this->bind(AdminMasterRegistryInterface::class)->to(AdminMasterRegistry::class);

        // Ray.Compiler requires explicit bindings for concrete classes that
        // Be injects as Reasons and that Ray.MediaQuery instantiates as
        // #[DbQuery] result factories. Non-compiled fake/test injectors could
        // JIT these classes, but prod compiled SQL contexts cannot.
        foreach (self::REASON_PROVIDERS as $provider) {
            $this->bind($provider);
        }

        foreach (self::MEDIA_QUERY_FACTORIES as $factory) {
            $this->bind($factory);
        }

        // FormFactory builds AbstractForm instances with their Aura.Input /
        // Aura.Filter / Aura.Html dependencies self-contained. It is cheap in
        // JSON contexts and rendered only by HtmlModule.
        $this->bind(FormFactory::class);
    }
}
