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

final class ActivateResourceTest extends TestCase
{
    private const PROVISIONAL_KEY = 'pending-secret-key-pilot7-2026abcd';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostActivatesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/entry/activate', [
            'secretKey' => self::PROVISIONAL_KEY,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('20000000dddd2222eeee3333ffff4444', $ro->body['customerId']);
        $this->assertSame('provisional@example.com', $ro->body['email']);
        $this->assertSame(2, $ro->body['customerStatus']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostUnknownKeyReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\SecretKeyNotFoundException::class);

        $this->resource->post('page://self/entry/activate', [
            'secretKey' => 'unknown-key-not-in-fixture-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostInvalidKeyFormatReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/entry/activate', [
            'secretKey' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/entry/activate', [
            'secretKey' => self::PROVISIONAL_KEY,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
