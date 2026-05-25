<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Final\LoggedOut;
use MyVendor\BeMart\Be\Input\LogoutInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
 * Session-clear deliberately out of scope: the Slice 7.2 contract
 * places HTTP session teardown on the EC-CUBE EventListener, which
 * observes this response and clears `$_SESSION['customer_id']`. This
 * mirrors Pilot 6 (doLogin) where the same EventListener performs the
 * session-write. Cart contents — kept in the same session by EC-CUBE —
 * are cleared as a side-effect of that session destruction.
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

        $this->code = Code::OK;
        $this->body = [
            'wasLoggedIn' => $final->wasLoggedIn,
            'customerId' => $final->customerId,
            'message' => 'ログアウトしました。',
        ];

        return $this;
    }
}
