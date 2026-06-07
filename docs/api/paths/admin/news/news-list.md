<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/news/news-list
EC-CUBE goNewsList + doCreateNews — collection endpoint (Wave 9).






## GET
ALPS `goNewsList` に対応する GET 操作。

**ALPS**: `goNewsList`



### Request

_No parameters required_

### Response

[Object: GET /admin/news/news-list response](../schemas/get-admin-news-news-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| news | array|null | ニュース一覧 - /admin/news/news-list のレスポンスで扱うニュース一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30cb\u30e5\u30fc\u30b9","description":"/admin/news/news-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30cb\u30e5\u30fc\u30b9\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `news` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"newsUrl":{"title":"\u5916\u90e8URL","description":"\u5916\u90e8\u30ea\u30f3\u30afURL\u3002\u8a2d\u5b9a\u6642\u306f\u30cb\u30e5\u30fc\u30b9\u672c\u6587\u306e\u4ee3\u308f\u308a\u306b\u3053\u306eURL\u3078\u9077\u79fb","type":["string","null"],"format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"linkMethod":{"type":["boolean","null"],"title":"\u65b0\u898f\u30a6\u30a3\u30f3\u30c9\u30a6\u3067\u958b\u304f","description":"\u5916\u90e8URL\u306e\u30ea\u30f3\u30af\u958b\u304d\u65b9\uff08boolean\uff09\u3002false=\u540c\u4e00\u30a6\u30a3\u30f3\u30c9\u30a6, true=\u65b0\u898f\u30a6\u30a3\u30f3\u30c9\u30a6\uff08target=\"_blank\"\uff09\u3002\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u3067target\u5c5e\u6027\u306e\u51fa\u529b\u5236\u5fa1\u306b\u4f7f\u7528 \u89b3\u5bdf\u5024 'false'\u3002","example":"false"},"newsId":{"type":["string","null"],"title":"\u30cb\u30e5\u30fc\u30b9ID","description":"dtb_news.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e NewsEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f `nw-` \u30d7\u30ec\u30d5\u30a3\u30c3\u30af\u30b9\u4ed8\u304d\u306e\u82f1\u6570\u5b57\u3092\u751f\u6210\u3057\uff08\u30b7\u30fc\u30c9 `nw-welcome` \u3092\u542b\u3080\uff09\u3001SQL \u5b9f\u88c5\u306f dtb_news.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlNewsStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (AdminNewsFetched / NewsUpdated / NewsDeleted) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `nw-welcome` \u3084 `nonexistent` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c10; \u89b3\u5bdf\u5024 'nw-welcome'\u3002","example":"nw-welcome","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"newsTitle":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30cb\u30e5\u30fc\u30b9\u30bf\u30a4\u30c8\u30eb","description":"\u30cb\u30e5\u30fc\u30b9\u8a18\u4e8b\u306e\u898b\u51fa\u3057 Fake\u89b3\u5bdf\u6587\u5b57\u9577 4\u301c4; \u89b3\u5bdf\u5024 '\u3088\u3046\u3053\u305d'\u3002","example":"\u3088\u3046\u3053\u305d"},"publishDate":{"title":"\u516c\u958b\u65e5","description":"\u30cb\u30e5\u30fc\u30b9\u306e\u516c\u958b\u65e5\u6642\u3002\u30d5\u30ed\u30f3\u30c8\u306e\u8868\u793a\u9806\u3092\u5236\u5fa1 Fake\u89b3\u5bdf\u6587\u5b57\u9577 25\u301c25; \u89b3\u5bdf\u5024 '2026-01-01T00:00:00+09:00'\u3002","type":["string","null"],"example":"2026-01-01T00:00:00+09:00","$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"},"newsDescription":{"type":["string","null"],"minLength":0,"maxLength":2000,"title":"\u30cb\u30e5\u30fc\u30b9\u672c\u6587","description":"\u30cb\u30e5\u30fc\u30b9\u8a18\u4e8b\u306e\u672c\u6587\u3002HTML\u5165\u529b\u53ef\u80fd\u3067HTMLPurifier\u306b\u3088\u308b\u6d44\u5316\u3042\u308a Fake\u89b3\u5bdf\u6587\u5b57\u9577 14\u301c14; \u89b3\u5bdf\u5024 'EC-CUBE \u3078\u3088\u3046\u3053\u305d\u3002'\u3002","example":"EC-CUBE \u3078\u3088\u3046\u3053\u305d\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| count | int|null | 件数 - /admin/news/news-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateNews | [<code>page://self/admin/news/news-list</code>](/admin/news/news-list.md) |
| goNews | [<code>page://self/admin/news/news</code>](/admin/news/news.md) |
| doUpdateNews | [<code>page://self/admin/news/news</code>](/admin/news/news.md) |
| doDeleteNews | [<code>page://self/admin/news/news</code>](/admin/news/news.md) |
## POST
ALPS `doCreateNews` に対応する POST 操作。

**ALPS**: `doCreateNews`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| newsTitle | string | ニュースタイトル（入力） - ニュース記事の見出し Fake観察文字長 4〜4; 観察値 'ようこそ'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ようこそ |
| publishDate | string | 公開日（入力） - ニュースの公開日時。フロントの表示順を制御 Fake観察文字長 25〜25; 観察値 '2026-01-01T00:00:00+09:00'。 |  | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2026-01-01T00:00:00+09:00 |
| newsDescription | string | ニュース本文（入力） - ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり Fake観察文字長 14〜14; 観察値 'EC-CUBE へようこそ。'。 |  | Optional | {"minLength":0,"maxLength":2000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | EC-CUBE へようこそ。 |
| newsUrl | string | 外部URL（入力） - 外部リンクURL。設定時はニュース本文の代わりにこのURLへ遷移 |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | /products |
| linkMethod | bool | 新規ウィンドウで開く（入力） - 外部URLのリンク開き方（boolean）。false=同一ウィンドウ, true=新規ウィンドウ（target="_blank"）。テンプレートでtarget属性の出力制御に使用 観察値 'false'。 |  | Optional | {"default":false,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | false |


### Response

[Object: POST /admin/news/news-list response](../schemas/post-admin-news-news-list.json)

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
| goNewsList | [<code>page://self/admin/news/news-list</code>](/admin/news/news-list.md) |