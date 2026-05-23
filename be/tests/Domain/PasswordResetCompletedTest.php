<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use DateTimeImmutable;
use MyVendor\BeMart\Be\Exception\ResetKeyInvalidException;
use MyVendor\BeMart\Be\Final\PasswordResetCompleted;
use MyVendor\BeMart\Be\Input\RequestPasswordResetInput;
use MyVendor\BeMart\Be\Input\ResetPasswordInput;
use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeCustomerStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakePasswordResetTokenStorage;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function assert;
use function dirname;

final class PasswordResetCompletedTest extends TestCase
{
    private const ALICE_EMAIL = 'alice@example.com';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const NEW_PASSWORD = 'new-password-pilot15-2026';

    private BecomingInterface $becoming;
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
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
        $this->customerStorage = $injector->getInstance(FakeCustomerStorage::class);
        $this->tokenStorage = $injector->getInstance(FakePasswordResetTokenStorage::class);
        $this->hasher = $injector->getInstance(PasswordHasherInterface::class);
    }

    private function issueResetKey(): string
    {
        ($this->becoming)(new RequestPasswordResetInput(email: self::ALICE_EMAIL));
        $sent = $this->mailer->passwordResets();
        $this->assertCount(1, $sent);

        return $sent[0]['resetKey'];
    }

    public function testHappyPathUpdatesHash(): void
    {
        $resetKey = $this->issueResetKey();

        $before = $this->customerStorage->getByEmail(self::ALICE_EMAIL);
        assert($before !== null);
        $oldHash = $before->passwordHash;

        $final = ($this->becoming)(new ResetPasswordInput(
            resetKey: $resetKey,
            password: self::NEW_PASSWORD,
        ));

        $this->assertInstanceOf(PasswordResetCompleted::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);

        $after = $this->customerStorage->getByEmail(self::ALICE_EMAIL);
        assert($after !== null);
        $this->assertNotSame($oldHash, $after->passwordHash);
        $this->assertTrue($this->hasher->verify(self::NEW_PASSWORD, $after->passwordHash));
    }

    public function testTokenConsumedAfterReset(): void
    {
        $resetKey = $this->issueResetKey();

        ($this->becoming)(new ResetPasswordInput(
            resetKey: $resetKey,
            password: self::NEW_PASSWORD,
        ));

        // The token has been deleted; a re-attempt observes the same
        // "no such token" miss as a wholly unknown key.
        $this->assertNull($this->tokenStorage->getByResetKey($resetKey));

        $this->expectException(ResetKeyInvalidException::class);
        ($this->becoming)(new ResetPasswordInput(
            resetKey: $resetKey,
            password: self::NEW_PASSWORD,
        ));
    }

    public function testExpiredTokenRejected(): void
    {
        // Seed a token with expiresAt in the past directly via the storage.
        $resetKey = 'expired-token-key-pilot15-1111aaaa';
        $this->tokenStorage->put(new PasswordResetTokenEntity(
            customerId: self::ALICE_ID,
            resetKey: $resetKey,
            expiresAt: new DateTimeImmutable('-1 second'),
        ));

        $this->expectException(ResetKeyInvalidException::class);
        ($this->becoming)(new ResetPasswordInput(
            resetKey: $resetKey,
            password: self::NEW_PASSWORD,
        ));
    }

    public function testUnknownKeyRejected(): void
    {
        $this->expectException(ResetKeyInvalidException::class);
        ($this->becoming)(new ResetPasswordInput(
            resetKey: 'unknown-reset-key-not-in-storage-zzzz',
            password: self::NEW_PASSWORD,
        ));
    }
}
