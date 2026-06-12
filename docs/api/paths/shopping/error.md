<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/error
EC-CUBE goShoppingError — 購入エラー表示 (Wave 3H pure renderer).

Pure static page: no Be Framework, no domain logic, no Reasons.
Anonymous-or-authenticated (the checkout flow lands here regardless
of the originating identity). Maps to `page://self/shopping/error`.

In production this page is hit by redirect from doConfirmOrder /
doCheckout when stock / payment / session checks fail. Wave 3H
renders the surface only — the actual error reason is not threaded
through here yet (production EC-CUBE puts the message in a flashbag).

The ALPS `#ShoppingError` resource declares a single outbound
transition: goCart.




## GET
ALPS `goShoppingError` に対応する GET 操作。

**ALPS**: `goShoppingError`



### Request

_No parameters required_

### Response

[Object: GET /shopping/error response](../schemas/get-shopping-error.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /shopping/error でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/shopping/error \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | string|null | フォーム送信先リンク - /shopping/error のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"minLength":0,"maxLength":255} |  |
| staticContent | object|null | 静的コンテンツ - /shopping/error で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"properties":{"page":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30da\u30fc\u30b8\u8b58\u5225\u5b50","description":"/shopping/error \u306e\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u3067\u8868\u793a\u5bfe\u8c61\u30da\u30fc\u30b8\u3092\u8b58\u5225\u3059\u308b\u5024\u3002\u30da\u30fc\u30b8\u756a\u53f7\u3067\u306f\u306a\u3044\u3002"},"sections":{"type":["array","null"],"title":"\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u30bb\u30af\u30b7\u30e7\u30f3\u4e00\u89a7","description":"/shopping/error \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u6271\u3046\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u30bb\u30af\u30b7\u30e7\u30f3\u4e00\u89a7\u3002\u914d\u5217\u8981\u7d20\u306fALPS\u610f\u5473\u3068Fake\u89b3\u5bdf\u306b\u57fa\u3065\u304d\u3001\u56fa\u5b9a\u3067\u304d\u306a\u3044\u52d5\u7684\u5217\u306f\u4f8b\u5916\u7406\u7531\u3092\u53f0\u5e33\u5316\u3059\u308b\u3002","items":{"type":"object","title":"\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u30bb\u30af\u30b7\u30e7\u30f3","description":"/shopping/error \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u30bb\u30af\u30b7\u30e7\u30f3\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `sections` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/shopping/error \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"body":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u672c\u6587","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 53\u301c53; \u89b3\u5bdf\u5024 '\u8ca9\u58f2\u696d\u8005: \u682a\u5f0f\u4f1a\u793eEC-CUBE\\n\u6240\u5728\u5730: \u5927\u962a\u5e02\u5317\u533a\u6885\u75301-1-1\\n\u9023\u7d61\u5148: 06-1234-5678'\u3002","example":"\u8ca9\u58f2\u696d\u8005: \u682a\u5f0f\u4f1a\u793eEC-CUBE\n\u6240\u5728\u5730: \u5927\u962a\u5e02\u5317\u533a\u6885\u75301-1-1\n\u9023\u7d61\u5148: 06-1234-5678"},"content":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u672c\u6587","description":"/shopping/error \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u672c\u6587\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0},"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/shopping/error \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"}},"additionalProperties":false,"required":["page","sections","title"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| goCart | [<code>page://self/cart</code>](/cart.md) |