# News

News は EC-CUBE のニュース記事を表す。ALPS の `News` descriptor は、`dtb_news` を BeMart の `NewsEntity` として投影する。

## Meaning

`News` は admin が作成・編集し、storefront に表示されるお知らせである。タイトル、本文、URL、公開日時、リンク方式を持つ。

## EC-CUBE Schema Projection

BeMart の `NewsEntity` は次の 6 項目を保持する。

- `newsId`
- `newsTitle`
- `newsDescription`
- `newsUrl`
- `publishDate`
- `linkMethod`

EC-CUBE の `creator_id` と `visible` は Phase 2 スコープ外である。

## Migration Decisions

`creator_id` は `dtb_member` への FK だが、structure-only ダンプでは `dtb_member` が空のため固定 NULL とする。NULL 以外を書き込むと FK 1452 が発生する。

`visible` はデフォルト表示として 1 を書く。これはインストーラ既定の表示状態に合わせるためである。

`publish_date` は MySQL `datetime` で、タイムゾーンを持たない。`SqlNewsStorage` は BeMart の ISO-8601 文字列を `Y-m-d H:i:s` に直列化し、read 時に Asia/Tokyo として ISO-8601 `+09:00` に戻す。これは `SqlTaxRuleStorage` の `apply_date` と同じパターンである。

`link_method` は tinyint(1) と bool を双方向に cast する。

## Persistence Behavior

`doUpdateNews` 遷移が存在するため、UPDATE 経路は実運用で踏まれる。ここが delete + create の TaxRule と異なる。

## Implementation References

- Phase 2b
- `NewsEntity`
- `SqlNewsStorage`
