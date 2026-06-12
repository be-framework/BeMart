<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/complete
EC-CUBE goShoppingComplete — ご注文完了 (Phase 3 — thin renderer).

EC-CUBE renders the order-complete screen (ALPS `#ShoppingComplete`)
after `doCheckout` succeeds. BeMart's `Shopping/Checkout::onPost`
(doCheckout) returns the `CheckoutCompleted` projection and sets
`Location: /shopping/complete?orderNo=...`; this resource backs that
URL and renders `Shopping/complete.twig`.

Phase 3 enrichment — the complete screen displays the freshly-placed
order's number (`#orderNo`) and the per-order complete message
(`#completeMessage`), the two data descriptors ALPS `#ShoppingComplete`
carries beyond the `goTop` / `goCart` transitions. EC-CUBE re-fetches
the `Order` row by id from the request; BeMart mirrors that: the
post-checkout redirect carries `orderNo` as a query parameter, and the
resource resolves the finalized-order header through
{@see \OrderQueryInterface::byOrderNo} (the same NEW(1)-onwards row
`CheckoutCompleted` registered). The body then carries `orderNo` so
the screen shows the real order number.

`completeMessage` is intentionally empty — EC-CUBE lets payment
plugins append to it via `appendCompleteMessage()`, but the finalized
order header carries no such field ({@see \CheckoutCompleted} produces
an empty string in Pilot 5 — a future Plugin Pilot wires it up). The
body surfaces it as a `''` default so the template's
complete-message block degrades to empty, matching EC-CUBE's
plugin-less render.

No Be Framework chain — the screen is a pure read of an
already-finalized order, no domain transition. An unknown `orderNo`
(or none supplied — a direct visit to the URL) still renders the
thank-you screen; the order-number block simply stays empty.

Maps to `page://self/shopping/complete`.




## GET
ALPS `goShoppingComplete` に対応する GET 操作。

**ALPS**: `goShoppingComplete`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: GET /shopping/complete response](../schemas/get-shopping-complete.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| completeMessage | string|null | 注文完了メッセージ - 注文完了画面に表示するメッセージ。主に決済プラグインが設定するカスタムメッセージ。複数プラグインからの利用を想定しappendCompleteMesssage()で追記する。HTML使用可 | Required | {"minLength":0,"maxLength":255} |  |
| staticContent | object|null | 静的コンテンツ - /shopping/complete で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"properties":{"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/shopping/complete \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"page":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30da\u30fc\u30b8\u8b58\u5225\u5b50","description":"/shopping/complete \u306e\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u3067\u8868\u793a\u5bfe\u8c61\u30da\u30fc\u30b8\u3092\u8b58\u5225\u3059\u308b\u5024\u3002\u30da\u30fc\u30b8\u756a\u53f7\u3067\u306f\u306a\u3044\u3002"}},"additionalProperties":false,"required":["title","page"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| links | object|null | ALPS遷移リンク集合 - /shopping/complete のレスポンスから利用できるALPS遷移リンク集合。property名がrel、値が遷移先URIを表す。 | Optional | {"properties":{"goTop":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goTop` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"},"goCart":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goCart` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"},"goMypage":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goMypage` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["goTop","goCart","goMypage"]} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/</code>](/.md) |
| goCart | [<code>page://self/cart</code>](/cart.md) |
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |