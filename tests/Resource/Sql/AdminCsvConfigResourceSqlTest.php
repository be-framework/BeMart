<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Exception\MissingCsrfTokenException;
use Ray\Csrf\Http\CompositeRequestToken;
use Ray\Csrf\Http\RequestTokenInterface;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for doUpdateCsv — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminCsvConfigResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs, same body-shape assertions, same AUTHN / CSRF branches.
 * The only difference is the storage binding
 * (CsvColumnConfigStorageInterface → SqlCsvColumnConfigStorage), layered
 * via the base class's sqlOverrideModule.
 *
 * Per G-23 the Fake-backed AdminCsvConfigResourceTest verifies the
 * round-trip by reading `CsvColumnConfigStorageInterface` back directly
 * out of the injector. The SQL sibling cannot reach into a private
 * injector, so it verifies the round-trip the way a client would: a
 * second `post` that REPLACES the vector observes the prior state via
 * the response shape, and the `csvType` / `count` echo proves the
 * Becoming chain reached SqlCsvColumnConfigStorage::replaceType. The
 * atomic-replace storage paths themselves are pinned in isolation by
 * {@see \MyVendor\BeMart\Be\Tests\Sql\SqlCsvColumnConfigStorageTest}.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. Fake green AND SQL green proves
 * the storage swap left the client-observable behaviour untouched.
 *
 * mtb_csv_type is empty in the structure-only dump and dtb_csv.csv_type_id
 * carries an enforced FK — every test seeds the master via seedCsvTypes
 * (the seedAdminMasters precedent) before posting a column vector.
 */
final class AdminCsvConfigResourceSqlTest extends AbstractResourceSqlTestCase
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
                $this->bind(AdminSession::class)
                    ->toInstance(new FakeAdminSession($this->adminId));
                $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class);
                $this->bind(RequestTokenInterface::class)->to(CompositeRequestToken::class);
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

    public function testOnPostHappyPathPersistsColumnVector(): void
    {
        $this->seedCsvTypes();

        $ro = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1, // product
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
                ['columnName' => 'productName', 'enabled' => true, 'sortNo' => 2],
                ['columnName' => 'note', 'enabled' => false, 'sortNo' => 3],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(3, $ro->body['csvType']);
        $this->assertSame(3, $ro->body['count']);
        // The columns echo mirrors the posted input shape.
        $this->assertSame('productCode', $ro->body['columns'][0]['columnName']);
        $this->assertFalse($ro->body['columns'][2]['enabled']);
    }

    public function testOnPostReplacesPreviousVectorAtomically(): void
    {
        $this->seedCsvTypes();

        // First write: 2 columns.
        $first = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'orderNo', 'enabled' => true, 'sortNo' => 1],
                ['columnName' => 'total', 'enabled' => true, 'sortNo' => 2],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $first->code);
        $this->assertSame(2, $first->body['count']);

        // Second write: 1 column — must replace, not merge. The new
        // count reflects ONLY the second vector.
        $second = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'orderDate', 'enabled' => true, 'sortNo' => 1],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $second->code);
        $this->assertSame(1, $second->body['count']);
        $this->assertSame('orderDate', $second->body['columns'][0]['columnName']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $this->seedCsvTypes();

        $this->expectException(MissingCsrfTokenException::class);
        $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
            ],
        ]);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->seedCsvTypes();
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
