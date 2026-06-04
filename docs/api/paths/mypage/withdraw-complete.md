---
layout: default
title: "/mypage/withdraw-complete"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/withdraw-complete
EC-CUBE goMypageWithdrawComplete — 退会手続き(完了)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
`Mypage/withdraw_complete.twig` after a successful `doWithdrawCustomer`
(the EventListener clears the session, then the controller renders the
complete screen). BeMart's {@see \Withdraw}::onPost converges the
withdrawal side-effects and returns the `CustomerWithdrawn` projection;
the ALPS surface declares the single transition `goTop` — no
`MypageWithdrawComplete` SCREEN resource ever existed. Phase 3 needs a
page to render `Mypage/withdraw_complete.twig` against, so this THIN
PURE RENDERER is added: no Be Framework, no domain logic, no Reasons.

`Mypage/withdraw_complete.twig` is a static confirmation (the
withdrawal-complete message + a go-to-top button). Unlike the other
Mypage screens it does NOT include the account navi (the customer is
already withdrawn / logged out), so the port carries no navi and no
customer-name context. It reads no dynamic data, so the thin-renderer
body carries nothing to surface.

Maps to `page://self/mypage/withdraw-complete`.




## GET


### Request

_No parameters required_

### Response

_Not available_