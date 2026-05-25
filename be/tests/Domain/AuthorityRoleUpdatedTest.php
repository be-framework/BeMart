<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AuthorityRoleUpdated;
use MyVendor\BeMart\Be\Input\UpdateAuthorityRoleInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Privilege-escalation guard tests — the load-bearing AUTHZ rule of
 * Wave 8 (doUpdateAuthorityRole):
 *
 *   caller.authority < target.authority    must hold
 *
 * Anything else (equal-or-lower) raises InsufficientAuthorityException.
 */
final class AuthorityRoleUpdatedTest extends TestCase
{
    private const SYSTEM_ADMIN_ID = 'ad000000000000000000000000000001';  // test-admin, authority=0
    private const SHOP_OWNER_ID   = 'ad000000000000000000000000000002';  // shop-owner, authority=1

    private BecomingInterface $becoming;

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

    public function testSystemAdminCanDemoteShopOwner(): void
    {
        $this->build(self::SYSTEM_ADMIN_ID);

        // system-admin (auth=0) flips shop-owner (auth=1) → 1 (no-op
        // here, but the caller HAS the privilege to do so since
        // 0 < 1). This proves the rule passes when caller is strictly
        // higher.
        $final = ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: 'shop-owner',
            authority: 1,
        ));

        $this->assertInstanceOf(AuthorityRoleUpdated::class, $final);
        $this->assertSame('shop-owner', $final->loginId);
        $this->assertFalse($final->changed);
        $this->assertSame(1, $final->previousAuthority);
    }

    public function testSystemAdminCanPromoteShopOwnerToSystemAdmin(): void
    {
        $this->build(self::SYSTEM_ADMIN_ID);

        $final = ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: 'shop-owner',
            authority: 0,
        ));

        $this->assertTrue($final->changed);
        $this->assertSame(1, $final->previousAuthority);
        $this->assertSame(0, $final->authority);
        // FakeQuery fixtures are static; role persistence is covered by the SQL suite.
    }

    public function testShopOwnerCannotPromoteSelfToSystemAdmin(): void
    {
        // The CRITICAL privilege-escalation guard. shop-owner
        // (auth=1) trying to lift themselves to auth=0. The check is
        // caller.authority < target.authority — both are 1 here, so
        // the strict inequality refuses.
        $this->build(self::SHOP_OWNER_ID);

        $this->expectException(InsufficientAuthorityException::class);
        ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: 'shop-owner',  // self
            authority: 0,
        ));
    }

    public function testShopOwnerCannotFlipPeerShopOwner(): void
    {
        // Equal-authority peers cannot flip each other.
        $this->build(self::SHOP_OWNER_ID);

        $this->expectException(InsufficientAuthorityException::class);
        ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: 'deputy',  // also auth=1
            authority: 0,
        ));
    }

    public function testShopOwnerCannotDemoteSystemAdmin(): void
    {
        // Lower-privilege caller (auth=1) targeting higher-privilege
        // target (auth=0) — refused because 1 >= 0.
        $this->build(self::SHOP_OWNER_ID);

        $this->expectException(InsufficientAuthorityException::class);
        ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: 'test-admin',
            authority: 1,
        ));
    }

    public function testUnknownTargetRaisesNotFound(): void
    {
        $this->build(self::SYSTEM_ADMIN_ID);

        $this->expectException(AdminNotFoundException::class);
        ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: 'no-such-admin',
            authority: 1,
        ));
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: 'shop-owner',
            authority: 0,
        ));
    }
}
