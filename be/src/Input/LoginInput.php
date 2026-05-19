<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerAuthenticated;
use SensitiveParameter;

/**
 * Input for doLogin — front-end customer authentication.
 *
 * Direct pattern (hello-world demo): Input → Final, no intermediate
 * Being. The Final's constructor consults CustomerQuery + PasswordHasher
 * and either succeeds (existence proof) or raises LoginFailedException.
 *
 *   LoginInput → CustomerAuthenticated (Final — credentials verified)
 *
 * Semantic validation: `email` and `password` are format-validated by
 * Be\Semantic\Email and Be\Semantic\Password respectively at Becoming
 * time. The Semantic enforces only static shape (RFC-ish email,
 * password length range); credential correctness is the Final's job.
 *
 * Note: Be Framework's Auth layer is intentionally lookup-only — the
 * Be Final returns the proof, but actually writing the customerId
 * into the HTTP session is the BEAR resource layer's responsibility
 * (Resource\Page\Login). This keeps the Be Framework session
 * abstraction read-only as established in Slice 6.
 *
 * @link https://schema.org/LoginAction
 */
#[Be(CustomerAuthenticated::class)]
final readonly class LoginInput
{
    /**
     * Phase B Slice 9: both fields come from the HTTP login form and
     * are marked as input sources for the boundary contract.
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $password
     */
    public function __construct(
        public string $email,
        #[SensitiveParameter] public string $password,
    ) {
    }
}
