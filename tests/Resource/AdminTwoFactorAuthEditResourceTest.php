<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_key_exists;
use function dirname;
use function in_array;

/**
 * Resource-layer coverage for the admin 2段階認証設定 Tier-2 page.
 *
 * POST-AUTH admin-self 2FA edit. `onGet` is the AUTHZ-guarded render that
 * seeds a server-side setup challenge for the authenticated admin; `onPost`
 * confirms the first device code and drives doSetTwoFactorAuth for the
 * SESSION admin only.
 *
 * The submit suite PROVES the write side-effect: a different secret is
 * seeded and the post is asserted to change the stored secret for the
 * session identity, never for a client-supplied loginId.
 */
final class AdminTwoFactorAuthEditResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TEST_LOGIN_ID = 'test-admin';

    private ResourceInterface $resource;
    private FakeTwoFactorAuth $twoFactorAuth;
    private HtmlAdminLoginChallengeAdapter $challenge;

    protected function setUp(): void
    {
        $this->clearSession();
    }

    protected function tearDown(): void
    {
        $this->clearSession();
    }

    private function clearSession(): void
    {
        unset(
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
        );
    }

    /**
     * Builds an injector with the AdminSession overridden to the given
     * identity, and captures the shared FakeTwoFactorAuth + challenge
     * instances the resource will use, so the submit side-effect is
     * observable.
     */
    private function bootWithAdminSession(string|null $adminId): void
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
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->twoFactorAuth = $injector->getInstance(FakeTwoFactorAuth::class);
        $this->challenge = $injector->getInstance(HtmlAdminLoginChallengeAdapter::class);
    }

    public function testOnGetReturnsFormAndMemberName(): void
    {
        $this->bootWithAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/two-factor-auth-edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminTwoFactorAuthForm::class, $ro->body['form']);
        $this->assertSame(self::TEST_ADMIN_ID, $ro->body['memberName']);
        $this->assertSame('BeMart', $ro->body['shopName']);
    }

    public function testOnGetSeedsServerGeneratedAuthKeyForSessionAdmin(): void
    {
        $this->bootWithAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/two-factor-auth-edit');

        // The QR JS must read a REAL server secret, not the '' placeholder.
        $this->assertSame(FakeTwoFactorAuth::FIXED_SECRET, $ro->body['authKey']);
        // The setup challenge is bound to the AUTHENTICATED identity.
        $challenge = $this->challenge->setupChallenge();
        $this->assertNotNull($challenge);
        $this->assertSame(self::TEST_ADMIN_ID, $challenge->adminId);
        $this->assertSame(self::TEST_LOGIN_ID, $challenge->loginId);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->bootWithAdminSession(null);

        $ro = $this->resource->get('page://self/admin/two-factor-auth-edit');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPostConfiguresOwnDeviceFromAuthenticatedSession(): void
    {
        $this->bootWithAdminSession(self::TEST_ADMIN_ID);
        // Seed a DIFFERENT secret first, then prove the post changes it to
        // the server-generated value bound to the session identity.
        $this->twoFactorAuth->enable(self::TEST_LOGIN_ID, 'STALE-PREVIOUS-SECRET');

        // GET seeds the adminId-keyed setup challenge (server secret).
        $this->resource->get('page://self/admin/two-factor-auth-edit');

        $ro = $this->resource->post('page://self/admin/two-factor-auth-edit', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doSetTwoFactorAuth', $ro->body['transitionId']);
        $this->assertSame(self::TEST_LOGIN_ID, $ro->body['loginId']);
        // PROOF the write happened: the stored secret is now the server value.
        $this->assertSame(
            FakeTwoFactorAuth::FIXED_SECRET,
            $this->twoFactorAuth->secrets[self::TEST_LOGIN_ID],
        );
        // Challenge consumed.
        $this->assertArrayNotHasKey(HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY, $_SESSION);
    }

    public function testOnPostAnonymousReturns403(): void
    {
        $this->bootWithAdminSession(null);

        $ro = $this->resource->post('page://self/admin/two-factor-auth-edit', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostIgnoresClientSuppliedLoginIdAndAuthKey(): void
    {
        $this->bootWithAdminSession(self::TEST_ADMIN_ID);
        $this->resource->get('page://self/admin/two-factor-auth-edit');

        $ro = $this->resource->post('page://self/admin/two-factor-auth-edit', [
            'loginId' => 'shop-owner',
            'authKey' => 'ATTACKER-CONTROLLED-SECRET',
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        // Identity is the SESSION admin, not the client loginId.
        $this->assertSame(self::TEST_LOGIN_ID, $ro->body['loginId']);
        // No write for the attacker-supplied principal or secret.
        $this->assertArrayNotHasKey('shop-owner', $this->twoFactorAuth->secrets);
        $this->assertFalse(
            in_array('ATTACKER-CONTROLLED-SECRET', $this->twoFactorAuth->secrets, true),
        );
        $this->assertSame(
            FakeTwoFactorAuth::FIXED_SECRET,
            $this->twoFactorAuth->secrets[self::TEST_LOGIN_ID],
        );
    }

    public function testOnPostWithoutSetupChallengeReturns403(): void
    {
        $this->bootWithAdminSession(self::TEST_ADMIN_ID);
        // No GET first => no setup challenge seeded.
        $ro = $this->resource->post('page://self/admin/two-factor-auth-edit', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostRejectsStaleChallengeForAnotherPrincipal(): void
    {
        $this->bootWithAdminSession(self::TEST_ADMIN_ID);
        // A leftover pre-auth setup challenge for a DIFFERENT admin must
        // never be consumable by this post-auth session.
        $this->challenge->startSetup('ad-other-999', 'shop-owner', 'OTHER-SECRET');

        $ro = $this->resource->post('page://self/admin/two-factor-auth-edit', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertArrayNotHasKey('shop-owner', $this->twoFactorAuth->secrets);
    }

    public function testOnPostWrongCodeThrowsAndLeavesSecretUnchanged(): void
    {
        $this->bootWithAdminSession(self::TEST_ADMIN_ID);
        $this->twoFactorAuth->enable(self::TEST_LOGIN_ID, 'EXISTING-DEVICE-SECRET');
        $this->resource->get('page://self/admin/two-factor-auth-edit');

        try {
            $this->resource->post('page://self/admin/two-factor-auth-edit', [
                'deviceToken' => '000000',
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
            $this->fail('expected TwoFactorAuthFailedException');
        } catch (TwoFactorAuthFailedException) {
            // A wrong first code must not overwrite the existing secret.
            $this->assertTrue(array_key_exists(self::TEST_LOGIN_ID, $this->twoFactorAuth->secrets));
            $this->assertSame('EXISTING-DEVICE-SECRET', $this->twoFactorAuth->secrets[self::TEST_LOGIN_ID]);
        }
    }
}
