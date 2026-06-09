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

**ALPS**: `doDeleteCustomer`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| customerId | string | 会員ID（入力） - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | customer-001 |


### Response

[Object: POST /admin/delete-customer response](../schemas/post-admin-delete-customer.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| alreadyDeleted | boolean|null | 既削除フラグ - /admin/delete-customer の処理状態を示す既削除フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| originalEmail | string | フォーム文脈項目 - /admin/delete-customer のフォーム文脈で使うフォーム文脈項目。入力保持、初期値、再表示に必要な補助値。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| message | string|null | 会員メッセージ - /admin/delete-customer のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| goCustomerList | [<code>page://self/admin/customer-list</code>](/admin/customer-list.md) |