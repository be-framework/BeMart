<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Module\AppMetaModule;
use BEAR\Package\PackageModule;
use Be\Framework\Module\BeModule;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistry;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\DefaultPaymentMethodFactory;
use MyVendor\BeMart\Be\Reason\Service\DefaultPurchaseFlow;
use MyVendor\BeMart\Be\Reason\Service\FixedCustomerInitialPoint;
use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
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
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Compatibility\Eccube\EccubeSecurityConfigWriter;
use MyVendor\BeMart\Compatibility\Eccube\EccubeTwoFactorAuth;
use MyVendor\BeMart\Compatibility\Eccube\OrderPdfCompatibilityService;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Interceptor\CsrfProtectedInterceptor;
use MyVendor\BeMart\Support\Resource\RequestQueryCapturingInvoker;
use MyVendor\BeMart\Support\Resource\RequestQueryContext;
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
    protected function configure(): void
    {
        $this->install(new PackageModule());
        $this->override(new AuraRouterModule($this->appMeta->appDir . '/config/aura-routes.php'));
        // PackageModule does not bind @AppName by itself; BEAR\Package\Module
        // factory normally overrides it. Install explicitly so tests can use
        // `new Injector(new *Module(...))` without the factory.
        $this->override(new AppMetaModule($this->appMeta));

        $this->bind(RequestQueryContext::class)->in(Scope::SINGLETON);
        $this->bind(InvokerInterface::class)->to(RequestQueryCapturingInvoker::class);
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

        // Shared registry over master storage interfaces. The storage
        // implementations come from the active persistence module (Fake or SQL).
        $this->bind(AdminMasterRegistryInterface::class)->to(AdminMasterRegistry::class);

        // FormFactory builds AbstractForm instances with their Aura.Input /
        // Aura.Filter / Aura.Html dependencies self-contained. It is cheap in
        // JSON contexts and rendered only by HtmlModule.
        $this->bind(FormFactory::class);
    }
}
