<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCreated;
use MyVendor\BeMart\Be\Input\AdminCreateProductInput;
use MyVendor\BeMart\Be\Reason\Query\FakeProductStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductCreatedTest extends TestCase
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
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
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

    public function testHappyPathPersistsProduct(): void
    {
        $final = ($this->becoming)(new AdminCreateProductInput(
            productCode: 'wave8-new-001',
            productName: 'Wave 8 新規',
            price02: 4200,
            stock: 100,
            productStatus: 1,
            description: 'Created in test',
        ));

        $this->assertInstanceOf(AdminProductCreated::class, $final);
        $this->assertSame('wave8-new-001', $final->productCode);
        $this->assertSame('Wave 8 新規', $final->productName);
        $this->assertSame(4200, $final->price02);
        $this->assertSame(100, $final->stock);
        $this->assertSame(1, $final->productStatus);

        $storage = $this->injector->getInstance(FakeProductStorage::class);
        $persisted = $storage->getByCode('wave8-new-001');
        $this->assertNotNull($persisted);
        $this->assertSame('Wave 8 新規', $persisted->productName);
    }

    public function testDefaultStatusIsVisible(): void
    {
        $final = ($this->becoming)(new AdminCreateProductInput(
            productCode: 'wave8-default-status-001',
            productName: 'デフォルト',
            price02: 100,
            stock: null,
        ));

        $this->assertInstanceOf(AdminProductCreated::class, $final);
        $this->assertSame(1, $final->productStatus);
    }

    public function testDuplicateCodeRaisesAlreadyInUse(): void
    {
        $this->expectException(ProductCodeAlreadyInUseException::class);
        ($this->becoming)(new AdminCreateProductInput(
            productCode: 'sample-001',  // pre-existing seed
            productName: 'Collision',
            price02: 1,
        ));
    }

    public function testInvalidProductCodeRaisesSemanticVariableException(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new AdminCreateProductInput(
            productCode: 'has space!',  // ProductCode regex disallows
            productName: 'Bad code',
            price02: 100,
        ));
    }

    public function testInvalidStatusRaisesSemanticVariableException(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new AdminCreateProductInput(
            productCode: 'wave8-bad-status',
            productName: 'Bad status',
            price02: 100,
            productStatus: 99,  // not in [1,2,3]
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminCreateProductInput(
            productCode: 'wave8-anon-001',
            productName: 'Anon',
            price02: 1000,
        ));
    }
}
