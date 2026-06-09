<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/non-member
EC-CUBE goShoppingNonMember / doSubmitNonMember —非会員購入 (Wave 7W).

onGet  → goShoppingNonMember (safe form-info, anonymous-accessible)
  onPost → doSubmitNonMember   (unsafe, Direct, Semantic-validated)

Wave 7W is the FORM ENTRY only. The Final intentionally does NOT
persist a Cart / PreOrder under the guest's identity, and Pilot 5's
doCheckout still requires a customer session — so the preOrderId
returned by onPost will currently 403 on the subsequent checkout.
Closing that gap is Phase 2's job (dedicated GuestProfile entity +
non-member branch in CheckoutPrepared). See NonMemberSubmitted's
docblock for the full rationale.

Failure mapping (onPost):
  - CSRF invalid              → 403 (boundary)
  - SemanticVariableException → 400 (any guest field malformed)

Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) under
the same `Shopping/` directory — the same file-plus-sibling-directory
pattern as Mypage / Entry.

Phase 3 — HTML FORM page. `Shopping/nonmember.twig` renders the
guest-info inputs through the Symfony FormView; BeMart exposes a
{@see \NonMemberForm} (Ray.WebFormModule AbstractForm) as `body['form']`
so the HTML port renders real `<input>`s via `{{ form.input(...) }}`.
The form is a field-definition + renderer only — VALIDATION AUTHORITY
STAYS WITH the Be Becoming chain (doSubmitNonMember /
SubmitNonMemberInput). On a domain rejection the resource bridges the
verdict onto the form. JSON contexts ignore `body['form']`.




## GET
EC-CUBE goShoppingNonMember — show the guest-info entry form.

Pure form-info endpoint: no Be Framework involved, no domain
logic. Anonymous-accessible (returns 200 regardless of session
state). Fields mirror SubmitNonMemberInput. `csrfToken` body
field stays `null` for the same reason described on Login::onGet
— EventListener mirrors the Symfony token into the session for
the subsequent POST.

**ALPS**: `goShoppingNonMember`



### Request

_No parameters required_

### Response

[Object: GET /shopping/non-member response](../schemas/get-shopping-non-member.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| fields | array|null | 静的表示フィールド - /shopping/non-member でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/shopping/non-member \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /shopping/non-member のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/shopping/non-member \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doSubmitNonMember | [<code>page://self/shopping/non-member</code>](/shopping/non-member.md) |
| goCart | [<code>page://self/cart</code>](/cart.md) |
## POST
EC-CUBE doSubmitNonMember — accept guest shipping info and return
the synthesised preOrderId.

Phase B Slice 9: every guest form field is user-controlled input.
Declared as taint sources so Psalm can trace them downstream.
Semantic value objects format-validate but do not universally
escape — sinks downstream remain responsible for their own
defence (bound params, HTML escape on render).

**ALPS**: `doSubmitNonMember`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| name01 | string | 姓（入力） - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 |  | Required | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 鈴木 |
| name02 | string | 名（入力） - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 |  | Required | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| kana01 | string | セイ（入力） - 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'ヤマダ', 'サトウ'; null 18/31。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヤマダ |
| kana02 | string | メイ（入力） - 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'アリス', 'ハナコ', 'タロウ'; null 18/31。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| email | string | メールアドレス（入力） - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 |  | Required | {"minLength":0,"maxLength":254,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | alice@example.com |
| phoneNumber | string | 電話番号（入力） - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 |  | Required | {"minLength":0,"maxLength":13,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0312345678 |
| postalCode | string | 郵便番号（入力） - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1500001 |
| pref | int | 都道府県（入力） - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 13 |
| addr01 | string | 市区町村（入力） - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 渋谷区 |
| addr02 | string | 番地・建物名（入力） - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 神宮前1-1-1 |
| sessionPrefix | string | セッション接頭辞（入力） - 購入フローのカートキーを構成するセッションスコープの接頭辞。saleTypeId と組み合わせて販売種別ごとのカートを分離する。 Fake観察文字長 16〜23; 観察値 'session-prefix-1', 'session-checkout-pilot5'。 | session-prefix-1 | Optional | {"minLength":0,"maxLength":128,"default":"session-prefix-1","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | session-prefix-1 |


### Response

[Object: POST /shopping/non-member response](../schemas/post-shopping-non-member.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| email | string | メールアドレス - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| preOrderId | string|null | 仮注文ID - 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去 Fake観察文字長 40〜40; 観察値 'deadbeefcafe1234567890abcdef01234567890a', 'deadbeefcafe1234567890abcdef01234567890b', 'aaaa00000000000000000000000000000000aaaa', 'past00000000000000000000000000000000past', 'deadbeefcafe1234567890abcdef01234567890c', 'bbbb00000000000000000000000000000000bbbb', 'cccc00000000000000000000000000000000cccc', 'aceface0000000000000000000000000000a11ce'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | deadbeefcafe1234567890abcdef01234567890a |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |

#### Links

| Relation | URL |
|----------|-----|
| goShopping | [<code>page://self/shopping</code>](/shopping.md) |