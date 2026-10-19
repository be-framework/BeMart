<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class LoginResourceTest extends TestCase
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

    public function testOnGetReturnsFormMetadata(): void
    {
        $ro = $this->resource->get('page://self/login');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goLogin', $ro->body['transitionId']);
        $this->assertSame(['email', 'password'], $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/login', $ro->body['submitTo']['href']);
    }

    public function testOnPostAuthenticatesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'local-dev-member-password',
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
        $this->expectException(\MyVendor\BeMart\Be\Exception\LoginFailedException::class);

        $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'not-the-right-password',
        ]);
    }

    public function testOnPostUnknownEmailReturns401(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\LoginFailedException::class);

        $this->resource->post('page://self/login', [
            'email' => 'nobody@example.com',
            'password' => 'local-dev-member-password',
        ]);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/login', [
            'email' => 'not-an-email',
            'password' => 'local-dev-member-password',
        ]);
    }

    public function testOnPostShortPasswordReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'short',
        ]);
    }
}
