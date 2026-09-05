<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberDeleted;
use MyVendor\BeMart\Be\Input\DeleteMemberInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class MemberDeletedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';  // test-admin, authority=0
    private const SHOP_OWNER_ID = 'ad000000000000000000000000000002';  // shop-owner, authority=1

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

    public function testHappyPathSoftDeletesAdmin(): void
    {
        $final = ($this->becoming)(new DeleteMemberInput(loginId: 'shop-owner'));

        $this->assertInstanceOf(MemberDeleted::class, $final);
        $this->assertSame('shop-owner', $final->loginId);
        $this->assertFalse($final->alreadyDeleted);
        // FakeQuery fixtures are static; soft-delete persistence is covered by the SQL suite.
    }

    #[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
    public function testIdempotentReDeleteIsNoOp(): void
    {
        $this->markTestSkipped('Idempotent re-delete needs mutable persistence; covered by the SQL suite.');
    }

    public function testSelfDeleteIsRefused(): void
    {
        // ALPS: 自身は削除不可. test-admin's adminId matches the session.
        $this->expectException(InsufficientAuthorityException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'test-admin'));
    }

    public function testShopOwnerCannotDeleteSystemAdmin(): void
    {
        $this->build(self::SHOP_OWNER_ID);

        $this->expectException(InsufficientAuthorityException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'test-admin'));
    }

    public function testShopOwnerCanDeletePeerShopOwner(): void
    {
        $this->build(self::SHOP_OWNER_ID);

        // deputy is also authority=1 — a peer, not a superior, so this is
        // ordinary staffing. Only outranking the caller is refused.
        $final = ($this->becoming)(new DeleteMemberInput(loginId: 'deputy'));

        $this->assertInstanceOf(MemberDeleted::class, $final);
        $this->assertSame('deputy', $final->loginId);
    }

    public function testStaleAdminSessionRefuses(): void
    {
        $this->build('ad0000000000000000000000000000ff');

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'shop-owner'));
    }

    public function testUnknownLoginIdRaisesNotFound(): void
    {
        $this->expectException(AdminNotFoundException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'no-such-admin'));
    }

    public function testNoAdminSessionRefusesBeforeExistenceCheck(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'no-such-admin'));
    }
}
