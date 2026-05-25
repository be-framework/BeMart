# 受注・明細ドメイン ディスクリプタ検証結果

検証対象: alps.json の受注・明細関連 33 ディスクリプタ
検証元ソース: Entity/Order.php, Entity/OrderItem.php, Entity/Shipping.php, Entity/PointTrait.php, Master/OrderStatus.php, Master/OrderItemType.php, Master/TaxType.php, Master/TaxDisplayType.php, Form/Type/Admin/OrderType.php, Form/Type/Admin/OrderItemType.php, Form/Type/Admin/ShippingType.php, packages/order_state_machine.php

---

## 受注 (Order) ドメイン

### orderNo

- **現在doc**: 「顧客向けの注文番号。フォーマットはカスタマイズ可能」
- **検証結果**: Entity: `@ORM\Column(name="order_no", type="string", length=255, nullable=true)`。DB にインデックスあり (`dtb_order_order_no_idx`)。プロパティは nullable。
- **改善要否**: 不要
- **備考**: 簡潔で正確

### orderDate

- **現在doc**: 「注文確定日時」
- **検証結果**: Entity: `@ORM\Column(name="order_date", type="datetimetz", nullable=true)`。DBインデックスあり。OrderType::copyFields() で新規登録時に `new \DateTime()` がセットされる。
- **改善要否**: 不要
- **備考**: 正確

### paymentDate

- **現在doc**: 「入金確認日時。入金済みステータスへの変更時に記録」
- **検証結果**: Entity: `@ORM\Column(name="payment_date", type="datetimetz", nullable=true)`。DBインデックスあり。
- **改善要否**: 不要
- **備考**: 正確

### orderNote

- **現在doc**: 「管理者用の内部メモ。顧客には表示されない」
- **検証結果**: Entity: `@ORM\Column(name="note", type="string", length=4000, nullable=true)`。OrderType で TextareaType、max=eccube_ltext_len(3000)。
- **改善要否**: 不要
- **備考**: 正確。プロパティ名は `note` だがディスクリプタ ID `orderNote` は Shipping の `note` と区別するための命名で適切

### orderStatus

- **現在doc**: 「1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外」
- **検証結果**: Master/OrderStatus.php の定数: NEW=1, CANCEL=3, IN_PROGRESS=4, DELIVERED=5, PAID=6, PENDING=7, PROCESSING=8, RETURNED=9。order_state_machine.php の遷移: pay(1->6), packing([1,6]->4), cancel([1,4,6]->3), back_to_in_progress(3->4), ship([1,6,4]->[5]), return(5->9), cancel_return(9->5)。ステートマシンの places に PENDING(7) と PROCESSING(8) が含まれるが遷移定義はない。
- **改善要否**: 不要
- **備考**: 正確。ステートマシン定義と完全に一致

### subtotal

- **現在doc**: 「商品合計金額（税込）。送料・手数料・値引き適用前の商品明細（orderItemType=1）のみの合計。PurchaseFlow.calculateSubTotal()で計算。送料無料条件の判定基準にも使用（お届け先ごとに判定）」
- **検証結果**: Entity: `@ORM\Column(name="subtotal", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})`
- **改善要否**: 不要
- **備考**: 正確

### discount

- **現在doc**: 「受注全体の値引き合計額。クーポン等による値引き」
- **検証結果**: Entity: `@ORM\Column(name="discount", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})`。OrderType で PriceType として編集可能。
- **改善要否**: 不要
- **備考**: 正確

### tax

- **現在doc**: 「税額合計」
- **検証結果**: Entity: `@ORM\Column(name="tax", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})`。PHPDocに `@deprecated 明細ごとに集計した税額と差異が発生する場合があるため非推奨` とある。
- **改善要否**: 要
- **推奨doc**: 「受注全体の税額合計（非推奨）。明細ごとの税額集計と差異が生じる場合があるため、正確な税額は getTaxByTaxRate() を使用すべき」

### paymentMethod

- **現在doc**: 「注文時点の支払方法名称（スナップショット）」
- **検証結果**: Entity: `@ORM\Column(name="payment_method", type="string", length=255, nullable=true)`。OrderType::copyFields() で `$Payment->getMethod()` からコピーされる。
- **改善要否**: 不要
- **備考**: 正確

## 受注明細 (OrderItem) ドメイン

### orderItemProductName

- **現在doc**: 「注文時点の商品名スナップショット。商品マスタの変更に影響されない」
- **検証結果**: Entity: `@ORM\Column(name="product_name", type="string", length=255)`。NOT NULL。FormType で TextType、NotBlank、max=eccube_mtext_len。
- **改善要否**: 不要
- **備考**: 正確

### orderItemProductCode

- **現在doc**: 「注文時点の商品コードスナップショット」
- **検証結果**: Entity: `@ORM\Column(name="product_code", type="string", length=255, nullable=true)`。OrderItemType::POST_SUBMIT で `$ProductClass->getCode()` からコピーされる（nullの場合のみ）。
- **改善要否**: 不要
- **備考**: 正確

### orderItemClassName1

- **現在doc**: 「注文時点の規格名1スナップショット（例: 色）」
- **検証結果**: Entity: `@ORM\Column(name="class_name1", type="string", length=255, nullable=true)`。OrderItemType::POST_SUBMIT で `$ClassCategory1->getClassName()->getName()` からコピー。
- **改善要否**: 不要
- **備考**: 正確

### orderItemClassName2

- **現在doc**: 「注文時点の規格名2スナップショット（例: サイズ）」
- **検証結果**: Entity: `@ORM\Column(name="class_name2", type="string", length=255, nullable=true)`。同様にコピー。
- **改善要否**: 不要
- **備考**: 正確

### orderItemClassCategoryName1

- **現在doc**: 「注文時点の規格分類値1スナップショット（例: 赤）」
- **検証結果**: Entity: `@ORM\Column(name="class_category_name1", type="string", length=255, nullable=true)`。OrderItemType::POST_SUBMIT で `$ClassCategory1->getName()` からコピー。
- **改善要否**: 不要
- **備考**: 正確

### orderItemClassCategoryName2

- **現在doc**: 「注文時点の規格分類値2スナップショット（例: L）」
- **検証結果**: Entity: `@ORM\Column(name="class_category_name2", type="string", length=255, nullable=true)`。同様にコピー。
- **改善要否**: 不要
- **備考**: 正確

### orderItemPrice

- **現在doc**: 「明細行の単価。orderItemType=1(商品)は税抜価格、orderItemType=2,3(送料/手数料)は税込価格で格納。計算式はorderItemTypeにより異なる」
- **検証結果**: Entity: `@ORM\Column(name="price", type="decimal", precision=12, scale=2, options={"default":0})`。OrderItem::getPriceIncTax() で TaxDisplayType が INCLUDED(税込) なら price をそのまま返し、EXCLUDED(税抜) なら price + tax を返す。FormType で PriceType (accept_minus=true)。
- **改善要否**: 不要
- **備考**: 正確。taxDisplayType との連動が適切に説明されている

### orderItemQuantity

- **現在doc**: 「明細行の数量」
- **検証結果**: Entity: `@ORM\Column(name="quantity", type="decimal", precision=10, scale=0, options={"default":0})`。FormType で IntegerType、NotBlank。
- **改善要否**: 不要
- **備考**: 正確

### orderItemTax

- **現在doc**: 「明細行の税額。商品明細は単価x税率で算出。送料・手数料は税込価格のため内税として算出。ポイント値引きは不課税のため0」
- **検証結果**: Entity: `@ORM\Column(name="tax", type="decimal", precision=10, scale=0, options={"default":0})`。OrderItem::getPriceIncTax() で TaxDisplayType.INCLUDED の場合 price をそのまま、EXCLUDED の場合 price + tax を返す。
- **改善要否**: 不要
- **備考**: 正確

### orderItemType

- **現在doc**: 「1=商品(課税/税抜), 2=送料(課税/税込), 3=手数料(課税/税込), 4=値引き(課税/税込), 5=税, 6=ポイント値引き(不課税/税込)。受注明細の行種別。商品は税抜価格で登録され税額を加算して計算、送料・手数料は税込価格で登録」
- **検証結果**: Master/OrderItemType.php の定数: PRODUCT=1, DELIVERY_FEE=2, CHARGE=3, DISCOUNT=4, TAX=5, POINT=6。FormType で HiddenType として送信。バリデーション分岐: 商品=金額正、値引き=金額負・数量正、送料/手数料=金額正・数量正。
- **改善要否**: 不要
- **備考**: 正確。定数値と一致

### taxAdjust

- **現在doc**: 「端数処理による税額調整値。複数明細の端数差分を吸収。端数処理方式はroundingType(1=四捨五入,2=切り捨て,3=切り上げ)で制御」
- **検証結果**: Entity (OrderItem): `@ORM\Column(name="tax_adjust", type="decimal", precision=10, scale=0, options={"unsigned":true,"default":0})`。OrderItemType::POST_SUBMIT で `$TaxRule->getTaxAdjust()` からコピーされる。
- **改善要否**: 不要
- **備考**: 正確

### taxType

- **現在doc**: 「1=課税（商品・送料・手数料・値引き）, 2=非課税, 3=不課税（ポイント値引き）。税額計算の対象判定に使用」
- **検証結果**: Master/TaxType.php: TAXATION=1(課税), NON_TAXABLE=2(不課税), TAX_EXEMPT=3(非課税)。Entity の `tax_type_id` カラムで OrderItem に紐づく。
- **改善要否**: 要
- **推奨doc**: 「1=課税（商品・送料・手数料・値引き）, 2=不課税（ポイント値引き）, 3=非課税。税額計算の対象判定に使用」

### taxDisplayType

- **現在doc**: 「1=税抜（商品明細で使用）, 2=税込（送料・手数料・値引きで使用）。orderItemTypeと連動し、明細行の価格が税抜/税込どちらで格納されているかを示す」
- **検証結果**: Master/TaxDisplayType.php: EXCLUDED=1(税抜), INCLUDED=2(税込)。OrderItem::getPriceIncTax() で分岐判定に使用。
- **改善要否**: 不要
- **備考**: 正確

## 配送 (Shipping) ドメイン

### deliveryName

- **現在doc**: 「注文時点の配送業者名スナップショット」
- **検証結果**: Entity: `@ORM\Column(name="delivery_name", type="string", length=255, nullable=true)`。プロパティ名は `$shipping_delivery_name`。ShippingType::POST_SUBMIT で `$Delivery->getName()` からコピーされる。
- **改善要否**: 不要
- **備考**: 正確

### deliveryTime

- **現在doc**: 「顧客が選択した配送希望時間帯（例: 午前中、14-16時）」
- **検証結果**: Entity: `@ORM\Column(name="delivery_time", type="string", length=255, nullable=true)`。プロパティ名は `$shipping_delivery_time`。ShippingType::POST_SUBMIT で `$DeliveryTime->getDeliveryTime()` からコピーされる。
- **改善要否**: 不要
- **備考**: 正確

### deliveryDate

- **現在doc**: 「顧客が指定した配送希望日」
- **検証結果**: Entity: `@ORM\Column(name="delivery_date", type="datetimetz", nullable=true)`。PHPDoc: 「お届け予定日/お届け希望日」。プロパティ名は `$shipping_delivery_date`。ShippingType で DateType (single_text)。
- **改善要否**: 不要
- **備考**: 正確

### shippingDate

- **現在doc**: 「実際の出荷日。管理者が出荷処理時に記録」
- **検証結果**: Entity: `@ORM\Column(name="shipping_date", type="datetimetz", nullable=true)`。PHPDoc: 「出荷日」。
- **改善要否**: 不要
- **備考**: 正確

### trackingNumber

- **現在doc**: 「配送業者の荷物追跡番号。confirmUrlと組み合わせて追跡URLを構成」
- **検証結果**: Entity: `@ORM\Column(name="tracking_number", type="string", length=255, nullable=true)`。ShippingType で TextType、max=eccube_mtext_len、正規表現バリデーション `/^[0-9a-zA-Z-]+$/u`（半角英数字とハイフンのみ）。
- **改善要否**: 不要
- **備考**: 正確

### shippingNote

- **現在doc**: 「配送に関する管理者メモ」
- **検証結果**: Entity: `@ORM\Column(name="note", type="string", length=4000, nullable=true)`。ShippingType で TextareaType、max=eccube_ltext_len(3000)。
- **改善要否**: 不要
- **備考**: 正確

### mailSendDate

- **現在doc**: 「出荷通知メールの送信日時。送信済み判定にも使用」
- **検証結果**: Entity: `@ORM\Column(name="mail_send_date", type="datetimetz", nullable=true)`。Shipping クラスに定数 `SHIPPING_MAIL_UNSENT=1`, `SHIPPING_MAIL_SENT=2` があるが、これは mailSendDate とは別のフラグ。
- **改善要否**: 不要
- **備考**: 正確。「送信済み判定にも使用」はnull判定による送信済みチェックを指す

## ポイント (PointTrait) ドメイン

### addPoint

- **現在doc**: 「注文により付与されるポイント数。商品単価(税抜) x pointRate x 数量で明細ごとに計算し合算。利用ポイント分を控除。発送済み(DELIVERED)遷移時に会員のpointに加算」
- **検証結果**: Entity (PointTrait): `@ORM\Column(name="add_point", type="decimal", precision=12, scale=0, options={"unsigned":true,"default":0})`。
- **改善要否**: 不要
- **備考**: 正確

### usePoint

- **現在doc**: 「注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加」
- **検証結果**: Entity (PointTrait): `@ORM\Column(name="use_point", type="decimal", precision=12, scale=0, options={"unsigned":true,"default":0})`。OrderType で NumberType、正の整数のみ、max=eccube_price_max。
- **改善要否**: 不要
- **備考**: 正確

## 注文完了メッセージ

### completeMessage

- **現在doc**: 「注文完了画面に表示するメッセージ。決済プラグインが設定するカスタムメッセージ」
- **検証結果**: Entity: `@ORM\Column(name="complete_message", type="text", nullable=true)`。PHPDoc: 「注文完了画面に表示するメッセージ。プラグインから注文完了時にメッセージを表示したい場合、このフィールドにセットすることで、注文完了画面で表示されます。複数のプラグインから利用されるため、appendCompleteMesssage()で追加してください。表示する際にHTMLは利用可能です。」
- **改善要否**: 要
- **推奨doc**: 「注文完了画面に表示するメッセージ。主に決済プラグインが設定するカスタムメッセージ。複数プラグインからの利用を想定し appendCompleteMesssage() で追記する。HTML使用可」

### completeMailMessage

- **現在doc**: 「注文確認メールに追加するメッセージ。決済プラグインが設定するカスタムメッセージ」
- **検証結果**: Entity: `@ORM\Column(name="complete_mail_message", type="text", nullable=true)`。PHPDoc: 「注文完了メールに表示するメッセージ。プラグインから注文完了メールにメッセージを表示したい場合、このフィールドにセットすることで、注文完了メールで表示されます。複数のプラグインから利用されるため、appendCompleteMailMesssage()で追加してください。」
- **改善要否**: 要
- **推奨doc**: 「注文完了メールに追加するメッセージ。主に決済プラグインが設定するカスタムメッセージ。複数プラグインからの利用を想定し appendCompleteMailMesssage() で追記する」

---

## サマリテーブル

| # | descriptorId | 改善要否 | 理由 |
|---|---|---|---|
| 1 | orderNo | 不要 | |
| 2 | orderDate | 不要 | |
| 3 | paymentDate | 不要 | |
| 4 | orderNote | 不要 | |
| 5 | orderStatus | 不要 | |
| 6 | subtotal | 不要 | |
| 7 | discount | 不要 | |
| 8 | tax | 要 | @deprecated が記載されていない |
| 9 | paymentMethod | 不要 | |
| 10 | orderItemProductName | 不要 | |
| 11 | orderItemProductCode | 不要 | |
| 12 | orderItemClassName1 | 不要 | |
| 13 | orderItemClassName2 | 不要 | |
| 14 | orderItemClassCategoryName1 | 不要 | |
| 15 | orderItemClassCategoryName2 | 不要 | |
| 16 | orderItemPrice | 不要 | |
| 17 | orderItemQuantity | 不要 | |
| 18 | orderItemTax | 不要 | |
| 19 | orderItemType | 不要 | |
| 20 | taxAdjust | 不要 | |
| 21 | taxType | 要 | 2と3の説明が逆（ソース: 2=不課税, 3=非課税） |
| 22 | taxDisplayType | 不要 | |
| 23 | deliveryName | 不要 | |
| 24 | deliveryTime | 不要 | |
| 25 | deliveryDate | 不要 | |
| 26 | shippingDate | 不要 | |
| 27 | trackingNumber | 不要 | |
| 28 | shippingNote | 不要 | |
| 29 | mailSendDate | 不要 | |
| 30 | addPoint | 不要 | |
| 31 | usePoint | 不要 | |
| 32 | completeMessage | 要 | appendメソッドとHTML利用可の情報が欠落 |
| 33 | completeMailMessage | 要 | appendメソッドの情報が欠落 |

**改善要: 4件 / 不要: 29件**

### 要改善の詳細

1. **taxType**: 2=非課税, 3=不課税と記載しているが、ソースでは `NON_TAXABLE=2(不課税)`, `TAX_EXEMPT=3(非課税)` で2と3の説明が逆
2. **tax**: Order の tax プロパティが `@deprecated` であることが未記載
3. **completeMessage**: 複数プラグインからの append パターンと HTML 利用可が未記載
4. **completeMailMessage**: 複数プラグインからの append パターンが未記載
