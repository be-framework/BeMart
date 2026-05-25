# 商品ドメイン ディスクリプタ検証レポート (Agent 1)

## 検証対象ファイル

- `src/Eccube/Entity/Product.php`
- `src/Eccube/Entity/ProductClass.php`
- `src/Eccube/Entity/ProductImage.php`
- `src/Eccube/Entity/Category.php`
- `src/Eccube/Entity/Tag.php`
- `src/Eccube/Entity/ClassName.php`
- `src/Eccube/Entity/ClassCategory.php`
- `src/Eccube/Entity/Master/SaleType.php`
- `src/Eccube/Entity/Master/ProductStatus.php`
- `src/Eccube/Form/Type/Admin/ProductType.php`
- `src/Eccube/Form/Type/Admin/ProductClassType.php`
- `src/Eccube/Form/Type/Admin/SearchProductType.php`

---

### productName

- **現在doc**: 商品の表示名
- **検証結果**: `Product.php` L466 `$name` — `@ORM\Column(name="name", type="string", length=255)`。NOT NULL。FormType では `TextType` に `NotBlank`, `Length(max=eccube_stext_len)` 制約。title `商品名`, def `https://schema.org/name`。
- **改善要否**: 不要
- **理由**: 現在の doc は簡潔で正確。Entity は string(255), NOT NULL で FormType にもバリデーションあり。factual error なし。

---

### descriptionList

- **現在doc**: 商品一覧・検索結果に表示する短い説明文
- **検証結果**: `Product.php` L480 `$description_list` — `@ORM\Column(name="description_list", type="text", nullable=true)`。FormType では `TextareaType`, `purify_html=true`, `required=false`, `Length(max=eccube_ltext_len)` 制約。
- **改善要否**: 不要
- **理由**: 正確。nullable, text 型で purify_html あり。doc は目的を正しく記述。

---

### descriptionDetail

- **現在doc**: 商品詳細ページに表示する説明文
- **検証結果**: `Product.php` L486 `$description_detail` — `@ORM\Column(name="description_detail", type="text", nullable=true)`。FormType では `TextareaType`, `purify_html=true`, `Length(max=eccube_ltext_len)` 制約。required はデフォルト（true）だが nullable のため DB 上は NULL 可。
- **改善要否**: 不要
- **理由**: 正確。FormType で purify_html が適用されることは freeArea の doc には言及されているが、descriptionDetail にもある。重大な欠落ではない。

---

### searchWord

- **現在doc**: フロント検索でヒットさせるためのキーワード。画面には表示されない検索補助データ
- **検証結果**: `Product.php` L494 `$search_word` — `@ORM\Column(name="search_word", type="text", nullable=true)`。FormType では `TextType`, `required=false`, `Length(max=eccube_ltext_len)` 制約。
- **改善要否**: 不要
- **理由**: 正確。Entity と FormType の定義と一致。

---

### productNote

- **現在doc**: 管理者のみが参照する内部メモ。フロントには表示されない
- **検証結果**: `Product.php` L472 `$note` — `@ORM\Column(name="note", type="text", nullable=true)`。FormType では `TextareaType`, `required=false`, `Length(max=eccube_ltext_len)` 制約。
- **改善要否**: 不要
- **理由**: 正確。Entity のプロパティ名は `note` で、FormType のフィールド名も `note`。管理画面専用の内部メモ。

---

### productStatus

- **現在doc**: 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示）
- **検証結果**: `Master/ProductStatus.php` で定数定義:
  - `DISPLAY_SHOW = 1` — 公開、フロント表示、管理画面デフォルト検索対象
  - `DISPLAY_HIDE = 2` — 非公開、フロント非表示、管理画面デフォルト検索対象
  - `DISPLAY_ABOLISHED = 3` — 廃止、フロント非表示、管理画面デフォルト検索対象外

  `Product.php` L578 `$Status` — `@ORM\ManyToOne(targetEntity="Eccube\Entity\Master\ProductStatus")`

  SearchProductType での使用: `ProductStatusType` として検索条件に使用。デフォルト検索値は `DISPLAY_SHOW(1)` と `DISPLAY_HIDE(2)` のみ（`DISPLAY_ABOLISHED(3)` は含まれない）。これにより「廃止は管理画面でもデフォルト検索対象外」という動作が確認できる。
- **改善要否**: 不要
- **理由**: 正確。ProductStatus のコメントとも一致。SearchProductType のデフォルト値からも「管理画面でもデフォルト非表示」が裏付けられる。

---

### productCode

- **現在doc**: SKU/品番。在庫管理や受注明細での識別に使用
- **検証結果**: `ProductClass.php` L188 `$code` — `@ORM\Column(name="product_code", type="string", length=255, nullable=true)`。FormType では `TextType`, `required=false`, `Length(max=eccube_stext_len)` 制約。ProductClass のプロパティであり Product ではない。
- **改善要否**: 不要
- **理由**: 正確。ProductClass に属する点は alps.json のコンテキスト（商品規格）で理解可能。

---

### stock

- **現在doc**: 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる
- **検証結果**: `ProductClass.php` L195 `$stock` — `@ORM\Column(name="stock", type="decimal", precision=10, scale=0, nullable=true)`。FormType では `NumberType`, `required=false`, `Regex(/^\d+$/u)` 制約。
- **改善要否**: 不要
- **理由**: 正確。Entity の型は decimal(10,0) で nullable。FormType で数値のみ許可。

---

### stockUnlimited

- **現在doc**: trueの場合、在庫数に関係なく在庫チェックをスキップ
- **検証結果**: `ProductClass.php` L202 `$stock_unlimited` — `@ORM\Column(name="stock_unlimited", type="boolean", options={"default":false})`。デフォルト false。FormType では `CheckboxType`, `label='admin.product.stock_unlimited__short'`, `required=false`。POST_SUBMIT イベントで stock_unlimited が false かつ stock が null の場合にエラー追加。
- **改善要否**: 不要
- **理由**: 正確。boolean 型でデフォルト false。

---

### saleLimit

- **現在doc**: 1回の注文で購入できる最大数量。未設定時は制限なし
- **検証結果**: `ProductClass.php` L209 `$sale_limit` — `@ORM\Column(name="sale_limit", type="decimal", precision=10, scale=0, nullable=true, options={"unsigned":true})`。FormType では `NumberType`, `required=false`, `Length(max=10)`, `GreaterThanOrEqual(1)`, `Regex(/^\d+$/u)` 制約。
- **改善要否**: 不要
- **理由**: 正確。nullable で未設定時は制限なし。最小値は 1。

---

### productClassVisible

- **現在doc**: この商品規格をフロントに表示するか。productStatusとは独立して制御
- **検証結果**: `ProductClass.php` L237 `$visible` — `@ORM\Column(name="visible", type="boolean", options={"default":true})`。デフォルト true。`Product._calc()` で `isVisible() == false` の規格はスキップされる。
- **改善要否**: 不要
- **理由**: 正確。boolean, デフォルト true、productStatus とは独立。

---

### currencyCode

- **現在doc**: ISO 4217通貨コード（例: JPY）。多通貨対応用
- **検証結果**: `ProductClass.php` L258 `$currency_code` — `@ORM\Column(name="currency_code", type="string", nullable=true)`。length 指定なしだがデフォルト 255。
- **改善要否**: 不要
- **理由**: 正確。nullable の string。

---

### pointRate

- **現在doc**: この商品規格固有のポイント付与率（%）。未設定時はbasicPointRateが適用
- **検証結果**: `ProductClass.php` L265 `$point_rate` — `@ORM\Column(name="point_rate", type="decimal", precision=10, scale=0, options={"unsigned":true}, nullable=true)`。
- **改善要否**: 不要
- **理由**: 正確。decimal(10,0), unsigned, nullable。未設定時に basicPointRate が適用される旨は正しい。

---

### fileName

- **現在doc**: 商品画像のファイル名
- **検証結果**: `ProductImage.php` L58 `$file_name` — `@ORM\Column(name="file_name", type="string", length=255)`。NOT NULL。ProductImage エンティティに属する。
- **改善要否**: 不要
- **理由**: 正確。ProductImage のファイル名で string(255), NOT NULL。

---

### sortNo

- **現在doc**: 一覧における並び順
- **検証結果**: 複数エンティティに存在:
  - `ProductImage.php` L65 `$sort_no` — `type="smallint", options={"unsigned":true}`
  - `Category.php` L177 `$sort_no` — `type="integer"`
  - `Tag.php` L67 `$sort_no` — `type="smallint", options={"unsigned":true}`
  - `ClassName.php` L72 `$sort_no` — `type="integer", options={"unsigned":true}`
  - `ClassCategory.php` L72 `$sort_no` — `type="integer", options={"unsigned":true}`
  - `AbstractMasterEntity.php` L56 `$sort_no` — `type="smallint", options={"unsigned":true}`

  各エンティティの一覧表示順を制御する汎用プロパティ。
- **改善要否**: 不要
- **理由**: 正確。複数エンティティで共用されるセマンティックディスクリプタとして「一覧における並び順」は適切。

---

### categoryName

- **現在doc**: カテゴリの表示名
- **検証結果**: `Category.php` L163 `$name` — `@ORM\Column(name="category_name", type="string", length=255)`。NOT NULL。
- **改善要否**: 不要
- **理由**: 正確。DB カラム名は `category_name`、Entity プロパティ名は `$name`。

---

### hierarchy

- **現在doc**: カテゴリツリーにおける深さ。ルートが1、子が2と増加
- **検証結果**: `Category.php` L170 `$hierarchy` — `@ORM\Column(name="hierarchy", type="integer", options={"unsigned":true})`。NOT NULL。`getNameWithLevel()` で `str_repeat('　', $this->getHierarchy() - 1)` としてインデント表示に使用。
- **改善要否**: 不要
- **理由**: 正確。integer, unsigned。ルートが 1 であることは `getNameWithLevel()` の `- 1` からも確認可能。

---

### tagName

- **現在doc**: 商品に付与するタグの表示名
- **検証結果**: `Tag.php` L60 `$name` — `@ORM\Column(name="name", type="string", length=255)`。NOT NULL。ProductTag を通じて Product と多対多の関係。
- **改善要否**: 不要
- **理由**: 正確。string(255), NOT NULL。

---

### classNameBackendName

- **現在doc**: 管理画面でのみ使用する規格の内部名。classNameLabelとは別に管理用の名称を持てる
- **検証結果**: `ClassName.php` L57 `$backend_name` — `@ORM\Column(name="backend_name", type="string", length=255, nullable=true)`。
- **改善要否**: 不要
- **理由**: 正確。nullable で、管理画面専用の内部名。classNameLabel（フロント向け表示名）との使い分けが説明されている。

---

### classCategoryBackendName

- **現在doc**: 管理画面でのみ使用する規格分類の内部名
- **検証結果**: `ClassCategory.php` L57 `$backend_name` — `@ORM\Column(name="backend_name", type="string", length=255, nullable=true)`。
- **改善要否**: 不要
- **理由**: 正確。nullable で、管理画面専用の内部名。

---

### classCategoryVisible

- **現在doc**: この規格分類値を商品選択肢として表示するか
- **検証結果**: `ClassCategory.php` L79 `$visible` — `@ORM\Column(name="visible", type="boolean", options={"default":true})`。デフォルト true。`Product._calc()` で `ClassCategory1->isVisible()` / `ClassCategory2->isVisible()` が false の規格はスキップされる。
- **改善要否**: 不要
- **理由**: 正確。boolean, デフォルト true。

---

### saleTypeName

- **現在doc**: （alps.json に saleTypeName ディスクリプタが存在するか確認 -> 指定リストに含まれている）
- **検証結果**: `Master/SaleType.php` は `AbstractMasterEntity` を継承。`AbstractMasterEntity` の `$name` — `@ORM\Column(name="name", type="string", length=255)`。定数 `SALE_TYPE_NORMAL = 1`。mtb_sale_type テーブル。
- **改善要否**: 要
- **理由**: alps.json に `saleTypeName` ディスクリプタが **存在しない**。検証対象リストに含まれているが、alps.json の descriptor 一覧に含まれていない。追加が必要。

- **推奨doc**:
```json
{"id": "saleTypeName", "title": "販売種別名", "tag": "src-entity", "doc": {"value": "販売種別の表示名（例: 通常）。カートの分離単位。販売種別が異なる商品は別カートに分かれる"}}
```

---

## サマリーテーブル

| ID | 改善要否 | 理由 |
|---|---|---|
| productName | 不要 | 正確 |
| descriptionList | 不要 | 正確 |
| descriptionDetail | 不要 | 正確 |
| searchWord | 不要 | 正確 |
| productNote | 不要 | 正確 |
| productStatus | 不要 | 正確 |
| productCode | 不要 | 正確 |
| stock | 不要 | 正確 |
| stockUnlimited | 不要 | 正確 |
| saleLimit | 不要 | 正確 |
| productClassVisible | 不要 | 正確 |
| currencyCode | 不要 | 正確 |
| pointRate | 不要 | 正確 |
| fileName | 不要 | 正確 |
| sortNo | 不要 | 正確 |
| categoryName | 不要 | 正確 |
| hierarchy | 不要 | 正確 |
| tagName | 不要 | 正確 |
| classNameBackendName | 不要 | 正確 |
| classCategoryBackendName | 不要 | 正確 |
| classCategoryVisible | 不要 | 正確 |
| saleTypeName | 要 | alps.json にディスクリプタが存在しない。追加が必要 |
