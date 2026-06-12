<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource coverage for the admin 2FA device-setup page
 * (doSetTwoFactorAuth). Login-context: anonymous-accessible.
 */
final class AdminTwoFactorAuthSetResourceTest extends TestCase
{
    private const ADMIN_ID = 'ad-setup-001';
    private const LOGIN_ID = 'fresh-admin';
    private const SERVER_SECRET = 'SERVER-GENERATED-SECRET';

    private ResourceInterface $resource;
    private FakeTwoFactorAuth $twoFactorAuth;
    private HtmlAdminLoginChallengeAdapter $challenge;

    protected function setUp(): void
    {
        $injector = new Injector(new TestModule(new Meta('MyVendor\\BeMart', 'test')), dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->twoFactorAuth = $injector->getInstance(FakeTwoFactorAuth::class);
        $this->challenge = $injector->getInstance(HtmlAdminLoginChallengeAdapter::class);
        unset(
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
        );
    }

    public function testOnGetRendersFormWithEmptyAuthKeyPlaceholder(): void
    {
        // authKey stays empty to match the EC-CUBE render baseline (the
        // QR `secret=` is blank) until a password-verified setup challenge
        // carries the server-generated secret.
        $ro = $this->resource->get('page://self/admin/two-factor-auth-set');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayHasKey('authKey', $ro->body);
        $this->assertSame('', $ro->body['authKey']);
    }

    public function testOnGetRendersServerGeneratedAuthKeyFromPendingSetup(): void
    {
        $this->challenge->startSetup(self::ADMIN_ID, self::LOGIN_ID, self::SERVER_SECRET);

        $ro = $this->resource->get('page://self/admin/two-factor-auth-set');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::SERVER_SECRET, $ro->body['authKey']);
    }

    public function testOnPutFailsWithoutPendingSetupChallenge(): void
    {
        $ro = $this->resource->put('page://self/admin/two-factor-auth-set', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPutConfiguresDeviceFromPendingSetupChallenge(): void
    {
        $this->challenge->startSetup(self::ADMIN_ID, self::LOGIN_ID, self::SERVER_SECRET);

        $ro = $this->resource->put('page://self/admin/two-factor-auth-set', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('doSetTwoFactorAuth', $ro->body['transitionId']);
        $this->assertSame(self::LOGIN_ID, $ro->body['loginId']);
        $this->assertSame(self::SERVER_SECRET, $this->twoFactorAuth->secrets[self::LOGIN_ID]);
        $this->assertSame(self::ADMIN_ID, $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] ?? null);
        $this->assertArrayNotHasKey(HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY, $_SESSION);
    }

    public function testOnPutIgnoresClientLoginIdAndAuthKey(): void
    {
        $this->challenge->startSetup(self::ADMIN_ID, self::LOGIN_ID, self::SERVER_SECRET);

        $ro = $this->resource->put('page://self/admin/two-factor-auth-set', [
            'loginId' => 'test-admin',
            'authKey' => 'ATTACKER-CONTROLLED-SECRET',
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame(self::LOGIN_ID, $ro->body['loginId']);
        $this->assertSame(self::SERVER_SECRET, $this->twoFactorAuth->secrets[self::LOGIN_ID]);
        $this->assertSame(FakeTwoFactorAuth::FIXED_SECRET, $this->twoFactorAuth->secrets['test-admin']);
    }

    public function testOnPutWrongCodeReturns400(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException::class);
        $this->challenge->startSetup(self::ADMIN_ID, self::LOGIN_ID, self::SERVER_SECRET);

        $this->resource->put('page://self/admin/two-factor-auth-set', [
            'deviceToken' => '000000',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

}
