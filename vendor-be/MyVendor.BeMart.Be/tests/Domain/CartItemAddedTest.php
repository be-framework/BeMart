<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OutOfStockException;
use MyVendor\BeMart\Be\Exception\ProductClassNotFoundException;
use MyVendor\BeMart\Be\Exception\QuantityFormatException;
use MyVendor\BeMart\Be\Final\CartItemAdded;
use MyVendor\BeMart\Be\Input\AddCartItemInput;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class CartItemAddedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testAddInStockProductSucceeds(): void
    {
        $final = ($this->becoming)(new AddCartItemInput('sample-001', 2));

        $this->assertInstanceOf(CartItemAdded::class, $final);
        $this->assertSame('sample-001', $final->productCode);
        $this->assertSame(2, $final->requestedQuantity);
        $this->assertSame(2, $final->adjustedQuantity);
        $this->assertSame(1200, $final->unitPrice);
        $this->assertSame(2400, $final->totalPrice);
        $this->assertSame('session-prefix-1_1', $final->cartKey);
        $this->assertSame('通常販売', $final->saleTypeName);
    }

    public function testStockShortageAutoAdjusts(): void
    {
        // sample-003 has stock=3. Request 5 → adjusted to 3.
        $final = ($this->becoming)(new AddCartItemInput('sample-003', 5));

        $this->assertSame(5, $final->requestedQuantity);
        $this->assertSame(3, $final->adjustedQuantity);
        $this->assertSame(13500, $final->totalPrice); // 4500 × 3
    }

    public function testSaleLimitCapsQuantity(): void
    {
        // single-purchase-rare-coin: saleLimit=1.
        $final = ($this->becoming)(new AddCartItemInput('single-purchase-rare-coin', 5));

        $this->assertSame(1, $final->adjustedQuantity);
    }

    public function testStockUnlimitedSkipsStockCap(): void
    {
        // unlimited-digital-book: stockUnlimited=true, stock=null.
        $final = ($this->becoming)(new AddCartItemInput('unlimited-digital-book', 99));

        $this->assertSame(99, $final->adjustedQuantity);
    }

    public function testOutOfStockThrows(): void
    {
        $this->expectException(OutOfStockException::class);
        ($this->becoming)(new AddCartItemInput('out-of-stock-test-001', 1));
    }

    public function testMissingProductThrows(): void
    {
        $this->expectException(ProductClassNotFoundException::class);
        ($this->becoming)(new AddCartItemInput('does-not-exist', 1));
    }

    public function testInvalidQuantityRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new AddCartItemInput('sample-001', 0));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                QuantityFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testSameSkuAddedTwiceMergesQuantity(): void
    {
        // test-cart-merge-001: price02=1000, stock=100.
        ($this->becoming)(new AddCartItemInput('test-cart-merge-001', 2));
        $final = ($this->becoming)(new AddCartItemInput('test-cart-merge-001', 3));

        // Merge sums quantity, totalPrice reflects the merged cart.
        $this->assertSame(5000, $final->totalPrice); // 1000 × (2+3)
    }

    public function testDifferentSaleTypeIsolatesCart(): void
    {
        $normal = ($this->becoming)(new AddCartItemInput('sample-001', 1));
        $preorder = ($this->becoming)(new AddCartItemInput('preorder-2026-spring-bag', 1));

        $this->assertSame('session-prefix-1_1', $normal->cartKey);
        $this->assertSame('session-prefix-1_2', $preorder->cartKey);
        $this->assertNotSame($normal->cartKey, $preorder->cartKey);
    }
}
