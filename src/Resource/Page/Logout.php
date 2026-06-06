<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Final\LoggedOut;
use MyVendor\BeMart\Be\Input\LogoutInput;

use function assert;
use function getenv;
use function session_status;
use function str_contains;

use const PHP_SESSION_ACTIVE;

/**
 * EC-CUBE doLogout — 会員ログアウト (Pilot — Direct, idempotent).
 *
 * Resource is the HTTP entry point: builds LogoutInput, hands it to
 * Becoming, and on success returns whether there was anyone to log out
 * along with their customerId. The Be layer pattern is Direct
 * (Input → Final) — see LogoutInput.
 *
 * Failure mapping (intentionally narrow):
 *   - missing/invalid CSRF token       → 403 (Slice 8 uniform CSRF guard)
 *   - SemanticVariableException        → 400 (defensive; LogoutInput has
 *                                              no semantically-validated
 *                                              fields, so this is unreachable
 *                                              today but kept uniform with
 *                                              the rest of Slice 8/9)
 *
 * Notably absent: 401. Per ALPS `type=idempotent`, logging out an
 * anonymous client is a no-op success — the response body simply
 * carries `wasLoggedIn=false`. The resource MUST NOT treat the absence
 * of a session as an error.
 *
 * In the html context this resource clears the flat customer session key
 * read by HtmlSessionAdapter. The clear is guarded by an html APP_CONTEXT
 * and PHP_SESSION_ACTIVE so app/test/prod contexts keep their existing
 * session behaviour.
 */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Phase B Slice 9: the CSRF token is user-controlled input.
     *
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[CsrfProtected]
    public function onPost(): static
    {
        $final = ($this->becoming)(new LogoutInput());

        assert($final instanceof LoggedOut);

        if (str_contains((string) getenv('APP_CONTEXT'), 'html') && session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY]);
        }

        // Post/Redirect/Get: EC-CUBE's doLogout redirects to the storefront
        // top page (the `goTop` transition declared above).
        $this->code = Code::OK;
        $this->headers['Location'] = '/';
        $this->body = [
            'wasLoggedIn' => $final->wasLoggedIn,
            'customerId' => $final->customerId,
            'message' => 'ログアウトしました。',
        ];

        return $this;
    }
}
