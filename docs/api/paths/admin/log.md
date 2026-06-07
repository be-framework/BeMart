<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/log
EC-CUBE ログ表示 — Setting/System Tier-2.

Thin GET renderer for `Setting/System/log.twig`. EC-CUBE reads log
files from Symfony's log directory; BeMart has no ALPS transition for
log inspection, so this resource exposes a stable form and a bounded
sample body without adding a file-read mutation surface.




## GET
ALPS `goAdminLog` に対応する GET 操作。

**ALPS**: `goAdminLog`



### Request

_No parameters required_

### Response

[Object: GET /admin/log response](../schemas/get-admin-log.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| log | array|null | ログ行一覧 - /admin/log のレスポンスで扱うログ行一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"string","title":"\u30ed\u30b0\u884c","minLength":0,"maxLength":255,"description":"/admin/log \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30ed\u30b0\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `log` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
