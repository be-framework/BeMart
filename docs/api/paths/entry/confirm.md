<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /entry/confirm
EC-CUBE goCustomerRegistrationConfirm — 新規会員登録(確認)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE's registration flow has
an `Entry::confirm` step between `goCustomerRegistration` (the form)
and `doRegisterCustomer` (the commit): the customer reviews the entered
values before the account is created. EC-CUBE keeps that step on the
SAME controller action, branching on the `mode` POST param
(`confirm` / `complete` / `back`); BeMart's Pilot 4 collapsed the flow
— `Entry::onGet` (form) hands straight to `Entry::onPost`
(doRegisterCustomer) — so no `CustomerRegistrationConfirm` resource
existed. Phase 3 needs a page to render `Entry/confirm.twig` against,
so this THIN PURE RENDERER is added: no Be Framework, no domain logic,
no Reasons.

FORM page (the form-page recipe — see var/templates/README.md). The
confirm screen re-shows the entered registration values as plain text
AND carries them forward as HIDDEN inputs so the final submit re-posts
the full payload to `doRegisterCustomer`. The resource exposes an
{@see \EntryConfirmForm} (every registration field declared `hidden`)
as `body['form']` so the HTML port renders the hidden carriers via
`{{ form.input(...) }}`.

FOLLOW-UP — the confirm screen's plain-text value cells show the
entered registration data; a pure `onGet` renderer has no submitted
values, so those cells render empty (the body carries no field
values). Threading the submitted payload into the confirm step — a
real `mode=confirm` POST handler that re-shows the values before the
commit — is a dedicated vertical slice, tracked in the enrichment
backlog. Recorded as a MISSING BODY FIELD residual in the render test.

Maps to `page://self/entry/confirm`. The submit target is
doRegisterCustomer (`page://self/entry`).




## GET
ALPS `goCustomerRegistrationConfirm` に対応する GET 操作。

**ALPS**: `goCustomerRegistrationConfirm`



### Request

_No parameters required_

### Response

[Object: GET /entry/confirm response](../schemas/get-entry-confirm.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /entry/confirm でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/entry/confirm \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /entry/confirm のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/entry/confirm \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| staticContent | object|null | 静的コンテンツ - /entry/confirm で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"properties":{"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/entry/confirm \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"page":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30da\u30fc\u30b8\u8b58\u5225\u5b50","description":"/entry/confirm \u306e\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u3067\u8868\u793a\u5bfe\u8c61\u30da\u30fc\u30b8\u3092\u8b58\u5225\u3059\u308b\u5024\u3002\u30da\u30fc\u30b8\u756a\u53f7\u3067\u306f\u306a\u3044\u3002"}},"additionalProperties":false,"required":["title","page"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| doRegisterCustomer | [<code>page://self/entry</code>](/entry.md) |
| goTop | [<code>page://self/</code>](/.md) |