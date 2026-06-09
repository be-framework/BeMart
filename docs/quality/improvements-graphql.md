---
layout: default
title: "eccube-api4 GraphQLスキーマ調査結果とALPS改善案"
---

# eccube-api4 GraphQLスキーマ調査結果とALPS改善案

## GraphQLスキーマの概要

- 定義済み型の数: 38型（Connection/Edge/PageInfo系の中間型を含む）
  - 実体型: 29型（Authority, Category, ClassCategory, ClassName, Country, Customer, CustomerAddress, CustomerFavoriteProduct, CustomerOrderStatus, CustomerStatus, Delivery, DeliveryDuration, DeliveryFee, DeliveryTime, DeviceType, Job, MailHistory, Member, Order, OrderItem, OrderItemType, OrderStatus, OrderStatusColor, Payment, PaymentOption, Pref, Product, ProductCategory, ProductClass, ProductConnection, ProductImage, ProductStatus, ProductStock, ProductTag, RoundingType, SaleType, Sex, Shipping, Tag, TaxDisplayType, TaxRule, TaxType, Work）
  - ページネーション型: 9型（CustomerConnection, CustomerEdge, CustomerPageInfo, OrderConnection, OrderEdge, OrderPageInfo, ProductConnection, ProductEdge, ProductPageInfo）
- Query: 7件（customer, customers, hello, order, orders, product, products）
- Mutation: 2件（updateProductStock, updateShipped）
- カスタムスカラー: 1件（DateTime）

## 型ごとの比較

### Product

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID。ALPSではidフィールドは通常省略 |
| name | YES | productName | OK |
| note | YES | productNote | OK |
| description_list | YES | descriptionList | OK（命名規則差異: snake_case vs camelCase） |
| description_detail | YES | descriptionDetail | OK |
| search_word | YES | searchWord | OK |
| free_area | YES | freeArea | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| ProductCategories | YES | ProductCategory | OK（複合型） |
| ProductClasses | YES | ProductClass | OK |
| ProductImage | YES | ProductImage | OK |
| ProductTag | YES | ProductTag | OK |
| CustomerFavoriteProducts | YES | CustomerFavoriteProduct | OK |
| Creator | YES | Member | ALPSでは Member として定義 |
| Status | YES | productStatus | OK |

### ProductClass

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| code | YES | productCode | OK |
| stock | YES | stock | OK |
| stock_unlimited | YES | stockUnlimited | OK |
| sale_limit | YES | saleLimit | OK |
| price01 | YES | price01 | OK |
| price02 | YES | price02 | OK |
| delivery_fee | YES | deliveryFee | OK |
| visible | YES | productClassVisible | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| currency_code | YES | currencyCode | OK |
| point_rate | YES | pointRate | OK |
| ProductStock | YES | (ProductClass内) | ALPSではProductClass内に含まれる |
| TaxRule | YES | TaxRule | OK |
| Product | YES | Product | OK |
| SaleType | **NO** | - | **欠落**: 販売種別（通常/予約/ダウンロード等） |
| ClassCategory1 | YES | classCategoryName | OK |
| ClassCategory2 | YES | classCategoryName | OK |
| DeliveryDuration | **NO** | - | **欠落**: 発送日目安 |
| Creator | YES | Member | OK |

### ProductStock

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| stock | YES | stock | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| ProductClass | YES | ProductClass | OK |
| Creator | YES | Member | OK |

### ProductImage

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| file_name | YES | fileName | OK |
| sort_no | YES | sortNo | OK |
| create_date | YES | createDate | OK |
| Product | YES | Product | OK |
| Creator | YES | Member | OK |

### ProductCategory

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| product_id | - | - | FK |
| category_id | - | - | FK |
| Product | YES | Product | OK |
| Category | YES | Category | OK |

### ProductTag

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| create_date | YES | createDate | OK |
| Product | YES | Product | OK |
| Tag | YES | Tag | OK |
| Creator | YES | Member | OK |

### Category

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| name | YES | categoryName | OK |
| hierarchy | YES | hierarchy | OK |
| sort_no | YES | sortNo | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| ProductCategories | YES | ProductCategory | OK |
| Children | - | - | カテゴリツリーの子要素。ALPSでは明示的に未定義 |
| Parent | - | - | カテゴリツリーの親要素。ALPSでは明示的に未定義 |
| Creator | YES | Member | OK |

### ClassCategory

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| backend_name | YES | classCategoryBackendName | OK |
| name | YES | classCategoryName | OK |
| sort_no | YES | sortNo | OK |
| visible | YES | classCategoryVisible | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| ClassName | YES | ClassName | OK |
| Creator | YES | Member | OK |

### ClassName

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| backend_name | YES | classNameBackendName | OK |
| name | YES | classNameLabel | OK |
| sort_no | YES | sortNo | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| ClassCategories | YES | ClassCategory | OK |
| Creator | YES | Member | OK |

### Order

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| pre_order_id | YES | preOrderId | OK |
| order_no | YES | orderNo | OK |
| message | YES | message | OK |
| name01 | YES | name01 | OK |
| name02 | YES | name02 | OK |
| kana01 | YES | kana01 | OK |
| kana02 | YES | kana02 | OK |
| company_name | YES | companyName | OK |
| email | YES | email | OK |
| phone_number | YES | phoneNumber | OK |
| postal_code | YES | postalCode | OK |
| addr01 | YES | addr01 | OK |
| addr02 | YES | addr02 | OK |
| birth | YES | birth | OK |
| subtotal | YES | subtotal | OK |
| discount | YES | discount | OK |
| delivery_fee_total | YES | deliveryFeeTotal | OK |
| charge | YES | charge | OK |
| tax | YES | tax | OK |
| total | YES | total | OK |
| payment_total | YES | paymentTotal | OK |
| payment_method | YES | paymentMethod | OK |
| note | YES | orderNote | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| order_date | YES | orderDate | OK |
| payment_date | YES | paymentDate | OK |
| currency_code | YES | currencyCode | OK |
| complete_message | **NO** | - | **欠落**: 注文完了画面のメッセージ（決済プラグイン向け） |
| complete_mail_message | **NO** | - | **欠落**: 注文完了メールの追加メッセージ（決済プラグイン向け） |
| add_point | **NO** | - | **欠落**: 付与ポイント数 |
| use_point | **NO** | - | **欠落**: 使用ポイント数 |
| OrderItems | YES | OrderItem | OK |
| Shippings | YES | Shipping | OK |
| MailHistories | YES | MailHistory | OK |
| Customer | YES | Customer | OK |
| Country | **NO** | - | **欠落**: 国（多言語対応用） |
| Pref | YES | pref | OK |
| Sex | YES | sex | OK |
| Job | YES | job | OK |
| Payment | YES | Payment | OK |
| DeviceType | YES | deviceType | OK |
| CustomerOrderStatus | **NO** | - | **欠落**: 顧客向け受注ステータス（マイページ表示用） |
| OrderStatusColor | **NO** | - | **欠落**: 受注ステータスの表示色 |
| OrderStatus | YES | orderStatus | OK |

### OrderItem

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| product_name | YES | orderItemProductName | OK |
| product_code | YES | orderItemProductCode | OK |
| class_name1 | YES | orderItemClassName1 | OK |
| class_name2 | YES | orderItemClassName2 | OK |
| class_category_name1 | YES | orderItemClassCategoryName1 | OK |
| class_category_name2 | YES | orderItemClassCategoryName2 | OK |
| price | YES | orderItemPrice | OK |
| quantity | YES | orderItemQuantity | OK |
| tax | YES | orderItemTax | OK |
| tax_rate | YES | taxRate | OK |
| tax_adjust | YES | taxAdjust | OK |
| tax_rule_id | **NO** | - | **欠落**: 適用された税率ルールのID（FK） |
| currency_code | YES | currencyCode | OK |
| processor_name | **NO** | - | **欠落**: 処理クラス名（PurchaseFlowで使用） |
| point_rate | YES | pointRate | OK |
| Order | YES | Order | OK |
| Product | YES | Product | OK |
| ProductClass | YES | ProductClass | OK |
| Shipping | YES | Shipping | OK |
| RoundingType | YES | roundingType | OK |
| TaxType | **NO** | - | **欠落**: 課税種別（課税/非課税/不課税） |
| TaxDisplayType | **NO** | - | **欠落**: 税表示種別（税込/税抜） |
| OrderItemType | YES | orderItemType | OK |

### Customer

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| name01 | YES | name01 | OK |
| name02 | YES | name02 | OK |
| kana01 | YES | kana01 | OK |
| kana02 | YES | kana02 | OK |
| company_name | YES | companyName | OK |
| postal_code | YES | postalCode | OK |
| addr01 | YES | addr01 | OK |
| addr02 | YES | addr02 | OK |
| email | YES | email | OK |
| phone_number | YES | phoneNumber | OK |
| birth | YES | birth | OK |
| first_buy_date | YES | firstBuyDate | OK |
| last_buy_date | YES | lastBuyDate | OK |
| buy_times | YES | buyTimes | OK |
| buy_total | YES | buyTotal | OK |
| note | YES | customerNote | OK |
| reset_expire | YES | resetExpire | OK |
| point | YES | point | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| CustomerFavoriteProducts | YES | CustomerFavoriteProduct | OK |
| CustomerAddresses | YES | CustomerAddress | OK |
| Orders | YES | Order | OK |
| Status | YES | customerStatus | OK |
| Sex | YES | sex | OK |
| Job | YES | job | OK |
| Country | **NO** | - | **欠落**: 国 |
| Pref | YES | pref | OK |

### CustomerAddress

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| name01 | YES | name01 | OK |
| name02 | YES | name02 | OK |
| kana01 | YES | kana01 | OK |
| kana02 | YES | kana02 | OK |
| company_name | YES | companyName | OK |
| postal_code | YES | postalCode | OK |
| addr01 | YES | addr01 | OK |
| addr02 | YES | addr02 | OK |
| phone_number | YES | phoneNumber | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| Customer | YES | Customer | OK |
| Country | **NO** | - | **欠落**: 国 |
| Pref | YES | pref | OK |

### Shipping

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| name01 | YES | name01 | OK |
| name02 | YES | name02 | OK |
| kana01 | YES | kana01 | OK |
| kana02 | YES | kana02 | OK |
| company_name | YES | companyName | OK |
| phone_number | YES | phoneNumber | OK |
| postal_code | YES | postalCode | OK |
| addr01 | YES | addr01 | OK |
| addr02 | YES | addr02 | OK |
| shipping_delivery_name | YES | deliveryName | OK |
| time_id | **NO** | - | **欠落**: 配送時間帯ID（FK） |
| shipping_delivery_time | YES | deliveryTime | OK |
| shipping_delivery_date | YES | deliveryDate | OK |
| shipping_date | YES | shippingDate | OK |
| tracking_number | YES | trackingNumber | OK |
| note | YES | shippingNote | OK |
| sort_no | YES | sortNo | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| mail_send_date | YES | mailSendDate | OK |
| Order | YES | Order | OK |
| OrderItems | YES | OrderItem | OK |
| Country | **NO** | - | **欠落**: 国 |
| Pref | YES | pref | OK |
| Delivery | YES | Delivery | OK |
| Creator | YES | Member | OK |

### Delivery

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| name | YES | deliveryMethodName | OK |
| service_name | YES | serviceName | OK |
| description | YES | deliveryDescription | OK |
| confirm_url | YES | confirmUrl | OK |
| sort_no | YES | sortNo | OK |
| visible | YES | deliveryVisible | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| PaymentOptions | **NO** | - | **欠落**: 支払方法との紐付け |
| DeliveryFees | **NO** | - | **欠落**: 地域別送料 |
| DeliveryTimes | **NO** | - | **欠落**: 配送時間帯マスタ |
| Creator | YES | Member | OK |
| SaleType | **NO** | - | **欠落**: 販売種別 |

### DeliveryFee

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| fee | **NO** | - | **欠落**: 地域別送料金額 |
| Delivery | YES | Delivery | OK |
| Pref | YES | pref | OK |

### DeliveryTime

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| delivery_time | **NO** | - | **欠落**: 配送時間帯名称（例: 午前中） |
| sort_no | YES | sortNo | OK |
| visible | **NO** | - | **欠落**: 表示/非表示 |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| Delivery | YES | Delivery | OK |

### DeliveryDuration

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| name | **NO** | - | **欠落**: 発送日目安名称（例: 1-2日後） |
| duration | **NO** | - | **欠落**: 日数 |
| sort_no | YES | sortNo | OK |

### Payment

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| method | YES | paymentMethodName | OK |
| charge | YES | paymentCharge | OK |
| rule_max | YES | paymentRuleMax | OK |
| sort_no | YES | sortNo | OK |
| fixed | **NO** | - | **欠落**: 固定/変動フラグ |
| payment_image | YES | paymentImage | OK |
| rule_min | YES | paymentRuleMin | OK |
| method_class | **NO** | - | **欠落**: 決済処理クラス名（FQCN） |
| visible | YES | paymentVisible | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| PaymentOptions | **NO** | - | **欠落**: 配送方法との紐付け |
| Creator | YES | Member | OK |

### PaymentOption

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| delivery_id | - | - | FK |
| payment_id | - | - | FK |
| Delivery | YES | Delivery | OK |
| Payment | YES | Payment | OK |

### Member

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| name | YES | memberName | OK |
| department | YES | department | OK |
| login_id | YES | loginId | OK |
| sort_no | YES | sortNo | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| login_date | YES | loginDate | OK |
| Work | YES | work | OK |
| Authority | YES | authority | OK |
| Creator | YES | Member | OK |

### MailHistory

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| send_date | YES | sendDate | OK |
| mail_subject | YES | mailSubject | OK |
| mail_body | YES | mailBody | OK |
| mail_html_body | YES | mailHtmlBody | OK |
| Order | YES | Order | OK |
| Creator | YES | Member | OK |

### TaxRule

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| tax_rate | YES | taxRuleRate | OK |
| tax_adjust | YES | taxRuleAdjust | OK |
| apply_date | YES | applyDate | OK |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| ProductClass | YES | ProductClass | OK |
| Creator | YES | Member | OK |
| Country | **NO** | - | **欠落**: 国 |
| Pref | YES | pref | OK |
| Product | YES | Product | OK |
| RoundingType | YES | roundingType | OK |

### マスタ/Enum型

| GraphQL型 | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| Authority | YES | authority | OK（ALPSでは値定義のみ） |
| Country | **NO** | - | **欠落**: 国マスタ（多言語対応） |
| CustomerOrderStatus | **NO** | - | **欠落**: 顧客向け受注ステータス |
| CustomerStatus | YES | customerStatus | OK |
| DeviceType | YES | deviceType | OK |
| Job | YES | job | OK |
| OrderItemType | YES | orderItemType | OK |
| OrderStatus | YES | orderStatus | OK |
| OrderStatusColor | **NO** | - | **欠落**: 受注ステータス表示色 |
| Pref | YES | pref | OK |
| ProductStatus | YES | productStatus | OK |
| RoundingType | YES | roundingType | OK |
| SaleType | **NO** | - | **欠落**: 販売種別マスタ |
| Sex | YES | sex | OK |
| Tag | YES | Tag | OK |
| TaxDisplayType | **NO** | - | **欠落**: 税表示種別 |
| TaxType | **NO** | - | **欠落**: 課税種別 |
| Work | YES | work | OK |

### Mutation

| GraphQL Mutation | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| updateProductStock | 部分的 | doUpdateProduct | ALPSにはstockの個別更新操作はない。doUpdateProductに含まれる想定 |
| updateShipped | 部分的 | doUpdateOrderStatus | ALPSには出荷処理の個別操作がない |

### Query (検索パラメータの観点)

| GraphQL Query パラメータ | ALPSにある | 備考 |
|---|---|---|
| customers.multi | **NO** | 複合検索キーワード（ID/メール/氏名） |
| customers.birth_month | **NO** | 誕生月フィルタ |
| customers.buy_product_name | **NO** | 購入商品名フィルタ |
| orders.multi | **NO** | 複合検索キーワード |
| orders.tracking_number | YES | trackingNumber |
| orders.shipping_mail | **NO** | 出荷メール送信状態フィルタ |
| orders.shipping_delivery_date_start/end | **NO** | お届け日範囲フィルタ |
| products.category_id | **NO** | カテゴリフィルタ |
| products.stock | **NO** | 在庫フィルタ |
| pagination (page, limit) | **NO** | ページネーションパラメータ |

## CustomerFavoriteProduct

| GraphQLフィールド | ALPSにある | ALPSのdescriptor id | 備考 |
|---|---|---|---|
| id | - | - | 内部ID |
| create_date | YES | createDate | OK |
| update_date | YES | updateDate | OK |
| Customer | YES | Customer | OK |
| Product | YES | Product | OK |

## ALPS改善案

### 欠落しているdescriptorの追加提案

| 提案id | title | 根拠（GraphQLのどの型.フィールド） | 優先度 |
|---|---|---|---|
| addPoint | 付与ポイント | Order.add_point | 高 |
| usePoint | 使用ポイント | Order.use_point | 高 |
| saleTypeName | 販売種別名 | SaleType.name | 高 |
| taxType | 課税種別 | TaxType（課税/非課税/不課税）、OrderItem.TaxType | 高 |
| taxDisplayType | 税表示種別 | TaxDisplayType（税込/税抜）、OrderItem.TaxDisplayType | 高 |
| countryName | 国名 | Country.name | 中 |
| deliveryTimeName | 配送時間帯名称 | DeliveryTime.delivery_time | 中 |
| deliveryTimeVisible | 配送時間帯表示 | DeliveryTime.visible | 中 |
| deliveryDurationName | 発送日目安名称 | DeliveryDuration.name | 中 |
| deliveryDurationDays | 発送日目安日数 | DeliveryDuration.duration | 中 |
| deliveryRegionalFee | 地域別送料 | DeliveryFee.fee | 中 |
| customerOrderStatusName | 顧客向け受注ステータス名 | CustomerOrderStatus.name | 中 |
| orderStatusColorName | 受注ステータス色名 | OrderStatusColor.name | 低 |
| completeMessage | 注文完了メッセージ | Order.complete_message | 低 |
| completeMailMessage | 注文完了メール追加メッセージ | Order.complete_mail_message | 低 |
| paymentFixed | 支払方法固定フラグ | Payment.fixed | 低 |
| displayOrderCount | 受注件数表示フラグ | OrderStatus.display_order_count | 低 |

### 既存descriptorの修正・拡充提案

#### 1. Order型にポイント関連フィールドを追加

現状のALPSのOrder descriptorには `point`（会員ポイント残高）への参照はあるが、受注単位の `addPoint`（付与ポイント）と `usePoint`（使用ポイント）が欠落している。GraphQLスキーマでは `add_point` と `use_point` が Order型に含まれている。

```text
提案: Order descriptorに以下を追加
- {"href": "#addPoint"}
- {"href": "#usePoint"}
```

#### 2. SaleType マスタの追加

販売種別（SaleType）はProductClassとDeliveryに関連する重要なマスタだが、ALPSに未定義。カートの分離ロジック（`cartKey`の説明にある「販売種別ごとにカートを分離」）に直接関わる。

```text
提案: 新規 SaleType descriptor を追加
- saleTypeName (semantic descriptor)
- SaleType (composite descriptor with saleTypeName, sortNo)
```

#### 3. TaxType / TaxDisplayType の追加

受注明細（OrderItem）の税計算方式を決定する重要なマスタ。現在のALPSには taxRate と taxAdjust はあるが、課税種別（課税/非課税/不課税）と税表示種別（税込/税抜）が欠落している。

```text
提案:
- taxType: "1=課税, 2=非課税, 3=不課税"
- taxDisplayType: "1=税込, 2=税抜"
- OrderItem descriptorに {"href": "#taxType"}, {"href": "#taxDisplayType"} を追加
```

#### 4. Delivery関連の詳細データ追加

配送方法（Delivery）に関連するDeliveryFee（地域別送料）、DeliveryTime（配送時間帯マスタ）、DeliveryDuration（発送日目安）が ALPSに未定義。

```text
提案:
- DeliveryFee descriptor（deliveryRegionalFee + pref）
- DeliveryTime descriptor（deliveryTimeName + deliveryTimeVisible + sortNo）
- DeliveryDuration descriptor（deliveryDurationName + deliveryDurationDays + sortNo）
- Delivery descriptorの子要素としてこれらへの参照を追加
```

#### 5. PaymentOption（配送方法と支払方法の紐付け）の追加

GraphQLスキーマでは PaymentOption型として定義されており、どの配送方法でどの支払方法が使えるかを制御する中間テーブル。ALPSには未定義。

```text
提案: PaymentOption descriptor を追加
- Delivery descriptorとPayment descriptorにPaymentOptionへの参照を追加
```

#### 6. Country マスタの追加

多言語/海外対応で使用される国マスタ。Customer、CustomerAddress、Order、Shipping、TaxRuleなど複数のエンティティから参照されている。

```text
提案:
- countryName (semantic descriptor)
- Country (composite descriptor)
- Customer, CustomerAddress, Order, Shipping の各descriptorに Country参照を追加
```

#### 7. API固有のMutation操作の検討

GraphQLの `updateProductStock` と `updateShipped` は、管理画面操作（doUpdateProduct, doUpdateOrderStatus）とは異なるAPI専用の操作。ALPSではAPIインタフェースとしてこれらを独立定義する価値がある。

```text
提案:
- doUpdateProductStock: 在庫数の個別更新（code指定、stock/stock_unlimited）
- doUpdateShipped: 出荷処理（id指定、shipping_date/tracking_number/note/is_send_mail）
- tag: "api" を付与してAPI固有操作であることを明示
```

### 命名規則の不整合

GraphQLスキーマではDBカラム名に近い snake_case を使用しているが、ALPSでは camelCase を使用している。これ自体は問題ないが、ALPSからGraphQL APIを自動生成する場合、マッピングルール（camelCase -> snake_case）を明示する必要がある。

| ALPS id | GraphQLフィールド名 | 変換パターン |
|---|---|---|
| productName | name (Product.name) | ALPSでは型プレフィックスを付加 |
| orderNote | note (Order.note) | ALPSでは型プレフィックスを付加 |
| customerNote | note (Customer.note) | ALPSでは型プレフィックスを付加 |
| shippingNote | note (Shipping.note) | ALPSでは型プレフィックスを付加 |
| deliveryFeeTotal | delivery_fee_total | camelCase -> snake_case |

これらのマッピングはALPSのextension等で定義可能だが、現時点では特に対応不要。

### 優先度サマリ

- **高優先度** (ドメインモデルの正確性に直結):
  - addPoint / usePoint（ポイント管理）
  - taxType / taxDisplayType（税計算）
  - saleTypeName / SaleType（カート分離・販売種別）

- **中優先度** (API表現の完全性):
  - DeliveryFee / DeliveryTime / DeliveryDuration（配送詳細）
  - Country（多言語対応）
  - customerOrderStatusName（顧客向け表示）

- **低優先度** (内部管理・UI向け):
  - orderStatusColorName, completeMessage, paymentFixed
  - API専用Mutation（doUpdateProductStock, doUpdateShipped）
