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

/**
 * Resource coverage for the masterdata select (PUT on /master-data) and
 * edit (PUT on /master-data-edit) transitions.
 */
final class AdminMasterDataEditResourceTest extends TestCase
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
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testSelectReturnsRows(): void
    {
        $ro = $this->resource->put('page://self/admin/master-data', [
            'masterType' => 'tag',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doSelectMasterData', $ro->body['transitionId']);
        $this->assertSame('tag', $ro->body['selectedMaster']);
    }

    public function testSelectUnknownMasterReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->put('page://self/admin/master-data', [
            'masterType' => 'no-such-master',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testUpdateWritesRows(): void
    {
        $ro = $this->resource->put('page://self/admin/master-data-edit', [
            'masterType' => 'tag',
            'rows' => [['id' => 't1', 'name' => '新タグ', 'sortNo' => 1]],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doUpdateMasterData', $ro->body['transitionId']);
        $this->assertSame(1, $ro->body['count']);
    }

    public function testUpdateMissingCsrfReturns403(): void
    {
        $ro = $this->resource->put('page://self/admin/master-data-edit', [
            'masterType' => 'tag',
            'rows' => [],
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUpdateAnonymousReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->put('page://self/admin/master-data-edit', [
            'masterType' => 'tag',
            'rows' => [],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
