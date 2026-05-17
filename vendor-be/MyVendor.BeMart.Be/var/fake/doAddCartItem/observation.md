# Phase 2 Observation: doAddCartItem

## 観察ソース

- `var/fake/doAddCartItem/client-input.json` — 50 件のクライアント入力サンプル
- `var/fake/product_classes.json` — 9 件の典型 ProductClass (Stock 各種 / SaleLimit 各種 / SaleType 3 種)
- `var/fake/carts.json` — 2 件の初期 Cart (販売種別ごとに分離した cartKey)

## client-input 観察結果 (6 軸)

### productCode

| 軸 | 観察値 | Semantic 制約への反映 |
|---|---|---|
| minLength | 1 (例: "x") | 1 文字も許容 |
| maxLength | 65 (例: "very-long-product-code-with-many-hyphens-and-numbers-12345-test") | maxLength=64 推奨 (Pilot 1 の 50 を拡張) |
| required | 100% (50/50 必須) | not_empty 検証 |
| optional / nullable | 0 件 | nullable=false |
| 値域 | ASCII 英数 + ハイフン | 正規表現 `^[a-zA-Z0-9-]+$` 推奨 |
| i18n | ASCII のみ (日本語 SKU は EC-CUBE 慣例外) | i18n 例外メッセージのみ用意 (内容は ASCII バリデーション) |

**判定**: Pilot 1 の `ProductCode` Semantic を再利用 (maxLength=64 に微調整)。新規作成不要。

### quantity

| 軸 | 観察値 | Semantic 制約への反映 |
|---|---|---|
| minLength (≒ min value) | 1 | int min=1 |
| maxLength (≒ max value) | 99 (在庫超えテスト用) | int max=999 (EC-CUBE 管理画面慣例) |
| required | 100% (50/50 必須) | not_empty / present |
| optional / nullable | 0 件 | nullable=false |
| 値域 | 正の整数のみ。小数なし | int, no float |
| i18n | 数値のため言語非依存。例外メッセージのみ i18n | `#[Message(['ja' => ..., 'en' => ...])]` |

**判定**: 新規 `Quantity` Semantic クラスを作成。

## server-fetched 観察結果

### ProductClass フィールドの典型値分布

| フィールド | 観察された値域 | 用途 (Reason) |
|---|---|---|
| `stock` | 0, 3, 10, 50, 100, null (stockUnlimited=true) | StockCheck Reason の判定材料 |
| `stockUnlimited` | true / false | StockCheck をスキップするフラグ |
| `saleLimit` | null (制限なし), 1, 2, 5 | SaleLimitCheck Reason の上限 |
| `price01` (通常価格) | 800〜30000 | 表示用 (計算には使わない) |
| `price02` (販売価格) | 800〜28000 | CartItemMergePrice の計算ベース |
| `deliveryFee` (商品別送料) | 0, 200, 500 | DeliveryFeeAccumulation の追加送料 |
| `saleTypeName` | 通常販売 / 予約販売 / ダウンロード販売 | SaleTypeResolution の cartKey 生成材料 |
| `saleTypeId` | 1 / 2 / 3 | cartKey 末尾 |

**判定**: いずれも Semantic 不要。`var/fake/product_classes.json` の典型値分布で網羅。

### Cart の初期状態

| 状態 | 観察 | 用途 |
|---|---|---|
| 空の Cart (cartKey=`session-prefix-1_1`) | items=[] | 新規追加テスト |
| 空の Cart (cartKey=`session-prefix-1_2`) | items=[], saleTypeId=2 | saleType 分離テスト |

**判定**: Reason `CartItemMergePrice` のテストは「同じ productCode を 2 回追加 → quantity 加算」を観察した上で実装する。

## Diamond Cascade の Reason 順序 (Phase 1 から確定)

```text
client-input (productCode, quantity)
    ↓
[1] StockCheck (ProductClass fetch → stock vs quantity 補正)
    ↓ adjustedQuantity
[2] SaleLimitCheck (saleLimit vs adjustedQuantity 補正)
    ↓ adjustedQuantity'
[3] SaleTypeResolution (saleTypeId → cartKey)
    ↓ cartKey
[4] DeliveryFeeAccumulation (deliveryFee × adjustedQuantity' + 既存 Cart 合計)
    ↓ deliveryFeeTotal
[5] CartItemMergePrice (price02 × adjustedQuantity' + 既存 CartItem merge)
    ↓ totalPrice + cartItem
CartItemAdded (Final)
```

## Phase 3 への引き渡し

- client-input JSON Schema: `var/schema/request/cart-add.json` を作成 (productCode, quantity の 2 フィールド)
- server-fetched は Schema 不要 (Fake fixture が真実)
- Semantic 新規: `Quantity` (Pilot 2 唯一の新規 Semantic)
- Semantic 再利用: `ProductCode` (Pilot 1 から、maxLength 拡張)

## 合意チェックリスト (auto モードのため事後参照用)

- [x] productCode は ASCII のみ、maxLength=64
- [x] quantity は int, min=1, max=999
- [x] stockUnlimited=true なら StockCheck スキップ
- [x] saleLimit=null なら SaleLimitCheck スキップ
- [x] saleTypeId 違いは別 cartKey
- [x] OutOfStockException は stock=0 かつ stockUnlimited=false のときのみ (stock>0 で requested>stock は自動補正)
- [x] 既存 Cart に同 productCode があれば quantity 加算 (上書きではない)
