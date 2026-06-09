<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /forgot-complete
EC-CUBE goForgotComplete — パスワード再発行(メール送信完了) (Phase 3
pure renderer).

Pure renderer: no Be Framework, no domain logic, no Reasons — the same
shape as {@see \Products}. EC-CUBE shows `Forgot/complete.twig` after a
successful `doRequestPasswordReset`; it is a static confirmation page
with NO form (data-page recipe).

Anonymous-accessible (returns 200 regardless of session state). Maps
to `page://self/forgot-complete`. The companion {@see \ForgotPassword}
resource owns the actual reset-request domain logic; this resource
carries only the confirmation page's hypermedia surface — the page
itself is static text ported from EC-CUBE's template.

Why a dedicated renderer (not a branch of ForgotPassword): EC-CUBE's
`doRequestPasswordReset` controller renders `Forgot/index.twig` on the
request screen and `Forgot/complete.twig` on completion — two distinct
templates, two distinct pages. BeMart's `ForgotPassword::onPost` is the
anti-enumeration request endpoint (uniform 200); this resource is the
separate confirmation page so each template has a 1:1 resource.




## GET
EC-CUBE goForgotComplete — render the reset-mail-sent confirmation
page scaffolding.

**ALPS**: `doRequestPasswordReset`



### Request

_No parameters required_

### Response

[Object: GET /forgot-complete response](../schemas/get-forgot-complete.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /forgot-complete でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/forgot-complete \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30d1\u30b9\u30ef\u30fc\u30c9\u518d\u8a2d\u5b9a\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | string|null | フォーム送信先リンク - /forgot-complete のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"minLength":0,"maxLength":255} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| links | object|null | ALPS遷移リンク集合 - /forgot-complete のレスポンスから利用できるALPS遷移リンク集合。property名がrel、値が遷移先URIを表す。 | Optional | {"properties":{"goTop":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goTop` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"},"goLogin":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goLogin` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["goTop","goLogin"]} |  |

#### Links

| Relation | URL |
|----------|-----|
| goLogin | [<code>page://self/login</code>](/login.md) |
| goTop | [<code>page://self/</code>](/.md) |