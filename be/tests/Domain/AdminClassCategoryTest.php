<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ClassCategoryNameFormatException;
use MyVendor\BeMart\Be\Exception\ClassCategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminClassCategoryListFetched;
use MyVendor\BeMart\Be\Final\ClassCategoryCreated;
use MyVendor\BeMart\Be\Final\ClassCategoryDeleted;
use MyVendor\BeMart\Be\Final\ClassCategoryUpdated;
use MyVendor\BeMart\Be\Final\ClassNameCreated;
use MyVendor\BeMart\Be\Input\CreateClassCategoryInput;
use MyVendor\BeMart\Be\Input\CreateClassNameInput;
use MyVendor\BeMart\Be\Input\DeleteClassCategoryInput;
use MyVendor\BeMart\Be\Input\GetAdminClassCategoryListInput;
use MyVendor\BeMart\Be\Input\UpdateClassCategoryInput;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeClassCategoryStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeClassNameStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;

/**
 * Wave 7 — admin catalog ClassCategory transitions (domain layer).
 */
final class AdminClassCategoryTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private FakeClassNameStorage $classNameStorage;
    private FakeClassCategoryStorage $storage;

    protected function setUp(): void
    {
        // Both stores share the lifetime of one test so a freshly-
        // created ClassName remains visible when the test then
        // creates a ClassCategory under it.
        $this->classNameStorage = new FakeClassNameStorage();
        $this->storage = new FakeClassCategoryStorage();
        $this->bindAs(self::TEST_ADMIN_ID);
    }

    private function bindAs(string|null $adminId): void
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
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    private function seedClassName(string $label): string
    {
        $final = ($this->becoming)(new CreateClassNameInput(classNameLabel: $label));
        assert($final instanceof ClassNameCreated);

        return $final->classNameId;
    }

    private function seedClassCategory(string $classNameId, string $name): string
    {
        $final = ($this->becoming)(new CreateClassCategoryInput(
            classNameId: $classNameId,
            classCategoryName: $name,
        ));
        assert($final instanceof ClassCategoryCreated);

        return $final->classCategoryId;
    }

    public function testCreateHappyPathPersistsValue(): void
    {
        $classNameId = $this->seedClassName('Color');

        $final = ($this->becoming)(new CreateClassCategoryInput(
            classNameId: $classNameId,
            classCategoryName: 'Red',
        ));

        $this->assertInstanceOf(ClassCategoryCreated::class, $final);
        $this->assertSame('Red', $final->name);
        $this->assertSame($classNameId, $final->classNameId);
        $this->assertNotEmpty($final->classCategoryId);
    }

    public function testCreateRejectsUnknownClassName(): void
    {
        $this->expectException(ClassNameNotFoundException::class);
        ($this->becoming)(new CreateClassCategoryInput(
            classNameId: 'nonexistent-zzz',
            classCategoryName: 'Red',
        ));
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $classNameId = $this->seedClassName('Color');
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new CreateClassCategoryInput(
            classNameId: $classNameId,
            classCategoryName: 'Red',
        ));
    }

    public function testCreateRejectsEmptyName(): void
    {
        $classNameId = $this->seedClassName('Color');
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new CreateClassCategoryInput(
                classNameId: $classNameId,
                classCategoryName: '',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                ClassCategoryNameFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testListScopedToOneAxis(): void
    {
        $colorId = $this->seedClassName('Color');
        $sizeId = $this->seedClassName('Size');
        $this->seedClassCategory($colorId, 'Red');
        $this->seedClassCategory($colorId, 'Blue');
        $this->seedClassCategory($sizeId, 'S');

        $final = ($this->becoming)(new GetAdminClassCategoryListInput(classNameId: $colorId));

        $this->assertInstanceOf(AdminClassCategoryListFetched::class, $final);
        $this->assertSame(2, $final->count);
        $this->assertSame($colorId, $final->classNameId);
    }

    public function testListWithoutFilterReturnsAllRows(): void
    {
        $colorId = $this->seedClassName('Color');
        $this->seedClassCategory($colorId, 'Red');
        $this->seedClassCategory($colorId, 'Blue');

        $final = ($this->becoming)(new GetAdminClassCategoryListInput());

        $this->assertInstanceOf(AdminClassCategoryListFetched::class, $final);
        $this->assertSame(2, $final->count);
        $this->assertNull($final->classNameId);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminClassCategoryListInput());
    }

    public function testUpdateRenamesValue(): void
    {
        $classNameId = $this->seedClassName('Color');
        $id = $this->seedClassCategory($classNameId, 'Red');

        $final = ($this->becoming)(new UpdateClassCategoryInput(
            classCategoryId: $id,
            classCategoryName: 'Crimson',
        ));

        $this->assertInstanceOf(ClassCategoryUpdated::class, $final);
        $this->assertSame('Crimson', $final->name);
        // classNameId is preserved across update — value stays on its
        // original axis.
        $this->assertSame($classNameId, $final->classNameId);
    }

    public function testUpdateRejectsUnknownId(): void
    {
        $this->expectException(ClassCategoryNotFoundException::class);
        ($this->becoming)(new UpdateClassCategoryInput(
            classCategoryId: 'nonexistent-zzz',
            classCategoryName: 'X',
        ));
    }

    public function testUpdateRejectsAnonymousAdmin(): void
    {
        $classNameId = $this->seedClassName('Color');
        $id = $this->seedClassCategory($classNameId, 'Red');
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateClassCategoryInput(
            classCategoryId: $id,
            classCategoryName: 'X',
        ));
    }

    public function testDeleteRemovesRow(): void
    {
        $classNameId = $this->seedClassName('Color');
        $id = $this->seedClassCategory($classNameId, 'Red');

        $final = ($this->becoming)(new DeleteClassCategoryInput(classCategoryId: $id));

        $this->assertInstanceOf(ClassCategoryDeleted::class, $final);
        $this->assertSame($id, $final->classCategoryId);
        $this->assertNull($this->storage->getById($id));
    }

    public function testDeleteRejectsUnknownId(): void
    {
        $this->expectException(ClassCategoryNotFoundException::class);
        ($this->becoming)(new DeleteClassCategoryInput(classCategoryId: 'nonexistent-zzz'));
    }

    public function testDeleteRejectsAnonymousAdmin(): void
    {
        $classNameId = $this->seedClassName('Color');
        $id = $this->seedClassCategory($classNameId, 'Red');
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new DeleteClassCategoryInput(classCategoryId: $id));
    }
}
