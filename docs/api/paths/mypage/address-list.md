<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/address-list
EC-CUBE 配送先住所一覧 — collection endpoint (Pilot 16).

Two responsibilities at one URI per BEAR.Sunday REST convention:

  - GET  → goCustomerAddressList       (list the book — safe read)
  - POST → doCreateCustomerAddress     (add a new row)

Single-resource operations (PUT / DELETE) live at
`page://self/mypage/address` (see Address resource).

Failure mapping:
  - SemanticVariableException → 400 (parameter format invalid)
  - UnauthenticatedException  → 401 (no / stale session)

GET is safe and skips CSRF; POST is unsafe and validates CSRF.
customerId is NEVER taken from the request body — the Be Final
pulls it from CustomerSession (Pilot 5 F-2 / Pilot 8 lesson).




## GET
ALPS `goCustomerAddressList` に対応する GET 操作。

**ALPS**: `goCustomerAddressList`



### Request

_No parameters required_

### Response

[Object: GET /mypage/address-list response](../schemas/get-mypage-address-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| count | int|null | 件数 - /mypage/address-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| addresses | array|null | 住所一覧 - /mypage/address-list のレスポンスで扱う住所一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u4f4f\u6240","description":"/mypage/address-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f4f\u6240\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `addresses` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"kana02":{"type":["string","null"],"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$","title":"\u30e1\u30a4","description":"\u540d\u306e\u30ab\u30bf\u30ab\u30ca\u8aad\u307f\u3002\u5168\u89d2\u30ab\u30bf\u30ab\u30ca\u306e\u307f\u8a31\u53ef\uff08\u3072\u3089\u304c\u306a\u5165\u529b\u6642\u306f\u81ea\u52d5\u5909\u63db\uff09\u3002\u65e5\u672c\u306e\u6c0f\u540d\u5165\u529b\u306b\u7279\u6709\u306e\u8aad\u307f\u4eee\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c3; \u89b3\u5bdf\u5024 '\u30a2\u30ea\u30b9', '\u30cf\u30ca\u30b3', '\u30bf\u30ed\u30a6'; null 18/31\u3002","example":"\u30a2\u30ea\u30b9"},"phoneNumber":{"title":"\u96fb\u8a71\u756a\u53f7","description":"\u65e5\u672c\u306e\u96fb\u8a71\u756a\u53f7\u5f62\u5f0f\uff08\u30cf\u30a4\u30d5\u30f3\u533a\u5207\u308a\uff09 \u65e5\u672c\u306e\u96fb\u8a71\u756a\u53f7\u3002Fake corpus\u306f\u30cf\u30a4\u30d5\u30f3\u306a\u3057\u4e2d\u5fc3\u3060\u304c\u3001\u5165\u529b\u3067\u306f\u30cf\u30a4\u30d5\u30f3\u4ed8\u304d\u3082\u8a31\u5bb9\u3059\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c10; \u89b3\u5bdf\u5024 '0312345678', '0901234567', '0612345678'; null 18/33\u3002","type":["string","null"],"pattern":"^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$","minLength":10,"maxLength":13,"example":"0312345678"},"name02":{"type":["string","null"],"minLength":0,"maxLength":80,"title":"\u540d","description":"\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u30fb\u304a\u554f\u3044\u5408\u308f\u305b\u3067\u5171\u901a\u4f7f\u7528\u3055\u308c\u308b\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c3; \u89b3\u5bdf\u5024 '\u30a2\u30ea\u30b9', '\u592a\u90ce', '\u6b21\u90ce', '\u82b1\u5b50', '\u4e09\u90ce', '\u6e08'\u3002","example":"\u30a2\u30ea\u30b9"},"kana01":{"type":["string","null"],"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$","title":"\u30bb\u30a4","description":"\u59d3\u306e\u30ab\u30bf\u30ab\u30ca\u8aad\u307f\u3002\u5168\u89d2\u30ab\u30bf\u30ab\u30ca\u306e\u307f\u8a31\u53ef\uff08\u3072\u3089\u304c\u306a\u5165\u529b\u6642\u306f\u81ea\u52d5\u5909\u63db\uff09\u3002\u65e5\u672c\u306e\u6c0f\u540d\u5165\u529b\u306b\u7279\u6709\u306e\u8aad\u307f\u4eee\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c3; \u89b3\u5bdf\u5024 '\u30e4\u30de\u30c0', '\u30b5\u30c8\u30a6'; null 18/31\u3002","example":"\u30e4\u30de\u30c0"},"name01":{"type":["string","null"],"minLength":0,"maxLength":80,"title":"\u59d3","description":"\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u30fb\u304a\u554f\u3044\u5408\u308f\u305b\u3067\u5171\u901a\u4f7f\u7528\u3055\u308c\u308b\u59d3 Fake\u89b3\u5bdf\u6587\u5b57\u9577 2\u301c2; \u89b3\u5bdf\u5024 '\u9234\u6728', '\u5c71\u7530', '\u4f50\u85e4', '\u9ad8\u6a4b', '\u9000\u4f1a'\u3002","example":"\u9234\u6728"},"addr02":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u756a\u5730\u30fb\u5efa\u7269\u540d","description":"\u756a\u5730\u30fb\u30d3\u30eb\u540d\u30fb\u90e8\u5c4b\u756a\u53f7\u7b49\u306e\u8a73\u7d30\u4f4f\u6240 Fake\u89b3\u5bdf\u6587\u5b57\u9577 5\u301c8; \u89b3\u5bdf\u5024 '\u795e\u5bae\u524d1-1-1', '\u4e38\u306e\u51852-2-2', '\u6885\u75301-1-1', '1-2-3'; null 18/33\u3002","example":"\u795e\u5bae\u524d1-1-1"},"postalCode":{"title":"\u90f5\u4fbf\u756a\u53f7","description":"\u65e5\u672c\u306e\u90f5\u4fbf\u756a\u53f7\u3002\u30cf\u30a4\u30d5\u30f3\u306a\u30577\u6841\u307e\u305f\u306f\u30cf\u30a4\u30d5\u30f3\u4ed8\u304d8\u6841 \u65e5\u672c\u306e\u90f5\u4fbf\u756a\u53f7\u3002\u5165\u529b\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u30cf\u30a4\u30d5\u30f3\u6709\u7121\u3092\u3069\u3061\u3089\u3082\u53d7\u3051\u5165\u308c\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 7\u301c8; \u89b3\u5bdf\u5024 '1500001', '1000005', '5300001', '530-0001'; null 18/33\u3002","type":["string","null"],"pattern":"^\\d{3}-?\\d{4}$","example":"1500001"},"pref":{"title":"\u90fd\u9053\u5e9c\u770c","description":"\u65e5\u672c\u306e\u90fd\u9053\u5e9c\u770c\uff081=\u5317\u6d77\u9053\u301c47=\u6c96\u7e04\u770c\uff09\u3002\u4f4f\u6240\u306e\u6700\u4e0a\u4f4d\u533a\u5206\u3068\u3057\u3066\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u3067\u4f7f\u7528\u3002\u914d\u9001\u6599\u306e\u5730\u57df\u533a\u5206\uff08DeliveryFee\uff09\u3084\u7a0e\u7387\u306e\u5730\u57df\u8a2d\u5b9a\uff08TaxRule\uff09\u306b\u3082\u4f7f\u7528 \u90fd\u9053\u5e9c\u770cID\u3002\u4f4f\u6240\u30d5\u30a9\u30fc\u30e0\u306e\u672a\u9078\u629e\u72b6\u614b\u3067\u306f0\u3001\u78ba\u5b9a\u4f4f\u6240\u3067\u306f1\u301c47\u3092\u4f7f\u3046\u3002 Fake\u89b3\u5bdf\u6570\u5024 13\u301c27; \u89b3\u5bdf\u5024 '13', '27'; null 3/9\u3002","type":["integer","null"],"minimum":0,"maximum":47,"example":13},"addr01":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u5e02\u533a\u753a\u6751","description":"\u90fd\u9053\u5e9c\u770c\u3088\u308a\u4e0b\u4f4d\u306e\u5e02\u533a\u753a\u6751\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c7; \u89b3\u5bdf\u5024 '\u6e0b\u8c37\u533a', '\u5343\u4ee3\u7530\u533a', '\u5927\u962a\u5e02\u5317\u533a', '\u5927\u962a\u5e02\u5317\u533a\u6885\u7530'; null 18/33\u3002","example":"\u6e0b\u8c37\u533a"},"companyName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u4f1a\u793e\u540d","description":"\u6cd5\u4eba\u9867\u5ba2\u306e\u793e\u540d\u3002B2B\u53d6\u5f15\u3084\u30a4\u30f3\u30dc\u30a4\u30b9\u3067\u4f7f\u7528 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c11; \u89b3\u5bdf\u5024 'Acme Corp.', '\u682a\u5f0f\u4f1a\u793eEC-CUBE'; null 24/32\u3002","example":"Acme Corp."},"prefName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u90fd\u9053\u5e9c\u770c\u540d","description":"\u90fd\u9053\u5e9c\u770c\u306e\u8868\u793a\u540d\uff08mtb_pref.name\uff09\u3002pref\uff08mtb_pref \u3078\u306e\u6574\u6570 FK\uff09\u3092\u4f4f\u6240\u884c\u306b\u63cf\u753b\u3059\u308b\u305f\u3081\u306e\u8868\u793a\u7528\u6295\u5f71\u30d5\u30a3\u30fc\u30eb\u30c9\u3067\u3042\u308a\u3001storage \u306e\u771f\u306e leaf \u5217\u3067\u306f\u306a\u3044\u3002Phase 3 enrichment \u3067\u8ffd\u52a0\u3002SqlAddressStorage \u304c dtb_customer_address.pref_id \u2192 mtb_pref \u3092 JOIN \u3057\u3066\u5145\u586b\u3059\u308b\uff08\u69cb\u9020\u306e\u307f\u30c0\u30f3\u30d7\u3067\u306f mtb_pref \u306f\u7a7a\u306e\u305f\u3081\u3001\u672a\u30b7\u30fc\u30c9\u6642\u306f\u7a7a\u6587\u5b57\u306b degrade\uff09\u3002\u914d\u9001\u5148\u4f4f\u6240\u4e00\u89a7\u753b\u9762\uff08CustomerAddress\uff09\u304c pref \u306e\u6574\u6570 id \u3067\u306f\u306a\u304f\u90fd\u9053\u5e9c\u770c\u540d\u3092\u8868\u793a\u3059\u308b\u305f\u3081\u306b\u4f7f\u7528 Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c3; \u89b3\u5bdf\u5024 '\u6771\u4eac\u90fd', '\u5927\u962a\u5e9c'\u3002","example":"\u6771\u4eac\u90fd"},"addressId":{"type":["string","null"],"title":"\u914d\u9001\u5148\u4f4f\u6240ID","description":"dtb_customer_address.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e AddressEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_customer_address.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u6240\u6709\u8005\u306f customerId\u3001AUTHZ \u691c\u67fb\u306f CustomerAddressUpdated / CustomerAddressDeleted \u3067 getById \u2192 customerId \u4e00\u81f4\u78ba\u8a8d\u306e\u9806\u3067\u5b9f\u65bd Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'addr00000000000000000000000000a1'\u3002","example":"addr00000000000000000000000000a1","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| doCreateCustomerAddress | [<code>page://self/mypage/address-list</code>](/mypage/address-list.md) |
| doUpdateCustomerAddress | [<code>page://self/mypage/address</code>](/mypage/address.md) |
| doDeleteCustomerAddress | [<code>page://self/mypage/address</code>](/mypage/address.md) |
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |
## POST
ALPS `doCreateCustomerAddress` に対応する POST 操作。

**ALPS**: `doCreateCustomerAddress`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| name01 | string | 姓（入力） - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 |  | Required | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 鈴木 |
| name02 | string | 名（入力） - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 |  | Required | {"minLength":0,"maxLength":80,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| postalCode | string | 郵便番号（入力） - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1500001 |
| pref | int | 都道府県（入力） - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 13 |
| addr01 | string | 市区町村（入力） - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 渋谷区 |
| addr02 | string | 番地・建物名（入力） - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 神宮前1-1-1 |
| phoneNumber | string | 電話番号（入力） - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 |  | Required | {"minLength":0,"maxLength":13,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0312345678 |
| kana01 | string | セイ（入力） - 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'ヤマダ', 'サトウ'; null 18/31。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヤマダ |
| kana02 | string | メイ（入力） - 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'アリス', 'ハナコ', 'タロウ'; null 18/31。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | アリス |
| companyName | string | 会社名（入力） - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Acme Corp. |


### Response

[Object: POST /mypage/address-list response](../schemas/post-mypage-address-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| kana02 | string|null | メイ - 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'アリス', 'ハナコ', 'タロウ'; null 18/31。 | Optional | {"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$"} | アリス |
| phoneNumber | string|null | 電話番号 - 日本の電話番号形式（ハイフン区切り） 日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。 Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。 | Optional | {"pattern":"^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$","minLength":10,"maxLength":13} | 0312345678 |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| kana01 | string|null | セイ - 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名 Fake観察文字長 3〜3; 観察値 'ヤマダ', 'サトウ'; null 18/31。 | Optional | {"minLength":0,"maxLength":32,"pattern":"^[\u30a1-\u30f6\u30fc\u3000 ]*$"} | ヤマダ |
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| addr02 | string|null | 番地・建物名 - 番地・ビル名・部屋番号等の詳細住所 Fake観察文字長 5〜8; 観察値 '神宮前1-1-1', '丸の内2-2-2', '梅田1-1-1', '1-2-3'; null 18/33。 | Optional | {"minLength":0,"maxLength":32} | 神宮前1-1-1 |
| postalCode | string|null | 郵便番号 - 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁 日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。 Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。 | Optional | {"pattern":"^\\d{3}-?\\d{4}$"} | 1500001 |
| pref | int|null | 都道府県 - 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用 都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。 Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。 | Optional | {"minimum":0,"maximum":47} | 13 |
| addr01 | string|null | 市区町村 - 都道府県より下位の市区町村名 Fake観察文字長 3〜7; 観察値 '渋谷区', '千代田区', '大阪市北区', '大阪市北区梅田'; null 18/33。 | Required | {"minLength":0,"maxLength":32} | 渋谷区 |
| companyName | string|null | 会社名 - 法人顧客の社名。B2B取引やインボイスで使用 Fake観察文字長 10〜11; 観察値 'Acme Corp.', '株式会社EC-CUBE'; null 24/32。 | Optional | {"minLength":0,"maxLength":32} | Acme Corp. |
| addressId | string|null | 配送先住所ID - dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施 Fake観察文字長 32〜32; 観察値 'addr00000000000000000000000000a1'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | addr00000000000000000000000000a1 |

#### Links

| Relation | URL |
|----------|-----|
| goCustomerAddressList | [<code>page://self/mypage/address-list</code>](/mypage/address-list.md) |