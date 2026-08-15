<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/export-order-pdf
EC-CUBE goExportOrderPdf — 帳票PDFをエクスポートする.

GET /admin/order/export-order-pdf?orderNos[]=…

The Resource only normalizes EC-CUBE's `ids[]`/legacy `orderNo`
request shape, then calls the Be Final. TCPDF/FPDI rendering is kept
behind OrderPdfCompatibilityInterface.

Failure mapping:
  - SemanticVariableException             → 400 (orderNos format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404 (unknown orderNo; all-or-nothing)




## GET
ALPS `goExportOrderPdf` に対応する GET 操作。

**ALPS**: `goExportOrderPdf` - 帳票PDFをエクスポートする



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNos |  | 注文番号一覧（入力） - /admin/order/export-order-pdf のレスポンスで扱う注文番号一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | array () | Optional | {"items":{"type":"string","title":"\u6ce8\u6587\u756a\u53f7","minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","description":"/admin/order/export-order-pdf \u306e\u5165\u529b\u306b\u542b\u307e\u308c\u308bCSV\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orderNos` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: GET /admin/order/export-order-pdf response](../schemas/get-admin-order-export-order-pdf.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | CSVメッセージ - /admin/order/export-order-pdf のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| orderNos | array | 注文番号一覧 - /admin/order/export-order-pdf のレスポンスで扱う注文番号一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"items":{"type":"string","title":"\u6ce8\u6587\u756a\u53f7","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9._:-]+$","description":"/admin/order/export-order-pdf \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308bCSV\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orderNos` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| pdf | string | 輸送ペイロード - PDFバイナリをJSON検査可能なUTF-8文字列へ正規化した表現。内容自体のPDF構造は別境界の責務。 | Required | {"minLength":0,"maxLength":5000000,"$comment":"PDF\u30d0\u30a4\u30ca\u30ea\u306fResource\u3067UTF-8\u6b63\u898f\u5316\u3057\u3066\u304b\u3089JSON Schema\u691c\u67fb\u3059\u308b\u3002PDF\u5185\u90e8\u69cb\u9020\u306fOrderPdfCompatibility\u5883\u754c\u306e\u8cac\u52d9\u3002"} |  |
| size | int|null | ペイロードサイズ - /admin/order/export-order-pdf のレスポンスで運ぶペイロードサイズ。CSV/PDF/ログ等の内部形式は専用境界で扱い、JSON Schemaでは輸送上の型とサイズを契約する。 | Required | {"minimum":0} |  |
| fileName | string | ファイル名 - 商品画像のファイル名 Fake観察文字長 12〜15; 観察値 'Mail/order.twig', 'Mail/entry.twig', 'sample-a.jpg', 'sample-b.jpg'。 | Required | {"minLength":1,"maxLength":255} | Mail/order.twig |

#### Links

| Relation | URL |
|----------|-----|
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |
| goExportOrder | [<code>page://self/admin/order/export-order</code>](/admin/order/export-order.md) |