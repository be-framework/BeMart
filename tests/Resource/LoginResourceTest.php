<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class LoginResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsFormMetadata(): void
    {
        $ro = $this->resource->get('page://self/login');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goLogin', $ro->body['transitionId']);
        $this->assertSame(['email', 'password', 'csrfToken'], $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/login', $ro->body['submitTo']['href']);
        // csrfToken carries the trusted reference the HTML form must
        // echo back so the doLogin POST passes CSRF validation.
        $this->assertSame(FakeCsrfToken::TOKEN, $ro->body['csrfToken']);
    }

    public function testOnPostAuthenticatesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'login-test-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('login-test@example.com', $ro->body['email']);
        $this->assertSame('10000000aaaa1111bbbb2222cccc3333', $ro->body['customerId']);
        $this->assertSame('鈴木', $ro->body['name01']);
        $this->assertSame(2, $ro->body['customerStatus']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostWrongPasswordReturns401(): void
    {
        $ro = $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'not-the-right-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('正しくありません', $ro->body['message']);
        // The body MUST NOT echo the email back — that would broadcast
        // existence information to attackers probing for valid emails.
        $this->assertArrayNotHasKey('email', $ro->body);
    }

    public function testOnPostUnknownEmailReturns401(): void
    {
        $ro = $this->resource->post('page://self/login', [
            'email' => 'nobody@example.com',
            'password' => 'login-test-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        // Same message as wrong-password case — no user enumeration.
        $this->assertStringContainsString('正しくありません', $ro->body['message']);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $ro = $this->resource->post('page://self/login', [
            'email' => 'not-an-email',
            'password' => 'login-test-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostShortPasswordReturns400(): void
    {
        $ro = $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'login-test-password-2026',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
