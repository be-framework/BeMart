<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use MyVendor\BeMart\Be\Reason\Service\ResetKeyGenerator;
use MyVendor\BeMart\Be\Semantic\ResetKey;
use PHPUnit\Framework\TestCase;

use function mb_strlen;

/**
 * Unit coverage for {@see ResetKeyGenerator} — the dedicated reset-key
 * generator that replaced the misuse of CustomerIdGeneratorInterface in
 * {@see \MyVendor\BeMart\Be\Final\PasswordResetRequested}.
 */
final class ResetKeyGeneratorTest extends TestCase
{
    public function testGeneratesKeyAboveResetKeySemanticMinimum(): void
    {
        $key = (new ResetKeyGenerator())->generate();

        // ResetKey Semantic floor is 16 chars; bin2hex(random_bytes(16))
        // yields 32 — comfortably above the minimum.
        $this->assertGreaterThanOrEqual(16, mb_strlen($key));
    }

    public function testGeneratedKeyPassesResetKeySemanticValidation(): void
    {
        $key = (new ResetKeyGenerator())->generate();

        // No exception thrown == the value is a valid ResetKey.
        (new ResetKey())->validate($key);
        $this->addToAssertionCount(1);
    }

    public function testSuccessiveKeysAreUnique(): void
    {
        $generator = new ResetKeyGenerator();

        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $keys[$generator->generate()] = true;
        }

        // 128 bits of entropy — 100 draws collide with negligible
        // probability; any collision signals a non-random generator.
        $this->assertCount(100, $keys);
    }
}
