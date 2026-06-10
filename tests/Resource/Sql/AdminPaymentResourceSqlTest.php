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
 * SQL-backed hypermedia coverage for the admin Payment master CRUD
 * endpoints — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminPaymentResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/payment/payment-list` and
 * `page://self/admin/payment/payment`), same body-shape assertions,
 * same AUTHN / AUTHZ / CSRF branches. The only differences are:
 *
 *  - the storage binding (PaymentMethodAdminStorageInterface →
 *    SqlPaymentMethodAdminStorage) and id query
 *    (PaymentMethodAdminIdQueryInterface →
 *    direct MediaQuery payment id proxy) are layered via the base class's
 *    sqlOverrideModule; persistence is against the real dtb_payment
 *    table.
 *
 *  - paymentIds are numeric strings drawn from dtb_payment.id, not the
 *    32-char hex the FakePaymentMethodAdminIdProvider emits. Both
 *    suites assert "the response carries a paymentId" but only the SQL
 *    side observes a numeric handle. An unknown-id PUT folds to a 404
 *    on both backends (SqlPaymentMethodAdminStorage rejects a
 *    non-numeric id as a miss, the same 404 path as any unknown id).
 *
 *  - the Fake-backed sibling starts with an empty PaymentMethodAdminStorageInterface
 *    and seeds every row through the resource layer's POST affordance;
 *    dtb_payment is likewise empty on each test, so this sibling seeds
 *    the same way — the POST drives the full Becoming chain
 *    (Input → PaymentMethodAdminCreated → direct MediaQuery payment id proxy
 *    → SqlPaymentMethodAdminStorage). No direct fixture seeding is
 *    needed since the Payment resource exposes a create affordance.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminPaymentResourceSqlTest extends AbstractResourceSqlTestCase
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
     * Seed a single payment-method master through the resource layer
     * and return the server-generated paymentId — mirrors the
     * Fake-backed sibling's helper exactly. The POST drives the full
     * Becoming chain so the row appears in the same transactional state
     * every subsequent assertion will see.
     */
    private function seed(string $name, int $charge = 0): string
    {
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => $name,
            'charge' => $charge,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['paymentId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'クレジットカード',
            'charge' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('クレジットカード', $ro->body['paymentMethodName']);
        $this->assertTrue($ro->body['visible']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'クレジットカード',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testListReturnsRows(): void
    {
        $this->seed('代金引換', 300);
        $this->seed('クレジットカード');

        $ro = $this->resource->get('page://self/admin/payment/payment-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/payment/payment-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testPutEditsMaster(): void
    {
        $id = $this->seed('クレジットカード');

        $ro = $this->resource->put('page://self/admin/payment/payment', [
            'paymentId' => $id,
            'paymentMethodName' => 'クレジット',
            'charge' => 200,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('クレジット', $ro->body['paymentMethodName']);
        $this->assertSame(200, $ro->body['charge']);
    }

    public function testPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/payment/payment', [
            'paymentId' => 'nonexistent-zzz',
            'paymentMethodName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('代金引換');

        $ro = $this->resource->delete('page://self/admin/payment/payment', [
            'paymentId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['paymentId']);
    }

}
