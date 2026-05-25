<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberUpdated;
use MyVendor\BeMart\Be\Input\UpdateMemberInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class MemberUpdatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    private function build(string|null $adminId): void
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
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathUpdatesName(): void
    {
        $final = ($this->becoming)(new UpdateMemberInput(
            loginId: 'shop-owner',
            name: '改名後オーナー',
        ));

        $this->assertInstanceOf(MemberUpdated::class, $final);
        $this->assertSame('shop-owner', $final->loginId);
        $this->assertSame('改名後オーナー', $final->name);
        // Authority and work are preserved (no role-flip via this path).
        $this->assertSame(1, $final->authority);
        $this->assertSame(1, $final->work);
        // FakeQuery fixtures are static; merged persistence is covered by the SQL suite.
    }

    public function testPartialUpdateLeavesUnchangedFieldsAlone(): void
    {
        // Null name leaves the existing value untouched — verifies the
        // merge-semantics for omitted optional fields stays intact.
        $final = ($this->becoming)(new UpdateMemberInput(
            loginId: 'deputy',
            // name omitted — keep existing.
        ));

        $this->assertSame('副管理者', $final->name);
        // Authority and work untouched too.
        $this->assertSame(1, $final->authority);
        $this->assertSame(1, $final->work);
    }

    public function testUnknownLoginIdRaisesNotFound(): void
    {
        $this->expectException(AdminNotFoundException::class);
        ($this->becoming)(new UpdateMemberInput(
            loginId: 'no-such-admin',
            name: 'irrelevant',
        ));
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateMemberInput(
            loginId: 'shop-owner',
            name: 'attacker-rename',
        ));
    }
}
