<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function getenv;
use function putenv;

final class AdminProductBulkStatusResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostHappyPathReturns200(): void
    {
        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001', 'admin-hidden-001'],
            'productStatus' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['requestedCount']);
        $this->assertSame(2, $ro->body['changedCount']);
    }

    public function testOnPostHtmlContextRedirectsToProductList(): void
    {
        $context = getenv('APP_CONTEXT');
        putenv('APP_CONTEXT=html-test-hal-app');
        $ro = null;

        try {
            $ro = $this->resource->post('page://self/admin/product-bulk-status', [
                'productCodes' => ['admin-active-001', 'admin-hidden-001'],
                'productStatus' => 3,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
        } finally {
            $context === false ? putenv('APP_CONTEXT') : putenv('APP_CONTEXT=' . $context);
        }

        assert($ro !== null);
        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/product-list', $ro->headers['Location']);
        $this->assertSame(2, $ro->body['requestedCount']);
    }

    public function testOnPostWithUnknownCodesReportsPartialCount(): void
    {
        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001', 'does-not-exist'],
            'productStatus' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['requestedCount']);
        $this->assertSame(1, $ro->body['changedCount']);
    }

    public function testOnPostInvalidStatusReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001'],
            'productStatus' => 99,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001'],
            'productStatus' => 2,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001'],
            'productStatus' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
