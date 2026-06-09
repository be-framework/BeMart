# doSortNoMove

`doSortNoMove` は管理画面の一覧で表示順を変更する汎用遷移である。ALPS では、EC-CUBE の複数の `*_sort_no_move` route を 1 つの抽象 transition として扱う。

## Meaning

`doSortNoMove` は admin が行をドラッグ&ドロップして `sort_no` を更新する操作である。並べ替え結果は冪等であり、同一順序の再送は同一状態になる。

## EC-CUBE Route Sources

この遷移は EC-CUBE の次のような route 群から導出する。

- `admin_setting_shop_payment_sort_no_move`
- `admin_setting_shop_delivery_sort_no_move`
- `admin_product_tag_sort_no_move`
- `admin_product_class_name_sort_no_move`
- `admin_product_class_category_sort_no_move`

## Scope

`sort_no` カラムを持つマスタの一覧でのみ参照される。代表例は `dtb_payment`、`dtb_delivery`、`dtb_tag`、`dtb_class_name`、`dtb_class_category` である。

`dtb_news` には `sort_no` カラムがないため、`NewsList` はこの遷移を参照しない。

## ALPS Modeling

マスタごとに専用 transition を作るのではなく、各 admin 一覧の `src-template` descriptor から共有参照する。これは EC-CUBE の画面上は似たインライン操作であり、意味としても「表示順ベクタの再配置」に揃うためである。
