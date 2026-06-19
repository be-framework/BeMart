<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Exception\MissingCsrfTokenException;
use Ray\Csrf\Http\CompositeRequestToken;
use Ray\Csrf\Http\RequestTokenInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class LoginResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class);
                $this->bind(RequestTokenInterface::class)->to(CompositeRequestToken::class);
            }
        });

        $injector = new Injector(
            $base,
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
            'password' => 'local-dev-member-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('login-test@example.com', $ro->body['email']);
        $this->assertSame('10000000aaaa1111bbbb2222cccc3333', $ro->body['customerId']);
        $this->assertSame('鈴木', $ro->body['name01']);
        $this->assertSame(2, $ro->body['customerStatus']);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertSame(FakeCsrfToken::TOKEN, $ro->uri->query['csrfToken']);
    }

    public function testOnPostWrongPasswordReturns401(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\LoginFailedException::class);

        $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'not-the-right-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostUnknownEmailReturns401(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\LoginFailedException::class);

        $this->resource->post('page://self/login', [
            'email' => 'nobody@example.com',
            'password' => 'local-dev-member-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/login', [
            'email' => 'not-an-email',
            'password' => 'local-dev-member-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostShortPasswordReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $this->expectException(MissingCsrfTokenException::class);
        $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'local-dev-member-password',
        ]);
    }
}
