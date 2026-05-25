<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Final\AdminLoggedOut;
use MyVendor\BeMart\Be\Input\AdminLogoutInput;

use function assert;
use function getenv;
use function session_status;
use function str_contains;

use const PHP_SESSION_ACTIVE;

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
 * In the html context this resource clears the flat admin session key
 * read by HtmlAdminSessionAdapter. The clear is guarded by
 * an html APP_CONTEXT and PHP_SESSION_ACTIVE so app/test/prod contexts keep
 * their existing session behaviour.
 *
 * Source-of-truth gap: alps.json does not currently carry a
 * `doAdminLogout` transition id; using the conventional name to
 * parallel `doLogout` for the customer side.
 */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Wave 4 / Phase B Slice 9: the CSRF token is user-controlled
     * input — same taint discipline as the customer logout.
     *
     */
    #[Link(rel: 'goAdminLogin', href: 'page://self/admin/login')]
    #[CsrfProtected]
    public function onPost(): static
    {
        try {
            $final = ($this->becoming)(new AdminLogoutInput());
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        }

        assert($final instanceof AdminLoggedOut);

        if (str_contains((string) getenv('APP_CONTEXT'), 'html') && session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY]);
        }

        // Post/Redirect/Get: EC-CUBE's doAdminLogout redirects back to the
        // admin login page (the `goAdminLogin` transition declared above).
        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/login';
        $this->body = [
            'wasLoggedIn' => $final->wasLoggedIn,
            'adminId' => $final->adminId,
            'message' => 'ログアウトしました。',
        ];

        return $this;
    }
}
