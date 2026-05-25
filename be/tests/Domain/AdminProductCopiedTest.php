<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCopied;
use MyVendor\BeMart\Be\Input\AdminCopyProductInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductCopiedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathCopiesProductWithPrefixedName(): void
    {
        $final = ($this->becoming)(new AdminCopyProductInput(
            productCode: 'admin-active-001',
            newProductCode: 'admin-active-001.copy',
        ));

        $this->assertInstanceOf(AdminProductCopied::class, $final);
        $this->assertSame('admin-active-001', $final->productCode);
        $this->assertSame('admin-active-001.copy', $final->newProductCode);
        // ALPS doc: タイトルは「(コピー) 」プレフィクス付き。
        $this->assertStringStartsWith('(コピー) ', $final->newProductName);
        $this->assertSame(3500, $final->price02);

        // Persistence read-back belongs to the SQL suite. Fake context is
        // static Ray.FakeQuery fixtures and does not mutate query state.
    }

    public function testCopyOfWithdrawnSourceIsVisible(): void
    {
        // Copying a withdrawn product yields a fresh visible draft.
        $final = ($this->becoming)(new AdminCopyProductInput(
            productCode: 'admin-withdrawn-001',
            newProductCode: 'admin-withdrawn-001.copy',
        ));

        $this->assertInstanceOf(AdminProductCopied::class, $final);
        $this->assertSame('admin-withdrawn-001.copy', $final->newProductCode);
        $this->assertStringStartsWith('(コピー) ', $final->newProductName);
    }

    public function testUnknownSourceRaisesNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);
        ($this->becoming)(new AdminCopyProductInput(
            productCode: 'does-not-exist',
            newProductCode: 'somewhere-new-001',
        ));
    }

    public function testCollidingTargetRaisesAlreadyInUse(): void
    {
        $this->expectException(ProductCodeAlreadyInUseException::class);
        ($this->becoming)(new AdminCopyProductInput(
            productCode: 'admin-active-001',
            newProductCode: 'sample-001',  // already exists
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminCopyProductInput(
            productCode: 'admin-active-001',
            newProductCode: 'newcode-001',
        ));
    }
}
