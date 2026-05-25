<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

final class AdminMemberListResourceTest extends TestCase
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsMemberList(): void
    {
        $ro = $this->resource->get('page://self/admin/member-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(3, $ro->body['count']);
        $loginIds = array_column($ro->body['members'], 'loginId');
        $this->assertContains('test-admin', $loginIds);

        // Shallow projection — no credentials leak.
        foreach ($ro->body['members'] as $row) {
            $this->assertArrayNotHasKey('passwordHash', $row);
        }
    }

    public function testOnGetWithNameFilterNarrowsResults(): void
    {
        $ro = $this->resource->get('page://self/admin/member-list', [
            'nameKeyword' => '副',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['count']);
        $this->assertSame('deputy', $ro->body['members'][0]['loginId']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/member-list');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
