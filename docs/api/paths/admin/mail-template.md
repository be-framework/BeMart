<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/mail-template
EC-CUBE doUpdateMailTemplate + goMailTemplateList — メールテンプレート
(Wave 8 + Wave 9).

- GET  → goMailTemplateList (collection list, safe, admin, Wave 9ι)
  - POST → doUpdateMailTemplate (per-id update, idempotent, Wave 8ε)

The migration scope only covers UPDATE of the subject — creating a
new template requires setting the underlying file_name, which is
Phase 2 scope. 厳密移植 alignment: dtb_mail_template has no body
columns (mail bodies are on-disk Twig files), so the former
mailBody / mailHtmlBody inputs were dropped.

Failure mapping:
  - Invalid CSRF                          → 403 (POST only)
  - SemanticVariableException             → 400 (subject format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - MailTemplateNotFoundException         → 404 (unknown id)




## GET
Wave 9ι: goMailTemplateList — admin lists every mail template.

**ALPS**: `goMailTemplateList`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| mailTemplateId | int | メールテンプレートID |  | Optional |  |  |


### Response

[Object: GET /admin/mail-template response](../schemas/get-admin-mail-template.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| mailTemplates | array|null | メールテンプレート一覧 - /admin/mail-template のレスポンスで扱うメールテンプレート一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"items":{"type":["object","null"],"title":"\u30e1\u30fc\u30eb\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8","description":"/admin/mail-template \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30e1\u30fc\u30eb\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `mailTemplates` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"mailTemplateId":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u30e1\u30fc\u30eb\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8ID","description":"dtb_mail_template.id\uff08int unsigned AUTO_INCREMENT\uff09\u306e\u6b63\u306e\u6574\u6570\u4e3b\u30ad\u30fc\u3002doUpdateMailTemplate \u306e\u5fc5\u9808\u5165\u529b\u3067\u3001\u65e2\u5b58\u884c\u3092\u6307\u3059\u5fc5\u8981\u304c\u3042\u308b\u3002SqlMailTemplateStorage \u306f findById / update \u3092\u3053\u306e id \u3067\u5f15\u304d\u3001\u672a\u77e5 id \u306f MailTemplateNotFoundException\uff08404\uff09\u3002\u65b0\u898f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u4f5c\u6210\u30d5\u30ed\u30fc\u306f file_name \u8a2d\u5b9a\u3068 Twig \u30d5\u30a1\u30a4\u30eb\u66f8\u304d\u51fa\u3057\u3092\u4f34\u3046\u305f\u3081 Phase 2 scope \u3067\u3042\u308a\u3001ID \u751f\u6210\u5668\u306f\u5b58\u5728\u3057\u306a\u3044\uff08\u66f4\u65b0\u5c02\u7528\u5951\u7d04\uff09 Fake\u89b3\u5bdf\u6570\u5024 1\u301c2; \u89b3\u5bdf\u5024 '1', '2'\u3002","example":1,"$comment":"EC-CUBE\u5074\u306e\u63a1\u756aID\u3068\u3057\u3066\u6271\u3046\u3002"},"mailTemplateName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u540d","description":"\u30e1\u30fc\u30eb\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 7\u301c9; \u89b3\u5bdf\u5024 '\u6ce8\u6587\u5b8c\u4e86\u30e1\u30fc\u30eb', '\u4f1a\u54e1\u767b\u9332\u5b8c\u4e86\u30e1\u30fc\u30eb'\u3002","example":"\u6ce8\u6587\u5b8c\u4e86\u30e1\u30fc\u30eb"},"fileName":{"type":["string","null"],"minLength":1,"maxLength":255,"title":"\u30d5\u30a1\u30a4\u30eb\u540d","description":"\u5546\u54c1\u753b\u50cf\u306e\u30d5\u30a1\u30a4\u30eb\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c15; \u89b3\u5bdf\u5024 'Mail/order.twig', 'Mail/entry.twig', 'sample-a.jpg', 'sample-b.jpg'\u3002","example":"Mail/order.twig"},"mailSubject":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30e1\u30fc\u30eb\u4ef6\u540d","description":"\u30e1\u30fc\u30eb\u306e\u4ef6\u540d\u3002\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u5909\u6570\u3092\u542b\u3080\u5834\u5408\u3042\u308a Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c13; \u89b3\u5bdf\u5024 '\u3054\u6ce8\u6587\u3042\u308a\u304c\u3068\u3046\u3054\u3056\u3044\u307e\u3059'\u3002","example":"\u3054\u6ce8\u6587\u3042\u308a\u304c\u3068\u3046\u3054\u3056\u3044\u307e\u3059"},"isDeletable":{"type":["boolean","null"],"title":"\u524a\u9664\u53ef\u80fd\u30d5\u30e9\u30b0","description":"dtb_mail_template.deletable \u7531\u6765\u306e\u524a\u9664\u53ef\u80fd\u30d5\u30e9\u30b0\u3002\u7ba1\u7406\u753b\u9762\u306e\u524a\u9664 affordance \u8868\u793a\u306b\u4f7f\u3046\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| id | string|int|null | ID - Fake観察文字長 13〜32; 観察値 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} | ad000000000000000000000000000001 |
| count | int|null | 件数 - /admin/mail-template のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| csrfToken | string |  | Required | {"$ref":"#/$defs/csrfToken"} |  |
| Mail | object|null | メールテンプレート詳細 - /admin/mail-template のレスポンスで扱うメールテンプレート詳細。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Optional | {"properties":{"id":{"type":["string","integer","null"],"title":"ID","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c32; \u89b3\u5bdf\u5024 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'\u3002","example":"ad000000000000000000000000000001","minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"},"isDeletable":{"type":["boolean","null"],"title":"\u524a\u9664\u53ef\u80fd\u30d5\u30e9\u30b0","description":"/admin/mail-template \u306e\u51e6\u7406\u72b6\u614b\u3092\u793a\u3059\u524a\u9664\u53ef\u80fd\u30d5\u30e9\u30b0\u3002\u753b\u9762\u8868\u793a\u3084\u51aa\u7b49\u51e6\u7406\u7d50\u679c\u306e\u5206\u5c90\u306b\u4f7f\u3046\u771f\u507d\u5024\u3002"},"file_name":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30d5\u30a1\u30a4\u30eb\u540d","description":"/admin/mail-template \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u904b\u3076\u30d5\u30a1\u30a4\u30eb\u540d\u3002CSV/PDF/\u30ed\u30b0\u7b49\u306e\u5185\u90e8\u5f62\u5f0f\u306f\u5c02\u7528\u5883\u754c\u3067\u6271\u3044\u3001JSON Schema\u3067\u306f\u8f38\u9001\u4e0a\u306e\u578b\u3068\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002"}},"additionalProperties":false,"required":["id","isDeletable","file_name"]} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateMailTemplate | [<code>page://self/admin/mail-template/create</code>](/admin/mail-template/create.md) |
| doUpdateMailTemplate | [<code>page://self/admin/mail-template</code>](/admin/mail-template.md) |
| goOrderMail | [<code>page://self/admin/order/send-mail</code>](/admin/order/send-mail.md) |
| goPaymentList | [<code>page://self/admin/payment/payment-list</code>](/admin/payment/payment-list.md) |
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |
## POST
ALPS `doUpdateMailTemplate` に対応する POST 操作。

**ALPS**: `doUpdateMailTemplate`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| mailTemplateId | int | メールテンプレートID（入力） - dtb_mail_template.id（int unsigned AUTO_INCREMENT）の正の整数主キー。doUpdateMailTemplate の必須入力で、既存行を指す必要がある。SqlMailTemplateStorage は findById / update をこの id で引き、未知 id は MailTemplateNotFoundException（404）。新規テンプレート作成フローは file_name 設定と Twig ファイル書き出しを伴うため Phase 2 scope であり、ID 生成器は存在しない（更新専用契約） Fake観察数値 1〜2; 観察値 '1', '2'。 |  | Required | {"$comment":"\u30e1\u30fc\u30eb\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","minLength":0,"maxLength":128} | 1 |
| mailSubject | string | メール件名（入力） - メールの件名。テンプレート変数を含む場合あり Fake観察文字長 13〜13; 観察値 'ご注文ありがとうございます'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ご注文ありがとうございます |
| mail_subject | string | メール件名（HTMLフォーム入力） - 管理画面HTMLフォームの `mail_subject` field。Resourceでは `mailSubject` と同じ doUpdateMailTemplate の件名入力として扱う。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"HTTP form boundary uses snake_case field names; Resource normalizes this to the ALPS mailSubject input."} | ご注文ありがとうございます |


### Response

[Object: POST /admin/mail-template response](../schemas/post-admin-mail-template.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| mailTemplateId | int|null | メールテンプレートID - dtb_mail_template.id（int unsigned AUTO_INCREMENT）の正の整数主キー。doUpdateMailTemplate の必須入力で、既存行を指す必要がある。SqlMailTemplateStorage は findById / update をこの id で引き、未知 id は MailTemplateNotFoundException（404）。新規テンプレート作成フローは file_name 設定と Twig ファイル書き出しを伴うため Phase 2 scope であり、ID 生成器は存在しない（更新専用契約） Fake観察数値 1〜2; 観察値 '1', '2'。 | Required | {"minimum":0,"maximum":2147483647,"$comment":"EC-CUBE\u5074\u306e\u63a1\u756aID\u3068\u3057\u3066\u6271\u3046\u3002"} | 1 |
| mailTemplateName | string|null | テンプレート名 - メールテンプレートの表示名 Fake観察文字長 7〜9; 観察値 '注文完了メール', '会員登録完了メール'。 | Required | {"minLength":0,"maxLength":32} | 注文完了メール |
| fileName | string|null | ファイル名 - 商品画像のファイル名 Fake観察文字長 12〜15; 観察値 'Mail/order.twig', 'Mail/entry.twig', 'sample-a.jpg', 'sample-b.jpg'。 | Required | {"minLength":1,"maxLength":255} | Mail/order.twig |
| changed | boolean|null | 処理状態フラグ - Fake観察数値 1〜1; 観察値 '1'。 | Required |  | 1 |
| mailSubject | string|null | メール件名 - メールの件名。テンプレート変数を含む場合あり Fake観察文字長 13〜13; 観察値 'ご注文ありがとうございます'。 | Required | {"minLength":0,"maxLength":32} | ご注文ありがとうございます |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/admin</code>](/admin.md) |
| goOrderMail | [<code>page://self/admin/order/send-mail</code>](/admin/order/send-mail.md) |
| doDeleteMailTemplate | [<code>page://self/admin/mail-template</code>](/admin/mail-template.md) |
## DELETE
EC-CUBE doDeleteMailTemplate.

The mail-template master still needs a full file-backed delete in
a later adapter pass; this surface is intentionally narrow and
concrete so the legacy route reaches a Resource with CSRF/AUTHZ
semantics instead of generic ActionRedirect.

**ALPS**: `doDeleteMailTemplate`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| mailTemplateId | int | メールテンプレートID（入力） - dtb_mail_template.id（int unsigned AUTO_INCREMENT）の正の整数主キー。doUpdateMailTemplate の必須入力で、既存行を指す必要がある。SqlMailTemplateStorage は findById / update をこの id で引き、未知 id は MailTemplateNotFoundException（404）。新規テンプレート作成フローは file_name 設定と Twig ファイル書き出しを伴うため Phase 2 scope であり、ID 生成器は存在しない（更新専用契約） Fake観察数値 1〜2; 観察値 '1', '2'。 |  | Required | {"$comment":"\u30e1\u30fc\u30eb\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","minLength":0,"maxLength":128} | 1 |


### Response

[Object: DELETE /admin/mail-template response](../schemas/delete-admin-mail-template.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| mailTemplateId | int|null | メールテンプレートID - dtb_mail_template.id（int unsigned AUTO_INCREMENT）の正の整数主キー。doUpdateMailTemplate の必須入力で、既存行を指す必要がある。SqlMailTemplateStorage は findById / update をこの id で引き、未知 id は MailTemplateNotFoundException（404）。新規テンプレート作成フローは file_name 設定と Twig ファイル書き出しを伴うため Phase 2 scope であり、ID 生成器は存在しない（更新専用契約） Fake観察数値 1〜2; 観察値 '1', '2'。 | Required | {"minimum":0,"maximum":2147483647,"$comment":"EC-CUBE\u5074\u306e\u63a1\u756aID\u3068\u3057\u3066\u6271\u3046\u3002"} | 1 |
| mailTemplateName | string|null | テンプレート名 - メールテンプレートの表示名 Fake観察文字長 7〜9; 観察値 '注文完了メール', '会員登録完了メール'。 | Required | {"minLength":0,"maxLength":32} | 注文完了メール |
| fileName | string|null | ファイル名 - 商品画像のファイル名 Fake観察文字長 12〜15; 観察値 'Mail/order.twig', 'Mail/entry.twig', 'sample-a.jpg', 'sample-b.jpg'。 | Required | {"minLength":1,"maxLength":255} | Mail/order.twig |
| message | string|null | 処理メッセージ - /admin/mail-template のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| goMailTemplateList | [<code>page://self/admin/mail-template</code>](/admin/mail-template.md) |