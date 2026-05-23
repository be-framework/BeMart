<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\CategoryNameFormatException;
use MyVendor\BeMart\Be\Exception\CategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\SortNoFormatException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCategoryFetched;
use MyVendor\BeMart\Be\Final\AdminCategoryListFetched;
use MyVendor\BeMart\Be\Final\CategoryCreated;
use MyVendor\BeMart\Be\Final\CategoryCsvExported;
use MyVendor\BeMart\Be\Final\CategoryCsvImported;
use MyVendor\BeMart\Be\Final\CategoryDeleted;
use MyVendor\BeMart\Be\Final\CategoryUpdated;
use MyVendor\BeMart\Be\Input\CreateCategoryInput;
use MyVendor\BeMart\Be\Input\DeleteCategoryInput;
use MyVendor\BeMart\Be\Input\ExportCategoryInput;
use MyVendor\BeMart\Be\Input\GetAdminCategoryInput;
use MyVendor\BeMart\Be\Input\GetAdminCategoryListInput;
use MyVendor\BeMart\Be\Input\ImportCategoryCsvInput;
use MyVendor\BeMart\Be\Input\UpdateCategoryInput;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeCategoryStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function count;
use function dirname;
use function str_contains;

/**
 * Wave 7 — admin catalog Category transitions (domain layer).
 *
 * Covers all 7 Direct flows for the Category resource:
 *
 *   - goCategoryList / goCategory          (safe reads, admin AUTHZ)
 *   - doCreateCategory / doUpdateCategory  (mutation, admin AUTHZ)
 *   - doDeleteCategory                     (mutation, admin AUTHZ)
 *   - doImportCategoryCsv                  (Phase 2 stub)
 *   - goExportCategory                     (CSV download)
 */
final class AdminCategoryTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private FakeCategoryStorage $storage;

    protected function setUp(): void
    {
        // Fresh storage shared across `bindAs` rebinds so a category
        // created under one admin session remains visible after a
        // rebind to a different (or anonymous) session.
        $this->storage = new FakeCategoryStorage();
        $this->bindAs(self::TEST_ADMIN_ID);
    }

    private function bindAs(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeCategoryStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(CategoryStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeCategoryStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    private function seedRoot(string $name, int $sortNo = 0): string
    {
        $final = ($this->becoming)(new CreateCategoryInput(
            categoryName: $name,
            sortNo: $sortNo,
        ));
        assert($final instanceof CategoryCreated);

        return $final->categoryId;
    }

    // ---- Create ----

    public function testCreateHappyPathPersistsAndReturnsId(): void
    {
        $final = ($this->becoming)(new CreateCategoryInput(
            categoryName: 'Food',
            sortNo: 10,
        ));

        $this->assertInstanceOf(CategoryCreated::class, $final);
        $this->assertSame('Food', $final->categoryName);
        $this->assertNull($final->parentId);
        $this->assertSame(10, $final->sortNo);
        $this->assertNotEmpty($final->categoryId);

        $persisted = $this->storage->getById($final->categoryId);
        $this->assertNotNull($persisted);
        $this->assertSame('Food', $persisted->categoryName);
    }

    public function testCreateChildPersistsWithParentLink(): void
    {
        $parentId = $this->seedRoot('Food');

        $final = ($this->becoming)(new CreateCategoryInput(
            categoryName: 'Cookies',
            sortNo: 20,
            parentId: $parentId,
        ));

        $this->assertInstanceOf(CategoryCreated::class, $final);
        $this->assertSame($parentId, $final->parentId);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new CreateCategoryInput(
            categoryName: 'Food',
            sortNo: 10,
        ));
    }

    public function testCreateRejectsUnknownParent(): void
    {
        $this->expectException(CategoryNotFoundException::class);
        ($this->becoming)(new CreateCategoryInput(
            categoryName: 'Cookies',
            sortNo: 20,
            parentId: 'nonexistent-parent-zzz',
        ));
    }

    public function testCreateRejectsEmptyName(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new CreateCategoryInput(categoryName: '', sortNo: 10));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                CategoryNameFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testCreateRejectsOutOfRangeSortNo(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new CreateCategoryInput(categoryName: 'Food', sortNo: 99999));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                SortNoFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    // ---- List ----

    public function testListReturnsEveryRow(): void
    {
        $this->seedRoot('Food', 10);
        $this->seedRoot('Drinks', 20);

        $final = ($this->becoming)(new GetAdminCategoryListInput());

        $this->assertInstanceOf(AdminCategoryListFetched::class, $final);
        $this->assertSame(2, $final->count);
        $this->assertCount(2, $final->categories);
        // Sorted ascending by sortNo.
        $this->assertSame('Food', $final->categories[0]['categoryName']);
        $this->assertSame('Drinks', $final->categories[1]['categoryName']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminCategoryListInput());
    }

    // ---- Single GET ----

    public function testGetByIdHappyPath(): void
    {
        $categoryId = $this->seedRoot('Food', 5);

        $final = ($this->becoming)(new GetAdminCategoryInput(categoryId: $categoryId));

        $this->assertInstanceOf(AdminCategoryFetched::class, $final);
        $this->assertSame($categoryId, $final->categoryId);
        $this->assertSame('Food', $final->categoryName);
        $this->assertSame(5, $final->sortNo);
    }

    public function testGetByIdRejectsUnknownId(): void
    {
        $this->expectException(CategoryNotFoundException::class);
        ($this->becoming)(new GetAdminCategoryInput(categoryId: 'nonexistent-zzz'));
    }

    public function testGetByIdRefusesBeforeExistenceCheck(): void
    {
        // Anti-enumeration: anonymous-as-admin + unknown id → 403, not 404.
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminCategoryInput(categoryId: 'nonexistent-zzz'));
    }

    // ---- Update ----

    public function testUpdateMergesPartialFields(): void
    {
        $categoryId = $this->seedRoot('Food', 10);

        $final = ($this->becoming)(new UpdateCategoryInput(
            categoryId: $categoryId,
            categoryName: 'Foods',
            // sortNo and parentId left null — must preserve.
        ));

        $this->assertInstanceOf(CategoryUpdated::class, $final);
        $this->assertSame('Foods', $final->categoryName);
        $this->assertSame(10, $final->sortNo);
    }

    public function testUpdateRejectsUnknownCategory(): void
    {
        $this->expectException(CategoryNotFoundException::class);
        ($this->becoming)(new UpdateCategoryInput(
            categoryId: 'nonexistent-zzz',
            categoryName: 'X',
        ));
    }

    public function testUpdateRejectsAnonymousAdmin(): void
    {
        $categoryId = $this->seedRoot('Food');
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateCategoryInput(categoryId: $categoryId, categoryName: 'X'));
    }

    // ---- Delete ----

    public function testDeleteHappyPathRemovesRow(): void
    {
        $categoryId = $this->seedRoot('Food');

        $final = ($this->becoming)(new DeleteCategoryInput(categoryId: $categoryId));

        $this->assertInstanceOf(CategoryDeleted::class, $final);
        $this->assertSame($categoryId, $final->categoryId);
        $this->assertNull($this->storage->getById($categoryId));
    }

    public function testDeleteRejectsUnknownCategory(): void
    {
        $this->expectException(CategoryNotFoundException::class);
        ($this->becoming)(new DeleteCategoryInput(categoryId: 'nonexistent-zzz'));
    }

    public function testDeleteRejectsAnonymousAdmin(): void
    {
        $categoryId = $this->seedRoot('Food');
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new DeleteCategoryInput(categoryId: $categoryId));
    }

    // ---- CSV import (Phase 2 stub) ----

    public function testCsvImportIsStubButRespectsAuthz(): void
    {
        $final = ($this->becoming)(new ImportCategoryCsvInput(
            csv: "categoryName,sortNo\nFood,10\nDrinks,20\n",
        ));

        $this->assertInstanceOf(CategoryCsvImported::class, $final);
        $this->assertFalse($final->accepted);
        $this->assertSame(3, $final->lineCount);
        $this->assertTrue(str_contains($final->message, 'Phase 2 stub'));
    }

    public function testCsvImportRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ImportCategoryCsvInput(csv: 'whatever'));
    }

    // ---- CSV export ----

    public function testCsvExportDumpsEveryRow(): void
    {
        $this->seedRoot('Food', 10);
        $this->seedRoot('Drinks', 20);

        $final = ($this->becoming)(new ExportCategoryInput());

        $this->assertInstanceOf(CategoryCsvExported::class, $final);
        $this->assertSame(2, $final->rowCount);
        $this->assertTrue(str_contains($final->csv, 'categoryId,categoryName,parentId,sortNo'));
        $this->assertTrue(str_contains($final->csv, 'Food'));
        $this->assertTrue(str_contains($final->csv, 'Drinks'));
    }

    public function testCsvExportRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ExportCategoryInput());
    }

    public function testCsvExportOnEmptyStoreReturnsHeaderOnly(): void
    {
        $final = ($this->becoming)(new ExportCategoryInput());
        $this->assertInstanceOf(CategoryCsvExported::class, $final);
        $this->assertSame(0, $final->rowCount);
        $this->assertSame("categoryId,categoryName,parentId,sortNo\n", $final->csv);
    }

    public function testListCountIsConsistentWithStorage(): void
    {
        $this->assertCount(0, $this->storage->list());
        $this->seedRoot('A');
        $this->seedRoot('B');
        $final = ($this->becoming)(new GetAdminCategoryListInput());
        assert($final instanceof AdminCategoryListFetched);
        $this->assertSame(count($this->storage->list()), $final->count);
    }
}
