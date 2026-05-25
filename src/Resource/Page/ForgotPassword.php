<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Final\PasswordResetRequested;
use MyVendor\BeMart\Be\Input\RequestPasswordResetInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doRequestPasswordReset — パスワードリセット依頼 (Pilot 14).
 *
 * Anti-enumeration: the response code (200) and body shape are
 * identical regardless of whether the supplied email is actually
 * registered. A real attacker cannot probe for valid emails by
 * watching for differences in status, body, or timing.
 *
 * The `issued` flag in the body deliberately reports the same string
 * for both branches; callers that need to programmatically check
 * delivery must reach into the test-only FakeMailer (which records
 * actual dispatches).
 */
class ForgotPassword extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $email
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    public function onPost(string $email, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new RequestPasswordResetInput(email: $email));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        }

        assert($final instanceof PasswordResetRequested);

        // Uniform 200 / uniform message — no enumeration signal.
        $this->code = Code::OK;
        $this->body = [
            'message' => 'リセット手続きのご案内をメールでお送りしました。',
        ];

        return $this;
    }
}
