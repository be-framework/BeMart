<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

/**
 * Wave 5 (goCustomerList) — admin-side filter search Resource.
 *
 * Mirrors AdminLogoutResourceTest's `rebindAdminSession` helper because
 * admin and customer firewalls are parallel (Wave 4 decision). The
 * default admin session bound in AppModule is null (anonymous),
 * so each test rebinds explicitly to drive the happy / forbidden
 * branches.
 */
final class AdminCustomerListResourceTest extends TestCase
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
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsCustomerList(): void
    {
        $ro = $this->resource->get('page://self/admin/customer-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(5, $ro->body['count']);
        $this->assertNotEmpty($ro->body['customers']);
        $emails = array_column($ro->body['customers'], 'email');
        $this->assertContains('alice@example.com', $emails);
        // Shallow projection — no credential fields leak.
        foreach ($ro->body['customers'] as $row) {
            $this->assertArrayNotHasKey('passwordHash', $row);
            $this->assertArrayNotHasKey('secretKey', $row);
        }

        $this->assertSame(
            ['nameKeyword' => null, 'emailKeyword' => null],
            $ro->body['filters'],
        );
    }

    public function testOnGetWithNameFilterNarrowsResults(): void
    {
        $ro = $this->resource->get('page://self/admin/customer-list', [
            'nameKeyword' => '鈴木',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
        $emails = array_column($ro->body['customers'], 'email');
        $this->assertContains('bob@example.com', $emails);
        $this->assertContains('login-test@example.com', $emails);
        $this->assertSame(
            ['nameKeyword' => '鈴木', 'emailKeyword' => null],
            $ro->body['filters'],
        );
    }

    public function testOnGetWithEmailFilterNarrowsResults(): void
    {
        $ro = $this->resource->get('page://self/admin/customer-list', [
            'emailKeyword' => 'carol',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['count']);
        $this->assertSame('carol@example.com', $ro->body['customers'][0]['email']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/customer-list');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
