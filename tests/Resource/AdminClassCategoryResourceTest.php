<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeClassCategoryStorage;
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

/**
 * Wave 7 — resource-layer coverage for the admin ClassCategory
 * endpoints.
 */
final class AdminClassCategoryResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeClassNameStorage $classNameStorage;
    private FakeClassCategoryStorage $storage;

    protected function setUp(): void
    {
        $this->classNameStorage = new FakeClassNameStorage();
        $this->storage = new FakeClassCategoryStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->classNameStorage, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeClassNameStorage $classNameStorage,
                private readonly FakeClassCategoryStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(ClassNameStorageInterface::class)->toInstance($this->classNameStorage);
                $this->bind(FakeClassNameStorage::class)->toInstance($this->classNameStorage);
                $this->bind(ClassCategoryStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeClassCategoryStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seedClassName(string $label): string
    {
        $ro = $this->resource->post('page://self/admin/class-name/class-name-list', [
            'classNameLabel' => $label,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['classNameId'];
        assert(is_string($id));

        return $id;
    }

    private function seedClassCategory(string $classNameId, string $name): string
    {
        $ro = $this->resource->post('page://self/admin/class-category/class-category-list', [
            'classNameId' => $classNameId,
            'classCategoryName' => $name,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['classCategoryId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateReturns201(): void
    {
        $classNameId = $this->seedClassName('Color');

        $ro = $this->resource->post('page://self/admin/class-category/class-category-list', [
            'classNameId' => $classNameId,
            'classCategoryName' => 'Red',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('Red', $ro->body['name']);
    }

    public function testCreateRejectsUnknownClassName(): void
    {
        $ro = $this->resource->post('page://self/admin/class-category/class-category-list', [
            'classNameId' => 'nonexistent-zzz',
            'classCategoryName' => 'Red',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $classNameId = $this->seedClassName('Color');
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/class-category/class-category-list', [
            'classNameId' => $classNameId,
            'classCategoryName' => 'Red',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testListScopedToOneAxis(): void
    {
        $colorId = $this->seedClassName('Color');
        $sizeId = $this->seedClassName('Size');
        $this->seedClassCategory($colorId, 'Red');
        $this->seedClassCategory($colorId, 'Blue');
        $this->seedClassCategory($sizeId, 'S');

        $ro = $this->resource->get(
            'page://self/admin/class-category/class-category-list',
            ['classNameId' => $colorId],
        );

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/class-category/class-category-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testPutRenamesValue(): void
    {
        $classNameId = $this->seedClassName('Color');
        $id = $this->seedClassCategory($classNameId, 'Red');

        $ro = $this->resource->put('page://self/admin/class-category/class-category', [
            'classCategoryId' => $id,
            'classCategoryName' => 'Crimson',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Crimson', $ro->body['name']);
    }

    public function testPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/class-category/class-category', [
            'classCategoryId' => 'nonexistent-zzz',
            'classCategoryName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $classNameId = $this->seedClassName('Color');
        $id = $this->seedClassCategory($classNameId, 'Red');

        $ro = $this->resource->delete('page://self/admin/class-category/class-category', [
            'classCategoryId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['classCategoryId']);
    }

    public function testDeleteUnknownIdReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/class-category/class-category', [
            'classCategoryId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
