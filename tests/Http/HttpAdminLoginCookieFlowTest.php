<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Query\TwoFactorAuthStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Injector;
use PHPUnit\Framework\TestCase;

use function assert;
use function is_string;

final class HttpAdminLoginCookieFlowTest extends TestCase
{
    private const HOST = '127.0.0.1:8101';
    private const ADMIN_LOGIN_ID = 'test-admin';
    private const ADMIN_PASSWORD = 'admin-test-password-2026';

    public function testTwoFactorCompletionElevatesCookieSessionForAdminPages(): void
    {
        $resource = new HttpResource(
            self::HOST,
            __DIR__ . '/prod-json-index.php',
            __DIR__ . '/log/admin-login-cookie-flow.log',
        );

        $loginForm = $resource->get('page://self/admin/login');
        $this->assertSame(Code::OK, $loginForm->code);

        $login = $resource->post('page://self/admin/login', [
            'loginId' => self::ADMIN_LOGIN_ID,
            'password' => self::ADMIN_PASSWORD,
            'csrfToken' => $this->bodyString($loginForm, 'csrfToken'),
        ]);

        $this->assertSame(Code::OK, $login->code, $login->toString());
        $next = $this->headerString($login, 'Location');
        $this->assertContains($next, ['/admin/two-factor-auth-set', '/admin/two-factor-auth']);

        $verified = $next === '/admin/two-factor-auth-set'
            ? $this->completeInitialTwoFactorSetup($resource, $next)
            : $this->completeTwoFactorChallenge($resource, $next);

        $this->assertSame(Code::OK, $verified->code, $verified->toString());
        $adminHome = $resource->get($this->headerString($verified, 'Location'));

        $this->assertSame(Code::OK, $adminHome->code, $adminHome->toString());
        $this->assertSame('/admin/index', $adminHome->body['_links']['self']['href'] ?? null);
    }

    private function completeInitialTwoFactorSetup(HttpResource $resource, string $location): HttpResponse
    {
        $setupForm = $resource->get($location);
        $this->assertSame(Code::OK, $setupForm->code, $setupForm->toString());
        $authKey = $this->bodyString($setupForm, 'authKey');

        return $resource->put($location, [
            'deviceToken' => $this->deviceToken($authKey),
            'csrfToken' => $this->bodyString($setupForm, 'csrfToken'),
        ]);
    }

    private function completeTwoFactorChallenge(HttpResource $resource, string $location): HttpResponse
    {
        $challengeForm = $resource->get($location);
        $this->assertSame(Code::OK, $challengeForm->code, $challengeForm->toString());

        return $resource->post($location, [
            'deviceToken' => $this->deviceToken($this->storedSecret()),
            'csrfToken' => $this->bodyString($challengeForm, 'csrfToken'),
        ]);
    }

    private function deviceToken(string $secret): string
    {
        $twoFactorAuth = Injector::getInstance('prod-eccube-sql-hal-app')
            ->getInstance(TwoFactorAuthInterface::class);
        assert($twoFactorAuth instanceof TwoFactorAuthInterface);

        return $twoFactorAuth->generateDeviceToken($secret);
    }

    private function storedSecret(): string
    {
        $storage = Injector::getInstance('prod-eccube-sql-hal-app')
            ->getInstance(TwoFactorAuthStorageInterface::class);
        assert($storage instanceof TwoFactorAuthStorageInterface);
        $secret = $storage->secret(self::ADMIN_LOGIN_ID)->secret;

        $this->assertIsString($secret);
        $this->assertNotSame('', $secret);

        return $secret;
    }

    private function bodyString(HttpResponse $response, string $key): string
    {
        $value = $response->body[$key] ?? null;
        $this->assertIsString($value, $response->toString());
        $this->assertNotSame('', $value, $response->toString());

        return $value;
    }

    private function headerString(HttpResponse $response, string $key): string
    {
        $value = $response->headers[$key] ?? null;
        $this->assertTrue(is_string($value) && $value !== '', $response->toString());

        return $value;
    }
}
