<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /contact
EC-CUBE doSubmitContact — お問い合わせ送信 (Pilot 15).

Anonymous-accessible: no AUTHN, no AUTHZ. CSRF guard remains
(Slice 8 uniformity).

Phase 3 — HTML FORM page. The resource builds a {@see \ContactForm}
(Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
the HTML port renders real `<input>` / `<textarea>` via
`{{ form.input(...) }}`. VALIDATION AUTHORITY STAYS WITH the Be
Framework Becoming chain. The JSON contexts ignore `body['form']`.




## GET
EC-CUBE goContactForm — show the contact form scaffolding.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state). `csrfToken` carries the trusted reference
{@see \CsrfToken::$token} issues — the HTML port
renders it into the form's hidden `_token` input so the
subsequent POST passes CSRF validation.

**ALPS**: `goContactForm`



### Request

_No parameters required_

### Response

[Object: GET /contact response](../schemas/get-contact.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /contact でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/contact \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /contact のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"rel":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30ea\u30f3\u30af\u95a2\u4fc2","description":"/contact \u306e\u30ea\u30f3\u30af\u95a2\u4fc2\u540d\u3002ALPS descriptor id\u3068\u5bfe\u5fdc\u3057\u3001\u30af\u30e9\u30a4\u30a2\u30f3\u30c8\u304c\u9077\u79fb\u610f\u5473\u3092\u8b58\u5225\u3059\u308b\u3002"},"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/contact \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["rel","href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doSubmitContact | [<code>page://self/contact</code>](/contact.md) |
## POST
ALPS `doSubmitContact` に対応する POST 操作。

**ALPS**: `doSubmitContact`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| contactName01 | string | お問い合わせ姓（入力） - お問い合わせフォームの姓。内部的にはNameTypeのname01と同じ仕組み |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| contactName02 | string | お問い合わせ名（入力） - お問い合わせフォームの名。内部的にはNameTypeのname02と同じ仕組み |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| contactEmail | string | お問い合わせメール（入力） - お問い合わせフォームのメールアドレス |  | Required | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | alice@example.com |
| contactContents | string | お問い合わせ内容（入力） - お問い合わせフォームの本文 |  | Required | {"minLength":0,"maxLength":2000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /contact response](../schemas/post-contact.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| contactEmail | string|null | お問い合わせメール - お問い合わせフォームのメールアドレス | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| contactName02 | string|null | お問い合わせ名 - お問い合わせフォームの名。内部的にはNameTypeのname02と同じ仕組み | Required | {"minLength":0,"maxLength":255} |  |
| contactName01 | string|null | お問い合わせ姓 - お問い合わせフォームの姓。内部的にはNameTypeのname01と同じ仕組み | Required | {"minLength":0,"maxLength":255} |  |
| ticketId | string|null | 受付番号 - 問い合わせ送信後に発行される公開受付番号。問い合わせ本文の readback resource ではなく、完了状態の成立証拠として使う。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/</code>](/.md) |