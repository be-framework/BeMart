<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Injector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Characterizes the supported DB-free application contexts before replacing
 * BeMart's custom Injector map with BEAR\Package context composition.
 */
final class InjectorContextCompositionTest extends TestCase
{
    /** @return iterable<string, array{non-empty-string}> */
    public static function dbFreeContexts(): iterable
    {
        yield 'fake HAL API' => ['fake-hal-api-app'];
        yield 'CLI fake HAL API' => ['cli-fake-hal-api-app'];
        yield 'dev fake HAL API' => ['dev-fake-hal-api-app'];
        yield 'CLI dev fake HAL API' => ['cli-dev-fake-hal-api-app'];
        yield 'test HAL API' => ['test-hal-api-app'];
        yield 'CLI test HAL API' => ['cli-test-hal-api-app'];
        yield 'HTTP test HAL API' => ['http-test-hal-api-app'];
        yield 'HTML test HAL API' => ['html-test-hal-api-app'];
        yield 'CLI HTML test HAL API' => ['cli-html-test-hal-api-app'];
        yield 'admin test HAL API' => ['admin-test-hal-api-app'];
    }

    /** @param non-empty-string $context */
    #[DataProvider('dbFreeContexts')]
    public function testDbFreeContextsResolveResourceClient(string $context): void
    {
        $resource = Injector::getInstance($context)->getInstance(ResourceInterface::class);

        $this->assertInstanceOf(ResourceInterface::class, $resource);
    }

    public function testTestContextUsesFakeSessionAndCsrfBindings(): void
    {
        $injector = Injector::getInstance('test-hal-api-app');

        $customerSession = $injector->getInstance(CustomerSession::class);
        $adminSession = $injector->getInstance(AdminSession::class);
        $csrfToken = $injector->getInstance(CsrfToken::class);

        $this->assertInstanceOf(FakeSession::class, $customerSession);
        $this->assertSame('customer-001', $customerSession->customerId);
        $this->assertInstanceOf(FakeAdminSession::class, $adminSession);
        $this->assertNull($adminSession->adminId);
        $this->assertInstanceOf(FakeCsrfToken::class, $csrfToken);
    }

    public function testAdminTestContextOverridesAdminSession(): void
    {
        $adminSession = Injector::getInstance('admin-test-hal-api-app')
            ->getInstance(AdminSession::class);

        $this->assertInstanceOf(FakeAdminSession::class, $adminSession);
        $this->assertSame('ad000000000000000000000000000001', $adminSession->adminId);
    }

    public function testHtmlTestContextUsesHtmlSessionAdapters(): void
    {
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = 'customer-from-session';
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 'admin-from-session';

        $injector = Injector::getInstance('html-test-hal-api-app');
        $customerSession = $injector->getInstance(CustomerSession::class);
        $adminSession = $injector->getInstance(AdminSession::class);

        $this->assertInstanceOf(HtmlSessionAdapter::class, $customerSession);
        $this->assertSame('customer-from-session', $customerSession->customerId);
        $this->assertInstanceOf(HtmlAdminSessionAdapter::class, $adminSession);
        $this->assertSame('admin-from-session', $adminSession->adminId);
    }

    protected function tearDown(): void
    {
        unset(
            $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY],
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
        );
    }
}
