<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

/**
 * Session-backed admin login challenge state for the pre-auth 2FA ladder.
 *
 * See {@see HtmlAdminLoginChallengeAdapter} for the production implementation and the security
 * invariants (session-id rotation on elevation, challenge retirement on abandon/logout). Extracted
 * so `Page\Admin\Login` / `TwoFactorAuth` / `TwoFactorAuthSet` depend on a port rather than a
 * concrete, final adapter - a test whose subject is not the challenge machinery itself can bind a
 * fake instead of exercising the real one. See #93.
 */
interface AdminLoginChallengeInterface
{
    public function startVerification(string $adminId, string $loginId): void;

    public function startSetup(string $adminId, string $loginId, string $authKey): void;

    public function verificationChallenge(): AdminTwoFactorChallenge|null;

    public function setupChallenge(): AdminTwoFactorChallenge|null;

    /**
     * Drop a pending challenge without elevating the session.
     *
     * Used when the challenge is refused rather than answered (too many
     * codes tried): the pre-auth identity must not survive, or the next
     * request would resume the same challenge.
     */
    public function abandonVerification(): void;

    public function completeVerification(AdminTwoFactorChallenge $challenge): void;

    public function completeSetup(AdminTwoFactorChallenge $challenge): void;

    public function regenerateActiveSessionId(): void;
}
