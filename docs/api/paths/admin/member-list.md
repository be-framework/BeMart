<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/member-list
EC-CUBE goMemberList — 管理者一覧 (Wave 8, admin member grid).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
{@see \UnauthorizedAdminAccessException} when
{@see \MyVendor\BeMart\Be\Reason\Service\AdminSession}
reports no admin session; we map that to 403. Distinct from
customer-side 401 (admin and customer firewalls are parallel,
Wave 4 decision).

Failure mapping:
  - SemanticVariableException             → 400 (filter / paging format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Filter scope (Wave 8 first iteration):
  - nameKeyword  — substring on admin's display `name`
  - limit / offset — paginated grid

Hypermedia: links to per-admin detail (goMember), to the create
affordance (doCreateMember), and to the role-flip surface
(doUpdateAuthorityRole). The latter two are admin sub-resources
surfaced here per the bear-hypermedia discipline.




## GET
Wave 8: filter / paging knobs are admin-form input. Same taint
discipline as the customer-list and order-list variants.

**ALPS**: `goMemberList` - 管理者一覧を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| nameKeyword | string | 名前検索キーワード - /admin/member-list の検索条件。商品名・会員名・管理者名など、この一覧画面で名前として扱う表示名を部分一致検索する。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 鈴木 |
| limit | int | 表示件数（入力） - /admin/member-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | 50 | Optional | {"default":50,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| offset | int | 開始位置（入力） - /admin/member-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | 0 | Optional | {"default":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /admin/member-list response](../schemas/get-admin-member-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| filters | object|null | 検索条件 - /admin/member-list の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | Required | {"properties":{"nameKeyword":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u540d\u524d\u691c\u7d22\u30ad\u30fc\u30ef\u30fc\u30c9","description":"/admin/member-list \u306e\u691c\u7d22\u6761\u4ef6\u3002\u5546\u54c1\u540d\u30fb\u4f1a\u54e1\u540d\u30fb\u7ba1\u7406\u8005\u540d\u306a\u3069\u3001\u3053\u306e\u4e00\u89a7\u753b\u9762\u3067\u540d\u524d\u3068\u3057\u3066\u6271\u3046\u8868\u793a\u540d\u3092\u90e8\u5206\u4e00\u81f4\u691c\u7d22\u3059\u308b\u3002","example":"\u9234\u6728"},"offset":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u958b\u59cb\u4f4d\u7f6e","description":"/admin/member-list \u306e\u4e00\u89a7\u8868\u793a\u3092\u5236\u5fa1\u3059\u308b\u30da\u30fc\u30b8\u30f3\u30b0/\u691c\u7d22\u6761\u4ef6\u3002\u4ef6\u6570\u3001\u958b\u59cb\u4f4d\u7f6e\u3001\u4e26\u3073\u9806\u3001\u524d\u5f8c\u30ea\u30f3\u30af\u3092\u30af\u30e9\u30a4\u30a2\u30f3\u30c8\u304c\u518d\u73fe\u3059\u308b\u305f\u3081\u306e\u5024\u3002"},"limit":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u8868\u793a\u4ef6\u6570","description":"/admin/member-list \u306e\u4e00\u89a7\u8868\u793a\u3092\u5236\u5fa1\u3059\u308b\u30da\u30fc\u30b8\u30f3\u30b0/\u691c\u7d22\u6761\u4ef6\u3002\u4ef6\u6570\u3001\u958b\u59cb\u4f4d\u7f6e\u3001\u4e26\u3073\u9806\u3001\u524d\u5f8c\u30ea\u30f3\u30af\u3092\u30af\u30e9\u30a4\u30a2\u30f3\u30c8\u304c\u518d\u73fe\u3059\u308b\u305f\u3081\u306e\u5024\u3002"}},"additionalProperties":false,"required":["nameKeyword","offset","limit"]} |  |
| count | int|null | 件数 - /admin/member-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| csrfToken | string|null | CSRFトークン - 一覧画面から送信する削除フォーム用のCSRFトークン。 | Required | {"minLength":0,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]*$"} |  |
| members | array|null | 管理メンバー一覧 - /admin/member-list のレスポンスで扱う管理メンバー一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u7ba1\u7406\u30e1\u30f3\u30d0\u30fc","description":"/admin/member-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u7ba1\u7406\u30e1\u30f3\u30d0\u30fc\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `members` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"sortNo":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u8868\u793a\u9806","description":"\u4e00\u89a7\u306b\u304a\u3051\u308b\u4e26\u3073\u9806 Fake\u89b3\u5bdf\u6570\u5024 1\u301c20; \u89b3\u5bdf\u5024 '1', '3', '2', '4', '10', '20'\u3002","example":1},"loginId":{"type":["string","null"],"title":"\u30ed\u30b0\u30a4\u30f3ID","description":"\u7ba1\u7406\u753b\u9762\u30ed\u30b0\u30a4\u30f3\u7528\u306eID\u3002\u4e00\u610f Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c13; \u89b3\u5bdf\u5024 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'\u3002","example":"test-admin","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"authority":{"type":["integer","null"],"minimum":0,"maximum":9,"title":"\u6a29\u9650","description":"\u7ba1\u7406\u8005\u6a29\u9650\u30ec\u30d9\u30eb\u30020=\u30b7\u30b9\u30c6\u30e0\u7ba1\u7406\u8005\uff08\u6700\u9ad8\u6a29\u9650\u3001\u5168\u6a5f\u80fd\u30a2\u30af\u30bb\u30b9\u53ef\u80fd\uff09, 1=\u5e97\u8217\u30aa\u30fc\u30ca\u30fc\uff08\u5236\u9650\u3042\u308a\u3001denyUrl\u3067\u5236\u9650\u3055\u308c\u305fURL\u306b\u30a2\u30af\u30bb\u30b9\u4e0d\u53ef\uff09\u3002\u6570\u5024\u304c\u5c0f\u3055\u3044\u307b\u3069\u6a29\u9650\u304c\u9ad8\u3044\u3002AuthorityRole\u306eURL\u62d2\u5426\u30d1\u30bf\u30fc\u30f3\u3067\u30a2\u30af\u30bb\u30b9\u5236\u5fa1 Fake\u89b3\u5bdf\u6570\u5024 0\u301c1; \u89b3\u5bdf\u5024 '1', '0'\u3002","example":1},"work":{"type":["integer","null"],"title":"\u7a3c\u50cd\u72b6\u614b","description":"Work Master\u306e\u5b9a\u6570\u3067\u5b9a\u7fa9: 0=NON_ACTIVE\uff08\u975e\u7a3c\u50cd\u3001\u30ed\u30b0\u30a4\u30f3\u4e0d\u53ef\uff09, 1=ACTIVE\uff08\u7a3c\u50cd\u3001\u30ed\u30b0\u30a4\u30f3\u53ef\u80fd\uff09\u3002\u7ba1\u7406\u8005\u30e1\u30f3\u30d0\u30fc\u306e\u6709\u52b9/\u7121\u52b9\u3092\u5236\u5fa1","minimum":0},"adminId":{"type":["string","null"],"title":"\u7ba1\u7406\u8005ID","description":"\u7ba1\u7406\u8005\u30e1\u30f3\u30d0\u30fc\u3092\u8b58\u5225\u3059\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002Fake \u3068 SQL \u306e ID \u5f62\u72b6\u5dee\u3092\u96a0\u3059\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'ad000000000000000000000000000001', 'ad000000000000000000000000000002', 'ad000000000000000000000000000003', 'ad000000000000000000000000000004'\u3002","example":"ad000000000000000000000000000001","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goMember | [<code>page://self/admin/member</code>](/admin/member.md) |
| doCreateMember | [<code>page://self/admin/member</code>](/admin/member.md) |
| doUpdateMember | [<code>page://self/admin/member</code>](/admin/member.md) |
| doDeleteMember | [<code>page://self/admin/member</code>](/admin/member.md) |
| doUpdateAuthorityRole | [<code>page://self/admin/authority-role</code>](/admin/authority-role.md) |