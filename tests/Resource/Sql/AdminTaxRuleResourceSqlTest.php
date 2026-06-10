<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function assert;
use function is_string;
use function str_contains;

/**
 * SQL-backed hypermedia coverage for the admin TaxRule master CRUD —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminTaxRuleResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/tax-rule/tax-rule-list` and
 * `page://self/admin/tax-rule/tax-rule`), same body-shape assertions,
 * same AUTHZ + CSRF branches. The only differences:
 *
 *  - the storage binding (TaxRuleStorageInterface → SqlTaxRuleStorage)
 *    and id query (TaxRuleIdQueryInterface → direct MediaQuery tax-rule id proxy)
 *    are layered via the base class's sqlOverrideModule; persistence
 *    is against the real dtb_tax_rule table.
 *  - the AUTHZ override rebinds AdminSession per case (via
 *    `rebindAdminSession`) — same convention as
 *    {@see AdminBaseInfoResourceSqlTest}.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 *
 * Note: alps.json has no `doUpdateTaxRule` transition — edits flow as
 * delete + create. Only POST / GET / DELETE are exercised, same shape
 * as the Fake-backed sibling.
 */
final class AdminTaxRuleResourceSqlTest extends AbstractResourceSqlTestCase
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
            }
        };
    }

    /**
     * Swap the admin session adminId and rebuild the Resource client
     * so the new binding takes effect — same shape as the Fake-backed
     * sibling's `rebindAdminSession`.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    private function seed(float $rate, string $applyDate = '2024-04-01T00:00:00+09:00'): string
    {
        $ro = $this->resource->post('page://self/admin/tax-rule/tax-rule-list', [
            'taxRate' => $rate,
            'applyDate' => $applyDate,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['taxRuleId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/tax-rule/tax-rule-list', [
            'taxRate' => 10.0,
            'applyDate' => '2024-04-01T00:00:00+09:00',
            'roundingType' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame(10.0, $ro->body['taxRate']);
        $this->assertSame(1, $ro->body['roundingType']);
        $this->assertSame('2024-04-01T00:00:00+09:00', $ro->body['applyDate']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/tax-rule/tax-rule-list', [
            'taxRate' => 10.0,
            'applyDate' => '2024-04-01T00:00:00+09:00',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testListReturnsRows(): void
    {
        $this->seed(10.0);
        $this->seed(8.0, '2023-10-01T00:00:00+09:00');

        $ro = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed(10.0);

        $ro = $this->resource->delete('page://self/admin/tax-rule/tax-rule', [
            'taxRuleId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['taxRuleId']);
    }

    public function testDeleteUnknownIdReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/tax-rule/tax-rule', [
            'taxRuleId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

}
