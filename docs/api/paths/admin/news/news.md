<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/news/news
EC-CUBE goNews + doUpdateNews + doDeleteNews — single-row endpoint
(Wave 9).

Phase 3 — HTML FORM page (admin pilot). `onGet` exposes an
{@see \AdminNewsForm} (Ray.WebFormModule AbstractForm) as `body['form']`
pre-filled with the persisted row so the admin edit page can render
real `<input>`s via `{{ form.input(...) }}`. The form is a
field-definition + renderer only — VALIDATION AUTHORITY STAYS WITH the
Be Framework Becoming chain. The JSON contexts (`app`, `prod`, `test`)
ignore `body['form']`; the resource tests assert key-wise on `body`
and are unaffected. FormFactory is self-sufficient (no Ray.Di bindings
needed).




## GET
ALPS `goNews` に対応する GET 操作。

**ALPS**: `goNews`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| newsId | string | ニュースID（入力） - dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形 Fake観察文字長 10〜10; 観察値 'nw-welcome'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | nw-welcome |


### Response

[Object: GET /admin/news/news response](../schemas/get-admin-news-news.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| newsUrl | string|null | 外部URL - 外部リンクURL。設定時はニュース本文の代わりにこのURLへ遷移 | Required | {"format":"uri-reference","minLength":1,"maxLength":2048} | /products |
| linkMethod | boolean|null | 新規ウィンドウで開く - 外部URLのリンク開き方（boolean）。false=同一ウィンドウ, true=新規ウィンドウ（target="_blank"）。テンプレートでtarget属性の出力制御に使用 観察値 'false'。 | Required |  | false |
| newsId | string|null | ニュースID - dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形 Fake観察文字長 10〜10; 観察値 'nw-welcome'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | nw-welcome |
| newsTitle | string|null | ニュースタイトル - ニュース記事の見出し Fake観察文字長 4〜4; 観察値 'ようこそ'。 | Required | {"minLength":0,"maxLength":32} | ようこそ |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| publishDate | string|null | 公開日 - ニュースの公開日時。フロントの表示順を制御 Fake観察文字長 25〜25; 観察値 '2026-01-01T00:00:00+09:00'。 | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2026-01-01T00:00:00+09:00 |
| newsDescription | string|null | ニュース本文 - ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり Fake観察文字長 14〜14; 観察値 'EC-CUBE へようこそ。'。 | Required | {"minLength":0,"maxLength":2000} | EC-CUBE へようこそ。 |

#### Links

| Relation | URL |
|----------|-----|
| goNewsList | [<code>page://self/admin/news/news-list</code>](/admin/news/news-list.md) |
| doCreateNews | [<code>page://self/admin/news/news-list</code>](/admin/news/news-list.md) |
| doUpdateNews | [<code>page://self/admin/news/news</code>](/admin/news/news.md) |
## PUT
ALPS `doUpdateNews` に対応する PUT 操作。

**ALPS**: `doUpdateNews`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| newsId | string | ニュースID（入力） - dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形 Fake観察文字長 10〜10; 観察値 'nw-welcome'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | nw-welcome |
| newsTitle | string | ニュースタイトル（入力） - ニュース記事の見出し Fake観察文字長 4〜4; 観察値 'ようこそ'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ようこそ |
| newsDescription | string | ニュース本文（入力） - ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり Fake観察文字長 14〜14; 観察値 'EC-CUBE へようこそ。'。 |  | Optional | {"minLength":0,"maxLength":2000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | EC-CUBE へようこそ。 |
| newsUrl | string | 外部URL（入力） - 外部リンクURL。設定時はニュース本文の代わりにこのURLへ遷移 |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | /products |
| publishDate | string | 公開日（入力） - ニュースの公開日時。フロントの表示順を制御 Fake観察文字長 25〜25; 観察値 '2026-01-01T00:00:00+09:00'。 |  | Optional | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2026-01-01T00:00:00+09:00 |
| linkMethod | bool | 新規ウィンドウで開く（入力） - 外部URLのリンク開き方（boolean）。false=同一ウィンドウ, true=新規ウィンドウ（target="_blank"）。テンプレートでtarget属性の出力制御に使用 観察値 'false'。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | false |


### Response

[Object: PUT /admin/news/news response](../schemas/put-admin-news-news.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| newsUrl | string|null | 外部URL - 外部リンクURL。設定時はニュース本文の代わりにこのURLへ遷移 | Required | {"format":"uri-reference","minLength":1,"maxLength":2048} | /products |
| linkMethod | boolean|null | 新規ウィンドウで開く - 外部URLのリンク開き方（boolean）。false=同一ウィンドウ, true=新規ウィンドウ（target="_blank"）。テンプレートでtarget属性の出力制御に使用 観察値 'false'。 | Required |  | false |
| newsId | string|null | ニュースID - dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形 Fake観察文字長 10〜10; 観察値 'nw-welcome'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | nw-welcome |
| newsTitle | string|null | ニュースタイトル - ニュース記事の見出し Fake観察文字長 4〜4; 観察値 'ようこそ'。 | Required | {"minLength":0,"maxLength":32} | ようこそ |
| publishDate | string|null | 公開日 - ニュースの公開日時。フロントの表示順を制御 Fake観察文字長 25〜25; 観察値 '2026-01-01T00:00:00+09:00'。 | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2026-01-01T00:00:00+09:00 |
| newsDescription | string|null | ニュース本文 - ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり Fake観察文字長 14〜14; 観察値 'EC-CUBE へようこそ。'。 | Required | {"minLength":0,"maxLength":2000} | EC-CUBE へようこそ。 |

#### Links

| Relation | URL |
|----------|-----|
| goNews | [<code>page://self/admin/news/news</code>](/admin/news/news.md) |
## DELETE
ALPS `doDeleteNews` に対応する DELETE 操作。

**ALPS**: `doDeleteNews`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| newsId | string | ニュースID（入力） - dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形 Fake観察文字長 10〜10; 観察値 'nw-welcome'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | nw-welcome |


### Response

[Object: DELETE /admin/news/news response](../schemas/delete-admin-news-news.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| newsId | string|null | ニュースID - dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形 Fake観察文字長 10〜10; 観察値 'nw-welcome'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | nw-welcome |

#### Links

| Relation | URL |
|----------|-----|
| goNewsList | [<code>page://self/admin/news/news-list</code>](/admin/news/news-list.md) |
| goPageList | [<code>page://self/admin/page/page-list</code>](/admin/page/page-list.md) |