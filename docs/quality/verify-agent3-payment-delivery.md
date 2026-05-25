# 決済・配送マスタドメイン ディスクリプタ検証結果

## 支払方法 (Payment)

### paymentMethodName

- **現在doc**: 支払方法の表示名
- **検証結果**: `Payment::$method` (string|null, max 255, nullable)。カラム名は `payment_method`。FormType では `method` フィールドとして NotBlank + Length 制約付き。`__toString()` もこの値を返す
- **改善要否**: 不要
- **備考**: 正確で簡潔

### paymentRuleMin

- **現在doc**: この支払方法を選択できる最低注文金額。未設定時は下限なし。判定対象は決済手数料(paymentCharge)を含めた合計金額
- **検証結果**: `Payment::$rule_min` (decimal(12,2), nullable, unsigned)。`OrderType::filterPayments()` で `($total + $charge) < $min` として判定。`$total` は `$Order->getPaymentTotal() - 現在の支払方法の手数料`、つまり「支払合計 - 現在手数料 + 候補手数料」が判定対象。「決済手数料を含めた合計金額」という表現は方向性は合っている
- **改善要否**: 不要
- **備考**: 判定ロジックの詳細は複雑だが、doc の要約は実質的に正確

### paymentRuleMax

- **現在doc**: この支払方法を選択できる最大注文金額。未設定時は上限なし。判定対象は決済手数料(paymentCharge)を含めた合計金額
- **検証結果**: `Payment::$rule_max` (decimal(12,2), nullable, unsigned)。`filterPayments()` で `($total + $charge) > $max` として判定。paymentRuleMin と同じロジック
- **改善要否**: 不要
- **備考**: paymentRuleMin と対称的で正確

### paymentImage

- **現在doc**: 支払方法のアイコン画像ファイル名
- **検証結果**: `Payment::$payment_image` (string|null, max 255, nullable)。FormType では HiddenType で、`payment_image_file` (FileType) でアップロード。管理画面テンプレートで表示
- **改善要否**: 不要
- **備考**: 正確

### paymentVisible

- **現在doc**: この支払方法をフロントに表示するか
- **検証結果**: `Payment::$visible` (boolean, default true)。FormType では ChoiceType (表示/非表示)。`PaymentValidator` で `$itemHolder->getPayment()->isVisible()` が false なら購入不可エラー。`OrderType` で `$Payment->isVisible()` が true のもののみ選択肢に表示
- **改善要否**: 不要
- **備考**: 正確

## 配送方法 (Delivery)

### deliveryMethodName

- **現在doc**: 配送方法の表示名
- **検証結果**: `Delivery::$name` (string|null, max 255, nullable)。FormType では NotBlank + Length 制約付き。`__toString()` もこの値を返す
- **改善要否**: 不要
- **備考**: 正確で簡潔

### serviceName

- **現在doc**: 配送業者のサービス名。例: ゆうパック、宅急便。配送方法名とは別に業者サービスを識別
- **検証結果**: `Delivery::$service_name` (string|null, max 255, nullable)。FormType では NotBlank + Length 制約付き
- **改善要否**: 不要
- **備考**: 正確。配送方法名とサービス名の使い分けも正しく説明されている

### deliveryDescription

- **現在doc**: 配送方法の説明文。顧客への案内に使用
- **検証結果**: `Delivery::$description` (string|null, max 4000, nullable)。FormType では TextareaType + Length 制約。管理画面テンプレート (`delivery_edit.twig`) で表示・編集されるが、デフォルトのフロントテンプレートでは使用されていない
- **改善要否**: 要
- **推奨doc**: 配送方法の説明文。管理画面で設定し、テンプレートカスタマイズで顧客に表示可能

### deliveryVisible

- **現在doc**: この配送方法をフロントに表示するか
- **検証結果**: `Delivery::$visible` (boolean, default true)。FormType では ChoiceType (表示/非表示)。`DeliveryRepository` で `visible: true` のみ取得されるなど、配送方法選択時のフィルタに使用される
- **改善要否**: 不要
- **備考**: 正確

## 配送時間帯 (DeliveryTime)

### deliveryTimeName

- **現在doc**: 配送時間帯の表示名。例: 午前中、14-16時
- **検証結果**: `DeliveryTime::$delivery_time` (string, max 255, NOT NULL)。配送方法に紐づく (`ManyToOne Delivery`)。`__toString()` でこの値を返す。sort_no による並び順、visible フラグあり
- **改善要否**: 不要
- **備考**: 正確

## 発送日目安 (DeliveryDuration)

### deliveryDurationName

- **現在doc**: 発送日目安の表示名。例: 1〜2日後
- **検証結果**: `DeliveryDuration::$name` (string|null, max 255, nullable)。`__toString()` でこの値を返す。sort_no による並び順あり
- **改善要否**: 不要
- **備考**: 正確

### deliveryDurationDays

- **現在doc**: 発送日目安の日数値。配送希望日の選択肢計算に使用
- **検証結果**: `DeliveryDuration::$duration` (smallint, default 0)。`ShippingType` で `getDuration()` により各商品の最大発送日数を計算し、配送希望日の選択肢（DatePeriod）を生成。マイナス値はお取り寄せを意味しスキップされる
- **改善要否**: 要
- **推奨doc**: 発送日目安の日数値。配送希望日の選択肢計算に使用。負の値はお取り寄せ品を意味し、配送希望日の選択不可となる

## 送料無料条件 (BaseInfo)

### deliveryFreeAmount

- **現在doc**: この金額以上の注文で送料無料。未設定時は条件なし。判定はお届け先ごとの商品合計金額（税込=subtotal）で行う
- **検証結果**: `BaseInfo::$delivery_free_amount` (decimal(12,2), nullable, unsigned)。`DeliveryFeeFreeByShippingPreprocessor` で、お届け先(Shipping)ごとに `$Item->getPriceIncTax() * $Item->getQuantity()` の合計が `getDeliveryFreeAmount()` 以上なら送料無料
- **改善要否**: 不要
- **備考**: 正確。「お届け先ごとの商品合計金額（税込）」の記述が実装と一致

### deliveryFreeQuantity

- **現在doc**: この数量以上の注文で送料無料。未設定時は条件なし
- **検証結果**: `BaseInfo::$delivery_free_quantity` (integer, nullable, unsigned)。`DeliveryFeeFreeByShippingPreprocessor` でお届け先(Shipping)ごとに数量合計が `getDeliveryFreeQuantity()` 以上なら送料無料
- **改善要否**: 要
- **推奨doc**: この数量以上の注文で送料無料。未設定時は条件なし。判定はお届け先ごとの商品数量合計で行う

## 税率 (TaxRule)

### taxRuleAdjust

- **現在doc**: 税率ルールマスタ（TaxRule）の端数処理による税額調整値。受注明細のtaxAdjustはこの値のスナップショット
- **検証結果**: `TaxRule::$tax_adjust` (decimal(10,0), unsigned, default 0)。`TaxRuleService::calcTax()` で `bcadd($roundTax, $taxAdjust, 2)` として税額に加算。`TaxProcessor` で `$item->setTaxAdjust($TaxRule->getTaxAdjust())` により受注明細にコピーされる
- **改善要否**: 不要
- **備考**: 正確

### applyDate

- **現在doc**: この税率ルールが有効になる日時。適用日以降の注文にこの税率が適用される。複数の税率ルールがある場合、注文日時点で最も新しい適用日のルールが使用される。過去の受注には影響しない
- **検証結果**: `TaxRule::$apply_date` (datetimetz, NOT NULL)。`TaxRuleRepository::getByRule()` で `apply_date < now()` かつ `ORDER BY apply_date DESC` で最新適用日のルールを取得。`TaxRule::compareTo()` でも `apply_date` による優先順位ソートあり
- **改善要否**: 不要
- **備考**: 正確で詳細

### roundingType

- **現在doc**: 1=四捨五入, 2=切り捨て, 3=切り上げ。受注明細の税額計算時の端数処理方式。TaxRuleで設定
- **検証結果**: `Master\RoundingType` はマスタエンティティで、定数 `ROUND=1`, `FLOOR=2`, `CEIL=3`。`TaxRule` が `ManyToOne` で参照。`TaxRuleService::roundByRoundingType()` で使用
- **改善要否**: 不要
- **備考**: 正確

## カート (CartItem)

### cartItemPrice

- **現在doc**: カート内の税込単価
- **検証結果**: `CartItem::$price` (decimal(12,2), default 0)。`CartService` で `$ProductClass->getPrice02IncTax()` を設定。`getPriceIncTax()` は `$this->price` をそのまま返す。コメントにも「Cart::priceは税込み金額が入っている」と記載
- **改善要否**: 不要
- **備考**: 正確

### quantity

- **現在doc**: 購入数量。カート明細と受注明細で共通使用
- **検証結果**: `CartItem::$quantity` (decimal(10,0), default 0)。`OrderItem` にも同名の `$quantity` (decimal(10,0)) がある。両方で購入数量を保持
- **改善要否**: 不要
- **備考**: 正確

---

## サマリーテーブル

| # | descriptorId | 改善要否 | 理由 |
|---|---|---|---|
| 1 | paymentMethodName | 不要 | |
| 2 | paymentRuleMin | 不要 | |
| 3 | paymentRuleMax | 不要 | |
| 4 | paymentImage | 不要 | |
| 5 | paymentVisible | 不要 | |
| 6 | deliveryMethodName | 不要 | |
| 7 | serviceName | 不要 | |
| 8 | deliveryDescription | 要 | デフォルトフロントテンプレートでは未使用。「顧客への案内に使用」は誤解を招く |
| 9 | deliveryVisible | 不要 | |
| 10 | deliveryDurationName | 不要 | |
| 11 | deliveryDurationDays | 要 | 負の値（お取り寄せ）の重要な仕様が欠落 |
| 12 | deliveryTimeName | 不要 | |
| 13 | deliveryFreeAmount | 不要 | |
| 14 | deliveryFreeQuantity | 要 | 判定がお届け先ごとであることが未記載（deliveryFreeAmountには記載あり） |
| 15 | taxRuleAdjust | 不要 | |
| 16 | applyDate | 不要 | |
| 17 | roundingType | 不要 | |
| 18 | cartItemPrice | 不要 | |
| 19 | quantity | 不要 | |

**改善要: 3件 / 不要: 16件**
