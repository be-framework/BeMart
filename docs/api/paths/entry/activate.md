<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /entry/activate
EC-CUBE doActivateCustomer — provisional → active (Pilot 7).

The email-link UX in EC-CUBE is GET, but the operation has side
effects (status flip + secretKey clear) so the Be migration uses
onPost behind a one-button confirmation form. Both the secretKey
and a CSRF token are submitted; the secretKey is the per-customer
proof-of-email-receipt, and the CSRF token guards against drive-by
activation triggered by another origin.

Failure mapping:
  - SemanticVariableException    → 400 (secretKey malformed)
  - SecretKeyNotFoundException   → 404 (wrong key / expired / already used)

Idempotent: re-activating a customer is a no-op on the storage side
but still redirects from this resource — the caller cannot tell
"first activate" from "second activate", which is correct.

Phase 3 — `onGet` is the email-verification-complete LANDING SCREEN.
EC-CUBE's `doActivateCustomer` controller renders `Entry/activate.twig`
(the "本登録が完了しました" page) after the status flip; `onPost`
performs the flip. The `onGet` here is a THIN PURE RENDERER for that
landing screen — no Be Framework, no domain logic — added so Phase 3
has a page to render `Entry/activate.twig` against. The template's
optional `{% if qtyInCart %}` cart button is gated behind a cart-state
field the thin-renderer body does not carry; the common case (no
pending cart) renders only the top-page button, recorded as a residual
in the render test.




## GET
EC-CUBE doActivateCustomer landing — the email-verification-complete
screen. Pure renderer: the body surfaces only the screen shape + the
outbound `goTop` transition (ALPS `#CustomerActivationComplete`).

**ALPS**: `goTop`



### Request

_No parameters required_

### Response

[Object: GET /entry/activate response](../schemas/get-entry-activate.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /entry/activate でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/entry/activate \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | string|null | フォーム送信先リンク - /entry/activate のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"minLength":0,"maxLength":255} |  |
| staticContent | object|null | 静的コンテンツ - /entry/activate で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"properties":{"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/entry/activate \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"page":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30da\u30fc\u30b8\u8b58\u5225\u5b50","description":"/entry/activate \u306e\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u3067\u8868\u793a\u5bfe\u8c61\u30da\u30fc\u30b8\u3092\u8b58\u5225\u3059\u308b\u5024\u3002\u30da\u30fc\u30b8\u756a\u53f7\u3067\u306f\u306a\u3044\u3002"}},"additionalProperties":false,"required":["title","page"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| links | object|null | ALPS遷移リンク集合 - /entry/activate のレスポンスから利用できるALPS遷移リンク集合。property名がrel、値が遷移先URIを表す。 | Optional | {"properties":{"goTop":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goTop` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["goTop"]} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/</code>](/.md) |
## POST
ALPS `doActivateCustomer` に対応する POST 操作。

**ALPS**: `doActivateCustomer`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| secretKey | string | メール認証キー（入力） - 会員アカウントのメール認証トークン。/entry/activate/{secret_key}形式のURLに使用。暗号鍵やAPIシークレットではない。会員登録時にランダム生成 Fake観察文字長 34〜34; 観察値 'pending-secret-key-pilot7-2026abcd'; null 19/25。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | pending-secret-key-pilot7-2026abcd |


### Response

[Object: POST /entry/activate response](../schemas/post-entry-activate.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 会員メッセージ - /entry/activate のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |

#### Links

| Relation | URL |
|----------|-----|
| goLogin | [<code>page://self/login</code>](/login.md) |