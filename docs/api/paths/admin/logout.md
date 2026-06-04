<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/logout
EC-CUBE doAdminLogout — 管理者ログアウト (Wave 4, Direct, idempotent).

Resource is the HTTP entry point: builds AdminLogoutInput, hands it
to Becoming, and on success returns whether there was an admin to
log out along with their adminId. The Be layer pattern is Direct
(Input → Final) — see AdminLogoutInput.

Failure mapping (intentionally narrow):
  - missing/invalid CSRF token  → 403 (Slice 8 uniform CSRF guard)
  - SemanticVariableException   → 400 (defensive; AdminLogoutInput has
                                         no semantically-validated
                                         fields, so this is unreachable
                                         today but kept uniform with
                                         the rest of Slice 8/9)

Notably absent: 401/403 for "no admin session". Per ALPS
`type=idempotent`, logging out an admin-anonymous client is a no-op
success — the response body simply carries `wasLoggedIn=false`.

In the html context this resource clears the flat admin session key
read by HtmlAdminSessionAdapter. The clear is guarded by
an html APP_CONTEXT and PHP_SESSION_ACTIVE so app/test/prod contexts keep
their existing session behaviour.

Source-of-truth gap: alps.json does not currently carry a
`doAdminLogout` transition id; using the conventional name to
parallel `doLogout` for the customer side.




## POST
Wave 4 / Phase B Slice 9: the CSRF token is user-controlled
input — same taint discipline as the customer logout.



### Request

_No parameters required_

### Response

_Not available_