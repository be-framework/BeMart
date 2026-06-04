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

MISSING-BODY-FIELD / domain follow-up (flagged, NOT implemented —
the brief freezes be/): the actual password update needs a Be
`doChangeAdminPassword` transition (current-password verification +
re-hash over the admin storage). `onPost` is intentionally NOT
implemented here; adding it requires the be/ domain layer.




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