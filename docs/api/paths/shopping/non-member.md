---
layout: default
title: "/shopping/non-member"
---

{% raw %}
<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/non-member
EC-CUBE goShoppingNonMember / doSubmitNonMember —非会員購入 (Wave 7W).

onGet  → goShoppingNonMember (safe form-info, anonymous-accessible)
  onPost → doSubmitNonMember   (unsafe, Direct, Semantic-validated)

Wave 7W is the FORM ENTRY only. The Final intentionally does NOT
persist a Cart / PreOrder under the guest's identity, and Pilot 5's
doCheckout still requires a customer session — so the preOrderId
returned by onPost will currently 403 on the subsequent checkout.
Closing that gap is Phase 2's job (dedicated GuestProfile entity +
non-member branch in CheckoutPrepared). See NonMemberSubmitted's
docblock for the full rationale.

Failure mapping (onPost):
  - CSRF invalid              → 403 (boundary)
  - SemanticVariableException → 400 (any guest field malformed)

Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) under
the same `Shopping/` directory — the same file-plus-sibling-directory
pattern as Mypage / Entry.

Phase 3 — HTML FORM page. `Shopping/nonmember.twig` renders the
guest-info inputs through the Symfony FormView; BeMart exposes a
{@see \NonMemberForm} (Ray.WebFormModule AbstractForm) as `body['form']`
so the HTML port renders real `<input>`s via `{{ form.input(...) }}`.
The form is a field-definition + renderer only — VALIDATION AUTHORITY
STAYS WITH the Be Becoming chain (doSubmitNonMember /
SubmitNonMemberInput). On a domain rejection the resource bridges the
verdict onto the form. JSON contexts ignore `body['form']`.




## GET
EC-CUBE goShoppingNonMember — show the guest-info entry form.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state). Fields mirror SubmitNonMemberInput. `csrfToken` body
field stays `null` for the same reason described on Login::onGet
— EventListener mirrors the Symfony token into the session for
the subsequent POST.



### Request

_No parameters required_

### Response

_Not available_
## POST
EC-CUBE doSubmitNonMember — accept guest shipping info and return
the synthesised preOrderId.

Phase B Slice 9: every guest form field is user-controlled input.
Declared as taint sources so Psalm can trace them downstream.
Semantic value objects format-validate but do not universally
escape — sinks downstream remain responsible for their own
defence (bound params, HTML escape on render).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| name01 | string | 姓 |  | Required |  |  |
| name02 | string | 名 |  | Required |  |  |
| kana01 | string | セイ |  | Required |  |  |
| kana02 | string | メイ |  | Required |  |  |
| email | string | メールアドレス |  | Required |  |  |
| phoneNumber | string | 電話番号 |  | Required |  |  |
| postalCode | string | 郵便番号 |  | Required |  |  |
| pref | int | 都道府県 |  | Required |  |  |
| addr01 | string | 市区町村 |  | Required |  |  |
| addr02 | string | 番地・建物名 |  | Required |  |  |


### Response

_Not available_
{% endraw %}
