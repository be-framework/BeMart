<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerStorage;
use MyVendor\BeMart\Be\Reason\Query\FakePasswordResetTokenStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function assert;
use function dirname;

final class ResetResourceTest extends TestCase
{
    private const ALICE_EMAIL = 'alice@example.com';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const NEW_PASSWORD = 'new-password-pilot15-2026';

    private ResourceInterface $resource;
    private FakeMailer $mailer;
    private FakeCustomerStorage $customerStorage;
    private FakePasswordResetTokenStorage $tokenStorage;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
        $this->customerStorage = $injector->getInstance(FakeCustomerStorage::class);
        $this->tokenStorage = $injector->getInstance(FakePasswordResetTokenStorage::class);
        $this->hasher = $injector->getInstance(PasswordHasherInterface::class);
    }

    private function issueResetKey(): string
    {
        $ro = $this->resource->post('page://self/forgot-password', [
            'email' => self::ALICE_EMAIL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $sent = $this->mailer->passwordResets();
        $this->assertCount(1, $sent);

        return $sent[0]['resetKey'];
    }

    public function testHappyPath(): void
    {
        $resetKey = $this->issueResetKey();

        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        // No email, no other profile fields — minimize info leak in body.
        $this->assertArrayNotHasKey('email', $ro->body);

        // The new password verifies against the stored hash.
        $persisted = $this->customerStorage->getByEmail(self::ALICE_EMAIL);
        assert($persisted !== null);
        $this->assertTrue($this->hasher->verify(self::NEW_PASSWORD, $persisted->passwordHash));
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
        $resetKey = $this->issueResetKey();

        $first = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $first->code);

        // Single-use: the token was consumed by the first reset.
        $second = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::BAD_REQUEST, $second->code);
        $this->assertStringContainsString('無効', $second->body['message']);
    }

    public function testExpiredKeyReturns400(): void
    {
        // Seed a token with expiresAt in the past directly via storage,
        // bypassing the issuer (which always sets +1h).
        $resetKey = 'expired-token-key-pilot15-aaaa1111';
        $this->tokenStorage->put(new PasswordResetTokenEntity(
            customerId: self::ALICE_ID,
            resetKey: $resetKey,
            expiresAt: new DateTimeImmutable('-1 second'),
        ));

        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertStringContainsString('無効', $ro->body['message']);
    }

    public function testInvalidPasswordFormatReturns400(): void
    {
        $resetKey = $this->issueResetKey();

        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
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
