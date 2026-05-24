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

final class ResetResourceTest extends TestCase
{
    private const ALICE_EMAIL = 'alice@example.com';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const NEW_PASSWORD = 'new-password-pilot15-2026';
    private const VALID_RESET_KEY = 'valid-reset-key-pilot15-aaaa1111';
    private const EXPIRED_RESET_KEY = 'expired-token-key-pilot15-aaaa1111';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testHappyPath(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => self::VALID_RESET_KEY,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        // No email, no other profile fields — minimize info leak in body.
        $this->assertArrayNotHasKey('email', $ro->body);

        // Password hash persistence is covered by the SQL suite. Fake
        // context is static Ray.FakeQuery fixtures and does not mutate
        // customer state.
    }

    public function testUnknownKeyReturns400(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => 'unknown-reset-key-not-in-storage-zzzz',
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // 400 (not 404) — does not distinguish format-invalid from
        // value-invalid at the HTTP level.
        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertStringContainsString('無効', $ro->body['message']);
    }

    public function testReusedKeyReturns400(): void
    {
        $this->markTestSkipped('Single-use token mutation is covered by the SQL suite.');
    }

    public function testExpiredKeyReturns400(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => self::EXPIRED_RESET_KEY,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertStringContainsString('無効', $ro->body['message']);
    }

    public function testInvalidPasswordFormatReturns400(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => self::VALID_RESET_KEY,
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testInvalidKeyFormatReturns400(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => 'short',
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => 'some-key-which-shape-passes-validation',
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
