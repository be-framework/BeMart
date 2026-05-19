<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\CustomerWithdrawn;
use MyVendor\BeMart\Be\Input\WithdrawCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doWithdrawCustomer — マイページから自分の会員アカウントを退会する.
 *
 * The Be Final converges four side-effects (capture → replace →
 * cart-clear → mail). This resource adds the AUTHN-via-Session and
 * CSRF guards on the HTTP boundary; session-clear after the response
 * is the EC-CUBE EventListener's job (Slice 7.2 contract).
 *
 * Failure mapping:
 *   - SemanticVariableException → 400 (sessionPrefix format invalid)
 *   - UnauthenticatedException  → 401 (no session)
 *   - missing/invalid csrfToken → 403
 */
class Withdraw extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $sessionPrefix
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onPost(
        string|null $sessionPrefix = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $input = $sessionPrefix === null
                ? new WithdrawCustomerInput()
                : new WithdrawCustomerInput(sessionPrefix: $sessionPrefix);

            $final = ($this->becoming)($input);
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        }

        assert($final instanceof CustomerWithdrawn);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'dummyEmail' => $final->dummyEmail,
            'cleared' => $final->cleared,
            'message' => '退会手続きが完了しました。',
        ];

        return $this;
    }
}
