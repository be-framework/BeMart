# Favorite

Favorite は EC-CUBE のお気に入り一覧画面で 1 行あたり描画される情報構造である。ALPS の `Favorite` descriptor は、`dtb_customer_favorite_product` の leaf 列に商品表示用 JOIN フィールドを加えた画面投影として定義する。

## Meaning

`Favorite` は customer が商品をお気に入りに入れた状態を表す。`customerId` と商品を結び、マイページのお気に入り一覧で商品名、価格、画像を表示する。

## EC-CUBE Schema Projection

`dtb_customer_favorite_product` の実カラムは `customer_id` / `product_id` である。画面表示には `dtb_product` / `dtb_product_class` / `dtb_product_image` を JOIN して商品名、単価、画像を取得する。

この descriptor は Entity から逆生成したものではなく、`Mypage/favorite.twig` から再導出した投影である。

## Field Semantics

`customerId` は所有者 FK である。

`productCode` は default class 規約で解決する自然キーである。条件は `class_category_id1 IS NULL AND class_category_id2 IS NULL` であり、Product と同じ扱いをする。

`productName` と `unitPrice` は `dtb_product` / `dtb_product_class` への JOIN で取得する表示用投影フィールドである。

`fileName` は `dtb_product_image` の最小 `sort_no` 行のファイル名である。画像無し商品では NULL になる。この規約は `CartItem.fileName` と同じである。

## Identity

所有者キーは `customerId` のみである。`productCode` が同じでも `customerId` が異なれば別行である。

EC-CUBE 4.3 には `(customer_id, product_id)` UNIQUE がない。`SqlFavoriteStorage` は `ON DUPLICATE KEY UPDATE id = id` でベストエフォートに冪等化している。

## Related Projection

この投影は `CartItem` と同型で、storage の leaf 列と画面表示用 JOIN フィールドを分けて読む必要がある。
