<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use MyVendor\BeMart\Be\Reason\Provider\ResetKeyProvider;
use MyVendor\BeMart\Be\Semantic\ResetKey;
use PHPUnit\Framework\TestCase;

use function mb_strlen;

/**
 * Unit coverage for {@see ResetKeyProvider} — the dedicated reset-key
 * provider that replaced the misuse of CustomerIdProvider in
 * {@see \MyVendor\BeMart\Be\Final\PasswordResetRequested}.
 */
final class ResetKeyProviderTest extends TestCase
{
    public function testGeneratesKeyAboveResetKeySemanticMinimum(): void
    {
        $key = (new ResetKeyProvider())->get();

        // ResetKey Semantic floor is 16 chars; bin2hex(random_bytes(16))
        // yields 32 — comfortably above the minimum.
        $this->assertGreaterThanOrEqual(16, mb_strlen($key));
    }

    public function testGeneratedKeyPassesResetKeySemanticValidation(): void
    {
        $key = (new ResetKeyProvider())->get();

        // No exception thrown == the value is a valid ResetKey.
        (new ResetKey())->validate($key);
        $this->addToAssertionCount(1);
    }

    public function testSuccessiveKeysAreUnique(): void
    {
        $provider = new ResetKeyProvider();

        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $keys[$provider->get()] = true;
        }

        // 128 bits of entropy — 100 draws collide with negligible
        // probability; any collision signals a non-random provider.
        $this->assertCount(100, $keys);
    }
}
