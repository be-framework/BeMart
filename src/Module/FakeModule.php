<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCustomerInitialPoint;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeInventoryAllocator;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeOrderNumberGenerator;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakePasswordHasher;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakePaymentGateway;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakePaymentMethodFactory;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakePurchaseFlow;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Be\Reason\Service\OrderNumberGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentGatewayInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\MediaQueryRuntimeModule;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\FakeQuery\FakeQueryModule;

use function dirname;

final class FakeModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new FakeQueryModule(
            dirname(__DIR__, 2) . '/be/var/fake/query',
            MediaQueryRuntimeModule::queryClasses(),
        ));

        $inventory = new FakeInventoryAllocator();
        $gateway = new FakePaymentGateway();
        $mailer = new FakeMailer();
        $session = new FakeSession('customer-001');
        $adminSession = new FakeAdminSession(null);
        $csrf = new FakeCsrfToken();

        $this->bind(FakeInventoryAllocator::class)->toInstance($inventory);
        $this->bind(InventoryAllocatorInterface::class)->toInstance($inventory);
        $this->bind(FakePaymentGateway::class)->toInstance($gateway);
        $this->bind(PaymentGatewayInterface::class)->toInstance($gateway);
        $this->bind(FakeMailer::class)->toInstance($mailer);
        $this->bind(MailerInterface::class)->toInstance($mailer);
        $this->bind(FakeSession::class)->toInstance($session);
        $this->bind(SessionInterface::class)->toInstance($session);
        $this->bind(FakeAdminSession::class)->toInstance($adminSession);
        $this->bind(AdminSessionInterface::class)->toInstance($adminSession);
        $this->bind(FakeCsrfToken::class)->toInstance($csrf);
        $this->bind(CsrfTokenInterface::class)->toInstance($csrf);

        $this->bind(PasswordHasherInterface::class)->to(FakePasswordHasher::class)->in(Scope::SINGLETON);
        $this->bind(CustomerInitialPointInterface::class)->to(FakeCustomerInitialPoint::class)->in(Scope::SINGLETON);
        $this->bind(PurchaseFlowInterface::class)->to(FakePurchaseFlow::class)->in(Scope::SINGLETON);
        $this->bind(PaymentMethodFactoryInterface::class)->to(FakePaymentMethodFactory::class)->in(Scope::SINGLETON);
        $this->bind(OrderNumberGeneratorInterface::class)->to(FakeOrderNumberGenerator::class)->in(Scope::SINGLETON);
    }
}
