<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
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
        private readonly CustomerSession $session,
        private readonly CustomerQueryInterface $customerQuery,
        private readonly MutationResponseInterface $mutationResponse,
        private readonly ResourceInterface $resource,
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
            'fields' => ['sessionPrefix', 'csrfToken'],
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

    /**
     * ALPS `doWithdrawCustomer` に対応する POST 操作。
     *
     * EC-CUBE WithdrawController::index state machine (mode POST param):
     *   confirm  -> render the final "退会手続きを実行してもよろしいでしょ
     *               うか？" confirmation screen, NO side-effects (the
     *               account stays ACTIVE — nothing is cleared/replaced/sent).
     *   complete -> actually withdraw (run the WithdrawCustomerInput chain)
     *               and 303 to /mypage/withdraw-complete.
     *
     * The FIRST warning page (`Page/Mypage/Withdraw.html.twig`, the "退会
     * 手続きの前にご確認ください" screen) POSTs `mode=confirm`; clicking it
     * must NOT withdraw — it only advances to the WithdrawConfirm review
     * page. Only the WithdrawConfirm page's "はい、退会します" button
     * (`mode=complete`) commits the withdrawal. This mirrors EC-CUBE
     * exactly and prevents withdrawing from the first warning click.
     *
     * A JSON / hypermedia client sends no `mode`: it keeps the collapsed
     * `doWithdrawCustomer` behaviour (withdraw immediately, 200 + body),
     * so the Resource/Flow tests that drive the transition directly stay
     * green.
     *
     * @psalm-taint-source input $sessionPrefix
     */
    #[Alps('doWithdrawCustomer')]
    #[JsonSchema(schema: 'post-mypage-withdraw.json', params: 'post-mypage-withdraw.param.json')]
    #[Link(rel: 'goMypageWithdrawConfirm', href: 'page://self/mypage/withdraw-confirm')]
    #[Link(rel: 'goMypageWithdrawComplete', href: 'page://self/mypage/withdraw-complete')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[CsrfToken]
    public function onPost(
        string|null $sessionPrefix = null,
        string|null $mode = null,
    ): static {
        // EC-CUBE: `mode=confirm` shows the final confirmation screen WITHOUT
        // performing the withdrawal. The account is left untouched (still
        // ACTIVE); only the WithdrawConfirm page's `mode=complete` commits.
        if ($mode === 'confirm') {
            return $this->renderConfirm();
        }

        $input = $sessionPrefix === null
            ? new WithdrawCustomerInput()
            : new WithdrawCustomerInput(sessionPrefix: $sessionPrefix);

        $final = ($this->becoming)($input);

        assert($final instanceof CustomerWithdrawn);

        ($this->mutationResponse)($this, Code::OK, '/mypage/withdraw-complete');
        $this->body = [
            'customerId' => $final->customerId,
            'dummyEmail' => $final->dummyEmail,
            'cleared' => $final->cleared,
            'message' => '退会手続きが完了しました。',
        ];

        return $this;
    }

    /**
     * EC-CUBE `mode=confirm` — render the final "退会手続きを実行しても
     * よろしいでしょうか？" confirmation screen with NO side-effects.
     *
     * The withdrawal chain is NOT run here: the customer record stays
     * ACTIVE, no cart is cleared and no mail is sent. The WithdrawConfirm
     * resource renders `Page/Mypage/WithdrawConfirm.html.twig` whose "はい、
     * 退会します" button POSTs `mode=complete` back to this route to actually
     * withdraw. The rendered confirm page becomes this response's view, so
     * the browser sees the review screen at `/mypage/withdraw` without a
     * redirect (mirrors EC-CUBE's `render('Mypage/withdraw_confirm.twig')`).
     *
     * The body stays `post-mypage-withdraw.json`-shaped (nothing withdrawn
     * yet — `cleared=false`, `dummyEmail` echoes the customer's still-current
     * email so the email-typed field validates) so the response contract
     * holds; the real user-visible signal lives in the rendered view.
     */
    private function renderConfirm(): static
    {
        $confirm = $this->resource->get('page://self/mypage/withdraw-confirm');

        $customerId = $this->session->customerId;
        $customer = $customerId === null ? null : $this->customerQuery->item($customerId);

        $this->code = Code::OK;
        $this->view = $confirm->toString();
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = [
            'customerId' => $customer->customerId ?? null,
            // Schema projection only — nothing withdrawn yet, so the real
            // address is unchanged. Echo it into the email-typed field so the
            // response schema (format:email, minLength 3) still validates.
            'dummyEmail' => $customer->email ?? 'pending@example.test',
            'cleared' => false,
        ];

        return $this;
    }
}
