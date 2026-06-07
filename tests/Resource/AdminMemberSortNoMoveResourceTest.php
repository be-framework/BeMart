<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/** Resource coverage for doSortNoMove on the admin member master. */
final class AdminMemberSortNoMoveResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const SHOP_OWNER_ID = 'ad000000000000000000000000000002';

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

    public function testMemberSortNoMoveReturnsConcreteSuccess(): void
    {
        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'member',
            'rowId' => self::SHOP_OWNER_ID,
            'sortNo' => 8,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('member', $ro->body['masterType']);
        $this->assertSame(self::SHOP_OWNER_ID, $ro->body['rowId']);
        $this->assertSame(8, $ro->body['sortNo']);
    }

    public function testMemberSortNoMoveMissingCsrfReturns403(): void
    {
        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'member',
            'rowId' => self::SHOP_OWNER_ID,
            'sortNo' => 8,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testMemberSortNoMoveUnknownRowReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\MasterRowNotFoundException::class);

        $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'member',
            'rowId' => 'ad000000000000000000000000999999',
            'sortNo' => 8,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
