<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for doCheckout (Phase 2b).
 *
 * Mirrors {@see \MyVendor\BeMart\Tests\Resource\CheckoutResourceTest}
 * but exercises the SQL Order backends end-to-end via
 * `ResourceInterface::post('page://self/shopping/checkout', ...)`.
 *
 * Per G-23 this is the migration contract: the same resource URI must
 * produce the same ShoppingComplete body shape whether
 * `OrderQueryInterface` / `OrderCommandInterface` resolve to the Fake
 * or the SQL pair. The Fake-backed sibling stays untouched; this SQL
 * sibling seeds the pre-order row directly via the SqlFixturesTrait
 * helper (no `var/fake/orders.json` involvement).
 *
 * What the SQL pair proves here that the unit test cannot:
 *  - `register` is invoked through the full Becoming cascade
 *    (CheckoutInput → CheckoutPrepared → CheckoutSettled →
 *    CheckoutCompleted) under the DI envelope, and PROMOTES the
 *    pre-order row {@see SqlOrderQuery::byPreOrderId} read at stage 1
 *    — the same physical dtb_order row goes PROCESSING(8)→NEW(1).
 *  - The order is durable: a post-checkout SELECT finds exactly one
 *    finalized row carrying the issued order_no.
 *
 * Scope note — the rest of the checkout cascade
 * ---------------------------------------------
 * PurchaseFlow / InventoryAllocator / PaymentGateway / OrderNumber /
 * Mailer are still Fake-bound (Phase 2 has not migrated them). The
 * seeded pre-order carries no dtb_order_item rows, so
 * {@see SqlOrderQuery::byPreOrderId} returns an OrderEntity with an
 * empty items list — FakePurchaseFlow then computes subtotal 0 + the
 * pre-order's delivery fee, and FakeInventoryAllocator allocates
 * nothing. That is fine for the contract under test: this sibling
 * proves the OrderCommand WRITE path, not the totals math (the
 * Fake-backed sibling already pins the totals against the
 * `var/fake/orders.json` fixture with real line items).
 *
 * Session is rebound per-test with a {@see FakeSession} carrying the
 * pre-order's numeric customer id (CheckoutPrepared's ownership check
 * compares `session->customerId()` against the OrderEntity's
 * customerId, both strings) — the same `rebindSession` pattern the
 * Fake-backed sibling uses, layered on top of the SQL override.
 */
final class CheckoutResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** A valid 40-lowercase-hex PreOrderId Semantic token. */
    private const PRE_ORDER_ID = 'aaaabbbbccccddddeeeeffff00001111deadbeef';

    private string|null $currentCustomerId = null;

    protected function extraOverride(): AbstractModule|null
    {
        $customerId = $this->currentCustomerId;

        return new class ($customerId) extends AbstractModule {
            public function __construct(private readonly string|null $customerId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                /** @var non-empty-string|null $id */
                $id = $this->customerId === '' ? null : $this->customerId;
                $this->bind(SessionInterface::class)
                    ->toInstance(new FakeSession($id));
            }
        };
    }

    /**
     * Swap the session customerId and rebuild the Resource client so the
     * new binding takes effect.
     */
    private function rebindSession(string|null $customerId): void
    {
        $this->currentCustomerId = $customerId;
        $this->resource = $this->buildResource();
    }

    /**
     * Seed a PROCESSING(8) pre-order row owned by a freshly-inserted
     * customer and return both ids. delivery_fee_total drives the
     * FakePurchaseFlow total since the row has no line items.
     */
    private function seedPreOrder(int $deliveryFee = 500): array
    {
        $customerId = $this->insertCustomer();
        $this->insertOrder([
            'customer_id' => $customerId,
            'pre_order_id' => self::PRE_ORDER_ID,
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
            'payment_id' => null,
            'subtotal' => 0,
            'total' => 0,
            'payment_total' => 0,
            'delivery_fee_total' => $deliveryFee,
        ]);

        return ['customerId' => (string) $customerId];
    }

    public function testOnPostCheckoutPromotesPreOrderAndReturns201(): void
    {
        $seed = $this->seedPreOrder(deliveryFee: 500);
        $this->rebindSession($seed['customerId']);

        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => self::PRE_ORDER_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $ro->body['orderNo']);
        $this->assertSame($seed['customerId'], $ro->body['customerId']);
        // No line items on the SQL-seeded pre-order → subtotal 0 + the
        // pre-order's delivery fee (FakePurchaseFlow passes it through).
        $this->assertSame(500, $ro->body['total']);
        $this->assertSame(500, $ro->body['paymentTotal']);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $ro->body['orderStatus']);
        $this->assertSame('', $ro->body['completeMessage']);
        $this->assertArrayHasKey('Location', $ro->headers);

        $orderNo = $ro->body['orderNo'];

        // The promotion mutated the SAME physical row — exactly one row
        // still carries the pre_order_id, now finalized as NEW.
        $stmt = $this->pdo->prepare(
            'SELECT order_no, order_status_id FROM dtb_order '
            . 'WHERE pre_order_id = :pre',
        );
        $stmt->execute([':pre' => self::PRE_ORDER_ID]);
        $rows = $stmt->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame($orderNo, $rows[0]['order_no']);
        $this->assertSame(
            FinalizedOrderEntity::STATUS_NEW,
            (int) $rows[0]['order_status_id'],
        );
    }

    public function testOnPostCheckoutOrderIsReadableAfterWrite(): void
    {
        $seed = $this->seedPreOrder(deliveryFee: 800);
        $this->rebindSession($seed['customerId']);

        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => self::PRE_ORDER_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $orderNo = $ro->body['orderNo'];

        // SqlOrderQuery — the read side — must surface the finalized row
        // the SqlOrderCommand write side just promoted.
        $stmt = $this->pdo->prepare(
            'SELECT order_status_id, total, delivery_fee_total '
            . 'FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => $orderNo]);
        $row = $stmt->fetch();
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, (int) $row['order_status_id']);
        $this->assertSame(800, (int) $row['delivery_fee_total']);
        $this->assertSame(800, (int) $row['total']);
    }

    public function testOnPostUnknownPreOrderReturns404(): void
    {
        // A well-formed 40-hex id with no matching pre-order row.
        $missing = '0000111122223333444455556666777788889999';
        $this->rebindSession('1');

        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => $missing,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame($missing, $ro->body['preOrderId']);
    }

    public function testOnPostForeignCustomerReturns403(): void
    {
        // The pre-order belongs to the seeded customer; a different
        // logged-in customer cannot confirm it.
        $this->seedPreOrder();
        $this->rebindSession('99999');

        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => self::PRE_ORDER_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertSame(self::PRE_ORDER_ID, $ro->body['preOrderId']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $seed = $this->seedPreOrder();
        $this->rebindSession($seed['customerId']);

        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => self::PRE_ORDER_ID,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
