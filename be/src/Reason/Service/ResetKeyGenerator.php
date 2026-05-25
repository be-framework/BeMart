<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function bin2hex;
use function random_bytes;

/**
 * Cryptographically unpredictable password-reset key generator.
 *
 * Emits `bin2hex(random_bytes(16))` — a 32-character lowercase-hex string
 * drawn from a CSPRNG. 32 chars sits well above the 16-char minimum the
 * {@see \MyVendor\BeMart\Be\Semantic\ResetKey} Semantic enforces, and the
 * 128 bits of entropy make the key infeasible to guess or enumerate.
 *
 * Unlike the *-IdGenerator services there is no Fake counterpart: the
 * full reset→consume flow tests read the issued key back from storage
 * (FakeMailer's captured mail, or dtb_customer.reset_key under SQL), so
 * no test needs a predictable value. This single implementation is bound
 * everywhere — test, dev and production alike.
 */
final class ResetKeyGenerator implements ResetKeyGeneratorInterface
{
    /** @return non-empty-string */
    #[Override]
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
