<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/withdraw
EC-CUBE doWithdrawCustomer — マイページから自分の会員アカウントを退会する.

The Be Final converges four side-effects (capture → replace →
cart-clear → mail). This resource adds the AUTHN-via-Session and
CSRF guards on the HTTP boundary; session-clear after the response
is the EC-CUBE EventListener's job (Slice 7.2 contract).

Failure mapping:
  - SemanticVariableException → 400 (sessionPrefix format invalid)
  - UnauthenticatedException  → 401 (no session)
  - missing/invalid csrfToken → 403




## GET
EC-CUBE goMypageWithdraw — show the withdrawal confirmation page.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Authenticated (mirrors Pilot 8 behavior): returns 401
directly from the Resource when no session is present.

Surfaces the current customer's email + name01/name02 so the
confirm page can render "退会されるアカウント: name01 name02
(email)". `csrfToken` body field stays `null` — EventListener
mirrors the Symfony token into the session for the subsequent
POST.



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| sessionPrefix | string |  |  | Optional |  |  |


### Response

_Not available_