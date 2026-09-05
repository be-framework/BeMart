<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Provide\Error\ExceptionStatusMapper;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminLoginResourceTest extends TestCase
{
    private ResourceInterface $resource;
    private FakeTwoFactorAuth $twoFactorAuth;

    protected function setUp(): void
    {
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(CsrfToken::class)->to(FakeCsrfToken::class);
            }
        });

        $injector = new Injector(
            $base,
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->twoFactorAuth = $injector->getInstance(FakeTwoFactorAuth::class);
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

    public function testOnPostAuthenticatesAndRedirectsToTwoFactorAuth(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'local-dev-admin-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('ad000000000000000000000000000001', $ro->body['adminId']);
        $this->assertSame('test-admin', $ro->body['loginId']);
        $this->assertSame('テスト管理者', $ro->body['name']);
        $this->assertSame(0, $ro->body['authority']);
        $this->assertSame('/admin/two-factor-auth', $ro->headers['Location']);
        $this->assertArrayNotHasKey(HtmlAdminSessionAdapter::ADMIN_ID_KEY, $_SESSION);
        $this->assertSame('test-admin', $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY]['loginId'] ?? null);
    }

    public function testOnPostStartsSetupChallengeWhenDeviceIsMissing(): void
    {
        $this->twoFactorAuth->secrets = [];

        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'local-dev-admin-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/two-factor-auth-set', $ro->headers['Location']);
        $this->assertSame('test-admin', $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY]['loginId'] ?? null);
        $this->assertSame(FakeTwoFactorAuth::FIXED_SECRET, $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY]['authKey'] ?? null);
    }

    public function testOnPostWrongPasswordReturns401(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\AdminLoginFailedException::class);

        $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'not-the-right-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'local-dev-admin-password',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostShortPasswordReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    /**
     * Browser-form rejection carries the mapped status on the response;
     * JSON clients get the same 429 from
     * {@see \MyVendor\BeMart\Provide\Error\ExceptionStatusMapper}.
     */
    public function testOnPostRefusesFurtherAttemptsWith429(): void
    {
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES; $i++) {
            $this->assertSame(Code::UNAUTHORIZED, $this->submit('test-admin', 'not-the-right-password')->code);
        }

        // Correct password, and still refused.
        $ro = $this->submit('test-admin', 'local-dev-admin-password');

        $this->assertSame(429, $ro->code);
        $this->assertStringNotContainsString('test-admin', (string) $ro->body['message']);
        $this->assertArrayNotHasKey(HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY, $_SESSION);
    }

    public function testRejectionMessageDoesNotDistinguishUnknownLoginIdFromWrongPassword(): void
    {
        $unknown = $this->submit('no-such-admin', 'local-dev-admin-password');
        $wrongPassword = $this->submit('test-admin', 'not-the-right-password');

        $this->assertSame(Code::UNAUTHORIZED, $unknown->code);
        $this->assertSame(Code::UNAUTHORIZED, $wrongPassword->code);
        $this->assertSame($unknown->body['message'], $wrongPassword->body['message']);
        $this->assertSame($unknown->body['errors'], $wrongPassword->body['errors']);
    }

    /** JSON clients get the rejection as a throwable; the shared mapper decides its status. */
    public function testThrottleRejectionMapsTo429ForJsonClients(): void
    {
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES; $i++) {
            $this->submit('test-admin', 'not-the-right-password');
        }

        try {
            $this->resource->post('page://self/admin/login', [
                'loginId' => 'test-admin',
                'password' => 'local-dev-admin-password',
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
            $this->fail('Expected LoginAttemptsExceededException');
        } catch (LoginAttemptsExceededException $e) {
            $mapper = new ExceptionStatusMapper();
            $this->assertSame(429, $mapper->status($e));
            $this->assertStringNotContainsString('test-admin', $mapper->message($e, 429));
        }
    }

    /** Browser form submit (`mode`), so the mapped status lands on the response instead of an exception. */
    private function submit(string $loginId, string $password): ResourceObject
    {
        return $this->resource->post('page://self/admin/login', [
            'loginId' => $loginId,
            'password' => $password,
            'mode' => 'login',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
