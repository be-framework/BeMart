<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/customer-list
EC-CUBE goCustomerList — 会員一覧 (Wave 5, admin filter search).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
UnauthorizedAdminAccessException when AdminSession reports
no admin session, which we map to 403. Distinct from customer-side
401 (Unauthenticated): admin and customer firewalls are parallel and
a logged-in customer is NOT logged-in-as-admin (Wave 4 decision).

Failure mapping:
  - SemanticVariableException             → 400 (filter format invalid)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Filter scope (Wave 5 first iteration):
  - nameKeyword  — substring on name01/name02/companyName
  - emailKeyword — substring on email
  - limit        — caps the result set (default 50)
  Phase 2 will add phoneNumber, dateRange, purchaseAmount filters.

Hypermedia: links to the per-customer admin detail and the admin
customer actions that are available from the list surface.




## GET
Wave 5: filter fields are admin-form input — taint discipline
mirrors the Wave 4 admin login.

**ALPS**: `goCustomerList`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| nameKeyword | string | 名前検索キーワード - /admin/customer-list の検索条件。商品名・会員名・管理者名など、この一覧画面で名前として扱う表示名を部分一致検索する。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 鈴木 |
| emailKeyword | string | メール検索キーワード - /admin/customer-list の検索条件。会員または管理者のメールアドレス/ログイン識別子を部分一致検索する。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | carol |
| limit | int | 表示件数（入力） - /admin/customer-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | 50 | Optional | {"default":50,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /admin/customer-list response](../schemas/get-admin-customer-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| filters | object|null | 検索条件 - /admin/customer-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | Required | {"properties":{"nameKeyword":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u540d\u524d\u691c\u7d22\u30ad\u30fc\u30ef\u30fc\u30c9","description":"/admin/customer-list \u306e\u691c\u7d22\u6761\u4ef6\u3002\u5546\u54c1\u540d\u30fb\u4f1a\u54e1\u540d\u30fb\u7ba1\u7406\u8005\u540d\u306a\u3069\u3001\u3053\u306e\u4e00\u89a7\u753b\u9762\u3067\u540d\u524d\u3068\u3057\u3066\u6271\u3046\u8868\u793a\u540d\u3092\u90e8\u5206\u4e00\u81f4\u691c\u7d22\u3059\u308b\u3002","example":"\u9234\u6728"},"emailKeyword":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30e1\u30fc\u30eb\u691c\u7d22\u30ad\u30fc\u30ef\u30fc\u30c9","description":"/admin/customer-list \u306e\u691c\u7d22\u6761\u4ef6\u3002\u4f1a\u54e1\u307e\u305f\u306f\u7ba1\u7406\u8005\u306e\u30e1\u30fc\u30eb\u30a2\u30c9\u30ec\u30b9/\u30ed\u30b0\u30a4\u30f3\u8b58\u5225\u5b50\u3092\u90e8\u5206\u4e00\u81f4\u691c\u7d22\u3059\u308b\u3002","example":"carol"}},"additionalProperties":false,"required":["nameKeyword","emailKeyword"]} |  |
| count | int|null | 件数 - /admin/customer-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| searchForm | object|array|null | 検索フォーム - /admin/customer-list のレスポンスで保持するフォーム文脈。Aura/WebForm由来の内部構造は別境界の責務で、ここではResource上の役割を示す。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| customers | array|null | 会員一覧 - /admin/customer-list のレスポンスで扱う会員一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u4f1a\u54e1\u6982\u8981","description":"/admin/customer-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u6982\u8981\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `customers` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name01":{"type":["string","null"],"minLength":0,"maxLength":80,"title":"\u59d3","description":"\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u30fb\u304a\u554f\u3044\u5408\u308f\u305b\u3067\u5171\u901a\u4f7f\u7528\u3055\u308c\u308b\u59d3 Fake\u89b3\u5bdf\u6587\u5b57\u9577 2\u301c2; \u89b3\u5bdf\u5024 '\u9234\u6728', '\u5c71\u7530', '\u4f50\u85e4', '\u9ad8\u6a4b', '\u9000\u4f1a'\u3002","example":"\u9234\u6728"},"customerId":{"type":["string","null"],"title":"\u4f1a\u54e1ID","description":"dtb_customer.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e Entity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u30de\u30b9\u30a2\u30b5\u30a4\u30f3\u30e1\u30f3\u30c8\u9632\u6b62\u306e\u305f\u3081\u3001Session/AuthZ \u7d4c\u7531\u3067\u8aad\u307f\u51fa\u3057\u3001\u30ea\u30af\u30a8\u30b9\u30c8\u672c\u6587\u304b\u3089\u306f\u53d7\u3051\u53d6\u3089\u306a\u3044\uff09\u3002Favorite / Cart / Order \u306e\u6240\u6709\u8005\u30ad\u30fc\u3068\u3057\u3066\u6a2a\u65ad\u4f7f\u7528 Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c32; \u89b3\u5bdf\u5024 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'\u3002","example":"customer-001","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"customerStatus":{"title":"\u4f1a\u54e1\u30b9\u30c6\u30fc\u30bf\u30b9","description":"1=\u4eee\u4f1a\u54e1\uff08\u30e1\u30fc\u30eb\u672a\u8a8d\u8a3c\uff09, 2=\u672c\u4f1a\u54e1\uff08\u8a8d\u8a3c\u6e08\u307f\uff09, 3=\u9000\u4f1a\u3002\u9000\u4f1a\u6642\u306f\u30e1\u30fc\u30eb\u30a2\u30c9\u30ec\u30b9\u304c\u7121\u52b9\u5316\u3055\u308c\u308b Fake\u89b3\u5bdf\u6570\u5024 1\u301c2; \u89b3\u5bdf\u5024 '2', '1'\u3002","type":"integer","enum":[1,2,3],"example":2},"email":{"title":"\u30e1\u30fc\u30eb\u30a2\u30c9\u30ec\u30b9","description":"\u4f1a\u54e1\u306e\u30ed\u30b0\u30a4\u30f3ID\u3092\u517c\u306d\u308b\u3002\u6709\u52b9\u4f1a\u54e1\u9593\u3067\u4e00\u610f \u30ed\u30b0\u30a4\u30f3ID\u3092\u517c\u306d\u308b\u30e1\u30fc\u30eb\u30a2\u30c9\u30ec\u30b9\u3002\u4f1a\u54e1\u767b\u9332\u30fb\u30ed\u30b0\u30a4\u30f3\u30fb\u901a\u77e5\u3067\u5171\u901a\u306b\u4f7f\u3046\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 15\u301c58; \u89b3\u5bdf\u5024 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'\u3002","type":"string","format":"email","minLength":3,"maxLength":254,"example":"alice@example.com"},"postalCode":{"title":"\u90f5\u4fbf\u756a\u53f7","description":"\u65e5\u672c\u306e\u90f5\u4fbf\u756a\u53f7\u3002\u30cf\u30a4\u30d5\u30f3\u306a\u30577\u6841\u307e\u305f\u306f\u30cf\u30a4\u30d5\u30f3\u4ed8\u304d8\u6841 \u65e5\u672c\u306e\u90f5\u4fbf\u756a\u53f7\u3002\u5165\u529b\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u30cf\u30a4\u30d5\u30f3\u6709\u7121\u3092\u3069\u3061\u3089\u3082\u53d7\u3051\u5165\u308c\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 7\u301c8; \u89b3\u5bdf\u5024 '1500001', '1000005', '5300001', '530-0001'; null 18/33\u3002","type":["string","null"],"pattern":"^\\d{3}-?\\d{4}$","example":"1500001"},"name02":{"type":["string","null"],"minLength":0,"maxLength":80,"title":"\u540d","description":"\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u30fb\u304a\u554f\u3044\u5408\u308f\u305b\u3067\u5171\u901a\u4f7f\u7528\u3055\u308c\u308b\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c3; \u89b3\u5bdf\u5024 '\u30a2\u30ea\u30b9', '\u592a\u90ce', '\u6b21\u90ce', '\u82b1\u5b50', '\u4e09\u90ce', '\u6e08'\u3002","example":"\u30a2\u30ea\u30b9"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goCustomer | [<code>page://self/admin/customer</code>](/admin/customer.md) |
| doCreateCustomer | [<code>page://self/admin/create-customer</code>](/admin/create-customer.md) |
| doDeleteCustomer | [<code>page://self/admin/delete-customer</code>](/admin/delete-customer.md) |
| doResendActivationMail | [<code>page://self/admin/customer/resend-activation-mail</code>](/admin/customer/resend-activation-mail.md) |