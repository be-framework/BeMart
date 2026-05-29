<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use Override;

use function array_key_exists;
use function bindec;
use function chr;
use function decbin;
use function hash_hmac;
use function ord;
use function pack;
use function random_int;
use function str_pad;
use function str_split;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function time;
use function unpack;

use const STR_PAD_LEFT;

/**
 * EC-CUBE-compatible TOTP (RFC 6238) implementation behind the
 * {@see TwoFactorAuthInterface} boundary.
 *
 * The RFC 6238 arithmetic (base32 secret → HMAC-SHA1 → 6-digit code with
 * a ±1 step window) mirrors `robthree/twofactorauth`, which EC-CUBE 4.3
 * uses. Secret persistence is the residual cutover concern: this default
 * keeps an in-process map (bound as a singleton) so the transition is
 * exercisable end to end; wiring it to `dtb_member.two_factor_auth_secret`
 * is the production-DB bring-up step (tracked in migration-status §4).
 */
final class EccubeTwoFactorAuth implements TwoFactorAuthInterface
{
    private const string BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const int PERIOD = 30;
    private const int DIGITS = 6;

    /** @var array<string, string> loginId => base32 secret */
    private array $secrets = [];

    #[Override]
    public function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    #[Override]
    public function enable(string $loginId, string $secret): void
    {
        $this->secrets[$loginId] = $secret;
    }

    #[Override]
    public function isEnabled(string $loginId): bool
    {
        return array_key_exists($loginId, $this->secrets);
    }

    #[Override]
    public function verify(string $loginId, string $token): bool
    {
        $secret = $this->secrets[$loginId] ?? null;
        if ($secret === null) {
            return false;
        }

        $timeSlice = (int) (time() / self::PERIOD);
        for ($offset = -1; $offset <= 1; $offset++) {
            if ($this->codeAt($secret, $timeSlice + $offset) === $token) {
                return true;
            }
        }

        return false;
    }

    private function codeAt(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $binary = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', substr($hash, $offset, 4));
        $value = $unpacked[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $bits = '';
        foreach (str_split($secret) as $char) {
            $index = strpos(self::BASE32_ALPHABET, $char);
            if ($index === false) {
                continue;
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr((int) bindec($chunk));
            }
        }

        return $bytes;
    }
}
