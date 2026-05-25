# ER-D / Entity 調査結果とALPS改善案

## テーブル/Entity概要

- 全テーブル数: 63（dtb_* 40, mtb_* 21, plg_* 2）
- 全カラム数: 568
- 業務カラム数（id, create_date, update_date, discriminator_type, 純粋FK除外）: 264
- 内部実装カラム（ALPS対象外）: 31（salt, method_class, authentication_key等）

## カバレッジサマリ

| 項目 | 値 |
|---|---|
| ALPSカバー済み | 236 / 264 |
| カバレッジ率 | 89.4% |
| 欠落カラム数 | 28 |

## 主要テーブルのカバレッジ

### dtb_product / Product Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| name | Yes | productName | |
| note | Yes | productNote | |
| description_list | Yes | descriptionList | |
| description_detail | Yes | descriptionDetail | |
| search_word | Yes | searchWord | |
| free_area | Yes | freeArea | |
| product_status_id | Yes | productStatus | FK->mtb |

カバレッジ: 7/7 (100%)

### dtb_order / Order Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| pre_order_id | Yes | preOrderId | |
| order_no | Yes | orderNo | |
| message | Yes | message | |
| name01 | Yes | name01 | |
| name02 | Yes | name02 | |
| kana01 | Yes | kana01 | |
| kana02 | Yes | kana02 | |
| company_name | Yes | companyName | |
| email | Yes | email | |
| phone_number | Yes | phoneNumber | |
| postal_code | Yes | postalCode | |
| addr01 | Yes | addr01 | |
| addr02 | Yes | addr02 | |
| birth | Yes | birth | |
| subtotal | Yes | subtotal | |
| discount | Yes | discount | |
| delivery_fee_total | Yes | deliveryFeeTotal | |
| charge | Yes | charge | |
| tax | Yes | tax | |
| total | Yes | total | |
| payment_total | Yes | paymentTotal | |
| payment_method | Yes | paymentMethod | |
| note | Yes | orderNote | |
| order_date | Yes | orderDate | |
| payment_date | Yes | paymentDate | |
| currency_code | Yes | currencyCode | |
| order_status_id | Yes | orderStatus | FK->mtb |
| complete_message | **No** | - | 注文完了画面のメッセージ |
| complete_mail_message | **No** | - | 注文完了メールのメッセージ |
| add_point | **No** | - | 付与ポイント |
| use_point | **No** | - | 使用ポイント |

カバレッジ: 27/31 (87%)

### dtb_customer / Customer Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| name01 | Yes | name01 | |
| name02 | Yes | name02 | |
| kana01 | Yes | kana01 | |
| kana02 | Yes | kana02 | |
| company_name | Yes | companyName | |
| postal_code | Yes | postalCode | |
| addr01 | Yes | addr01 | |
| addr02 | Yes | addr02 | |
| email | Yes | email | |
| phone_number | Yes | phoneNumber | |
| birth | Yes | birth | |
| password | Yes | password | |
| secret_key | Yes | secretKey | |
| first_buy_date | Yes | firstBuyDate | |
| last_buy_date | Yes | lastBuyDate | |
| buy_times | Yes | buyTimes | |
| buy_total | Yes | buyTotal | |
| note | Yes | customerNote | |
| reset_key | Yes | resetKey | |
| reset_expire | Yes | resetExpire | |
| point | Yes | point | |
| customer_status_id | Yes | customerStatus | FK->mtb |
| sex_id | Yes | sex | FK->mtb |
| job_id | Yes | job | FK->mtb |
| salt | Skip | - | 内部セキュリティ |

カバレッジ: 24/24 (100%) ※salt除外

### dtb_shipping / Shipping Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| name01 | Yes | name01 | |
| name02 | Yes | name02 | |
| kana01 | Yes | kana01 | |
| kana02 | Yes | kana02 | |
| company_name | Yes | companyName | |
| phone_number | Yes | phoneNumber | |
| postal_code | Yes | postalCode | |
| addr01 | Yes | addr01 | |
| addr02 | Yes | addr02 | |
| delivery_name | Yes | deliveryName | |
| delivery_time | Yes | deliveryTime | |
| delivery_date | Yes | deliveryDate | |
| shipping_date | Yes | shippingDate | |
| tracking_number | Yes | trackingNumber | |
| note | Yes | shippingNote | |
| sort_no | Yes | sortNo | |
| mail_send_date | Yes | mailSendDate | |

カバレッジ: 17/17 (100%)

### dtb_cart / Cart Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| cart_key | Yes | cartKey | |
| pre_order_id | Yes | preOrderId | |
| total_price | Yes | totalPrice | |
| delivery_fee_total | Yes | deliveryFeeTotal | |
| sort_no | Yes | sortNo | |
| add_point | **No** | - | 付与ポイント（カート内） |
| use_point | **No** | - | 使用ポイント（カート内） |

カバレッジ: 5/7 (71%)

### dtb_order_item / OrderItem Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| product_name | Yes | orderItemProductName | |
| product_code | Yes | orderItemProductCode | |
| class_name1 | Yes | orderItemClassName1 | |
| class_name2 | Yes | orderItemClassName2 | |
| class_category_name1 | Yes | orderItemClassCategoryName1 | |
| class_category_name2 | Yes | orderItemClassCategoryName2 | |
| price | Yes | orderItemPrice | |
| quantity | Yes | orderItemQuantity | |
| tax | Yes | orderItemTax | |
| tax_rate | Yes | taxRate | |
| tax_adjust | Yes | taxAdjust | |
| currency_code | Yes | currencyCode | |
| point_rate | Yes | pointRate | |
| order_item_type_id | Yes | orderItemType | FK->mtb |
| processor_name | Skip | - | 内部処理クラス名 |

カバレッジ: 14/14 (100%) ※processor_name除外

### dtb_product_class / ProductClass Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| product_code | Yes | productCode | |
| stock | Yes | stock | |
| stock_unlimited | Yes | stockUnlimited | |
| sale_limit | Yes | saleLimit | |
| price01 | Yes | price01 | |
| price02 | Yes | price02 | |
| delivery_fee | Yes | deliveryFee | |
| visible | Yes | productClassVisible | |
| currency_code | Yes | currencyCode | |
| point_rate | Yes | pointRate | |

カバレッジ: 10/10 (100%)

### dtb_payment / Payment Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| payment_method | Yes | paymentMethodName | |
| charge | Yes | paymentCharge | |
| rule_max | Yes | paymentRuleMax | |
| rule_min | Yes | paymentRuleMin | |
| payment_image | Yes | paymentImage | |
| visible | Yes | paymentVisible | |
| sort_no | Yes | sortNo | |
| fixed | Skip | - | 内部フラグ |
| method_class | Skip | - | 内部FQCN |

カバレッジ: 7/7 (100%) ※内部除外

### dtb_delivery / Delivery Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| name | Yes | deliveryMethodName | |
| service_name | Yes | serviceName | |
| description | Yes | deliveryDescription | |
| confirm_url | Yes | confirmUrl | |
| visible | Yes | deliveryVisible | |
| sort_no | Yes | sortNo | |

カバレッジ: 6/6 (100%)

### dtb_tax_rule / TaxRule Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| tax_rate | Yes | taxRuleRate | |
| tax_adjust | Yes | taxRuleAdjust | |
| apply_date | Yes | applyDate | |
| rounding_type_id | Yes | roundingType | FK->mtb |

カバレッジ: 4/4 (100%)

### dtb_page / Page Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| page_name | Yes | pageName | |
| url | Yes | pageUrl | |
| file_name | Yes | pageFileName | |
| edit_type | Yes | pageEditType | |
| author | **No** | - | ページ作成者名 |
| description | **No** | - | meta description |
| keyword | **No** | - | meta keywords |
| meta_robots | **No** | - | robots設定 |
| meta_tags | **No** | - | カスタムmetaタグ |

カバレッジ: 4/9 (44%)

### dtb_delivery_duration / DeliveryDuration Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| name | **No** | - | 表示名（例: 1〜2日） |
| duration | **No** | - | 日数 |
| sort_no | Yes | sortNo | |

カバレッジ: 1/3 (33%)

### dtb_delivery_fee / DeliveryFee Entity

| カラム名 | ALPSにある | ALPSのid | 備考 |
|---|---|---|---|
| fee | **No** | - | 送料金額 |

カバレッジ: 0/1 (0%)（FK除外後）

## ALPS改善案

### 欠落している重要カラムの追加提案

| 提案id | title | 元テーブル.カラム | 優先度 | 理由 |
|---|---|---|---|---|
| addPoint | 付与ポイント | dtb_order.add_point, dtb_cart.add_point | 高 | ポイント機能は既存ALPSで`point`,`basicPointRate`,`pointRate`等が定義済み。受注/カートの付与ポイントが欠落 |
| usePoint | 使用ポイント | dtb_order.use_point, dtb_cart.use_point | 高 | ポイント使用額。paymentTotalの計算に関わる（paymentTotal = total - usePoint * conversionRate） |
| completeMessage | 注文完了メッセージ | dtb_order.complete_message | 中 | 決済プラグインが注文完了画面に表示するメッセージ。カスタマイズポイント |
| completeMailMessage | 注文完了メール追記 | dtb_order.complete_mail_message | 中 | 決済プラグインが注文確認メールに追加するメッセージ |
| pageDescription | ページMETAディスクリプション | dtb_page.description | 中 | SEO用。フロントページの`<meta name="description">`に出力 |
| pageKeyword | ページMETAキーワード | dtb_page.keyword | 低 | SEO用。現在はあまり重要でない |
| pageMetaRobots | METAロボット | dtb_page.meta_robots | 低 | `noindex,nofollow`等のrobots指定 |
| pageMetaTags | カスタムMETAタグ | dtb_page.meta_tags | 低 | 任意のmetaタグをヘッダに追加 |
| pageAuthor | ページ作成者 | dtb_page.author | 低 | meta author |
| deliveryDurationName | お届け日数名 | dtb_delivery_duration.name | 中 | 「1〜2日」等の表示名。配送方法設定で使用 |
| deliveryDurationDays | お届け日数 | dtb_delivery_duration.duration | 中 | 日数値。配送希望日の選択肢計算に使用 |
| deliveryFeeAmount | 送料金額 | dtb_delivery_fee.fee | 高 | 都道府県別送料。送料計算の基礎データ |
| saleType | 販売種別 | mtb_sale_type.name | 中 | 1=通常, 2=ダウンロード等。カート分離の基準 |
| taxDisplayType | 税表示種別 | mtb_tax_display_type.name | 中 | 1=税込表示, 2=税抜表示。受注明細の価格表示方式 |
| taxType | 税種別 | mtb_tax_type.name | 中 | 1=課税, 2=非課税, 3=不課税。受注明細の課税区分 |
| customerOrderStatus | 顧客向け受注ステータス | mtb_customer_order_status.name | 低 | マイページで顧客に表示するステータス名。管理側orderStatusとは異なる表示 |
| deliveryTimeVisible | 配送時間帯表示 | dtb_delivery_time.visible | 低 | 配送時間帯を選択肢として表示するか |

### Master テーブルの扱い

大半のマスタテーブル（mtb_*）はALPSで既にdescriptorとして表現済み:

- mtb_product_status -> `productStatus`（値の説明をdoc内に含む）
- mtb_order_status -> `orderStatus`（値の説明をdoc内に含む）
- mtb_customer_status -> `customerStatus`
- mtb_sex -> `sex`
- mtb_job -> `job`
- mtb_rounding_type -> `roundingType`
- mtb_order_item_type -> `orderItemType`
- mtb_device_type -> `deviceType`
- mtb_authority -> `authority`
- mtb_work -> `work`
- mtb_pref -> `pref`

ALPSに反映すべきマスタテーブル:

| テーブル | 提案 | 理由 |
|---|---|---|
| mtb_sale_type | `saleType` を追加 | カート分離ロジック、配送方法との紐付けに使用。ProductClass構造に影響 |
| mtb_tax_display_type | `taxDisplayType` を追加 | 受注明細の税込/税抜表示。taxRateと合わせて税計算の完全な記述に必要 |
| mtb_tax_type | `taxType` を追加 | 課税/非課税/不課税の区分。インボイス制度対応で重要度上昇 |
| mtb_customer_order_status | `customerOrderStatus` を追加（低優先度） | 顧客向けのステータス名。管理側orderStatusの値とは表示が異なる |

UI設定系マスタ（mtb_page_max, mtb_product_list_max, mtb_product_list_order_by）はフロントの表示設定のためALPS対象外で問題なし。

### dtb_order_pdf について

dtb_order_pdf は帳票PDF出力用の設定テーブル（8カラム欠落）。プラグイン由来のテーブルであり、コアのドメインモデルとは独立している。ALPSへの追加は低優先度。必要であれば `OrderPdf` 構造descriptorとして一括追加可能。

### 構造descriptorへの反映提案

新規セマンティックdescriptorを追加した場合、以下の構造descriptorにも参照を追加する:

- `Order`: `addPoint`, `usePoint`, `completeMessage`, `completeMailMessage`
- `Cart`: `addPoint`, `usePoint`
- `ShoppingConfirm`: `addPoint`, `usePoint`（ポイント表示）
- `Page`: `pageDescription`, `pageKeyword`, `pageMetaRobots`, `pageMetaTags`, `pageAuthor`
- `Delivery`: `deliveryDurationName`, `deliveryDurationDays`, `deliveryFeeAmount`
- `ProductClass`: `saleType`
- `OrderItem`: `taxDisplayType`, `taxType`
- `TaxRule`: `taxType`
