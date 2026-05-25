<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Exception\ResetKeyInvalidException;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\PasswordResetTokenStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * Password reset completed — Final, proof the token was valid and the
 * new password hash was persisted.
 *
 *   ResetPasswordInput → PasswordResetCompleted  (this stage)
 *
 * Three failure modes ALL raise ResetKeyInvalidException (no
 * enumeration signal — same merged-failure-mode design as Pilot 7's
 * SecretKeyNotFoundException):
 *   1. resetKey does not map to any token (wrong / never-issued)
 *   2. token's expiresAt is in the past (expired)
 *   3. token was already consumed by a prior reset (single-use violation)
 *
 * Case (3) is structurally identical to (1) after consumption: the
 * Final's last act on success is to `delete()` the token, so a
 * re-attempt observes the same "no token for this key" miss.
 *
 * Public surface is intentionally minimal: only `customerId`. The
 * email is not echoed (the resource layer should not leak it back as
 * a confirmation field that could be probed), and the plaintext
 * password is consumed inside the constructor via
 * `#[SensitiveParameter]` so it never reaches a public property.
 *
 * ALPS doc: "リセットキーを検証して新しいパスワードを保存する。
 * キーは1回のみ使用可。"
 */
final readonly class PasswordResetCompleted
{
    public string $customerId;

    public function __construct(
        #[Input] string $resetKey,
        #[Input] #[SensitiveParameter] string $password,
        #[Inject] PasswordResetTokenStorageInterface $tokenStorage,
        #[Inject] CustomerCommandInterface $customerCommand,
        #[Inject] PasswordHasherInterface $passwordHasher,
    ) {
        $token = $tokenStorage->byResetKey($resetKey);
        if ($token === null) {
            throw new ResetKeyInvalidException();
        }

        if ($token->expiresAt < new DateTimeImmutable('now')) {
            throw new ResetKeyInvalidException();
        }

        $hash = $passwordHasher->hash($password);
        $customerCommand->password($token->customerId, $hash);

        // Single-use: consume the token immediately. A subsequent attempt
        // with the same resetKey will miss on getByResetKey() above and
        // raise the same ResetKeyInvalidException — the caller cannot
        // distinguish "wrong key" from "already used".
        $tokenStorage->delete($resetKey);

        $this->customerId = $token->customerId;
    }
}
