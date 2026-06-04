---
layout: default
title: "doc4.ec-cube.net 調査結果とALPS改善案"
---

# doc4.ec-cube.net 調査結果とALPS改善案

## 調査したページ

- [https://doc4.ec-cube.net/spec_order](https://doc4.ec-cube.net/spec_order): 受注仕様。受注ステータスの流れ、注文明細区分（6種類）、課税区分、税表示区分、料金計算ルール、ポイント計算仕様、キャンセル/返品処理
- [https://doc4.ec-cube.net/spec_tax](https://doc4.ec-cube.net/spec_tax): 税率設定。標準税率/商品別税率/軽減税率対応、内税運用
- [https://doc4.ec-cube.net/spec_payment](https://doc4.ec-cube.net/spec_payment): 支払方法設定。利用条件金額は決済手数料を含んだ金額で判定
- [https://doc4.ec-cube.net/customize_order_state_machine](https://doc4.ec-cube.net/customize_order_state_machine): 受注ステートマシン。Symfony Workflow Componentベース、状態遷移定義、EventSubscriberによるカスタマイズ
- [https://doc4.ec-cube.net/customize_service](https://doc4.ec-cube.net/customize_service): 購入フロー（PurchaseFlow）。3フロータイプ（cart/shopping/order）、7種のProcessor

## 発見した仕様情報

### 受注ステートマシン

ソースコード `app/config/eccube/packages/order_state_machine.php` から確認した正確な状態遷移:

#### ステータス定数値

| ID | 定数名 | 表示名 | 備考 |
|----|--------|--------|------|
| 1 | NEW | 新規受付 | initial_marking |
| 3 | CANCEL | 注文取消 | 発送前キャンセル。在庫・ポイント戻し |
| 4 | IN_PROGRESS | 対応中 | 梱包作業中等 |
| 5 | DELIVERED | 発送済み | 加算ポイント付与 |
| 6 | PAID | 入金済み | 入金日記録 |
| 7 | PENDING | 決済処理中 | 決済処理待機（ステートマシン外で遷移） |
| 8 | PROCESSING | 購入処理中 | 注文未完了（ステートマシン外で遷移） |
| 9 | RETURNED | 返品 | 発送後返品。在庫・ポイント戻しなし |

#### 遷移ルール（ソースコード確認済み）

| トランジション名 | from | to | doc4の記述 |
|------------------|------|-----|-----------|
| pay | NEW(1) | PAID(6) | 入金日登録 |
| packing | NEW(1), PAID(6) | IN_PROGRESS(4) | 対応中へ |
| cancel | NEW(1), IN_PROGRESS(4), PAID(6) | CANCEL(3) | 在庫・ポイント戻し |
| back_to_in_progress | CANCEL(3) | IN_PROGRESS(4) | キャンセル取消 |
| ship | NEW(1), PAID(6), IN_PROGRESS(4) | DELIVERED(5) | 加算ポイント付与 |
| return | DELIVERED(5) | RETURNED(9) | ポイント戻し |
| cancel_return | RETURNED(9) | DELIVERED(5) | 返品取消 |

PENDING(7)とPROCESSING(8)はステートマシンの places に含まれるがtransitionsには含まれない。購入フロー内で直接セットされる。

#### 典型的なフロー
```text
PROCESSING(8) -> PENDING(7) -> NEW(1) -> PAID(6) -> IN_PROGRESS(4) -> DELIVERED(5)
```

### 税計算仕様

#### 注文明細区分と課税

| ID | 区分 | 課税区分 | 税表示区分 | 計算ルール |
|----|------|----------|-----------|-----------|
| 1 | 商品 | 課税 | 税抜 | (単価 x 税率) x 数量 |
| 2 | 送料 | 課税 | 税込 | 単価 x 数量（税加算なし） |
| 3 | 手数料 | 課税 | 税込 | 単価 x 数量（税加算なし） |
| 4 | 値引き | 課税 | 税込 | 課税対象の値引き |
| 5 | 税 | - | - | 全体税金行 |
| 6 | ポイント値引き | 不課税 | 税込 | 利用ポイント x 換算レート（切り捨て） |

#### 税率ルール
- 標準税率（全商品共通）+ 商品別税率（規格単位、optionProductTaxRule有効時）
- 軽減税率（8%）と標準税率（10%）の混在可能
- 端数処理: 1=四捨五入, 2=切り捨て, 3=切り上げ
- 適用日による時限設定が可能
- 送料無料条件: お届け先ごとに商品合計金額（税込）で判定

#### ポイント計算
- 利用時: 利用ポイント x pointConversionRate（切り捨て、不課税）
- 付与時: 商品単価（税抜） x pointRate x 数量。利用ポイント分を控除

### 決済仕様

#### 支払方法の利用条件
- 利用条件金額は**決済手数料を含んだ金額**で判定（doc4で明記）
- paymentRuleMin/paymentRuleMax で範囲指定

#### PaymentMethodインターフェース（ソースコードから）
- `verify()`: 注文確認時の決済検証（doConfirmOrder時に呼び出し）
- `apply()`: 決済適用
- `checkout()`: 決済確定
- `rollback()`: 決済ロールバック

#### PurchaseFlow との連携
- cart フロー: カート投入時のバリデーション
- shopping フロー: 注文確定時の税計算、送料計算、在庫引当、ポイント減算
- order フロー: 管理画面からの受注編集時の在庫差分、ポイント差分

## ALPS改善案

### doc属性の追加・修正

| descriptor id | 現在のdoc | 推奨するdoc | 根拠 |
|---------------|-----------|------------|------|
| orderStatus | `1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。ステートマシンで遷移を制御` | `1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7(決済処理中)と8(購入処理中)はPurchaseFlow内で直接セットされステートマシン遷移の対象外` | spec_order + order_state_machine.php |
| orderItemType | `1=商品, 2=送料, 3=手数料, 4=値引き, 5=税, 6=ポイント。受注明細の行種別` | `1=商品(課税/税抜), 2=送料(課税/税込), 3=手数料(課税/税込), 4=値引き(課税/税込), 5=税, 6=ポイント値引き(不課税/税込)。受注明細の行種別。商品は税抜価格で登録され税額を加算して計算、送料・手数料は税込価格で登録` | spec_order |
| orderItemPrice | `明細行の単価。税表示方式により税込/税抜が異なる` | `明細行の単価。orderItemType=1(商品)は税抜価格、orderItemType=2,3(送料/手数料)は税込価格で格納。計算式はorderItemTypeにより異なる` | spec_order |
| orderItemTax | `明細行の税額` | `明細行の税額。商品明細は単価x税率で算出。送料・手数料は税込価格のため内税として算出。ポイント値引きは不課税のため0` | spec_order |
| taxRate | `注文時点の適用税率（%）。軽減税率（8%）と標準税率（10%）が混在可能` | `注文時点の適用税率（%）。軽減税率（8%）と標準税率（10%）が混在可能。標準税率はTaxRuleで設定し適用日による時限変更が可能。optionProductTaxRule有効時は商品規格単位の個別税率を優先適用` | spec_tax |
| taxAdjust | `端数処理による税額調整値。複数明細の端数差分を吸収` | `端数処理による税額調整値。複数明細の端数差分を吸収。端数処理方式はroundingType(1=四捨五入,2=切り捨て,3=切り上げ)で制御` | spec_tax |
| roundingType | `1=四捨五入, 2=切り捨て, 3=切り上げ` | `1=四捨五入, 2=切り捨て, 3=切り上げ。受注明細の税額計算時の端数処理方式。TaxRuleで設定` | spec_tax |
| subtotal | `商品合計金額（税込）。送料・手数料・値引き適用前の商品のみの合計` | `商品合計金額（税込）。送料・手数料・値引き適用前の商品明細のみの合計。送料無料条件の判定基準にも使用（お届け先ごとに判定）` | spec_order |
| total | `受注合計金額。計算式: 小計＋送料＋手数料−値引き` | `受注合計金額。計算式: subtotal(商品税込合計) + deliveryFeeTotal(送料) + charge(手数料) - discount(値引き)` | spec_order |
| paymentTotal | `実際の支払金額。ポイント使用時はtotalからポイント分を差し引いた額` | `実際の支払金額。計算式: total - (利用ポイント x pointConversionRate)。ポイント値引きは不課税・切り捨て` | spec_order |
| paymentCharge | `この支払方法を利用した場合に加算される手数料` | `この支払方法を利用した場合に加算される手数料。受注のcharge(手数料)にセットされる。利用条件金額の判定にはこの手数料を含めた金額が使用される` | spec_payment |
| paymentRuleMin | `この支払方法を選択できる最低注文金額。未設定時は下限なし` | `この支払方法を選択できる最低注文金額。未設定時は下限なし。判定対象は決済手数料(paymentCharge)を含めた合計金額` | spec_payment |
| paymentRuleMax | `この支払方法を選択できる最大注文金額。未設定時は上限なし` | `この支払方法を選択できる最大注文金額。未設定時は上限なし。判定対象は決済手数料(paymentCharge)を含めた合計金額` | spec_payment |
| point | `会員の現在のポイント残高。注文時にポイント使用で減算、付与で加算` | `会員の現在のポイント残高。注文時にポイント使用で減算、付与は発送済み(DELIVERED)ステータスへの遷移時に加算。付与計算: 商品単価(税抜) x pointRate x 数量 - 利用ポイント分控除` | spec_order + customize_order_state_machine |
| doUpdateOrderStatus | `ステータス遷移に応じて在庫やポイントを調整。許可された遷移のみ実行可能` | `Symfony Workflowステートマシンによるステータス遷移。許可された遷移のみ実行可能。cancel時: 在庫戻し・利用ポイント戻し。ship時: 加算ポイント付与。return時: 加算ポイント取消。back_to_in_progress時: キャンセル取消（在庫・ポイント再引当）` | customize_order_state_machine |
| doCheckout | `税計算、送料計算、在庫引当、ポイント減算を実行。決済処理後に注文確認メールを送信しカートをクリア` | `PurchaseFlow(shoppingフロー)による税計算・送料計算・在庫引当・ポイント減算を実行。PaymentMethod::checkout()で決済確定後、注文確認メールを送信しカートをクリア。処理中はorderStatus=PROCESSING(8)->PENDING(7)->NEW(1)と遷移` | spec_order + customize_service |
| doConfirmOrder | `PaymentMethod::verifyによる決済検証を実行` | `PurchaseFlow(shoppingフロー)で集計後、PaymentMethod::verify()による決済検証を実行。決済検証失敗時はShoppingErrorへリダイレクト` | spec_order + customize_service |
| doAddCartItem | `在庫・販売制限・配送設定をチェックし、制限超過時は数量を自動調整` | `PurchaseFlow(cartフロー)により在庫チェック・販売制限数チェック・配送設定チェックを実行。制限超過時は数量を自動調整。販売種別が異なる商品は別カート(cartKey)に分離` | customize_service |
| deliveryFreeAmount | `この金額以上の注文で送料無料。未設定時は条件なし` | `この金額以上の注文で送料無料。未設定時は条件なし。判定はお届け先ごとの商品合計金額（税込=subtotal）で行う` | spec_order |
| applyDate | `この税率ルールが有効になる日時。適用日以降の注文にこの税率が適用される` | `この税率ルールが有効になる日時。適用日以降の注文にこの税率が適用される。複数の税率ルールがある場合、注文日時点で最も新しい適用日のルールが使用される。過去の受注には影響しない` | spec_tax |

### 状態遷移の追加・修正

現在のALPSにはステートマシンの遷移がdescriptorとして明示的に定義されていない。`doUpdateOrderStatus` が遷移操作を担っているが、個々の遷移（pay, packing, cancel, ship, return等）がALPSレベルで分離されていない。

推奨: `doUpdateOrderStatus` の doc を充実させることで対応（上記テーブル参照）。個々のtransitionをsafeでないdescriptorとして分離する方法もあるが、EC-CUBEの実装ではUIからは `doUpdateOrderStatus` 一本で遷移を行うため、現状のモデルが実装に即している。

### 新規descriptorの追加提案

| 提案descriptor id | title | doc | 根拠 |
|-------------------|-------|-----|------|
| taxDisplayType | 税表示区分 | `1=税抜（商品明細で使用）, 2=税込（送料・手数料・値引きで使用）。orderItemTypeと連動し、明細行の価格が税抜/税込どちらで格納されているかを示す` | spec_order: 税表示区分は明細の計算ロジックに直結する重要属性だが、ALPSに未定義 |
| taxType | 課税区分 | `1=課税（商品・送料・手数料・値引き）, 2=不課税（ポイント値引き）, 3=非課税（商品券譲渡等）。税額計算の対象判定に使用` | spec_order: 課税区分は税計算のフィルタリング条件だが、ALPSに未定義 |
| usePoint | 利用ポイント数 | `注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加` | spec_order: 受注のポイント利用数はpointとは別属性（pointは残高）。OrderエンティティのusePoint |
| addPoint | 加算ポイント数 | `注文により付与されるポイント数。商品単価(税抜) x pointRate x 数量 で明細ごとに計算し合算。利用ポイント分を控除。発送済み(DELIVERED)遷移時に会員のpointに加算` | spec_order: OrderエンティティのaddPoint |
| orderItemTaxType | 受注明細課税区分 | `受注明細行の課税区分。OrderItemに紐づくTaxType。1=課税, 2=不課税, 3=非課税` | spec_order: OrderItemエンティティに存在するがALPSのOrderItemに未反映 |
| orderItemTaxDisplayType | 受注明細税表示区分 | `受注明細行の税表示区分。OrderItemに紐づくTaxDisplayType。1=税抜, 2=税込` | spec_order: OrderItemエンティティに存在するがALPSのOrderItemに未反映 |
