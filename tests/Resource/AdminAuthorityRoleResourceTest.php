<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for doUpdateAuthorityRole — the Wave 8
 * critical AUTHZ extension. The privilege-escalation guard is
 * exercised through HTTP semantics: peer-level callers get 403; a
 * strictly-higher-privilege caller succeeds; idempotent replays
 * return 200 with `changed=false`.
 */
final class AdminAuthorityRoleResourceTest extends TestCase
{
    private const SYSTEM_ADMIN_ID = 'ad000000000000000000000000000001';
    private const SHOP_OWNER_ID   = 'ad000000000000000000000000000002';

    private ResourceInterface $resource;

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
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testSystemAdminCanPromoteShopOwner(): void
    {
        $this->rebindAdminSession(self::SYSTEM_ADMIN_ID);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['changed']);
        $this->assertSame(1, $ro->body['previousAuthority']);
        $this->assertSame(0, $ro->body['authority']);
    }

    public function testIdempotentReplaySetsChangedFalse(): void
    {
        $this->rebindAdminSession(self::SYSTEM_ADMIN_ID);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 1,  // already this value
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
    }

    public function testShopOwnerCannotPromoteSelf(): void
    {
        // THE critical privilege-escalation guard test.
        $this->rebindAdminSession(self::SHOP_OWNER_ID);

        $this->expectException(\MyVendor\BeMart\Be\Exception\InsufficientAuthorityException::class);

        $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testShopOwnerCannotFlipPeer(): void
    {
        $this->rebindAdminSession(self::SHOP_OWNER_ID);

        $this->expectException(\MyVendor\BeMart\Be\Exception\InsufficientAuthorityException::class);

        $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'deputy',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testUnknownTargetReturns404(): void
    {
        $this->rebindAdminSession(self::SYSTEM_ADMIN_ID);

        $this->expectException(\MyVendor\BeMart\Be\Exception\AdminNotFoundException::class);

        $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'no-such-admin',
            'authority' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    /**
     * Phase 3 admin HTML Tier-2: the GET renderer exposes the
     * authority-rule editor body shape (`authorityOptions` + `rules`).
     */
    public function testOnGetReturnsAuthorityRuleBody(): void
    {
        $this->rebindAdminSession(self::SYSTEM_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/authority-role');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertNotEmpty($ro->body['authorityOptions']);
        $this->assertArrayHasKey('rules', $ro->body);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/authority-role');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
