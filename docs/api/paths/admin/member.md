<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/member
EC-CUBE goMember / doCreateMember / doUpdateMember / doDeleteMember
— 管理者 (Wave 8). The four verbs on the admin-member detail
resource share one URL (`page://self/admin/member`) and dispatch by
HTTP method:

- GET    → goMember            (safe read, no CSRF)
  - POST   → doCreateMember      (unsafe, CSRF, multi-Reason Being)
  - PUT    → doUpdateMember      (idempotent, CSRF, name/mail merge)
  - DELETE → doDeleteMember      (idempotent, CSRF, soft-delete)

All four are admin-only. The Be Finals raise
{@see \UnauthorizedAdminAccessException} when no admin session is
present; we map that to 403 here.

Distinct from the role-flip surface ({@see \AuthorityRole}) — that
goes through its own URL because the privilege-escalation guard
needs to be observable in the resource layout.

Failure mapping (common to all four):
  - Invalid CSRF                          → 403 (POST/PUT/DELETE)
  - SemanticVariableException             → 400 (any field format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - AdminNotFoundException                → 404 (no such loginId)

POST-only:
  - LoginIdAlreadyTakenException          → 409 (loginId conflict)

DELETE-only:
  - InsufficientAuthorityException        → 403 (caller targeting self)




## GET
Wave 8: the loginId comes from the admin UI (typed input or
query string) — user-controlled.

**ALPS**: `goMember`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID（入力） - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | test-admin |


### Response

[Object: GET /admin/member response](../schemas/get-admin-member.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| name | string|null | 処理表示名 - Fake観察文字長 1〜7; 観察値 'テスト管理者', '副管理者', '店舗オーナー', '削除済み管理者', 'Red', 'Blue', 'S', 'Color'。 | Required | {"minLength":0,"maxLength":32} | テスト管理者 |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| sortNo | int|null | 表示順 - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| authority | int|null | 権限 - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 Fake観察数値 0〜1; 観察値 '1', '0'。 | Required | {"minimum":0,"maximum":9} | 1 |
| work | int|null | 稼働状態 - Work Masterの定数で定義: 0=NON_ACTIVE（非稼働、ログイン不可）, 1=ACTIVE（稼働、ログイン可能）。管理者メンバーの有効/無効を制御 | Required | {"minimum":0} |  |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goMemberList | [<code>page://self/admin/member-list</code>](/admin/member-list.md) |
## POST
Wave 8: all form fields are user-controlled. The admin AUTHZ
check lives inside the first Being (MemberCreating), so this
method just maps the exceptions.

**ALPS**: `doCreateMember`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID（入力） - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | test-admin |
| password | string | パスワード（入力） - 書き込み専用（ハッシュ化して保存） Fake観察文字長 50〜63; 観察値 '$2y$12$Vl/YKSI0DjUOxYJWH9ytAeVk3Z7l21e.6UM7gh46gpdsbvT4OQ4eG', '$2y$10$deputyplaceholder.hash.never.verified.0123456789abcdef', '$2y$10$zyxwvutsrqponmlkjihgfedcbaZYXWVUTSRQPONMLKJIHGFEDCBA9876', '$2y$12$dC7U8xCHBGmNT2TjlWbv6.ho4y.Lcezn5PT0ywpUsaxk0x49tUune', '$2y$10$shopownerplaceholder.hash.never.verified.0123456789ab', '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123', '$2y$10$0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRS', '$2y$12$placeholder.hash.never.verified.never.0123456789abcde'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | $2y$12$Vl/YKSI0DjUOxYJWH9ytAeVk3Z7l21e.6UM7gh46gpdsbvT4OQ4eG |
| name | string | 処理表示名（入力） - Fake観察文字長 1〜7; 観察値 'テスト管理者', '副管理者', '店舗オーナー', '削除済み管理者', 'Red', 'Blue', 'S', 'Color'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | テスト管理者 |
| authority | int | 権限（入力） - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 Fake観察数値 0〜1; 観察値 '1', '0'。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |


### Response

[Object: POST /admin/member response](../schemas/post-admin-member.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name | string|null | 処理表示名 - Fake観察文字長 1〜7; 観察値 'テスト管理者', '副管理者', '店舗オーナー', '削除済み管理者', 'Red', 'Blue', 'S', 'Color'。 | Required | {"minLength":0,"maxLength":32} | テスト管理者 |
| sortNo | int|null | 表示順 - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |
| authority | int|null | 権限 - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 Fake観察数値 0〜1; 観察値 '1', '0'。 | Required | {"minimum":0,"maximum":9} | 1 |
| work | int|null | 稼働状態 - Work Masterの定数で定義: 0=NON_ACTIVE（非稼働、ログイン不可）, 1=ACTIVE（稼働、ログイン可能）。管理者メンバーの有効/無効を制御 | Required | {"minimum":0} |  |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goMember | [<code>page://self/admin/member</code>](/admin/member.md) |
## PUT
Wave 8: doUpdateMember — edits `name` only. The other admin
fields (authority, work, passwordHash) have their own dedicated
transitions / are out of scope for Phase 1. EC-CUBE 4.3
dtb_member has no email column, so no mailAddress field is
accepted.

**ALPS**: `doUpdateMember`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID（入力） - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | test-admin |
| name | string | 処理表示名（入力） - Fake観察文字長 1〜7; 観察値 'テスト管理者', '副管理者', '店舗オーナー', '削除済み管理者', 'Red', 'Blue', 'S', 'Color'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | テスト管理者 |


### Response

[Object: PUT /admin/member response](../schemas/put-admin-member.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name | string|null | 処理表示名 - Fake観察文字長 1〜7; 観察値 'テスト管理者', '副管理者', '店舗オーナー', '削除済み管理者', 'Red', 'Blue', 'S', 'Color'。 | Required | {"minLength":0,"maxLength":32} | テスト管理者 |
| sortNo | int|null | 表示順 - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |
| authority | int|null | 権限 - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 Fake観察数値 0〜1; 観察値 '1', '0'。 | Required | {"minimum":0,"maximum":9} | 1 |
| work | int|null | 稼働状態 - Work Masterの定数で定義: 0=NON_ACTIVE（非稼働、ログイン不可）, 1=ACTIVE（稼働、ログイン可能）。管理者メンバーの有効/無効を制御 | Required | {"minimum":0} |  |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |

#### Links

| Relation | URL |
|----------|-----|
| goMember | [<code>page://self/admin/member</code>](/admin/member.md) |
## DELETE
Wave 8: doDeleteMember — soft-delete (work=0). Idempotent
replay returns 200 with `alreadyDeleted=true`. Self-target
raises {@see InsufficientAuthorityException} → 403.

**ALPS**: `doDeleteMember`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID（入力） - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | test-admin |


### Response

[Object: DELETE /admin/member response](../schemas/delete-admin-member.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/member のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |
| alreadyDeleted | boolean | 既削除フラグ - /admin/member の処理状態を示す既削除フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  | false |

#### Links

| Relation | URL |
|----------|-----|
| goMemberList | [<code>page://self/admin/member-list</code>](/admin/member-list.md) |