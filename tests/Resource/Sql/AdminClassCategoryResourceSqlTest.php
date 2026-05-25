<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function assert;
use function is_string;

/**
 * SQL-backed hypermedia coverage for the admin ClassCategory endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminClassCategoryResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/class-category/class-category-list`,
 * `page://self/admin/class-category/class-category`), same body-shape
 * assertions, same AUTHN / CSRF / 404 branches. The only differences
 * are:
 *
 *  - the storage bindings (ClassCategoryStorageInterface →
 *    SqlClassCategoryStorage, ClassCategoryIdGeneratorInterface →
 *    SqlClassCategoryIdGenerator, plus the ClassName pairing the
 *    create-Final's referential check needs) are layered via the base
 *    class's sqlOverrideModule; persistence is against the real
 *    dtb_class_category / dtb_class_name tables.
 *
 *  - classCategoryIds / classNameIds are numeric strings drawn from the
 *    table autoinc, not the 32-char hex the Fake generators emit. Both
 *    suites assert "the response carries an id" but only the Fake side
 *    ever observes a hex handle — SqlClassCategoryStorage rejects a
 *    non-numeric id as a miss on lookup (the same 404 path as any
 *    unknown id, by design). `nonexistent-zzz` therefore folds to a 404
 *    on both backends, so the unknown-id cases mirror exactly.
 *
 *  - dtb_class_category / dtb_class_name are empty on each test (the
 *    per-test transaction rolls back), so the list / put / delete cases
 *    seed their own rows through the resource layer first — same shape
 *    the Fake sibling uses (the Fake storages also start empty).
 *
 * The class_name_id FK pin: a ClassCategory belongs to a parent
 * ClassName axis. The seed helpers POST a class-name first, then a
 * class-category referencing the returned classNameId — exactly the
 * Fake-backed sibling's flow. The create-Final's referential check
 * (`ClassNameStorage::getById`) runs against SqlClassNameStorage here,
 * so `nonexistent-zzz` (non-numeric, a miss) folds to the same 404 the
 * Fake produces.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — a contract change masquerading as a
 * storage change.
 */
final class AdminClassCategoryResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

    protected function extraOverride(): AbstractModule|null
    {
        $adminId = $this->currentAdminId;

        return new class ($adminId) extends AbstractModule {
            /** @param non-empty-string|null $adminId */
            public function __construct(private readonly string|null $adminId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)
                    ->toInstance(new FakeAdminSession($this->adminId));
            }
        };
    }

    /**
     * Swap the admin session adminId and rebuild the Resource client so
     * the new binding takes effect — same shape as the Fake-backed
     * sibling's `rebindAdminSession`.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    /**
     * Seed a single class name (axis) through the resource layer and
     * return the server-generated classNameId — mirrors the Fake-backed
     * sibling's helper exactly. The POST drives the full Becoming chain
     * (Input → ClassName-create Final → SqlClassNameIdGenerator →
     * SqlClassNameStorage) so the parent axis row appears in the same
     * transactional state every subsequent assertion will see.
     */
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

    /**
     * Seed a single class category (variant value) under an axis and
     * return the server-generated classCategoryId — mirrors the
     * Fake-backed sibling's helper exactly.
     */
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
        // `nonexistent-zzz` is non-numeric — SqlClassNameStorage::getById
        // surfaces it as a miss, so the ClassCategory-create Final raises
        // its ClassNameNotFound 404, identical to the Fake-backed sibling.
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
        // `nonexistent-zzz` is non-numeric — SqlClassCategoryStorage::getById
        // surfaces it as a miss, so the ClassCategory Update Final raises
        // its normal 404, identical to the Fake-backed sibling.
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
