<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\CustomerSessionWriterInterface;
use MyVendor\BeMart\Be\Final\LoggedOut;
use MyVendor\BeMart\Be\Input\LogoutInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

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
 * through the session-writer port. Non-html contexts bind a no-op writer,
 * so Resource code does not branch on environment or touch PHP session storage.
 */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CustomerSessionWriterInterface $sessionWriter,
    ) {
    }

    /**
     * Phase B Slice 9: the CSRF token is user-controlled input.
     *
     */
    #[Alps('doLogout')]
    #[JsonSchema(schema: 'post-logout.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[CsrfProtected]
    public function onPost(): static
    {
        $final = ($this->becoming)(new LogoutInput());

        assert($final instanceof LoggedOut);

        $this->sessionWriter->clear();

        // Post/Redirect/Get: EC-CUBE's doLogout redirects to the storefront
        // top page (the `goTop` transition declared above).
        $this->code = Code::SEE_OTHER;
        $this->headers['Location'] = '/';
        $this->body = [
            'wasLoggedIn' => $final->wasLoggedIn,
            'customerId' => $final->customerId,
            'message' => 'ログアウトしました。',
        ];

        return $this;
    }
}
