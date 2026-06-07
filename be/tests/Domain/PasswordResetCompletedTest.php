<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ResetKeyInvalidException;
use MyVendor\BeMart\Be\Final\PasswordResetCompleted;
use MyVendor\BeMart\Be\Input\ResetPasswordInput;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class PasswordResetCompletedTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const VALID_RESET_KEY = 'valid-reset-key-pilot15-aaaa1111';
    private const EXPIRED_RESET_KEY = 'expired-token-key-pilot15-aaaa1111';
    private const NEW_PASSWORD = 'new-password-pilot15-2026';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsCompletedState(): void
    {
        $final = ($this->becoming)(new ResetPasswordInput(
            resetKey: self::VALID_RESET_KEY,
            password: self::NEW_PASSWORD,
        ));

        $this->assertInstanceOf(PasswordResetCompleted::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        // FakeQuery fixtures are static; password hash persistence is covered by the SQL suite.
    }

    #[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
    public function testTokenConsumedAfterReset(): void
    {
        $this->markTestSkipped('Token consumption needs mutable persistence; covered by the SQL suite.');
    }

    public function testExpiredTokenRejected(): void
    {
        $this->expectException(ResetKeyInvalidException::class);
        ($this->becoming)(new ResetPasswordInput(
            resetKey: self::EXPIRED_RESET_KEY,
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
