<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Form\AdminOrderMailForm;
use MyVendor\BeMart\Form\AdminOrderShippingForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function count;
use function dirname;
use function str_contains;

/**
 * Wave 9η — admin order extras (bulk delete + mail + exports + shipping).
 *
 *   - POST /admin/order/bulk-delete         doBulkDeleteOrder
 *   - POST /admin/order/send-mail           doSendOrderMail
 *   - POST /admin/order/create              doCreateOrder
 *   - GET  /admin/order/export-order        goExportOrder
 *   - GET  /admin/order/export-shipping     goExportShipping
 *   - GET  /admin/order/export-order-pdf    goExportOrderPdf
 *   - POST /admin/order/import-shipping     doImportShippingCsv (stub)
 *   - POST /admin/order/shipping-address    doSelectShippingAddress
 *   - PUT  /admin/order/shipping-address    doUpdateShippingAddress
 */
final class AdminOrderExtrasResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ORDER_NO_A = 'admin000000000000000extras00001a';
    private const ORDER_NO_B = 'admin000000000000000extras00001b';

    private ResourceInterface $resource;
    private Injector $injector;
    private AddressStorageInterface $addressStorage;
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
        $this->seedOrders();
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        $this->addressStorage = $this->injector->getInstance(AddressStorageInterface::class);

        $mailer = $this->injector->getInstance(MailerInterface::class);
        assert($mailer instanceof FakeMailer);
        $this->mailer = $mailer;
    }

    private function seedOrders(): void
    {
        $this->orderStorage->put(new FinalizedOrderEntity(
            orderNo: self::ORDER_NO_A,
            preOrderId: 'admin0000000000000extras00001ap',
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            subtotal: 8000,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: 800,
            total: 9300,
            paymentTotal: 9300,
            addPoint: 93,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-15 10:00:00',
            paymentDate: '2026-05-15 10:00:00',
        ));
        $this->orderStorage->put(new FinalizedOrderEntity(
            orderNo: self::ORDER_NO_B,
            preOrderId: 'admin0000000000000extras00001bp',
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            subtotal: 5000,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: 500,
            total: 6000,
            paymentTotal: 6000,
            addPoint: 60,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-16 10:00:00',
            paymentDate: '2026-05-16 10:00:00',
        ));
    }

    // ------------------------------------------------------------------
    // doBulkDeleteOrder
    // ------------------------------------------------------------------

    public function testBulkDeleteHappyPath(): void
    {
        $ro = $this->resource->post('page://self/admin/order/bulk-delete', [
            'orderNos' => [self::ORDER_NO_A, self::ORDER_NO_B],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['requestedCount']);
        $this->assertSame(2, $ro->body['changedCount']);

        $persisted = $this->orderStorage->byOrderNo(self::ORDER_NO_A);
        assert($persisted !== null);
        $this->assertSame(FinalizedOrderEntity::STATUS_CANCEL, $persisted->orderStatus);
    }

    public function testBulkDeleteSilentlySkipsUnknown(): void
    {
        $ro = $this->resource->post('page://self/admin/order/bulk-delete', [
            'orderNos' => [self::ORDER_NO_A, 'nonexistent-zzz000000000000000zz'],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['requestedCount']);
        $this->assertSame(1, $ro->body['changedCount']);
    }

    public function testBulkDeleteReplayCountsZero(): void
    {
        $this->resource->post('page://self/admin/order/bulk-delete', [
            'orderNos' => [self::ORDER_NO_A],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $ro = $this->resource->post('page://self/admin/order/bulk-delete', [
            'orderNos' => [self::ORDER_NO_A],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(0, $ro->body['changedCount']);
    }

    public function testBulkDeleteEmptyListReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/order/bulk-delete', [
            'orderNos' => [],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testBulkDeleteMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/order/bulk-delete', [
            'orderNos' => [self::ORDER_NO_A],
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testBulkDeleteWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrders();

        $ro = $this->resource->post('page://self/admin/order/bulk-delete', [
            'orderNos' => [self::ORDER_NO_A],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    // ------------------------------------------------------------------
    // doSendOrderMail
    // ------------------------------------------------------------------

    public function testSendMailHappyPathInvokesMailer(): void
    {
        $before = count($this->mailer->sent);

        $ro = $this->resource->post('page://self/admin/order/send-mail', [
            'orderNo' => self::ORDER_NO_A,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ORDER_NO_A, $ro->body['orderNo']);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame($before + 1, count($this->mailer->sent));
    }

    public function testSendMailUnknownOrderReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/order/send-mail', [
            'orderNo' => 'nonexistent-zzz000000000000000zz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSendMailMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/order/send-mail', [
            'orderNo' => self::ORDER_NO_A,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testSendMailWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrders();

        $ro = $this->resource->post('page://self/admin/order/send-mail', [
            'orderNo' => self::ORDER_NO_A,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnGetSendMailRendersComposer(): void
    {
        $ro = $this->resource->get('page://self/admin/order/send-mail', [
            'orderNo' => self::ORDER_NO_A,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminOrderMailForm::class, $ro->body['form']);
        $this->assertSame(self::ORDER_NO_A, $ro->body['orderNo']);
    }

    public function testOnGetSendMailRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/order/send-mail');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    // ------------------------------------------------------------------
    // doCreateOrder
    // ------------------------------------------------------------------

    public function testCreateOrderHappyPathReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/order/create', [
            'customerId' => self::ALICE_ID,
            'paymentMethodId' => 2,
            'subtotal' => 1000,
            'deliveryFeeTotal' => 100,
            'tax' => 100,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame(1200, $ro->body['total']);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $ro->body['orderStatus']);
        $this->assertArrayHasKey('Location', $ro->headers);

        // Round-trip through OrderQuery to confirm persistence.
        $orderNo = $ro->body['orderNo'];
        $persisted = $this->orderStorage->byOrderNo($orderNo);
        $this->assertNotNull($persisted);
    }

    public function testCreateOrderMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/order/create', [
            'customerId' => self::ALICE_ID,
            'paymentMethodId' => 2,
            'subtotal' => 1000,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateOrderWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/order/create', [
            'customerId' => self::ALICE_ID,
            'paymentMethodId' => 2,
            'subtotal' => 1000,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ------------------------------------------------------------------
    // goExportOrder
    // ------------------------------------------------------------------

    public function testExportOrderDumpsRows(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-order');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('text/csv; charset=UTF-8', $ro->headers['Content-Type']);
        // 2 seed orders + 1 pre-existing seed (from Ray.FakeQuery fixture JSON).
        $this->assertGreaterThanOrEqual(2, $ro->body['rowCount']);
        $this->assertTrue(str_contains($ro->body['csv'], 'orderNo'));
        $this->assertTrue(str_contains($ro->body['csv'], self::ORDER_NO_A));
    }

    public function testExportOrderWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/order/export-order');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ------------------------------------------------------------------
    // goExportShipping (also covered indirectly via doUpdateShippingAddress)
    // ------------------------------------------------------------------

    public function testExportShippingEmptyByDefault(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-shipping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(0, $ro->body['rowCount']);
        $this->assertTrue(str_contains($ro->body['csv'], 'trackingNumber'));
    }

    public function testExportShippingWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/order/export-shipping');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ------------------------------------------------------------------
    // goExportOrderPdf
    // ------------------------------------------------------------------

    public function testExportOrderPdfReturnsPdfDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNos' => [self::ORDER_NO_A],
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('application/pdf', $ro->headers['Content-Type']);
        $this->assertSame('attachment; filename="nouhinsyo-No' . self::ORDER_NO_A . '.pdf"', $ro->headers['Content-Disposition']);
        $this->assertSame(self::ORDER_NO_A, $ro->body['orderNo']);
        $this->assertSame([self::ORDER_NO_A], $ro->body['orderNos']);
        $this->assertGreaterThan(0, $ro->body['size']);
        $this->assertStringStartsWith('%PDF-', $ro->body['pdf']);
        $this->assertStringNotContainsString('PDF STUB', $ro->body['pdf']);
    }

    public function testExportOrderPdfUnknownReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNos' => ['nonexistent-zzz000000000000000zz'],
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testExportOrderPdfWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrders();
        $ro = $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNos' => [self::ORDER_NO_A],
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ------------------------------------------------------------------
    // doImportShippingCsv (real ingestion)
    // ------------------------------------------------------------------

    public function testImportShippingPersistsKnownOrdersAndSkipsUnknown(): void
    {
        $ro = $this->resource->post('page://self/admin/order/import-shipping', [
            'csv' => "受注番号,お問い合わせ番号\n"
                . "past0000000000000000000000000001,XY-123\n"
                . "no-such-order,ZZ-999\n",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doImportShippingCsv', $ro->body['transitionId']);
        $this->assertTrue($ro->body['accepted']);
        $this->assertSame(1, $ro->body['imported']);
        $this->assertSame(1, $ro->body['skipped']);
    }

    public function testImportShippingWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/order/import-shipping', [
            'csv' => 'foo',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testImportShippingMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/order/import-shipping', [
            'csv' => 'foo',
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnGetImportShippingRendersUploadForm(): void
    {
        $ro = $this->resource->get('page://self/admin/order/import-shipping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame([], $ro->body);
    }

    public function testOnGetImportShippingRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/order/import-shipping');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    // ------------------------------------------------------------------
    // doSelectShippingAddress / doUpdateShippingAddress
    // ------------------------------------------------------------------

    private function seedAddress(string $addressId): void
    {
        $this->addressStorage->put(new AddressEntity(
            addressId: $addressId,
            customerId: self::ALICE_ID,
            name01: '山田',
            name02: '太郎',
            kana01: 'ヤマダ',
            kana02: 'タロウ',
            companyName: null,
            phoneNumber: '03-1234-5678',
            postalCode: '1010001',
            pref: 13,
            addr01: '千代田区',
            addr02: '千代田1-1',
        ));
    }

    public function testSelectShippingAddressHappyPath(): void
    {
        $this->seedAddress('addr-alice-001');

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => self::ORDER_NO_A,
            'addressId' => 'addr-alice-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame(13, $ro->body['pref']);
        $this->assertSame('千代田区', $ro->body['addr01']);
    }

    public function testSelectShippingAddressUnknownOrderReturns404(): void
    {
        $this->seedAddress('addr-alice-001');

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => 'nonexistent-zzz000000000000000zz',
            'addressId' => 'addr-alice-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSelectShippingAddressUnknownAddressReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => self::ORDER_NO_A,
            'addressId' => 'addr-does-not-exist',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSelectShippingAddressForeignAddressReturns404(): void
    {
        // Seed an address owned by some other customer.
        $this->addressStorage->put(new AddressEntity(
            addressId: 'addr-bob-001',
            customerId: 'bob000000000000000000000000000000',
            name01: '佐藤',
            name02: '次郎',
            kana01: null,
            kana02: null,
            companyName: null,
            phoneNumber: null,
            postalCode: '1500001',
            pref: 13,
            addr01: '渋谷区',
            addr02: '神宮前1-1',
        ));

        $ro = $this->resource->post('page://self/admin/order/shipping-address', [
            'orderNo' => self::ORDER_NO_A,
            'addressId' => 'addr-bob-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testUpdateShippingAddressHappyPath(): void
    {
        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => self::ORDER_NO_A,
            'name01' => '田中',
            'name02' => '花子',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前2-2',
            'phoneNumber' => '090-0000-0000',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('田中', $ro->body['name01']);
        $this->assertSame('神宮前2-2', $ro->body['addr02']);

        // The shipping CSV export should now have one row.
        $exported = $this->resource->get('page://self/admin/order/export-shipping');
        $this->assertSame(1, $exported->body['rowCount']);
    }

    public function testUpdateShippingAddressUnknownOrderReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => 'nonexistent-zzz000000000000000zz',
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
        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => self::ORDER_NO_A,
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

    public function testShippingAddressWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrders();

        $ro = $this->resource->put('page://self/admin/order/shipping-address', [
            'orderNo' => self::ORDER_NO_A,
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

    // ----------------------------------------------------------------
    // GET /admin/order/shipping-address — Order Tier-2 shipping editor
    // ----------------------------------------------------------------

    public function testOnGetShippingRendersBlankEditor(): void
    {
        $ro = $this->resource->get('page://self/admin/order/shipping-address', [
            'orderNo' => self::ORDER_NO_A,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminOrderShippingForm::class, $ro->body['form']);
        $this->assertSame(self::ORDER_NO_A, $ro->body['orderNo']);
    }

    public function testOnGetShippingRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/order/shipping-address');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
