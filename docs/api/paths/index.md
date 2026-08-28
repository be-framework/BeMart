<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /index
EC-CUBE goTop — トップページ (Wave 3H pure renderer).

Pure renderer: no Be Framework, no domain logic, no Reasons.
Anonymous-accessible (returns 200 regardless of session state).
Maps to `page://self/`.

The ALPS `#Top` resource lists 13 descriptors. In the production
frontend these are populated via Twig / EC-CUBE side queries (shop
message, news, recommended products, category nav, etc.). Wave 3H
deliberately limits this renderer to the link surface and a stub
`staticContent` shape — full data lookup (shop message, news,
recommended products, category navigation) is deferred and noted
inline as TODO until a dedicated Top aggregation lands.




## GET
EC-CUBE goTop — render the top page scaffolding.

**ALPS**: `goTop`



### Request

_No parameters required_

### Response

[Object: GET /index response](../schemas/get-index.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /index でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/index \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | string|null | フォーム送信先リンク - /index のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"minLength":0,"maxLength":255} |  |
| staticContent | string|null | 静的コンテンツ - /index で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"minLength":0,"maxLength":255} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/products</code>](/products.md) |
| goCart | [<code>page://self/cart</code>](/cart.md) |
| goContactForm | [<code>page://self/contact</code>](/contact.md) |
| goCustomerRegistration | [<code>page://self/entry</code>](/entry.md) |
| goLogin | [<code>page://self/login</code>](/login.md) |
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |
| goHelpAbout | [<code>page://self/help/about</code>](/help/about.md) |
| goHelpGuide | [<code>page://self/help/guide</code>](/help/guide.md) |
| goHelpAgreement | [<code>page://self/help/agreement</code>](/help/agreement.md) |
| goHelpPrivacy | [<code>page://self/help/privacy</code>](/help/privacy.md) |
| goHelpTradeLaw | [<code>page://self/help/trade-law</code>](/help/trade-law.md) |