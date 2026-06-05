---
layout: default
title: "類似名の区別の検証結果"
---

# 類似名の区別の検証結果

EC-CUBE 4.3 のソースコード (`src/Eccube/`) を調査し、ALPSプロファイルにおける類似セマンティックIDの意味的区別を検証した。

## 手数料: charge vs paymentCharge

### charge

- **Entity**: `Order.charge` (`dtb_order.charge`, decimal(12,2))
  - ファイル: `src/Eccube/Entity/Order.php` L504-506
- **計算**: `PurchaseFlow::calculateCharge()` が `OrderItemType::CHARGE` の明細を集計して設定する
  - ファイル: `src/Eccube/Service/PurchaseFlow/PurchaseFlow.php` L331-341
- **意味**: 受注に確定した手数料の合計額（税込）。手数料明細（`OrderItem` where `order_item_type_id = 3`）の金額を集計したスナップショット値

### Payment.charge（ALPSでの paymentCharge に相当）

- **Entity**: `Payment.charge` (`dtb_payment.charge`, decimal(12,2), nullable)
  - ファイル: `src/Eccube/Entity/Payment.php` L63-65
- **使われ方**: `PaymentChargePreprocessor` が `Payment.getCharge()` を読み取り、手数料明細の `price` に設定する
- **意味**: 支払方法マスタに設定された手数料金額。管理画面で支払方法ごとに設定する定義値

### 関係性

```text
Payment.charge (マスタ定義値)
  ↓ PaymentChargePreprocessor がコピー
OrderItem.price (明細種別=CHARGE の明細)
  ↓ PurchaseFlow::calculateCharge() が集計
Order.charge (受注スナップショット)
```

### 推奨doc改善

| ID | 現状 | 推奨doc |
|---|---|---|
| `charge` | 手数料 | 受注の手数料合計（税込）。手数料明細の集計値 |
| `paymentCharge` | 支払方法の手数料 | 支払方法マスタの手数料設定額。受注時にcharge明細の単価としてコピーされる |

---

## 税率: taxRate vs taxRuleRate

### taxRate

- **Entity (1)**: `OrderItem.tax_rate` (`dtb_order_item.tax_rate`, decimal(10,0)) -- **永続化される**
  - ファイル: `src/Eccube/Entity/OrderItem.php` L211-213
- **Entity (2)**: `ProductClass.tax_rate` -- **永続化されない**。ORM マッピングなし、メモリ上の算出値
  - ファイル: `src/Eccube/Entity/ProductClass.php` L36
- **計算**: `TaxProcessor` が `TaxRule.getTaxRate()` を読み取り `OrderItem.setTaxRate()` にコピーする
- **意味**: 受注明細に確定適用された税率。注文時点の税率をスナップショットとして保存する

### TaxRule.taxRate（ALPSでの taxRuleRate に相当）

- **Entity**: `TaxRule.tax_rate` (`dtb_tax_rule.tax_rate`, decimal(10,0))
  - ファイル: `src/Eccube/Entity/TaxRule.php` L92-94
- **使われ方**: 税率マスタ。商品別・規格別の税率設定が可能。`apply_date`（適用日）により時限的な税率変更に対応
- **意味**: 税率マスタの定義値。`TaxRuleRepository::getByRule()` で商品→デフォルトの優先順位で取得される

### 関係性

```text
TaxRule.tax_rate (マスタ定義値、適用日で管理)
  ↓ TaxProcessor がコピー（購入フロー時のみ）
OrderItem.tax_rate (受注明細スナップショット)
```

### 推奨doc改善

| ID | 現状 | 推奨doc |
|---|---|---|
| `taxRate` | 適用税率 | 受注明細の確定税率。注文時にtaxRuleRateからコピーされたスナップショット |
| `taxRuleRate` | マスタ税率 | 税率マスタの定義値。適用日・商品別設定が可能。受注時にtaxRateへコピーされる |

---

## 合計: total vs totalPrice vs paymentTotal

### total

- **Entity**: `Order.total` (`dtb_order.total`, decimal(12,2))
- **計算**: `PurchaseFlow::calculateTotal()` が全明細の `priceIncTax * quantity` を合計する
- **意味**: 受注の最終合計金額（税込）。全明細の合算。値引き・ポイント値引きは負数の明細なので自動的に減算される

### totalPrice

- **Entity**: `Cart.total_price` (`dtb_cart.total_price`, decimal(12,2))
- **Cart内**: `Cart::setTotal()` / `Cart::getTotal()` は `setTotalPrice()` / `getTotalPrice()` のエイリアス
- **Order内**: `Order::getTotalPrice()` は **deprecated** で `getPaymentTotal()` を返す
- **意味**: Cartでは全明細の合計金額。Orderでは`paymentTotal`への後方互換エイリアス

### paymentTotal

- **Entity**: `Order.payment_total` (`dtb_order.payment_total`, decimal(12,2))
- **計算**: `PurchaseFlow::calculateTotal()` 内で `total` と同じ値が設定される
- **意味**: 実際の支払い金額。現在の実装では `total` と同値だが、将来的な分離を想定したフィールド

### 関係性

```text
Order.total = Order.paymentTotal （現在の実装では同値）
  = Σ(全OrderItem の priceIncTax * quantity)

Cart.totalPrice = Cart.total （エイリアス関係）
Order.getTotalPrice() → Order.getPaymentTotal() （deprecated エイリアス）
OrderItem.getTotalPrice() = priceIncTax * quantity （計算メソッド、非永続化）
```

### 推奨doc改善

| ID | 現状 | 推奨doc |
|---|---|---|
| `total` | 合計 | 受注の合計金額（税込）。全明細の集計値（商品+送料+手数料-値引き-ポイント） |
| `totalPrice` | 総額 | Cartの合計金額。Orderでは非推奨（paymentTotalのエイリアス）。OrderItemでは税込単価x数量の計算値 |
| `paymentTotal` | 支払い合計 | 受注の実支払額。現在はtotalと同値だが、将来の拡張を想定した分離フィールド |

---

## 送料: deliveryFee vs deliveryFeeAmount vs deliveryFeeTotal

### DeliveryFee (Entity)

- **Entity**: `DeliveryFee` (`dtb_delivery_fee`)
  - `fee` (decimal(12,2)): 送料金額
  - `Delivery` (ManyToOne): 配送方法への関連
  - `Pref` (ManyToOne): 都道府県への関連
- **意味**: 配送方法マスタの都道府県別送料設定

### deliveryFeeAmount（実際は BaseInfo.delivery_free_amount）

- **Entity**: `BaseInfo.delivery_free_amount` (`dtb_base_info.delivery_free_amount`, decimal(12,2), nullable)
- **使われ方**: `DeliveryFeeFreePreprocessor` で送料無料判定の閾値として使用
- **意味**: 送料無料条件の金額設定。この金額以上の注文で送料が0になる。**注意**: 「送料額」ではなく「送料無料となる注文金額の閾値」

### deliveryFeeTotal

- **Entity**: `Order.delivery_fee_total` (`dtb_order.delivery_fee_total`, decimal(12,2))
- **計算**: `PurchaseFlow::calculateDeliveryFeeTotal()` が送料明細を集計して設定する
- **意味**: 受注の送料合計額（税込）。送料明細の集計スナップショット

### 送料の計算フロー

```text
DeliveryFee.fee (配送方法x都道府県のマスタ送料)
  + ProductClass.deliveryFee * quantity (商品個別送料、オプション有効時のみ)
  ↓ DeliveryFeePreprocessor が合算して送料明細を作成
OrderItem.price (明細種別=DELIVERY_FEE の明細)
  ↓ DeliveryFeeFreePreprocessor
  ↓ (送料無料条件を満たす場合は quantity を 0 に設定)
  ↓ PurchaseFlow::calculateDeliveryFeeTotal() が集計
Order.deliveryFeeTotal (受注スナップショット)
```

### 推奨doc改善

| ID | 現状 | 推奨doc |
|---|---|---|
| `deliveryFee` | 送料 | 配送方法x都道府県別のマスタ送料額。DeliveryFee Entityのfeeプロパティ |
| `deliveryFeeAmount` | 送料額 | 送料無料条件金額（delivery_free_amount）。この金額以上で送料無料。名前が紛らわしい: 送料額ではなく送料無料閾値 |
| `deliveryFeeTotal` | 送料合計 | 受注/カートの送料合計額（税込）。全お届け先の送料明細の集計スナップショット |

---

## 金額計算の全体像

### PurchaseFlow の計算順序

`PurchaseFlow::calculateAll()` で以下の順序で計算:

```text
1. calculateDeliveryFeeTotal  -- 送料合計
2. calculateCharge             -- 手数料合計
3. calculateDiscount           -- 値引き合計（正数で格納）
4. calculateSubTotal           -- 商品小計（Order のみ）
5. calculateTax                -- 税額合計
6. calculateTotal              -- 合計 & 支払合計
```

### 計算式

```text
total = Σ(OrderItem.priceIncTax * OrderItem.quantity)  -- 全明細種別を含む
paymentTotal = total  -- 現在の実装では同値

subtotal         = Σ(OrderItem[type=PRODUCT].priceIncTax * quantity)
deliveryFeeTotal = Σ(OrderItem[type=DELIVERY_FEE].priceIncTax * quantity)
charge           = Σ(OrderItem[type=CHARGE].priceIncTax * quantity)
discount         = -Σ(OrderItem[type=DISCOUNT or POINT].priceIncTax * quantity)

total = subtotal + deliveryFeeTotal + charge - discount
```

### マスタ値とスナップショット値の対応表

| マスタ値 (設定) | スナップショット値 (受注確定時) | コピー元 Processor |
|---|---|---|
| `Payment.charge` | `Order.charge` (via OrderItem) | PaymentChargePreprocessor |
| `TaxRule.tax_rate` | `OrderItem.tax_rate` | TaxProcessor |
| `DeliveryFee.fee` | `Order.delivery_fee_total` (via OrderItem) | DeliveryFeePreprocessor |
| `BaseInfo.delivery_free_amount` | *(閾値判定のみ、直接コピーなし)* | DeliveryFeeFreePreprocessor |
| `ProductClass.delivery_fee` | `Order.delivery_fee_total` (via OrderItem に加算) | DeliveryFeePreprocessor |
