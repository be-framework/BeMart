<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goMypageChangeComplete — 会員情報編集(完了)
 * (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
 * `Mypage/change_complete.twig` after a successful `doUpdateCustomer`
 * (mypage_change). BeMart's {@see Change}::onPost (Pilot 8) returns the
 * `CustomerUpdated` projection directly and the ALPS surface declares
 * the single transition `goMypage` — no `MypageChangeComplete` SCREEN
 * resource ever existed. Phase 3 needs a page to render
 * `Mypage/change_complete.twig` against, so this THIN PURE RENDERER is
 * added: no Be Framework, no domain logic, no Reasons.
 *
 * `Mypage/change_complete.twig` is a static confirmation (the
 * change-complete message + a back-to-top button + the shared Mypage
 * navi). It reads no dynamic data, so the thin-renderer body carries
 * nothing to surface. The Mypage navi welcome line uses `app.user.*` in
 * EC-CUBE; the BeMart port's `navi.html.twig` reads `name01`/`name02`
 * from the page body, which are absent here (the customer name is a
 * MISSING BODY FIELD follow-up — the thin renderer has no session-bound
 * customer context) so the navi welcome renders the empty name, exactly
 * as EC-CUBE renders for a missing user.
 *
 * Maps to `page://self/mypage/change-complete`.
 */
class ChangeComplete extends ResourceObject
{
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goMypageChangeComplete',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'mypage-change-complete',
                'title' => 'マイページ/会員情報編集(完了)',
            ],
            'links' => [
                'goTop' => 'page://self/',
                'goMypage' => 'page://self/mypage',
            ],
        ];

        return $this;
    }
}
