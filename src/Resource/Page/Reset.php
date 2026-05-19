<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ResetKeyInvalidException;
use MyVendor\BeMart\Be\Final\PasswordResetCompleted;
use MyVendor\BeMart\Be\Input\ResetPasswordInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doResetPassword — リセットキーを検証して新しいパスワードを
 * 保存する (Pilot 15, consumer pair to Pilot 14 doRequestPasswordReset).
 *
 * Failure mapping (both -> 400, same code on purpose):
 *   - SemanticVariableException  → 400 (resetKey or password malformed)
 *   - ResetKeyInvalidException   → 400 (wrong key / expired / already used)
 *
 * Both failures collapse to the same HTTP status (400 rather than
 * 404) so an attacker cannot distinguish "format-invalid" from
 * "value-invalid" by status alone — same anti-enumeration design as
 * the merged ResetKeyInvalid exception itself.
 *
 * Single-use is enforced inside the Be Final (token consumed via
 * `PasswordResetTokenStorageInterface::delete()` immediately on
 * success); this resource only translates the failure modes.
 */
class Reset extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $resetKey
     * @psalm-taint-source input $password
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    public function onPost(string $resetKey, string $password, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new ResetPasswordInput(
                resetKey: $resetKey,
                password: $password,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (ResetKeyInvalidException) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'パスワードリセットリンクが無効か、既に使用済みです。'];

            return $this;
        }

        assert($final instanceof PasswordResetCompleted);

        $this->code = Code::OK;
        $this->body = ['customerId' => $final->customerId];

        return $this;
    }
}
