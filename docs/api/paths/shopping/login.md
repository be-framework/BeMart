<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/login
EC-CUBE goShoppingLogin — 購入ログイン (Wave 3H pure renderer).

Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
Anonymous-accessible (this IS the unauthenticated branch of the
checkout flow). Maps to `page://self/shopping/login`.

Reached when an anonymous visitor hits `goShopping`. Three exits:
member login (doLogin), customer registration (goCustomerRegistration),
or non-member purchase (goShoppingNonMember). The page itself carries a
login form (the same `CustomerLoginType` shape as the standalone
`goLogin` page) plus the guest-purchase link.

Phase 3 — HTML FORM page. `Shopping/login.twig` renders the login
inputs through the Symfony FormView; BeMart exposes a {@see \LoginForm}
(the same AbstractForm the standalone Login page uses — EC-CUBE's
`shopping_login` route reuses `CustomerLoginType`) as `body['form']`
so the HTML port renders real `<input>`s via `{{ form.input(...) }}`.
The form is a field-definition + renderer only.

Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) and
`Shopping\NonMember.php` (Wave 7W).




## GET
ALPS `goShoppingLogin` に対応する GET 操作。

**ALPS**: `goShoppingLogin`



### Request

_No parameters required_

### Response

[Object: GET /shopping/login response](../schemas/get-shopping-login.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /shopping/login でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/shopping/login \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | string|null | フォーム送信先リンク - /shopping/login のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"minLength":0,"maxLength":255} |  |
| staticContent | string|null | 静的コンテンツ - /shopping/login で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"minLength":0,"maxLength":255} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| links | object|null | ALPS遷移リンク集合 - /shopping/login のレスポンスから利用できるALPS遷移リンク集合。property名がrel、値が遷移先URIを表す。 | Optional | {"properties":{"doLogin":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `doLogin` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"},"goCustomerRegistration":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goCustomerRegistration` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"},"goShoppingNonMember":{"$ref":"#/$defs/uriReference","title":"ALPS\u9077\u79fb\u30ea\u30f3\u30af","description":"ALPS `goShoppingNonMember` \u9077\u79fb\u306e\u30ea\u30f3\u30af\u5148URI\u3002property\u540d\u304crel\u3001\u5024\u304chref\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["doLogin","goCustomerRegistration","goShoppingNonMember"]} |  |

#### Links

| Relation | URL |
|----------|-----|
| doLogin | [<code>page://self/login</code>](/login.md) |
| goCustomerRegistration | [<code>page://self/entry</code>](/entry.md) |
| goShoppingNonMember | [<code>page://self/shopping/non-member</code>](/shopping/non-member.md) |