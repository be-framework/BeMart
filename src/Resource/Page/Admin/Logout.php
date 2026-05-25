<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Final\AdminLoggedOut;
use MyVendor\BeMart\Be\Input\AdminLogoutInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doAdminLogout — 管理者ログアウト (Wave 4, Direct, idempotent).
 *
 * Resource is the HTTP entry point: builds AdminLogoutInput, hands it
 * to Becoming, and on success returns whether there was an admin to
 * log out along with their adminId. The Be layer pattern is Direct
 * (Input → Final) — see AdminLogoutInput.
 *
 * Failure mapping (intentionally narrow):
 *   - missing/invalid CSRF token  → 403 (Slice 8 uniform CSRF guard)
 *   - SemanticVariableException   → 400 (defensive; AdminLogoutInput has
 *                                          no semantically-validated
 *                                          fields, so this is unreachable
 *                                          today but kept uniform with
 *                                          the rest of Slice 8/9)
 *
 * Notably absent: 401/403 for "no admin session". Per ALPS
 * `type=idempotent`, logging out an admin-anonymous client is a no-op
 * success — the response body simply carries `wasLoggedIn=false`.
 *
 * Session-clear deliberately out of scope: the Slice 7.2 contract
 * places HTTP session teardown on the EC-CUBE EventListener, which
 * observes this response and clears the admin session keys. Mirrors
 * Pilot doLogin / doLogout for the customer firewall.
 *
 * Source-of-truth gap: alps.json does not currently carry a
 * `doAdminLogout` transition id; using the conventional name to
 * parallel `doLogout` for the customer side.
 */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * Wave 4 / Phase B Slice 9: the CSRF token is user-controlled
     * input — same taint discipline as the customer logout.
     *
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goAdminLogin', href: 'page://self/admin/login')]
    public function onPost(string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminLogoutInput());
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        }

        assert($final instanceof AdminLoggedOut);

        $this->code = Code::OK;
        $this->body = [
            'wasLoggedIn' => $final->wasLoggedIn,
            'adminId' => $final->adminId,
            'message' => 'ログアウトしました。',
        ];

        return $this;
    }
}
