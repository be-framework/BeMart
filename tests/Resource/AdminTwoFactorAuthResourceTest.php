<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource coverage for the admin 2FA challenge page
 * (doVerifyTwoFactorAuth). Login-context: anonymous-accessible.
 */
final class AdminTwoFactorAuthResourceTest extends TestCase
{
    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const LOGIN_ID = 'test-admin';

    private ResourceInterface $resource;
    private HtmlAdminLoginChallengeAdapter $challenge;

    protected function setUp(): void
    {
        $injector = new Injector(new TestModule(new Meta('MyVendor\\BeMart', 'test')), dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
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

    public function testOnGetRendersForm(): void
    {
        $ro = $this->resource->get('page://self/admin/two-factor-auth');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayHasKey('form', $ro->body);
    }

    public function testOnPostFailsWithoutPendingVerificationChallenge(): void
    {
        $ro = $this->resource->post('page://self/admin/two-factor-auth', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostDoesNotSynthesizeChallengeFromExistingAdminSession(): void
    {
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = self::ADMIN_ID;

        $ro = $this->resource->post('page://self/admin/two-factor-auth', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertArrayNotHasKey(HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY, $_SESSION);
    }

    public function testOnPostVerifiesTokenFromPendingChallenge(): void
    {
        $this->challenge->startVerification(self::ADMIN_ID, self::LOGIN_ID);

        $ro = $this->resource->post('page://self/admin/two-factor-auth', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('doVerifyTwoFactorAuth', $ro->body['transitionId']);
        $this->assertSame(self::LOGIN_ID, $ro->body['loginId']);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertSame(self::ADMIN_ID, $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] ?? null);
        $this->assertArrayNotHasKey(HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY, $_SESSION);
    }

    public function testOnPostIgnoresClientLoginId(): void
    {
        $this->challenge->startVerification(self::ADMIN_ID, self::LOGIN_ID);

        $ro = $this->resource->post('page://self/admin/two-factor-auth', [
            'loginId' => 'unknown-user',
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame(self::LOGIN_ID, $ro->body['loginId']);
    }

    public function testOnPostWrongTokenReturns400(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException::class);
        $this->challenge->startVerification(self::ADMIN_ID, self::LOGIN_ID);

        $this->resource->post('page://self/admin/two-factor-auth', [
            'deviceToken' => '000000',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    /**
     * A wrong code leaves the challenge in place so the admin can retype
     * it — but only up to the throttle. On refusal the pending challenge
     * is dropped, so the browser cannot keep POSTing codes at it.
     */
    public function testOnPostRefusesFurtherCodesWith429AndDropsTheChallenge(): void
    {
        $this->challenge->startVerification(self::ADMIN_ID, self::LOGIN_ID);

        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES; $i++) {
            try {
                $this->post('000000');
                $this->fail('expected TwoFactorAuthFailedException');
            } catch (TwoFactorAuthFailedException) {
            }
        }

        // Right code this time, and still refused.
        $ro = $this->post(FakeTwoFactorAuth::VALID_TOKEN);

        $this->assertSame(429, $ro->code);
        $this->assertNotSame('', $ro->body['message']);
        $this->assertArrayNotHasKey(HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY, $_SESSION);
        $this->assertArrayNotHasKey(HtmlAdminSessionAdapter::ADMIN_ID_KEY, $_SESSION);

        // Challenge gone: the next submission has nothing to answer.
        $this->assertSame(Code::FORBIDDEN, $this->post(FakeTwoFactorAuth::VALID_TOKEN)->code);
    }

    private function post(string $deviceToken): ResourceObject
    {
        return $this->resource->post('page://self/admin/two-factor-auth', [
            'deviceToken' => $deviceToken,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
