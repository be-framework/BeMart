<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class AdminLoginResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostAuthenticatesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'local-dev-admin-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('ad000000000000000000000000000001', $ro->body['adminId']);
        $this->assertSame('test-admin', $ro->body['loginId']);
        $this->assertSame('テスト管理者', $ro->body['name']);
        $this->assertSame(0, $ro->body['authority']);
        $this->assertArrayHasKey('Location', $ro->headers);
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
}
