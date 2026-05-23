<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for goMypageHistory (Phase 3
 * enrichment).
 *
 * Mirrors {@see \MyVendor\BeMart\Tests\Resource\MypageHistoryResourceTest}
 * but drives {@see \MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery::historyByOrderNo}
 * via `ResourceInterface::get('page://self/mypage/history')` after
 * seeding rows through the SQL fixture helpers — dtb_order +
 * dtb_payment + dtb_shipping + dtb_order_item + dtb_mail_history.
 *
 * Fake / SQL parity contract — the same resource URI must produce the
 * same enriched body shape (orderNo / message / paymentMethod /
 * shippings[] with grouped items / mailHistories[]) whether
 * OrderQueryInterface resolves to the Fake or the SQL impl.
 *
 * customerId comes from {@see SessionInterface} (Pilot 5 F-2 lesson —
 * the actor is read from the session, never the request body); the
 * test rebinds it to the inserted customer's numeric dtb_customer.id
 * (SqlOrderQuery's owner AUTHZ compares string-equal against it).
 */
final class MypageHistoryResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** @var non-empty-string|null */
    private string|null $currentCustomerId = null;

    protected function extraOverride(): AbstractModule|null
    {
        $customerId = $this->currentCustomerId;

        return new class ($customerId) extends AbstractModule {
            /** @param non-empty-string|null $customerId */
            public function __construct(private readonly string|null $customerId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)
                    ->toInstance(new FakeSession($this->customerId));
            }
        };
    }

    /** @param non-empty-string|null $customerId */
    private function loginAs(string|null $customerId): void
    {
        $this->currentCustomerId = $customerId;
        $this->resource = $this->buildResource();
    }

    public function testOnGetHappyPathReturnsEnrichedHistoryBody(): void
    {
        $customerId = $this->insertCustomer(['email' => 'hist-owner@example.com']);
        $paymentId = $this->insertPayment(['payment_method' => '銀行振込']);
        $order = $this->insertOrder([
            'customer_id' => $customerId,
            'payment_id' => $paymentId,
            'order_no' => 'SQL-HIST-001',
            'message' => '配送は平日希望です。',
            'subtotal' => 11000,
            'delivery_fee_total' => 600,
            'charge' => 0,
            'tax' => 1100,
            'total' => 12700,
            'payment_total' => 12700,
            'add_point' => 127,
        ]);
        $shippingId = $this->insertShipping([
            'order_id' => $order['id'],
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'postal_code' => '5300001',
            'addr01' => '大阪市北区梅田',
            'addr02' => '1-2-3',
            'phone_number' => '0612345678',
            'delivery_name' => 'サンプル宅配便',
            'delivery_time' => '午前中',
        ]);
        $this->insertOrderItem($order['id'], [
            'shipping_id' => $shippingId,
            'product_name' => 'サンプル商品 A',
            'product_code' => 'sample-001',
            'price' => 1200,
            'quantity' => 1,
        ]);
        $this->insertOrderItem($order['id'], [
            'shipping_id' => $shippingId,
            'product_name' => 'Sample Product B',
            'product_code' => 'sample-002',
            'price' => 9800,
            'quantity' => 1,
        ]);
        $this->insertMailHistory($order['id'], [
            'send_date' => '2026-04-01 10:05:00',
            'mail_subject' => 'ご注文ありがとうございます',
            'mail_body' => 'ご注文を承りました。',
        ]);

        $this->loginAs((string) $customerId);

        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'SQL-HIST-001',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('SQL-HIST-001', $ro->body['orderNo']);
        $this->assertSame('配送は平日希望です。', $ro->body['message']);
        $this->assertSame('銀行振込', $ro->body['paymentMethod']);
        $this->assertSame(12700, $ro->body['total']);
        $this->assertSame(127, $ro->body['addPoint']);

        $this->assertCount(1, $ro->body['shippings']);
        $shipping = $ro->body['shippings'][0];
        $this->assertSame('山田', $shipping['name01']);
        $this->assertSame('太郎', $shipping['name02']);
        $this->assertSame('5300001', $shipping['postalCode']);
        $this->assertSame('サンプル宅配便', $shipping['deliveryName']);
        $this->assertCount(2, $shipping['items']);
        $this->assertSame('sample-001', $shipping['items'][0]['productCode']);
        $this->assertSame('Sample Product B', $shipping['items'][1]['productName']);
        $this->assertSame(9800, $shipping['items'][1]['unitPrice']);

        $this->assertCount(1, $ro->body['mailHistories']);
        $this->assertSame('ご注文ありがとうございます', $ro->body['mailHistories'][0]['mailSubject']);
    }

    public function testOnGetUnknownOrderReturns404(): void
    {
        $customerId = $this->insertCustomer(['email' => 'hist-404@example.com']);
        $this->loginAs((string) $customerId);

        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'SQL-HIST-NOPE',
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetWrongOwnerReturns403(): void
    {
        $owner = $this->insertCustomer(['email' => 'hist-owner2@example.com']);
        $intruder = $this->insertCustomer(['email' => 'hist-intruder@example.com']);
        $this->insertOrder([
            'customer_id' => $owner,
            'order_no' => 'SQL-HIST-OWNED',
        ]);

        $this->loginAs((string) $intruder);

        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'SQL-HIST-OWNED',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnGetAnonymousReturns401(): void
    {
        $owner = $this->insertCustomer(['email' => 'hist-anon@example.com']);
        $this->insertOrder([
            'customer_id' => $owner,
            'order_no' => 'SQL-HIST-ANON',
        ]);

        $this->loginAs(null);

        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'SQL-HIST-ANON',
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }
}
