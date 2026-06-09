# Page

Page は EC-CUBE の CMS ページ定義を表す。ALPS の `Page` descriptor は、`dtb_page` のページ本体を BeMart の `PageEntity` として投影し、レイアウト配置である `dtb_page_layout` とは境界を分ける。

## Meaning

`Page` は admin がページ名、URL、ファイル名、編集種別を保守するための CMS エントリである。storefront では、Twig ファイルとルートの対応を通じて表示ページになる。

## EC-CUBE Schema Projection

BeMart の `PageEntity` は次の 5 項目を保持する。

- `pageId`
- `pageName`
- `pageUrl`
- `pageFileName`
- `pageEditType`

EC-CUBE の `master_page_id` / `author` / `description` / `keyword` / `meta_robots` / `meta_tags` / `create_date` / `update_date` は Phase 2 スコープ外であり、書き込み時は NULL とする。

## Scope Boundary

`dtb_page_layout` はページとレイアウトの配置を表す別スコープである。Wave 9 admin slice では placement 行を INSERT しない。`Page` descriptor はページ本体を扱い、配置編集は別モデルとして扱う。

## Migration Decisions

`master_page_id` は EC-CUBE のデフォルトテンプレート上書き機構を表す self-FK である。BeMart 管理 UI では非対応のため固定 NULL とする。

`page_name` / `file_name` は EC-CUBE 上 nullable だが、`PageEntity` 上は non-null である。read 時に NULL を空文字列へ coalesce して投影形を維持する。

`edit_type` は `smallint(5) unsigned NOT NULL DEFAULT 1` である。`PageCreated` は `EDIT_TYPE_USER = 0` を書き、システム seed は 2 を使う。

## Persistence Behavior

`doUpdatePage` 遷移が存在するため、UPDATE 経路は実運用で踏まれる。

remove は `dtb_page_layout` の placement 行を先に削除してから `dtb_page` を削除する。外部投入された placement 行がある場合でも FK 1451 を避けるためである。

## Implementation References

- Phase 2b
- Wave 9
- `PageEntity`
- `PageCreated`
- `SqlPageStorage`
