<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * AuthKey — the base32 TOTP secret round-tripped from the device-setup
 * screen (the QR code the admin scanned). No constraint is asserted at
 * the ontology boundary beyond it being a string; the secret is handed
 * straight to the 2FA boundary for registration.
 */
final class AuthKey
{
    #[Validate]
    public function validate(string $authKey): void
    {
    }
}
