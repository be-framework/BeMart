<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function str_contains;

/**
 * SQL-backed hypermedia coverage for the admin shipping-address
 * transitions (Phase 2b, G-23 storage migration contract).
 *
 * Mirrors the shipping-address slice of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminOrderExtrasResourceTest}:
 *
 *   - POST /admin/order/shipping-address   doSelectShippingAddress
 *   - PUT  /admin/order/shipping-address   doUpdateShippingAddress
 *   - GET  /admin/order/export-shipping    goExportShipping
 *
 * Those three URIs are the ones that exercise
 * {@see \MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface}
 * — the Reason this commit migrates from Fake to SQL. The bulk-delete /
 * send-mail / create-order / export-order / export-order-pdf /
 * import-shipping branches of the Fake-backed sibling do NOT touch
 * ShippingAddressStorage, so they are not re-mirrored here (their own
 * storage Reasons already have SQL siblings elsewhere).
 *
 * Per G-23 this is the migration contract: the same resource URIs must
 * produce the same body shapes whether ShippingAddressStorageInterface
 * (and the OrderQuery / AddressStorage it composes with) resolve to the
 * Fake or the SQL impl. The Fake-backed sibling stays untouched; this
 * SQL sibling seeds rows via the SqlFixturesTrait helpers.
 *
 * Difference from the Fake-backed sibling: customer / address ids are
 * the numeric `dtb_customer.id` / `dtb_customer_address.id` values the
 * SQL fixture helpers return, not the 32-char hex tokens the Fake test
 * hard-codes — SqlAddressStorage rejects non-numeric ids as a miss.
 *
 * AdminSession is rebound per-test with a {@see FakeAdminSession}
 * layered on top of the SQL override, same pattern as
 * {@see AdminOrderStatusResourceSqlTest}.
 */
final class AdminOrderExtrasResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

    /** Numeric dtb_customer.id of the order owner used across the suite. */
    private string $aliceId = '';

    /** A second customer — used for the cross-customer ownership 404. */
    private string $bobId = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->aliceId = (string) $this->insertCustomer(['email' => 'alice@example.com']);
        $this->bobId = (string) $this->insertCustomer(['email' => 'bob@example.com']);

        // mtb_pref is empty in the structure-only dump; seed the single
        // prefecture id the fixtures use so the FK from
        // dtb_customer_address.pref_id / dtb_shipping.pref_id holds.
        $this->insertPref(13, 'Tokyo');
    }

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
     * Swap the admin session adminId and rebuild the Resource client.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    /**
     * Seed a finalized dtb_order owned by the given customer and return
     * its order_no. order_status_id is NEW so SqlOrderQuery::byOrderNo
     * (which excludes PROCESSING) finds it.
     */
    private function seedOrder(string $orderNo, string $customerId): string
    {
        $this->insertOrder([
            'order_no' => $orderNo,
            'customer_id' => (int) $customerId,
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);

        return $orderNo;
    }

    // ------------------------------------------------------------------
    // doSelectShippingAddress (POST)
    // ------------------------------------------------------------------

    public function testSelectShippingAddressHappyPath(): void
    {
        $orderNo = $this->seedOrder('SQL-SHIP-SEL-1', $this->aliceId);
        $addressId = $this->insertAddress([
            'customer_id' => (int) $this->aliceId,
            'name01' => '山田',
            'name02' => '太郎',
            'postal_code' => '1010001',
            'pref_id' => 13,
            'addr01' => '千代田区',
            'addr02' => '千代田1-1',
            'phone_number' => '0312345678',
        ]);

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'addressId' => (string) $addressId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($orderNo, $ro->body['orderNo']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame(13, $ro->body['pref']);
        $this->assertSame('千代田区', $ro->body['addr01']);
        $this->assertSame('千代田1-1', $ro->body['addr02']);

        // The pick is durable: getByOrderNo round-trips through SQL.
        $stmt = $this->pdo->prepare(
            'SELECT s.name01 FROM dtb_shipping s '
            . 'INNER JOIN dtb_order o ON o.id = s.order_id '
            . 'WHERE o.order_no = :no',
        );
        $stmt->execute([':no' => $orderNo]);
        $this->assertSame('山田', $stmt->fetchColumn());
    }

    public function testSelectShippingAddressUnknownOrderReturns404(): void
    {
        $addressId = $this->insertAddress(['customer_id' => (int) $this->aliceId]);

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => 'nonexistentordernononononononono',
            'addressId' => (string) $addressId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSelectShippingAddressUnknownAddressReturns404(): void
    {
        $orderNo = $this->seedOrder('SQL-SHIP-SEL-2', $this->aliceId);

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'addressId' => '999999',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSelectShippingAddressForeignAddressReturns404(): void
    {
        // An address owned by Bob cannot be attached to Alice's order:
        // ownership mismatch → 404 (anti-enumeration).
        $orderNo = $this->seedOrder('SQL-SHIP-SEL-3', $this->aliceId);
        $bobAddressId = $this->insertAddress([
            'customer_id' => (int) $this->bobId,
            'name01' => '佐藤',
            'name02' => '次郎',
        ]);

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'addressId' => (string) $bobAddressId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSelectShippingAddressMissingCsrfReturns403(): void
    {
        $orderNo = $this->seedOrder('SQL-SHIP-SEL-4', $this->aliceId);
        $addressId = $this->insertAddress(['customer_id' => (int) $this->aliceId]);

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'addressId' => (string) $addressId,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testSelectShippingAddressWithoutAdminReturns403(): void
    {
        $orderNo = $this->seedOrder('SQL-SHIP-SEL-5', $this->aliceId);
        $addressId = $this->insertAddress(['customer_id' => (int) $this->aliceId]);

        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'addressId' => (string) $addressId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    // ------------------------------------------------------------------
    // doUpdateShippingAddress (PUT)
    // ------------------------------------------------------------------

    public function testUpdateShippingAddressHappyPath(): void
    {
        $orderNo = $this->seedOrder('SQL-SHIP-UPD-1', $this->aliceId);

        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'name01' => '田中',
            'name02' => '花子',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前2-2',
            'phoneNumber' => '0900000000',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('田中', $ro->body['name01']);
        $this->assertSame('神宮前2-2', $ro->body['addr02']);

        // The shipping CSV export now sees one row.
        $exported = $this->resource->get('page://self/admin/order/export-shipping');
        $this->assertSame(1, $exported->body['rowCount']);
    }

    public function testUpdateShippingAddressOverwritesExistingRow(): void
    {
        // A second PUT to the same order UPDATEs in place — no second
        // dtb_shipping row, and the export still has exactly one row.
        $orderNo = $this->seedOrder('SQL-SHIP-UPD-2', $this->aliceId);

        $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'name01' => 'V1',
            'name02' => 'Name',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => 'A',
            'addr02' => 'B',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'name01' => 'V2',
            'name02' => 'Name',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => 'A',
            'addr02' => 'B',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('V2', $ro->body['name01']);

        $exported = $this->resource->get('page://self/admin/order/export-shipping');
        $this->assertSame(1, $exported->body['rowCount']);
    }

    public function testUpdateShippingAddressUnknownOrderReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => 'nonexistentordernononononononono',
            'name01' => 'X',
            'name02' => 'Y',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => 'A',
            'addr02' => 'B',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testUpdateShippingAddressMissingCsrfReturns403(): void
    {
        $orderNo = $this->seedOrder('SQL-SHIP-UPD-3', $this->aliceId);

        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'name01' => 'X',
            'name02' => 'Y',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => 'A',
            'addr02' => 'B',
            'phoneNumber' => '0312345678',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUpdateShippingAddressWithoutAdminReturns403(): void
    {
        $orderNo = $this->seedOrder('SQL-SHIP-UPD-4', $this->aliceId);

        $this->rebindAdminSession(null);

        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => $orderNo,
            'name01' => 'X',
            'name02' => 'Y',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => 'A',
            'addr02' => 'B',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ------------------------------------------------------------------
    // goExportShipping (GET) — listAll
    // ------------------------------------------------------------------

    public function testExportShippingEmptyByDefault(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-shipping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('text/csv; charset=UTF-8', $ro->headers['Content-Type']);
        $this->assertSame(0, $ro->body['rowCount']);
        $this->assertTrue(str_contains($ro->body['csv'], 'trackingNumber'));
    }

    public function testExportShippingDumpsEveryRow(): void
    {
        $orderA = $this->seedOrder('SQL-SHIP-EXP-A', $this->aliceId);
        $orderB = $this->seedOrder('SQL-SHIP-EXP-B', $this->aliceId);
        foreach ([$orderA, $orderB] as $orderNo) {
            $this->resource->put('page://self/admin/order/shipping-address', [
                'orderNo' => $orderNo,
                'name01' => '山田',
                'name02' => '太郎',
                'postalCode' => '1500001',
                'pref' => 13,
                'addr01' => '渋谷区',
                'addr02' => '神宮前1-1',
                'phoneNumber' => '0312345678',
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
        }

        $ro = $this->resource->get('page://self/admin/order/export-shipping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['rowCount']);
        $this->assertTrue(str_contains($ro->body['csv'], $orderA));
        $this->assertTrue(str_contains($ro->body['csv'], $orderB));
    }

    public function testExportShippingWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/order/export-shipping');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
