<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/file-manager
EC-CUBE ファイル管理 — admin CMS thin renderer (Phase 3 HTML).

PORT-side note: EC-CUBE's `FileController` is a `user_data/` file
manager (browse / upload / create-folder / move / delete / download
directly on the filesystem). It has no Be domain entity — the
filesystem IS its model. This resource is therefore a THIN HTML
RENDERER only — it carries no `be/src/` Becoming chain, authenticating
at the resource layer via {@see \AdminSession}.

The body renders the file manager in its **fresh / empty-directory**
state: `arrFileList` empty, `tplIsTopDir` true (at the user_data root),
`tplNowDir` empty, the JS tree-data variables empty arrays. The
`Content/file.twig` port omits the per-file rows (no `arrFileList`
data) and the directory-tree JS payload — enumerated as residuals.

FLAGGED: a future wave should model the user_data file manager (a
`be/src/` filesystem-backed storage + Get/Upload/Delete Inputs) so this
resource can list real files. The current renderer proves only the
page chrome + upload/new-folder form.




## GET
ALPS `goAdminContentFileManager` に対応する GET 操作。

**ALPS**: `goAdminContentFileManager`



### Request

_No parameters required_

### Response

[Object: GET /admin/content/file-manager response](../schemas/get-admin-content-file-manager.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| tplIsTopDir | boolean|null | テンプレートルートディレクトリ判定 - /admin/content/file-manager の処理状態を示すテンプレートルートディレクトリ判定。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| arrFileList | array|null | ファイル一覧 - /admin/content/file-manager のレスポンスで扱うファイル一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Optional | {"items":{"type":"string","title":"\u51e6\u7406\u884c","minLength":0,"maxLength":255,"description":"/admin/content/file-manager \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `arrFileList` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| errors | array|null | 検証エラー - /admin/content/file-manager のレスポンスで扱う検証エラー。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Optional | {"items":{"type":"string","title":"\u30a8\u30e9\u30fc\u30e1\u30c3\u30bb\u30fc\u30b8","minLength":0,"maxLength":1000,"description":"/admin/content/file-manager \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `errors` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| tplNowDir | string|null | 現在テンプレートディレクトリ - /admin/content/file-manager の画面表示に使う現在テンプレートディレクトリ。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Required | {"minLength":0,"maxLength":255} |  |
| tplParentDir | string|null | 親テンプレートディレクトリ - /admin/content/file-manager の画面表示に使う親テンプレートディレクトリ。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Required | {"minLength":0,"maxLength":255} |  |
