# CartItem

CartItem は EC-CUBE のカート画面で 1 明細あたり描画される情報構造である。ALPS の `CartItem` descriptor は、`dtb_cart_item` の実カラムだけでなく、カート行の表示に必要な商品情報を合成した画面投影として定義する。

## Meaning

`CartItem` は購入された SKU、数量、単価、商品名、商品画像、規格バリエーションをまとめたカート行である。これは Entity から逆生成したものではなく、`Cart/index.twig` の `ec-cartRow` から再導出した投影である。

## EC-CUBE Schema Projection

`dtb_cart_item` の実カラムとしては `product_class_id` / `price` / `quantity` を持つ。画面表示には `CartItem.ProductClass` から Product / ClassCategory を辿り、商品名・商品画像・規格名・規格分類名を合成する。

## Field Semantics

`productClassId` は購入された具体的 SKU、つまり `dtb_product_class.id` である。削除、数量増減リンクのキーになる。

`productId` は商品詳細リンクのターゲットであり、`dtb_product.id` を指す。

`mainImage` は `dtb_product_image` の最小 `sort_no` 行のファイル名である。バリエーション無し、画像無しの商品では NULL になる。

`classCategoryName1` / `classCategoryName2` と `className1` / `className2` は、ProductClass の ClassCategory1/2 の値と軸名である。例として「色：赤」がある。規格無し商品では NULL になる。

`unitPrice` はカート追加時点の `price02` のスナップショットである。明細小計は `unitPrice * quantity` としてテンプレートで算出する。

## Related Projection

この投影は `Favorite` と同型で、storage の leaf 列と画面表示用 JOIN フィールドを分けて読む必要がある。
