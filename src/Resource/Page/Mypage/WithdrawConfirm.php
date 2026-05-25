<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goMypageWithdrawConfirm — 退会手続き(実行確認)
 * (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE's withdraw flow has
 * TWO confirmation screens served by the same `mypage_withdraw`
 * controller action:
 *   1. `Mypage/withdraw.twig`         — the "退会手続きの前にご確認
 *      ください" warning page; its button POSTs `mode=confirm`.
 *      BeMart's {@see Withdraw}::onGet + `Page/Mypage/Withdraw.html.twig`
 *      already port this screen.
 *   2. `Mypage/withdraw_confirm.twig` — rendered after `mode=confirm`;
 *      the "退会手続きを実行してもよろしいでしょうか？" final
 *      confirmation, with a cancel link + an execute button that POSTs
 *      `mode=complete` to actually withdraw.
 *
 * BeMart's {@see Withdraw}::onPost performs the withdrawal directly (the
 * Be Final converges the side-effects); the ALPS surface collapses the
 * two-screen confirm into the single `doWithdrawCustomer` transition, so
 * no `MypageWithdrawConfirm` SCREEN resource ever existed. Phase 3 needs
 * a page to render `Mypage/withdraw_confirm.twig` against, so this THIN
 * PURE RENDERER is added: no Be Framework, no domain logic, no Reasons.
 *
 * This is a CONFIRM screen — it renders only a CSRF hidden token and a
 * submit button, no editable `<input>` fields, so (per
 * var/templates/README.md) no AbstractForm is needed; the form-page
 * recipe's `<Name>Form` exists for screens that render `<input>` fields.
 * The submit target is doWithdrawCustomer (`page://self/mypage/withdraw`,
 * POST). `csrfToken` stays null — the EventListener mirrors the live
 * Symfony token into the body for the subsequent POST.
 *
 * The Mypage navi welcome line reads `name01`/`name02` from the page
 * body, which are absent here (the customer name is a MISSING BODY
 * FIELD follow-up — the thin renderer has no session-bound customer
 * context) so the navi welcome renders the empty name.
 *
 * Maps to `page://self/mypage/withdraw-confirm`.
 */
class WithdrawConfirm extends ResourceObject
{
    #[Link(rel: 'doWithdrawCustomer', href: 'page://self/mypage/withdraw', method: 'post')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goMypageWithdrawConfirm',
            'fields' => ['csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/mypage/withdraw',
            ],
            'csrfToken' => null,
            'links' => [
                'doWithdrawCustomer' => 'page://self/mypage/withdraw',
                'goMypage' => 'page://self/mypage',
            ],
        ];

        return $this;
    }
}
