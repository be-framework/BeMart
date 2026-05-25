<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Final\LoggedOut;
use MyVendor\BeMart\Be\Input\LogoutInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;
use function getenv;
use function session_status;

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
 * read by HtmlSessionAdapter. The clear is guarded by APP_CONTEXT=html
 * and PHP_SESSION_ACTIVE so app/test/prod contexts keep their existing
 * session behaviour.
 */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * Phase B Slice 9: the CSRF token is user-controlled input.
     *
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onPost(string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new LogoutInput());
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        }

        assert($final instanceof LoggedOut);

        if (getenv('APP_CONTEXT') === 'html' && session_status() === PHP_SESSION_ACTIVE) {
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
