<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\StorefrontProductListFetched;
use MyVendor\BeMart\Be\Input\GetStorefrontProductListInput;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function array_column;
use function dirname;

/**
 * Storefront catalog listing — the customer-facing `goProductList`
 * domain transition (`GetStorefrontProductListInput` →
 * `StorefrontProductListFetched`).
 *
 * Unlike the admin grid ({@see ProductListFetchedTest}), this transition
 * has NO admin firewall — it is anonymous-accessible — and the Final
 * projects ONLY visible (公開) products. The seed corpus
 * (`be/var/fake/products.json`) carries 3 visible rows
 * (`sample-001`, `sample-002`, `admin-active-001`), 1 hidden
 * (`admin-hidden-001`, status 2) and 1 withdrawn (`admin-withdrawn-001`,
 * status 3).
 */
final class StorefrontProductListFetchedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testReturnsOnlyVisibleProducts(): void
    {
        $final = ($this->becoming)(new GetStorefrontProductListInput());

        $this->assertInstanceOf(StorefrontProductListFetched::class, $final);

        $ids = array_column($final->products, 'id');
        $this->assertContains('sample-001', $ids);
        $this->assertContains('sample-002', $ids);
        $this->assertContains('admin-active-001', $ids);
    }

    public function testExcludesHiddenAndWithdrawnProducts(): void
    {
        $final = ($this->becoming)(new GetStorefrontProductListInput());

        $ids = array_column($final->products, 'id');
        // status 2 (非公開) — admin-only, never on the storefront.
        $this->assertNotContains('admin-hidden-001', $ids);
        // status 3 (廃止) — soft-deleted.
        $this->assertNotContains('admin-withdrawn-001', $ids);
    }

    public function testTotalItemCountMatchesVisibleRowCount(): void
    {
        $final = ($this->becoming)(new GetStorefrontProductListInput());

        $this->assertSame(5, $final->totalItemCount);
        $this->assertCount(5, $final->products);
    }

    public function testEachRowCarriesTheStorefrontProjectionShape(): void
    {
        $final = ($this->becoming)(new GetStorefrontProductListInput());

        foreach ($final->products as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('price02', $row);
        }
    }

    public function testProjectsTheVisibleProductFieldValues(): void
    {
        $final = ($this->becoming)(new GetStorefrontProductListInput());

        $byId = [];
        foreach ($final->products as $row) {
            $byId[$row['id']] = $row;
        }

        $this->assertSame('サンプル商品 A', $byId['sample-001']['name']);
        $this->assertSame(1200, $byId['sample-001']['price02']);
    }
}
