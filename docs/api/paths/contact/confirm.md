<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /contact/confirm
EC-CUBE goContactConfirm — お問い合わせ(確認)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE's contact flow has a
`Contact::confirm` step between `goContactForm` (the form) and
`doSubmitContact` (the send): the customer reviews the inquiry before
it is sent. EC-CUBE keeps that step on the SAME controller action,
branching on the `mode` POST param (`confirm` / `complete` / `back`);
BeMart's Pilot 15 collapsed the flow — `Contact::onGet` (form) hands
straight to `Contact::onPost` (doSubmitContact) — so no
`ContactConfirm` resource existed. Phase 3 needs a page to render
`Contact/confirm.twig` against, so this THIN PURE RENDERER is added:
no Be Framework, no domain logic, no Reasons.

FORM page (the form-page recipe — see var/templates/README.md). The
confirm screen re-shows the submitted inquiry as plain text AND
carries it forward as HIDDEN inputs so the final submit re-posts the
inquiry to `doSubmitContact`. The resource exposes a
{@see \ContactConfirmForm} (every inquiry field declared `hidden`) as
`body['form']`.

FOLLOW-UP — the confirm screen's plain-text value cells show the
submitted inquiry; a pure `onGet` renderer has no submitted values, so
those cells render empty. Threading the submitted payload into the
confirm step is a dedicated vertical slice, tracked in the enrichment
backlog. Recorded as a MISSING BODY FIELD residual in the render test.

Maps to `page://self/contact/confirm`. The submit target is
doSubmitContact (`page://self/contact`).




## GET


### Request

_No parameters required_

### Response

_Not available_