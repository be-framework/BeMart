<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/two-factor-auth
EC-CUBE admin 2段階認証 (challenge) — top-level wave, Phase 3.

Thin renderer for the admin 2FA challenge screen
(`admin/two_factor_auth.twig`, extends `login_frame.twig`). This is a
LOGIN-CONTEXT page: it is reached AFTER correct credentials but
BEFORE the admin session is fully established, so — like the admin
login page — it is anonymous-accessible (no admin-firewall guard).

EC-CUBE's controller verifies the submitted TOTP token against the
member's stored secret. There is no Be Framework 2FA transition (no
such id in `alps.json`, and the be/ domain layer is frozen for this
wave), so this resource is a THIN RENDERER: `onGet` exposes an
{@see \AdminTwoFactorAuthForm} as `body['form']` for the HTML page.

DOMAIN follow-up (flagged, NOT implemented — the brief freezes be/):
the token verification needs a Be `doVerifyTwoFactorAuth` transition.
`onPost` is intentionally NOT implemented here.




## GET
Renders the admin 2FA challenge form.

Anonymous-accessible (login-context): returns 200 regardless of
session state — the admin firewall guard is downstream of a
successful challenge.



### Request

_No parameters required_

### Response

_Not available_