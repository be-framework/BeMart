<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/page/page-list
EC-CUBE goPageList + doCreatePage — collection endpoint (Wave 9 CMS).

- GET  → goPageList    (admin lists CMS pages — safe read)
- POST → doCreatePage  (admin creates a new free page)




## GET
ALPS `goPageList` に対応する GET 操作。

**ALPS**: `goPageList` - ページ一覧を見る



### Request

_No parameters required_

### Response

[Object: GET /admin/page/page-list response](../schemas/get-admin-page-page-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| count | int|null | 件数 - /admin/page/page-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| pages | array|null | ページ一覧 - /admin/page/page-list のレスポンスで扱うページ一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30da\u30fc\u30b8","description":"/admin/page/page-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30da\u30fc\u30b8\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `pages` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"pageFileName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u30d5\u30a1\u30a4\u30eb\u540d","description":"\u30da\u30fc\u30b8\u306e\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u30d5\u30a1\u30a4\u30eb\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c7; \u89b3\u5bdf\u5024 'index', 'company', 'foo'\u3002","example":"index"},"pageUrl":{"title":"\u30da\u30fc\u30b8URL","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09 Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c8; \u89b3\u5bdf\u5024 'homepage', 'company', 'foo'\u3002","type":["string","null"],"format":"uri-reference","minLength":1,"maxLength":2048,"example":"homepage"},"pageId":{"type":["string","null"],"title":"\u30da\u30fc\u30b8ID","description":"dtb_page.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e PageEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f `pg-` \u30d7\u30ec\u30d5\u30a3\u30c3\u30af\u30b9\u4ed8\u304d\u306e\u82f1\u6570\u5b57\u3092\u751f\u6210\u3057\uff08\u30b7\u30fc\u30c9 `pg-homepage` \u3092\u542b\u3080\uff09\u3001SQL \u5b9f\u88c5\u306f dtb_page.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlPageStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (AdminPageFetched / PageUpdated / PageDeleted) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `pg-homepage` \u3084 `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62 Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c11; \u89b3\u5bdf\u5024 'pg-homepage', 'pg-company', 'pg-foo'\u3002","example":"pg-homepage","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"pageName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30da\u30fc\u30b8\u540d","description":"\u7ba1\u7406\u753b\u9762\u3067\u306e\u30da\u30fc\u30b8\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c6; \u89b3\u5bdf\u5024 '\u30db\u30fc\u30e0\u30da\u30fc\u30b8', '\u4f1a\u793e\u6848\u5185', 'Foo'\u3002","example":"\u30db\u30fc\u30e0\u30da\u30fc\u30b8"},"pageEditType":{"type":["integer","null"],"title":"\u30da\u30fc\u30b8\u7de8\u96c6\u533a\u5206","description":"\u30da\u30fc\u30b8\u7de8\u96c6\u30ec\u30d9\u30eb\u30020=EDIT_TYPE_USER\uff08\u30e6\u30fc\u30b6\u30fc\u4f5c\u6210\u3001\u5b8c\u5168\u7de8\u96c6/\u524a\u9664\u53ef\uff09, 1=EDIT_TYPE_PREVIEW\uff08\u30d7\u30ec\u30d3\u30e5\u30fc\uff09, 2=EDIT_TYPE_DEFAULT\uff08\u30b7\u30b9\u30c6\u30e0\u30da\u30fc\u30b8\u3001\u69cb\u9020\u30ed\u30c3\u30af\u30fb\u524a\u9664\u4e0d\u53ef\uff09, 3=EDIT_TYPE_DEFAULT_CONFIRM\uff08\u5185\u5bb9\u7de8\u96c6\u53ef\u80fd\u306a\u30b7\u30b9\u30c6\u30e0\u30da\u30fc\u30b8\u3001\u5229\u7528\u898f\u7d04\u7b49\uff09\u3002editType>=2\u306f\u524a\u9664\u4e0d\u53ef Fake\u89b3\u5bdf\u6570\u5024 0\u301c2; \u89b3\u5bdf\u5024 '0', '2'\u3002","example":0,"minimum":0}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreatePage | [<code>page://self/admin/page/page-list</code>](/admin/page/page-list.md) |
| goPage | [<code>page://self/admin/page/page</code>](/admin/page/page.md) |
| doUpdatePage | [<code>page://self/admin/page/page</code>](/admin/page/page.md) |
| doDeletePage | [<code>page://self/admin/page/page</code>](/admin/page/page.md) |
## POST
ALPS `doCreatePage` に対応する POST 操作。

**ALPS**: `doCreatePage` - ページを作成する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageName | string | ページ名（入力） - 管理画面でのページ表示名 Fake観察文字長 3〜6; 観察値 'ホームページ', '会社案内', 'Foo'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ホームページ |
| pageUrl | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） Fake観察文字長 3〜8; 観察値 'homepage', 'company', 'foo'。 |  | Required | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | homepage |
| pageFileName | string | テンプレートファイル名（入力） - ページのテンプレートファイル名 Fake観察文字長 3〜7; 観察値 'index', 'company', 'foo'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | index |


### Response

[Object: POST /admin/page/page-list response](../schemas/post-admin-page-page-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| pageFileName | string|null | テンプレートファイル名 - ページのテンプレートファイル名 Fake観察文字長 3〜7; 観察値 'index', 'company', 'foo'。 | Required | {"minLength":0,"maxLength":32} | index |
| pageUrl | string|null | ページURL - ページのURLパス（Symfonyルート名。例: homepage, product_list） Fake観察文字長 3〜8; 観察値 'homepage', 'company', 'foo'。 | Required | {"format":"uri-reference","minLength":1,"maxLength":2048} | homepage |
| pageId | string|null | ページID - dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 6〜11; 観察値 'pg-homepage', 'pg-company', 'pg-foo'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | pg-homepage |
| pageName | string|null | ページ名 - 管理画面でのページ表示名 Fake観察文字長 3〜6; 観察値 'ホームページ', '会社案内', 'Foo'。 | Required | {"minLength":0,"maxLength":32} | ホームページ |
| pageEditType | int|null | ページ編集区分 - ページ編集レベル。0=EDIT_TYPE_USER（ユーザー作成、完全編集/削除可）, 1=EDIT_TYPE_PREVIEW（プレビュー）, 2=EDIT_TYPE_DEFAULT（システムページ、構造ロック・削除不可）, 3=EDIT_TYPE_DEFAULT_CONFIRM（内容編集可能なシステムページ、利用規約等）。editType>=2は削除不可 Fake観察数値 0〜2; 観察値 '0', '2'。 | Required | {"minimum":0} | 0 |

#### Links

| Relation | URL |
|----------|-----|
| goPageList | [<code>page://self/admin/page/page-list</code>](/admin/page/page-list.md) |