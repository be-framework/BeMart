<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use RuntimeException;

final class SqlCartCommandTest extends AbstractSqlTestCase
{
    public function testSaveInsertsNewCartWithItems(): void
    {
        $this->insertProduct(['product_code' => 'NEW-A', 'price02' => 100]);
        $this->insertProduct(['product_code' => 'NEW-B', 'price02' => 200]);

        $command = $this->sql(CartCommandInterface::class);
        $command->save(new CartEntity(
            cartKey: 'fresh-session_1',
            saleTypeId: 1,
            saleTypeName: 'ignored on save',
            items: [
                new CartItemEntity(productCode: 'NEW-A', quantity: 2, price: 100),
                new CartItemEntity(productCode: 'NEW-B', quantity: 3, price: 200),
            ],
            totalPrice: 800,
            deliveryFeeTotal: 500,
            preOrderId: 'pre-fresh-001',
        ));

        // Read back via the query — round-trips through real schema.
        $query = $this->sql(CartQueryInterface::class);
        $cart = $query->byCartKey('fresh-session_1');

        $this->assertNotNull($cart);
        $this->assertSame(800, $cart->totalPrice);
        $this->assertSame(500, $cart->deliveryFeeTotal);
        $this->assertSame('pre-fresh-001', $cart->preOrderId);
        $this->assertCount(2, $cart->items);
        $codes = array_map(static fn ($i) => $i->productCode, $cart->items);
        sort($codes);
        $this->assertSame(['NEW-A', 'NEW-B'], $codes);
    }

    public function testSaveOverwritesExistingCartReplacingItems(): void
    {
        $this->insertProduct(['product_code' => 'OLD-1', 'price02' => 100]);
        $this->insertProduct(['product_code' => 'NEW-1', 'price02' => 250]);
        $this->insertProduct(['product_code' => 'NEW-2', 'price02' => 300]);

        $command = $this->sql(CartCommandInterface::class);
        // First save — old shape.
        $command->save(new CartEntity(
            cartKey: 'overwrite_1',
            saleTypeId: 1,
            saleTypeName: '',
            items: [new CartItemEntity(productCode: 'OLD-1', quantity: 1, price: 100)],
            totalPrice: 100,
            deliveryFeeTotal: 0,
            preOrderId: 'pre-old',
        ));
        // Second save — overwriting with completely different items.
        $command->save(new CartEntity(
            cartKey: 'overwrite_1',
            saleTypeId: 1,
            saleTypeName: '',
            items: [
                new CartItemEntity(productCode: 'NEW-1', quantity: 2, price: 250),
                new CartItemEntity(productCode: 'NEW-2', quantity: 1, price: 300),
            ],
            totalPrice: 800,
            deliveryFeeTotal: 100,
            preOrderId: 'pre-new',
        ));

        $query = $this->sql(CartQueryInterface::class);
        $cart = $query->byCartKey('overwrite_1');
        $this->assertNotNull($cart);
        // Old item must NOT survive.
        $codes = array_map(static fn ($i) => $i->productCode, $cart->items);
        sort($codes);
        $this->assertSame(['NEW-1', 'NEW-2'], $codes);
        $this->assertSame(800, $cart->totalPrice);
        $this->assertSame('pre-new', $cart->preOrderId);

        // Confirm physical row count too — no orphan items lurking on
        // the original cart_id (cascaded delete worked).
        $countItems = $this->pdo->query('SELECT COUNT(*) FROM dtb_cart_item');
        $this->assertNotFalse($countItems);
        $this->assertSame(2, (int) $countItems->fetchColumn());
    }

    public function testSaveThrowsForUnknownProductCode(): void
    {
        $command = $this->sql(CartCommandInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GHOST-PRODUCT');
        $command->save(new CartEntity(
            cartKey: 'will-fail_1',
            saleTypeId: 1,
            saleTypeName: '',
            items: [new CartItemEntity(productCode: 'GHOST-PRODUCT', quantity: 1, price: 0)],
            totalPrice: 0,
            deliveryFeeTotal: 0,
            preOrderId: 'pre-fail',
        ));
    }

    public function testClearByPreOrderIdRemovesCartAndCascadesItems(): void
    {
        $productId = $this->insertProduct(['product_code' => 'CLR-1']);
        $cart = $this->insertCart([
            'cart_key' => 'clear-by-pre_1',
            'pre_order_id' => 'pre-clear-001',
        ]);
        $this->insertCartItem($cart['id'], $this->defaultProductClassId($productId));

        $command = $this->sql(CartCommandInterface::class);
        $command->clearByPreOrderId('pre-clear-001');

        $query = $this->sql(CartQueryInterface::class);
        $this->assertNull($query->byCartKey('clear-by-pre_1'));
        // Items cascaded too.
        $countItems = $this->pdo->query('SELECT COUNT(*) FROM dtb_cart_item');
        $this->assertNotFalse($countItems);
        $this->assertSame(0, (int) $countItems->fetchColumn());
    }

    public function testClearByPreOrderIdIsNoOpWhenMissing(): void
    {
        $this->insertCart(['cart_key' => 'keep_1', 'pre_order_id' => 'keep-pre-1']);

        $command = $this->sql(CartCommandInterface::class);
        $command->clearByPreOrderId('does-not-exist');

        // The unrelated cart survives.
        $query = $this->sql(CartQueryInterface::class);
        $this->assertNotNull($query->byCartKey('keep_1'));
    }

    public function testClearBySessionPrefixOnlyRemovesMatchingPrefix(): void
    {
        $this->insertCart(['cart_key' => 'drop_1']);
        $this->insertCart(['cart_key' => 'drop_2']);
        $this->insertCart(['cart_key' => 'drop_3']);
        $this->insertCart(['cart_key' => 'keep_1']);
        // Trickier: same prefix string but with an extra char before
        // the underscore — must NOT match `drop`.
        $this->insertCart(['cart_key' => 'dropper_1']);

        $command = $this->sql(CartCommandInterface::class);
        $command->clearBySessionPrefix('drop');

        $query = $this->sql(CartQueryInterface::class);
        $this->assertSame([], $query->bySessionPrefix('drop'));
        $this->assertNotNull($query->byCartKey('keep_1'));
        $this->assertNotNull($query->byCartKey('dropper_1'));
    }
}
