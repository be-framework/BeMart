<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\AdminSessionWriterInterface;
use MyVendor\BeMart\Be\Final\AdminLoggedOut;
use MyVendor\BeMart\Be\Input\AdminLogoutInput;
use BEAR\Resource\Annotation\JsonSchema;

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
 * In the html context this resource clears the flat admin session key
 * through the session-writer port. Non-html contexts bind a no-op writer,
 * so Resource code does not branch on environment or touch PHP session storage.
 *
 * Source-of-truth gap: alps.json does not currently carry a
 * `doAdminLogout` transition id; using the conventional name to
 * parallel `doLogout` for the customer side.
 */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSessionWriterInterface $sessionWriter,
    ) {
    }

    /**
     * Wave 4 / Phase B Slice 9: the CSRF token is user-controlled
     * input — same taint discipline as the customer logout.
     *
     */
    #[Alps('doAdminLogout')]
    #[JsonSchema(schema: 'post-admin-logout.json')]
    #[Link(rel: 'goAdminLogin', href: 'page://self/admin/login')]
    #[CsrfProtected]
    public function onPost(): static
    {
        $final = ($this->becoming)(new AdminLogoutInput());

        assert($final instanceof AdminLoggedOut);

        $this->sessionWriter->clear();

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
