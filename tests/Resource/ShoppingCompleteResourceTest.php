<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 enrichment — covers the Complete resource's JSON body.
 *
 * The Complete resource resolves the finalized-order header by the
 * `orderNo` the post-checkout redirect carries. It is a thin renderer —
 * no Be Becoming chain — so the AppModule default binding (FakeOrderQuery
 * over FakeFinalizedOrderStorage, which seeds one past order) suffices.
 */
final class ShoppingCompleteResourceTest extends TestCase
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

    public function testOnGetWithKnownOrderNoCarriesOrderNumber(): void
    {
        $ro = $this->resource->get('page://self/shopping/complete', [
            'orderNo' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertIsArray($ro->body);
        $this->assertSame(FakeFinalizedOrderStorage::SEED_ORDER_NO, $ro->body['orderNo']);
        $this->assertSame('goShoppingComplete', $ro->body['transitionId']);
        // Pilot 5's CheckoutCompleted produces no plugin-appended message.
        $this->assertSame('', $ro->body['completeMessage']);
    }

    public function testOnGetWithNoOrderNoRendersBlankOrderNumber(): void
    {
        $ro = $this->resource->get('page://self/shopping/complete');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertIsArray($ro->body);
        $this->assertSame('', $ro->body['orderNo']);
    }

    public function testOnGetWithUnknownOrderNoRendersBlankOrderNumber(): void
    {
        $ro = $this->resource->get('page://self/shopping/complete', [
            'orderNo' => 'ffffffffffffffffffffffffffffffff',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertIsArray($ro->body);
        $this->assertSame('', $ro->body['orderNo']);
    }

    public function testOnGetExposesOutboundTransitions(): void
    {
        $ro = $this->resource->get('page://self/shopping/complete');

        $this->assertIsArray($ro->body);
        $this->assertSame('page://self/', $ro->body['links']['goTop']);
        $this->assertSame('page://self/cart', $ro->body['links']['goCart']);
    }
}
