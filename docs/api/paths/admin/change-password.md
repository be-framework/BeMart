{% raw %}
<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/change-password
EC-CUBE admin パスワード変更 — top-level wave, Phase 3.

Thin renderer for the forced/voluntary admin password-change screen
(`admin/change_password.twig`). EC-CUBE's controller validates the
current password and applies the new one via the Symfony security
password hasher. There is no Be Framework `doChangeAdminPassword`
transition (no such id in `alps.json`, and the be/ domain layer is
frozen for this wave), so this resource is a THIN RENDERER: it
enforces the admin firewall and exposes an
{@see \AdminChangePasswordForm} as `body['form']` for the HTML page to
render via `{{ form.input(...) }}`.

Hard ActionRedirect completion: `onPost` now drives the Be
`doChangePassword` transition ({@see \ChangeAdminPasswordInput} →
{@see \AdminPasswordChanged}) — current-password verification +
re-hash over the admin storage, with the credential/CSRF/session
boundary enforced Be/BEAR-side.




## GET
Renders the admin password-change form.

Admin-only: returns 403 for an anonymous request — the same
firewall contract as the other admin pages, enforced at the
resource layer (there is no Be Final to raise
`UnauthorizedAdminAccessException`).



### Request

_No parameters required_

### Response

_Not available_
## POST
Applies the admin's own password change (doChangePassword).

Failure mapping:
- Invalid CSRF                         → 403 (interceptor)
- SemanticVariableException            → 400
- InvalidCurrentPasswordException      → 400
- PasswordConfirmationMismatchException→ 400
- PasswordPolicyViolationException     → 400
- UnauthorizedAdminAccessException     → 403 (no admin session)
- AdminNotFoundException               → 404 (stale session)



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| currentPassword | string |  |  | Required |  |  |
| changePasswordFirst | string |  |  | Required |  |  |
| changePasswordSecond | string |  |  | Required |  |  |


### Response

_Not available_
{% endraw %}
