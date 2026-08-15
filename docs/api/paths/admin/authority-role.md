<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/authority-role
EC-CUBE doUpdateAuthorityRole — 権限ルール更新 (Wave 8).

GET  → render URL deny rules from `dtb_authority_role`.
  POST → either update URL deny rules or flip one admin member's
         persisted `authority` column.

EC-CUBE's HTML form posts `AuthorityRoles[*][Authority]` and
`AuthorityRoles[*][deny_url]` to edit URL deny rules. BeMart stores
those rows in `dtb_authority_role` and redirects back to the same
page for browser PRG/readback. The same ALPS transition also keeps
the legacy member role-flip shape (`loginId`, `authority`) because
existing member-management workflow uses this resource as a distinct
authorization-sensitive action.

Choice of POST (not PATCH): BEAR.Sunday's natural verb set is GET /
POST / PUT / DELETE — PATCH is not first-class. POST carries the
same browser-form shape as Wave 7 OrderStatus and Wave 6
DeleteCustomer (POST + CSRF + form body).

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (authority format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - AdminNotFoundException                → 404 (unknown loginId)
  - InsufficientAuthorityException        → 403 (priv-escalation refused)

Idempotency: when the supplied member `authority` matches the
persisted value, the projection carries `changed=false` and the
storage is untouched. URL deny rule updates replace the submitted
rule set and return the saved rows.

Mass-assignment safety: member role-flip accepts only `loginId`
(target) and `authority` (new value). URL deny rule edit accepts
only `AuthorityRoles[*][Authority]` and `AuthorityRoles[*][deny_url]`.




## GET
Phase 3 admin HTML Tier-2: render the authority-rule management
screen. The ALPS transition covers `doUpdateAuthorityRole`; EC-CUBE
uses the same resource to edit URL-deny rules stored in
`dtb_authority_role`.

**ALPS**: `doUpdateAuthorityRole` - 権限ルールを更新する



### Request

_No parameters required_

### Response

[Object: GET /admin/authority-role response](../schemas/get-admin-authority-role.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| authorityOptions | array|null | 権限選択肢一覧 - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 | Required | {"items":{"type":["object","null"],"title":"\u6a29\u9650\u9078\u629e\u80a2","description":"/admin/authority-role \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6a29\u9650\u9078\u629e\u80a2\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `authorityOptions` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"label":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u30e9\u30d9\u30eb","description":"/admin/authority-role \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u8868\u793a\u30e9\u30d9\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"id":{"type":["string","integer","null"],"title":"ID","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c32; \u89b3\u5bdf\u5024 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'\u3002","example":"ad000000000000000000000000000001","minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |
| rules | array|null | 権限ルール一覧 - /admin/authority-role のレスポンスで扱う権限ルール一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u6a29\u9650\u30eb\u30fc\u30eb","description":"/admin/authority-role \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6a29\u9650\u30eb\u30fc\u30eb\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `rules` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"authority":{"type":["integer","null"],"minimum":0,"maximum":9,"title":"\u6a29\u9650","description":"\u7ba1\u7406\u8005\u6a29\u9650\u30ec\u30d9\u30eb\u30020=\u30b7\u30b9\u30c6\u30e0\u7ba1\u7406\u8005\uff08\u6700\u9ad8\u6a29\u9650\u3001\u5168\u6a5f\u80fd\u30a2\u30af\u30bb\u30b9\u53ef\u80fd\uff09, 1=\u5e97\u8217\u30aa\u30fc\u30ca\u30fc\uff08\u5236\u9650\u3042\u308a\u3001denyUrl\u3067\u5236\u9650\u3055\u308c\u305fURL\u306b\u30a2\u30af\u30bb\u30b9\u4e0d\u53ef\uff09\u3002\u6570\u5024\u304c\u5c0f\u3055\u3044\u307b\u3069\u6a29\u9650\u304c\u9ad8\u3044\u3002AuthorityRole\u306eURL\u62d2\u5426\u30d1\u30bf\u30fc\u30f3\u3067\u30a2\u30af\u30bb\u30b9\u5236\u5fa1 Fake\u89b3\u5bdf\u6570\u5024 0\u301c1; \u89b3\u5bdf\u5024 '1', '0'\u3002","example":1},"denyUrl":{"title":"\u62d2\u5426URL","description":"\u30a2\u30af\u30bb\u30b9\u3092\u62d2\u5426\u3059\u308b\u7ba1\u7406\u753b\u9762URL\u30d1\u30bf\u30fc\u30f3\u3002authority=1\uff08\u5e97\u8217\u30aa\u30fc\u30ca\u30fc\uff09\u306b\u5bfe\u3057\u3066\u9069\u7528","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |
| csrfToken | string |  | Required | {"$ref":"#/$defs/csrfToken"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateAuthorityRole | [<code>page://self/admin/authority-role</code>](/admin/authority-role.md) |
| goMemberList | [<code>page://self/admin/member-list</code>](/admin/member-list.md) |
## POST
Wave 8: browser form input for URL deny rules. The primary HTML
shape carries CSRF at the request boundary plus
`AuthorityRoles[*][Authority]` / `AuthorityRoles[*][deny_url]`.

The legacy member role-flip shape (`loginId`, `authority`)
remains supported for member workflow.

**ALPS**: `doUpdateAuthorityRole` - 権限ルールを更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| loginId | string | ログインID（入力） - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | test-admin |
| authority | int | 権限（入力） - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 Fake観察数値 0〜1; 観察値 '1', '0'。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |
| AuthorityRoles | array | 権限ルール入力一覧 - 権限管理画面のURL拒否ルール行。HTML form の AuthorityRoles[*][Authority] / deny_url を受け取る。 | array () | Optional | {"items":{"type":["object","null"],"properties":{"Authority":{"type":["integer","string","null"],"title":"\u6a29\u9650"},"authority":{"type":["integer","string","null"],"title":"\u6a29\u9650"},"deny_url":{"type":["string","null"],"title":"\u62d2\u5426URL"},"denyUrl":{"type":["string","null"],"title":"\u62d2\u5426URL"}},"additionalProperties":true},"minItems":0} |  |


### Response

[Object: POST /admin/authority-role response](../schemas/post-admin-authority-role.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/authority-role のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| adminId | string|null | 管理者ID - 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。 Fake観察文字長 32〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'。 | Optional | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | ad000000000000000000000000000001 |
| loginId | string|null | ログインID - 管理画面ログイン用のID。一意 Fake観察文字長 6〜13; 観察値 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'。 | Optional | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | test-admin |
| previousAuthority | int|null | 処理派生項目 - /admin/authority-role の処理文脈から派生した処理派生項目。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Optional | {"minimum":0} |  |
| authority | int|null | 権限 - 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御 Fake観察数値 0〜1; 観察値 '1', '0'。 | Optional | {"minimum":0,"maximum":9} | 1 |
| changed | boolean | 処理状態フラグ - Fake観察数値 1〜1; 観察値 '1'。 | Optional |  | 1 |
| transitionId | string |  | Optional | {"$ref":"#/$defs/transitionId"} |  |
| count | int |  | Optional | {"$ref":"#/$defs/nonNegativeInteger"} |  |
| rules | array|null | 権限ルール一覧 - 更新後の権限URL拒否ルール一覧。 | Optional | {"items":{"type":["object","null"],"properties":{"authority":{"$ref":"#/properties/authority"},"denyUrl":{"$ref":"#/$defs/uriReference"}},"additionalProperties":false},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goMember | [<code>page://self/admin/member</code>](/admin/member.md) |
| goMemberList | [<code>page://self/admin/member-list</code>](/admin/member-list.md) |
| goLoginHistoryList | [<code>page://self/admin/login-history</code>](/admin/login-history.md) |