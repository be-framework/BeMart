<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminShippingCsvImported;
use MyVendor\BeMart\Be\Input\AdminImportShippingCsvInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminShippingCsvImportedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const KNOWN_ORDER_NO = 'past0000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    private function build(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testImportsKnownOrdersAndSkipsUnknown(): void
    {
        $final = ($this->becoming)(new AdminImportShippingCsvInput(
            csv: "受注番号,お問い合わせ番号\n"
                . self::KNOWN_ORDER_NO . ",XY-123\n"
                . "no-such-order,ZZ-999\n",
        ));

        $this->assertInstanceOf(AdminShippingCsvImported::class, $final);
        $this->assertTrue($final->accepted);
        $this->assertSame(3, $final->lineCount);
        $this->assertSame(1, $final->imported);
        $this->assertSame(1, $final->skipped);
    }

    public function testRefusesAnonymous(): void
    {
        $this->build(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminImportShippingCsvInput(csv: 'whatever'));
    }
}
