<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminAuthenticated;
use SensitiveParameter;

/**
 * Input for doAdminLogin — back-office admin authentication.
 *
 * Direct pattern (hello-world demo): Input → Final, no intermediate
 * Being. The Final's constructor consults AdminQuery + PasswordHasher
 * and either succeeds (existence proof) or raises
 * AdminLoginFailedException.
 *
 *   AdminLoginInput → AdminAuthenticated (Final — credentials verified)
 *
 * Mirrors Pilot 6 customer {@see LoginInput} in shape, but the
 * authentication key is `loginId` (a username) instead of `email`
 * because EC-CUBE admins use a username for sign-in. This is the
 * admin firewall — distinct from the customer firewall.
 *
 * Semantic validation: `loginId` is validated by Be\Semantic\LoginId
 * (1-128 chars), `password` by Be\Semantic\Password (8-255 chars) at
 * Becoming time. The Semantic enforces only static shape; credential
 * correctness is the Final's job.
 *
 * Note: like customer login, this is intentionally lookup-only — the
 * Be Final returns the proof, but writing adminId into the HTTP
 * session is the EC-CUBE EventListener's job (Slice 7.2 contract,
 * generalized for the admin firewall in a follow-up wave).
 *
 * Source-of-truth gap: the ALPS profile does not currently carry a
 * `doAdminLogin` transition id (only customer `doLogin`); admin auth
 * lives only as descriptors (`loginId`, `password`, `authority`, etc.).
 * Implementation here uses the conventional name `doAdminLogin` to
 * parallel the customer naming, and the ALPS profile is expected to
 * gain a matching transition in a later sweep.
 *
 * @link https://schema.org/LoginAction
 */
#[Be(AdminAuthenticated::class)]
final readonly class AdminLoginInput
{
    /**
     * Wave 4: both fields come from the HTTP admin-login form and are
     * marked as input sources for the boundary contract — same
     * discipline as the customer login input.
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $password
     */
    public function __construct(
        public string $loginId,
        #[SensitiveParameter] public string $password,
    ) {
    }
}
