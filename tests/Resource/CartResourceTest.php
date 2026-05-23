<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class CartResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsCartsForSessionPrefix(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        // fixture has session-prefix-1_1 + session-prefix-1_2, both empty.
        $this->assertSame(2, $ro->body['cartCount']);
        $this->assertSame(0, $ro->body['totalPrice']);
        $this->assertCount(2, $ro->body['carts']);
        $this->assertSame('session-prefix-1_1', $ro->body['carts'][0]['cartKey']);
        $this->assertSame(1, $ro->body['carts'][0]['saleTypeId']);
    }

    public function testOnGetReturnsEmptyForUnknownPrefix(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'no-such-session',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(0, $ro->body['cartCount']);
        $this->assertSame([], $ro->body['carts']);
    }

    public function testOnGetDefaultsToFixturePrefix(): void
    {
        $ro = $this->resource->get('page://self/cart');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['cartCount']);
    }

    /**
     * After adding an item, the cart-row body carries the display
     * fields the re-derived ALPS `CartItem` descriptor composes — so
     * the Cart HTML port can render a faithful EC-CUBE cart row.
     * FakeCartQuery re-derives `productName` from the product-class
     * Fake on read, mirroring SqlCartQuery's JOIN.
     */
    public function testOnGetItemBodyCarriesCartRowDisplayFields(): void
    {
        $post = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $post->code);

        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);
        $this->assertSame(Code::OK, $ro->code);

        $item = $ro->body['carts'][0]['items'][0];
        // dtb_cart_item's real columns.
        $this->assertSame('sample-001', $item['productCode']);
        $this->assertSame(3, $item['quantity']);
        $this->assertSame(1200, $item['price']);
        // Read-side display projection — productName re-derived from
        // the product-class Fake (var/fake/product_classes.json).
        $this->assertSame('サンプル商品 A', $item['productName']);
        // The enriched body exposes every cart-row display key.
        $this->assertArrayHasKey('productClassId', $item);
        $this->assertArrayHasKey('productId', $item);
        $this->assertArrayHasKey('mainImage', $item);
        $this->assertArrayHasKey('classCategoryName1', $item);
        $this->assertArrayHasKey('className1', $item);
        $this->assertArrayHasKey('classCategoryName2', $item);
        $this->assertArrayHasKey('className2', $item);
        // The Fake product fixture carries no image / no variation —
        // those fields are null, the same shape SQL produces for a
        // product with no dtb_product_image / no class category.
        $this->assertNull($item['mainImage']);
        $this->assertNull($item['classCategoryName1']);
    }
}
