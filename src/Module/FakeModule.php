<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCustomerInitialPoint;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeInventoryAllocator;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakePaymentGateway;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakePaymentMethodFactory;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakePurchaseFlow;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCacheClearer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeClassCsvCompatibility;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCustomizeAssetWriter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMaintenanceMode;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMasterDataWriter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSecurityConfigWriter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTemplateCompatibility;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Be\Reason\Fake\Service\NullCsrfToken;
use MyVendor\BeMart\Tests\Fake\Http\NullRequestToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Http\RequestTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface;
use MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface;
use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentGatewayInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\Scope;
use Ray\FakeQuery\FakeQueryModule;

use function dirname;

/** Fake persistence / external-service modifier. */
final class FakeModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $root = dirname(__DIR__, 2);
        $this->install(new FakeQueryModule(
            $root . '/be/var/fake/query',
            MediaQueryQueries::fromAppRoot($root),
        ));

        if (PHP_SAPI === 'cli-server') {
            $this->override(new HtmlFakeCartModule());
        }

        $inventory = new FakeInventoryAllocator();
        $gateway = new FakePaymentGateway();
        $mailer = new FakeMailer();
        $session = new FakeSession('customer-001');
        $adminSession = new FakeAdminSession(null);
        $csrf = new NullCsrfToken();

        $this->bind(FakeInventoryAllocator::class)->toInstance($inventory);
        $this->bind(InventoryAllocatorInterface::class)->toInstance($inventory);
        $this->bind(FakePaymentGateway::class)->toInstance($gateway);
        $this->bind(PaymentGatewayInterface::class)->toInstance($gateway);
        $this->bind(FakeMailer::class)->toInstance($mailer);
        $this->bind(MailerInterface::class)->toInstance($mailer);
        $this->bind(FakeSession::class)->toInstance($session);
        $this->bind(CustomerSession::class)->toInstance($session);
        $this->bind(FakeAdminSession::class)->toInstance($adminSession);
        $this->bind(AdminSession::class)->toInstance($adminSession);
        $this->bind(NullCsrfToken::class)->toInstance($csrf);
        $this->bind(FakeCsrfToken::class);
        $this->bind(CsrfTokenInterface::class)->toInstance($csrf);
        $this->bind(RequestTokenInterface::class)->to(NullRequestToken::class);

        $twoFactorAuth = new FakeTwoFactorAuth();
        $securityConfig = new FakeSecurityConfigWriter();
        $this->bind(FakeTwoFactorAuth::class)->toInstance($twoFactorAuth);
        $this->bind(TwoFactorAuthInterface::class)->toInstance($twoFactorAuth);
        $this->bind(FakeSecurityConfigWriter::class)->toInstance($securityConfig);
        $this->bind(SecurityConfigWriterInterface::class)->toInstance($securityConfig);

        $cacheClearer = new FakeCacheClearer();
        $assetWriter = new FakeCustomizeAssetWriter();
        $maintenance = new FakeMaintenanceMode();
        $this->bind(FakeCacheClearer::class)->toInstance($cacheClearer);
        $this->bind(CacheClearerInterface::class)->toInstance($cacheClearer);
        $this->bind(FakeCustomizeAssetWriter::class)->toInstance($assetWriter);
        $this->bind(CustomizeAssetWriterInterface::class)->toInstance($assetWriter);
        $this->bind(FakeMaintenanceMode::class)->toInstance($maintenance);
        $this->bind(MaintenanceModeInterface::class)->toInstance($maintenance);

        $masterDataWriter = new FakeMasterDataWriter();
        $this->bind(FakeMasterDataWriter::class)->toInstance($masterDataWriter);
        $this->bind(MasterDataWriterInterface::class)->toInstance($masterDataWriter);

        $classCsv = new FakeClassCsvCompatibility();
        $this->bind(FakeClassCsvCompatibility::class)->toInstance($classCsv);
        $this->bind(ClassCsvCompatibilityInterface::class)->toInstance($classCsv);

        $templateCompat = new FakeTemplateCompatibility();
        $this->bind(FakeTemplateCompatibility::class)->toInstance($templateCompat);
        $this->bind(TemplateCompatibilityInterface::class)->toInstance($templateCompat);

        $this->bind(CustomerInitialPointInterface::class)->to(FakeCustomerInitialPoint::class)->in(Scope::SINGLETON);
        $this->bind(PurchaseFlowInterface::class)->to(FakePurchaseFlow::class)->in(Scope::SINGLETON);
        $this->bind(PaymentMethodFactoryInterface::class)->to(FakePaymentMethodFactory::class)->in(Scope::SINGLETON);
    }
}
