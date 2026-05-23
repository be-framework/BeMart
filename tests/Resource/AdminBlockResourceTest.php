<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeBlockStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function is_string;

/**
 * Wave 9 — resource-layer coverage for the admin Block endpoints.
 */
final class AdminBlockResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeBlockStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakeBlockStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeBlockStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(BlockStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeBlockStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $name, string $file): string
    {
        $ro = $this->resource->post('page://self/admin/block/block-list', [
            'blockName' => $name,
            'blockFileName' => $file,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['blockId'];
        assert(is_string($id));

        return $id;
    }

    public function testListIncludesSeed(): void
    {
        $ro = $this->resource->get('page://self/admin/block/block-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/block/block-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateHappyPath(): void
    {
        $ro = $this->resource->post('page://self/admin/block/block-list', [
            'blockName' => 'バナー',
            'blockFileName' => 'banner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('バナー', $ro->body['blockName']);
        $this->assertTrue($ro->body['blockDeletable']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/block/block-list', [
            'blockName' => 'バナー',
            'blockFileName' => 'banner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUpdateMerges(): void
    {
        $id = $this->seed('Old', 'old');
        $ro = $this->resource->put('page://self/admin/block/block', [
            'blockId' => $id,
            'blockName' => 'New',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('New', $ro->body['blockName']);
    }

    public function testDeleteUserBlockHappyPath(): void
    {
        $id = $this->seed('Tmp', 'tmp');
        $ro = $this->resource->delete('page://self/admin/block/block', [
            'blockId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
    }

    public function testDeleteSystemBlockIsRefused(): void
    {
        $ro = $this->resource->delete('page://self/admin/block/block', [
            'blockId' => FakeBlockStorage::SEED_BLOCK_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
