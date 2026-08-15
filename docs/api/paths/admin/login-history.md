<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/login-history
EC-CUBE goLoginHistoryList — 管理画面ログイン履歴 (Wave 8).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
{@see \UnauthorizedAdminAccessException} when the admin session is
empty (mapped to 403).

ALPS doc: 成功/失敗・IP アドレス・User-Agent を記録. Wave 8
surfaces timestamp / loginId / success / clientIp; the User-Agent
field is Phase 2 (the fake storage seeds a sample of the four
surfaced fields only).

Failure mapping:
  - SemanticVariableException             → 400 (limit format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET
ALPS `goLoginHistoryList` に対応する GET 操作。

**ALPS**: `goLoginHistoryList` - ログイン履歴を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| limit | int | 表示件数（入力） - /admin/login-history の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | 50 | Optional | {"default":50,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /admin/login-history response](../schemas/get-admin-login-history.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| entries | array|null | ログイン履歴一覧 - /admin/login-history のレスポンスで扱うログイン履歴一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30ed\u30b0\u30a4\u30f3\u5c65\u6b74","description":"/admin/login-history \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30ed\u30b0\u30a4\u30f3\u5c65\u6b74\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `entries` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"clientIp":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30af\u30e9\u30a4\u30a2\u30f3\u30c8IP","description":"\u30ed\u30b0\u30a4\u30f3\u8a66\u884c\u5143\u306eIP\u30a2\u30c9\u30ec\u30b9\u3002\u30bb\u30ad\u30e5\u30ea\u30c6\u30a3\u76e3\u67fb\u7528 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c12; \u89b3\u5bdf\u5024 '192.0.2.10', '203.0.113.45', '198.51.100.7', '203.0.113.99'\u3002","example":"192.0.2.10"},"success":{"type":["boolean","null"],"title":"\u51e6\u7406\u4e00\u89a7","description":"\u89b3\u5bdf\u5024 'true', 'false'\u3002","example":"true"},"timestamp":{"title":"\u51e6\u7406\u6d3e\u751f\u9805\u76ee","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 25\u301c25; \u89b3\u5bdf\u5024 '2026-05-19T09:12:34+09:00', '2026-05-18T22:08:01+09:00', '2026-05-18T18:55:12+09:00', '2026-05-18T08:00:00+09:00'\u3002","type":"string","example":"2026-05-19T09:12:34+09:00","pattern":"^\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"},"loginId":{"type":["string","null"],"title":"\u30ed\u30b0\u30a4\u30f3ID","description":"\u7ba1\u7406\u753b\u9762\u30ed\u30b0\u30a4\u30f3\u7528\u306eID\u3002\u4e00\u610f Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c13; \u89b3\u5bdf\u5024 'test-admin', 'shop-owner', 'deputy', 'deleted-admin', 'unknown-user'\u3002","example":"test-admin","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| count | int|null | 件数 - /admin/login-history のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goSecurity | [<code>page://self/admin/security</code>](/admin/security.md) |
| goMemberList | [<code>page://self/admin/member-list</code>](/admin/member-list.md) |