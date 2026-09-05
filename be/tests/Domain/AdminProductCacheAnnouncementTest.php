<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Input\AdminCopyProductInput;
use MyVendor\BeMart\Be\Input\AdminCreateProductInput;
use MyVendor\BeMart\Be\Input\AdminDeleteProductInput;
use MyVendor\BeMart\Be\Input\AdminUpdateProductInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\RecordingProductCacheInvalidator;
use MyVendor\BeMart\Module\TestModule;
use Override;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

/**
 * Every admin write announces that the product corpus changed
 *
 * The storefront reads a cached corpus. Without the announcement it keeps serving the old row
 * until a TTL runs out, and the shorter that TTL is made the more the cache costs - a write that
 * says so is what lets the cache be worth having. The invalidation itself is covered by the
 * cache tests; what this pins is the wiring: the four transitions that change a product all call it.
 */
final class AdminProductCacheAnnouncementTest extends TestCase
{
    private const TEST_ADMIN_ID = 'admin00000000000000000000000001';

    private BecomingInterface $becoming;
    private RecordingProductCacheInvalidator $invalidator;

    protected function setUp(): void
    {
        $this->invalidator = new RecordingProductCacheInvalidator();
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class (new FakeAdminSession(self::TEST_ADMIN_ID), $this->invalidator) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly RecordingProductCacheInvalidator $invalidator,
            ) {
                parent::__construct();
            }

            #[Override]
            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                $this->bind(ProductCacheInvalidatorInterface::class)->toInstance($this->invalidator);
            }
        });

        $this->becoming = (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))->getInstance(BecomingInterface::class);
    }

    public function testAnUpdateAnnouncesIt(): void
    {
        ($this->becoming)(new AdminUpdateProductInput(productCode: 'admin-active-001', productName: '新しい名前'));

        $this->assertSame(1, $this->invalidator->calls);
    }

    public function testACreateAnnouncesIt(): void
    {
        ($this->becoming)(new AdminCreateProductInput(
            productCode: 'announce-001',
            productName: '新商品',
            price02: 1200,
        ));

        $this->assertSame(1, $this->invalidator->calls);
    }

    public function testADeleteAnnouncesIt(): void
    {
        ($this->becoming)(new AdminDeleteProductInput(productCode: 'admin-active-001'));

        $this->assertSame(1, $this->invalidator->calls);
    }

    public function testACopyAnnouncesIt(): void
    {
        ($this->becoming)(new AdminCopyProductInput(productCode: 'admin-active-001', newProductCode: 'admin-active-001.copy'));

        $this->assertSame(1, $this->invalidator->calls);
    }
}

