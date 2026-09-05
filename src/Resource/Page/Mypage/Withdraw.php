<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Auth\CartSessionPrefixInterface;
use MyVendor\BeMart\Auth\CustomerSessionWriterInterface;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\CustomerWithdrawn;
use MyVendor\BeMart\Be\Input\WithdrawCustomerInput;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doWithdrawCustomer — マイページから自分の会員アカウントを退会する.
 *
 * The Be Final converges four side-effects (capture → replace →
 * cart-clear → mail). This resource adds the AUTHN-via-Session and
 * CSRF guards on the HTTP boundary, and clears the customer session
 * through the session-writer port once the Final proves the account
 * is gone — the credentials the session carries no longer name a
 * login-capable customer.
 *
 * Failure mapping:
 *   - UnauthenticatedException  → 401 (no session)
 *   - missing/invalid csrfToken → 403
 */
class Withdraw extends ResourceObject
{
    private const DEFAULT_SESSION_PREFIX = 'session-prefix-1';

    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CustomerSession $session,
        private readonly CustomerQueryInterface $customerQuery,
        private readonly CartSessionPrefixInterface $cartSessionPrefix,
        private readonly MutationResponseInterface $mutationResponse,
        private readonly CustomerSessionWriterInterface $sessionWriter,
    ) {
    }

    /**
     * EC-CUBE goMypageWithdraw — show the withdrawal confirmation page.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Authenticated (mirrors Pilot 8 behavior): returns 401
     * directly from the Resource when no session is present.
     *
     * Surfaces the current customer's email + name01/name02 so the
     * confirm page can render "退会されるアカウント: name01 name02
     * (email)". `csrfToken` body field stays `null` — EventListener
     * mirrors the Symfony token into the session for the subsequent
     * POST.
     */
    #[Alps('goMypageWithdraw')]
    #[JsonSchema(schema: 'get-mypage-withdraw.json')]
    #[Link(rel: 'doWithdrawCustomer', href: 'page://self/mypage/withdraw', method: 'post')]
    public function onGet(): static
    {
        $customerId = $this->session->customerId;
        if ($customerId === null) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        }

        $customer = $this->customerQuery->item($customerId);
        if ($customer === null) {
            // Stale session: the session points to a customerId that
            // no longer exists in the store (e.g. already withdrawn
            // in another tab). Treat as unauthenticated.
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goMypageWithdraw',
            'fields' => ['csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/mypage/withdraw',
            ],
            'csrfToken' => null,
            'customerId' => $customer->customerId,
            'email' => $customer->email,
            'name01' => $customer->name01,
            'name02' => $customer->name02,
        ];

        return $this;
    }

    /** ALPS `doWithdrawCustomer` に対応する POST 操作。 */
    #[Alps('doWithdrawCustomer')]
    #[JsonSchema(schema: 'post-mypage-withdraw.json', params: 'post-mypage-withdraw.param.json')]
    #[Link(rel: 'goMypageWithdrawComplete', href: 'page://self/mypage/withdraw-complete')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[CsrfProtected]
    public function onPost(): static
    {
        $final = ($this->becoming)(new WithdrawCustomerInput(
            sessionPrefix: $this->cartSessionPrefix->prefix() ?? self::DEFAULT_SESSION_PREFIX,
        ));

        assert($final instanceof CustomerWithdrawn);

        $this->sessionWriter->clear();

        ($this->mutationResponse)($this, Code::OK, '/mypage/withdraw-complete');
        $this->body = [
            'customerId' => $final->customerId,
            'cleared' => $final->cleared,
            'message' => '退会手続きが完了しました。',
        ];

        return $this;
    }
}
