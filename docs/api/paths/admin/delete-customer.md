<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/delete-customer
EC-CUBE doDeleteCustomer — 会員を削除する (管理画面).

Admin-side counterpart of Wave 2G's mypage WithdrawResource. The
resource is the HTTP entry point: builds AdminDeleteCustomerInput,
hands it to Becoming, and projects the resulting AdminCustomerDeleted
into the response body. CSRF is enforced — this is a state-changing
operation.

ALPS doc: 会員を物理削除する。受注は会員IDをNULLにして保持。
Despite the "物理削除" wording, EC-CUBE 4.x preserves the row for FK
integrity (customer_status flips to 3 + email rewritten with a dummy);
the per-order customerId-NULLing cascade is OUT OF SCOPE here — see
the AdminCustomerDeleted Final's docblock.

Method choice — POST not DELETE: BEAR has no natural "DELETE by-id-
in-body" pattern (DELETE would put the id in the URL, but admin
tooling supplies it via a form click on the customer-list row). POST
with a CSRF token keeps the resource shape consistent with the rest
of the admin Page\Admin\... surface (CreateCustomer, Logout).

Failure mapping (cross-firewall AUTHZ → existence ladder):
  - Invalid CSRF                       → 403 (token missing / bad)
  - SemanticVariableException          → 400 (customerId format)
  - UnauthorizedAdminAccessException   → 403 (no admin session)
  - CustomerNotFoundException          → 404 (no such customerId)

Success (200): `{customerId, originalEmail, alreadyDeleted, message}`.
The `alreadyDeleted` flag distinguishes a fresh delete (false, mail
sent) from an idempotent replay (true, no mail) — same shape as the
pilot's idempotent re-add convention.

Anti-enumeration: the 403 / 404 ordering matches the Be Final's
check sequence (AUTHZ first, existence second). An admin-anonymous
client learns NOTHING about which customerIds resolve — same
discipline as goCustomer (Wave 5N).




## POST
Wave 6: customerId is user-controlled input from the admin UI
(admin clicks a customer-list row, the row's customerId feeds
this form). Same taint discipline as goCustomer's email.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| customerId | string | 会員ID |  | Required |  |  |


### Response

_Not available_