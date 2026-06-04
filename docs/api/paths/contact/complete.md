---
layout: default
title: "/contact/complete"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /contact/complete
EC-CUBE goContactComplete — お問い合わせ(完了)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
`Contact/complete.twig` after a successful `doSubmitContact`. BeMart's
`Contact::onPost` (Pilot 15) returns the `ContactSubmitted` projection
and the ALPS surface declares the single transition `goTop` — no
`ContactComplete` SCREEN resource ever existed. Phase 3 needs a page
to render `Contact/complete.twig` against, so this THIN PURE RENDERER
is added: no Be Framework, no domain logic, no Reasons. It exposes
only the complete-screen shape + the outbound `goTop` transition.

`Contact/complete.twig` is mostly static (the completion message +
a top-page button), but the Resource also carries the public receipt
`ticketId` issued by doSubmitContact.

Maps to `page://self/contact/complete`.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| ticketId | string | 受付番号 |  | Optional |  |  |


### Response

_Not available_