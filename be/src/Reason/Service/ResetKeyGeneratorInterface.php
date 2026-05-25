<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Issues a password-reset key — the opaque one-time token EC-CUBE emails
 * to a customer who requested a reset ({@see \MyVendor\BeMart\Be\Final\PasswordResetRequested}).
 *
 * Distinct from the *-IdGenerator services (e.g. {@see CustomerIdGeneratorInterface}):
 * an id generator mints a storage primary key, whereas a reset key must be
 * unguessable and validate against the {@see \MyVendor\BeMart\Be\Semantic\ResetKey}
 * Semantic. That Semantic enforces a 16-character minimum, so any
 * implementation MUST emit a value comfortably above that floor.
 */
interface ResetKeyGeneratorInterface
{
    /**
     * @return non-empty-string an unguessable reset key, length comfortably
     *                          above the ResetKey Semantic's 16-char minimum
     */
    public function generate(): string;
}
