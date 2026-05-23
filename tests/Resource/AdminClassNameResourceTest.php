<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeClassNameStorage;
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
use function str_contains;

/**
 * Wave 7 — resource-layer coverage for the admin ClassName endpoints.
 */
final class AdminClassNameResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeClassNameStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakeClassNameStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeClassNameStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(ClassNameStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeClassNameStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $label): string
    {
        $ro = $this->resource->post('page://self/admin/class-name/class-name-list', [
            'classNameLabel' => $label,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['classNameId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/class-name/class-name-list', [
            'classNameLabel' => 'Color',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('Color', $ro->body['name']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/class-name/class-name-list', [
            'classNameLabel' => 'Color',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/class-name/class-name-list', [
            'classNameLabel' => 'Color',
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testListReturnsRows(): void
    {
        $this->seed('Color');
        $this->seed('Size');

        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testPutRenamesAxis(): void
    {
        $id = $this->seed('Color');

        $ro = $this->resource->put('page://self/admin/class-name/class-name', [
            'classNameId' => $id,
            'classNameLabel' => 'Colour',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Colour', $ro->body['name']);
    }

    public function testPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/class-name/class-name', [
            'classNameId' => 'nonexistent-zzz',
            'classNameLabel' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('Color');

        $ro = $this->resource->delete('page://self/admin/class-name/class-name', [
            'classNameId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['classNameId']);
    }

    public function testDeleteRejectsMissingCsrf(): void
    {
        $id = $this->seed('Color');
        $ro = $this->resource->delete('page://self/admin/class-name/class-name', [
            'classNameId' => $id,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }
}
