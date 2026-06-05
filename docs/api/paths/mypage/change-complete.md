---
layout: default
title: "/mypage/change-complete"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/change-complete
EC-CUBE goMypageChangeComplete — 会員情報編集(完了)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
`Mypage/change_complete.twig` after a successful `doUpdateCustomer`
(mypage_change). BeMart's {@see \Change}::onPost (Pilot 8) returns the
`CustomerUpdated` projection directly and the ALPS surface declares
the single transition `goMypage` — no `MypageChangeComplete` SCREEN
resource ever existed. Phase 3 needs a page to render
`Mypage/change_complete.twig` against, so this THIN PURE RENDERER is
added: no Be Framework, no domain logic, no Reasons.

`Mypage/change_complete.twig` is a static confirmation (the
change-complete message + a back-to-top button + the shared Mypage
navi). It reads no dynamic data, so the thin-renderer body carries
nothing to surface. The Mypage navi welcome line uses `app.user.*` in
EC-CUBE; the BeMart port's `navi.html.twig` reads `name01`/`name02`
from the page body, which are absent here (the customer name is a
MISSING BODY FIELD follow-up — the thin renderer has no session-bound
customer context) so the navi welcome renders the empty name, exactly
as EC-CUBE renders for a missing user.

Maps to `page://self/mypage/change-complete`.




## GET


### Request

_No parameters required_

### Response

_Not available_