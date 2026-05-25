<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AdminLoginFailedException;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * Admin authenticated — Final, proof the admin credentials check passed.
 *
 *   AdminLoginInput → AdminAuthenticated  (this stage — verification)
 *
 * Two failure modes both raise AdminLoginFailedException (no
 * enumeration):
 *   1. no admin with that loginId
 *   2. password does not verify
 *
 * Existence of this object proves: loginId is registered AND password
 * matches stored hash. The public surface exposes the adminId and the
 * admin profile fields the BEAR resource needs to populate the session
 * and the response body. The plaintext password is consumed inside the
 * constructor (#[SensitiveParameter]) and is intentionally NOT promoted
 * to a public property — mirrors Pilot 6 customer authentication.
 *
 * Distinct from customer-side {@see CustomerAuthenticated}: admins are
 * a different AAA principal class (admin firewall vs customer
 * firewall, per EC-CUBE / Symfony Security convention). The two Final
 * types are not interchangeable even though the shapes are similar.
 */
final readonly class AdminAuthenticated
{
    public string $adminId;
    public string $loginId;
    public string $name;
    public int $authority;

    public function __construct(
        #[Input] string $loginId,
        #[Input] #[SensitiveParameter] string $password,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] PasswordHasherInterface $passwordHasher,
    ) {
        $admin = $adminQuery->findByLoginId($loginId);
        if ($admin === null) {
            throw new AdminLoginFailedException();
        }

        if (! $passwordHasher->verify($password, $admin->passwordHash)) {
            throw new AdminLoginFailedException();
        }

        $this->adminId = $admin->adminId;
        $this->loginId = $admin->loginId;
        $this->name = $admin->name;
        $this->authority = $admin->authority;
    }
}
