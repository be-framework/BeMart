<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /logout
EC-CUBE doLogout — 会員ログアウト (Pilot — Direct, idempotent).

Resource is the HTTP entry point: builds LogoutInput, hands it to
Becoming, and on success returns whether there was anyone to log out
along with their customerId. The Be layer pattern is Direct
(Input → Final) — see LogoutInput.

Failure mapping (intentionally narrow):
  - missing/invalid CSRF token       → 403 (Slice 8 uniform CSRF guard)
  - SemanticVariableException        → 400 (defensive; LogoutInput has
                                             no semantically-validated
                                             fields, so this is unreachable
                                             today but kept uniform with
                                             the rest of Slice 8/9)

Notably absent: 401. Per ALPS `type=idempotent`, logging out an
anonymous client is a no-op success — the response body simply
carries `wasLoggedIn=false`. The resource MUST NOT treat the absence
of a session as an error.

In the html context this resource clears the flat customer session key
read by HtmlSessionAdapter. The clear is guarded by an html APP_CONTEXT
and PHP_SESSION_ACTIVE so app/test/prod contexts keep their existing
session behaviour.




## POST
Phase B Slice 9: the CSRF token is user-controlled input.



### Request

_No parameters required_

### Response

_Not available_