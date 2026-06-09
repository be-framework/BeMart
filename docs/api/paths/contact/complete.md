<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /contact/complete
EC-CUBE goContactComplete — お問い合わせ(完了)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
`Contact/complete.twig` after a successful `doSubmitContact`. BeMart's
`Contact::onPost` (Pilot 15) returns the `ContactSubmitted` projection
and the ALPS surface declares the single transition `goTop` — no
`ContactComplete` SCREEN resource ever existed. Phase 3 needs a page
to render `Contact/complete.twig` against, so this THIN PURE RENDERER
is added: no Be Framework, no domain logic, no Reasons. It exposes
only the complete-screen shape + the outbound `goTop` transition.

`Contact/complete.twig` is mostly static (the completion message +
a top-page button), but the Resource also carries the public receipt
`ticketId` issued by doSubmitContact.

Maps to `page://self/contact/complete`.




## GET
ALPS `goContactComplete` に対応する GET 操作。

**ALPS**: `goContactComplete`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| ticketId | string | 受付番号（入力） - 問い合わせ送信後に発行される公開受付番号。問い合わせ本文の readback resource ではなく、完了状態の成立証拠として使う。 |  | Optional | {"default":"","minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /contact/complete response](../schemas/get-contact-complete.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /contact/complete でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/contact/complete \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | string|null | フォーム送信先リンク - /contact/complete のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"minLength":0,"maxLength":255} |  |
| staticContent | object|null | 静的コンテンツ - /contact/complete で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"properties":{"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/contact/complete \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"page":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30da\u30fc\u30b8\u8b58\u5225\u5b50","description":"/contact/complete \u306e\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u3067\u8868\u793a\u5bfe\u8c61\u30da\u30fc\u30b8\u3092\u8b58\u5225\u3059\u308b\u5024\u3002\u30da\u30fc\u30b8\u756a\u53f7\u3067\u306f\u306a\u3044\u3002"}},"additionalProperties":false,"required":["title","page"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| ticketId | string|null | 受付番号 - 問い合わせ送信後に発行される公開受付番号。問い合わせ本文の readback resource ではなく、完了状態の成立証拠として使う。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} |  |
| links | object|null | ALPS遷移リンク集合 - /contact/complete のレスポンスから利用できるALPS遷移リンク集合。property名がrel、値が遷移先URIを表す。 | Optional | {"properties":{"goTop":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goTop` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["goTop"]} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/</code>](/.md) |