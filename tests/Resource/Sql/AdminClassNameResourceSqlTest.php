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
use function str_contains;

/**
 * SQL-backed hypermedia coverage for the admin ClassName endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminClassNameResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/class-name/class-name-list`,
 * `page://self/admin/class-name/class-name`), same body-shape
 * assertions, same AUTHN / CSRF branches. The only differences are:
 *
 *  - the storage binding (ClassNameStorageInterface →
 *    SqlClassNameStorage) and id generator
 *    (ClassNameIdGeneratorInterface → direct MediaQuery class-name id proxy) are
 *    layered via the base class's sqlOverrideModule; persistence is
 *    against the real dtb_class_name table.
 *
 *  - classNameIds are numeric strings drawn from dtb_class_name.id, not
 *    the 32-char hex the FakeClassNameIdGenerator emits. Both suites
 *    assert "the response carries a classNameId" but only the Fake side
 *    ever observes a hex handle — SqlClassNameStorage rejects a
 *    non-numeric id as a miss on lookup (the same 404 path as any
 *    unknown id, by design). `nonexistent-zzz` therefore folds to a 404
 *    on both backends, so the unknown-id case mirrors exactly.
 *
 *  - dtb_class_name is empty on each test (the per-test transaction
 *    rolls back), so the list / put / delete cases seed their own rows
 *    through the resource layer first — same shape the Fake sibling
 *    uses (FakeClassNameStorage also starts empty).
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — a contract change masquerading as a
 * storage change.
 */
final class AdminClassNameResourceSqlTest extends AbstractResourceSqlTestCase
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
     * (Input → ClassName-create Final → direct MediaQuery class-name id proxy →
     * SqlClassNameStorage) so the row appears in the same transactional
     * state every subsequent assertion will see.
     */
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
        // `nonexistent-zzz` is non-numeric — SqlClassNameStorage::getById
        // surfaces it as a miss, so the ClassName Update Final raises its
        // normal 404, identical to the Fake-backed sibling.
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
