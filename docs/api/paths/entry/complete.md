---
layout: default
title: "/entry/complete"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /entry/complete
EC-CUBE goCustomerRegistrationComplete — 新規会員登録(仮登録完了)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
`Entry/complete.twig` after a successful `doRegisterCustomer` (the
verification-ON branch — provisional → email confirm). BeMart's
`Entry::onPost` (Pilot 4) implements the verification-OFF flow only:
it returns the `CustomerRegistered` projection (`customerStatus = 2 =
Active`) and the ALPS surface declares the single transition `goTop`
— no `CustomerRegistrationComplete` SCREEN resource ever existed.
Phase 3 needs a page to render `Entry/complete.twig` against, so this
THIN PURE RENDERER is added: no Be Framework, no domain logic, no
Reasons. It exposes only the complete-screen shape + the outbound
`goTop` transition.

`Entry/complete.twig` is a static provisional-registration confirmation
(the temporary-member message + a top-page button) — it reads no
dynamic data, so the thin-renderer body carries nothing to surface.

Maps to `page://self/entry/complete`.




## GET


### Request

_No parameters required_

### Response

_Not available_