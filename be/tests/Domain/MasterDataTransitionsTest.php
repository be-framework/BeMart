<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MasterDataSelected;
use MyVendor\BeMart\Be\Final\MasterDataUpdated;
use MyVendor\BeMart\Be\Input\SelectMasterDataInput;
use MyVendor\BeMart\Be\Input\UpdateMasterDataInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMasterDataWriter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class MasterDataTransitionsTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private Injector $injector;
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

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
    }

    public function testSelectReturnsRows(): void
    {
        $final = ($this->becoming)(new SelectMasterDataInput(masterType: 'tag'));

        $this->assertInstanceOf(MasterDataSelected::class, $final);
        $this->assertSame('tag', $final->masterType);
        $this->assertNotEmpty($final->rows);
    }

    public function testSelectUnknownMasterRejectedAtSemanticBoundary(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new SelectMasterDataInput(masterType: 'no-such-master'));
    }

    public function testUpdateWritesRows(): void
    {
        $final = ($this->becoming)(new UpdateMasterDataInput(
            masterType: 'tag',
            rows: [['id' => 't1', 'name' => '新タグ', 'sortNo' => 1]],
        ));

        $this->assertInstanceOf(MasterDataUpdated::class, $final);
        $this->assertSame(1, $final->count);
        $this->assertCount(1, $this->injector->getInstance(FakeMasterDataWriter::class)->writes);
    }

    public function testUpdateRefusesAnonymous(): void
    {
        $this->build(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateMasterDataInput(masterType: 'tag', rows: []));
    }
}
