<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
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
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(new TestModule(new Meta('MyVendor\\BeMart', 'test')), dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetRendersForm(): void
    {
        $ro = $this->resource->get('page://self/admin/two-factor-auth');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayHasKey('form', $ro->body);
    }

    public function testOnPostVerifiesToken(): void
    {
        $ro = $this->resource->post('page://self/admin/two-factor-auth', [
            'loginId' => 'test-admin',
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doVerifyTwoFactorAuth', $ro->body['transitionId']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostWrongTokenReturns400(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException::class);

        $this->resource->post('page://self/admin/two-factor-auth', [
            'loginId' => 'test-admin',
            'deviceToken' => '000000',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/two-factor-auth', [
            'loginId' => 'test-admin',
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
