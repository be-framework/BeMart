<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/two-factor-auth-set
EC-CUBE admin 2段階認証 デバイス登録 — top-level wave, Phase 3.

Thin renderer for the admin 2FA device-setup screen
(`admin/two_factor_auth_set.twig`, extends `login_frame.twig`). This
is a LOGIN-CONTEXT page reached after correct credentials when the
member has no 2FA device yet, so — like the admin login page — it is
anonymous-accessible (no admin-firewall guard).

EC-CUBE's controller generates a TOTP secret, renders it as a QR code
(the JS in the template builds the `otpauth://` URI) and verifies the
first token. There is no Be Framework 2FA transition (no such id in
`alps.json`, and the be/ domain layer is frozen for this wave), so
this resource is a THIN RENDERER: `onGet` exposes an
{@see \AdminTwoFactorAuthForm} as `body['form']` for the HTML page.

MISSING-BODY-FIELD follow-ups (flagged, NOT implemented — the brief
freezes be/): the QR-code JS needs `authKey` (the generated TOTP
secret), `memberName` and `shopName` to build the `otpauth://` URI.
Those require a Be `doSetupTwoFactorAuth` transition (secret
generation over the admin storage); `onPost` is not implemented here.
The body carries empty placeholders so the page still renders.




## GET
Renders the admin 2FA device-setup form.

Anonymous-accessible (login-context): returns 200 regardless of
session state.



### Request

_No parameters required_

### Response

_Not available_