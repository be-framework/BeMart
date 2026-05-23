<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeTagStorage;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
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
 * Wave 9 — resource-layer coverage for the admin Tag endpoints.
 */
final class AdminTagResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeTagStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakeTagStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeTagStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(TagStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeTagStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $name): string
    {
        $ro = $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => $name,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['tagId'];
        assert(is_string($id));

        return $id;
    }

    public function testListIncludesSeed(): void
    {
        $ro = $this->resource->get('page://self/admin/tag/tag-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/tag/tag-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateHappyPath(): void
    {
        $ro = $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => '限定',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('限定', $ro->body['tagName']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => '限定',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('Tmp');
        $ro = $this->resource->delete('page://self/admin/tag/tag', [
            'tagId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
    }

    public function testDeleteUnknownReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/tag/tag', [
            'tagId' => 'nonexistent',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
