# Term Usage Index

This index reports lexical identifier matches only; it does not prove semantic equivalence.

## Summary

- Terms used in API: 404
- Terms with same-name ALPS descriptor: 152
- Lexical ALPS coverage: 37.6%
- Reserved representation fields: 0
- ☑︎ = ALPS descriptor binding

## Terms

### `Authority`

- usages:
  - schema property: post-admin-authority-role.param.json#/properties/AuthorityRoles/items/properties/Authority

### `AuthorityRoles`

- usages:
  - parameter: POST /admin/authority-role {AuthorityRoles}
  - schema property: post-admin-authority-role.param.json#/properties/AuthorityRoles

### `Mail`

- usages:
  - schema property: get-admin-mail-template.json#/properties/Mail

### `accepted`

- usages:
  - schema property: post-admin-category-csv.json#/properties/accepted
  - schema property: post-admin-order-import-shipping.json#/properties/accepted
  - schema property: post-admin-product-csv-class-category.json#/properties/accepted
  - schema property: post-admin-product-csv-class-name.json#/properties/accepted

### `active`

- usages:
  - schema property: get-admin-template-template-list.json#/properties/templates/items/properties/active

### `addPoint` ☑︎

- title: 付与ポイント
- doc: 注文により付与されるポイント数。商品単価(税抜) x pointRate x 数量で明細ごとに計算し合算。利用ポイント分を控除。発送済み(DELIVERED)遷移時に会員のpointに加算
- usages:
  - schema property: get-admin-order.json#/properties/addPoint
  - schema property: get-mypage-history.json#/properties/addPoint
  - schema property: get-shopping-confirm.json#/properties/addPoint
  - schema property: post-admin-order-create.json#/properties/addPoint
  - schema property: post-shopping-checkout.json#/properties/addPoint

### `addedCount`

- usages:
  - schema property: post-mypage-reorder.json#/properties/addedCount

### `addr01` ☑︎

- title: 市区町村
- def: https://schema.org/addressLocality
- doc: 都道府県より下位の市区町村名
- usages:
  - parameter: POST /admin/base-info {addr01}
  - parameter: POST /admin/create-customer {addr01}
  - parameter: PUT /admin/order/shipping-address {addr01}
  - parameter: POST /entry {addr01}
  - parameter: PUT /mypage/address {addr01}
  - parameter: POST /mypage/address-list {addr01}
  - parameter: POST /mypage/change {addr01}
  - parameter: POST /shopping/non-member {addr01}
  - parameter: POST /shopping/shipping-edit {addr01}
  - parameter: POST /shopping/shipping-multiple-edit {addr01}
  - schema property: post-admin-base-info.param.json#/properties/addr01
  - schema property: post-admin-create-customer.param.json#/properties/addr01
  - schema property: post-entry.param.json#/properties/addr01
  - schema property: post-mypage-address-list.param.json#/properties/addr01
  - schema property: post-mypage-change.param.json#/properties/addr01
  - schema property: post-shopping-non-member.param.json#/properties/addr01
  - schema property: post-shopping-shipping-edit.param.json#/properties/addr01
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/addr01
  - schema property: put-admin-order-shipping-address.param.json#/properties/addr01
  - schema property: put-mypage-address.param.json#/properties/addr01
  - schema property: get-admin-base-info.json#/properties/addr01
  - schema property: get-admin-customer.json#/properties/addr01
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/addr01
  - schema property: get-mypage-change.json#/properties/addr01
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/addr01
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/addr01
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/addr01
  - schema property: get-shopping.json#/properties/defaultShippingAddress/properties/addr01
  - schema property: post-admin-base-info.json#/properties/addr01
  - schema property: post-mypage-address-list.json#/properties/addr01
  - schema property: post-shopping-shipping-edit.json#/properties/addr01
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/addr01
  - schema property: put-admin-order-shipping-address.json#/properties/addr01
  - schema property: put-mypage-address.json#/properties/addr01

### `addr02` ☑︎

- title: 番地・建物名
- def: https://schema.org/streetAddress
- doc: 番地・ビル名・部屋番号等の詳細住所
- usages:
  - parameter: POST /admin/base-info {addr02}
  - parameter: POST /admin/create-customer {addr02}
  - parameter: PUT /admin/order/shipping-address {addr02}
  - parameter: POST /entry {addr02}
  - parameter: PUT /mypage/address {addr02}
  - parameter: POST /mypage/address-list {addr02}
  - parameter: POST /mypage/change {addr02}
  - parameter: POST /shopping/non-member {addr02}
  - parameter: POST /shopping/shipping-edit {addr02}
  - parameter: POST /shopping/shipping-multiple-edit {addr02}
  - schema property: post-admin-base-info.param.json#/properties/addr02
  - schema property: post-admin-create-customer.param.json#/properties/addr02
  - schema property: post-entry.param.json#/properties/addr02
  - schema property: post-mypage-address-list.param.json#/properties/addr02
  - schema property: post-mypage-change.param.json#/properties/addr02
  - schema property: post-shopping-non-member.param.json#/properties/addr02
  - schema property: post-shopping-shipping-edit.param.json#/properties/addr02
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/addr02
  - schema property: put-admin-order-shipping-address.param.json#/properties/addr02
  - schema property: put-mypage-address.param.json#/properties/addr02
  - schema property: get-admin-base-info.json#/properties/addr02
  - schema property: get-admin-customer.json#/properties/addr02
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/addr02
  - schema property: get-mypage-change.json#/properties/addr02
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/addr02
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/addr02
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/addr02
  - schema property: get-shopping.json#/properties/defaultShippingAddress/properties/addr02
  - schema property: post-admin-base-info.json#/properties/addr02
  - schema property: post-mypage-address-list.json#/properties/addr02
  - schema property: post-shopping-shipping-edit.json#/properties/addr02
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/addr02
  - schema property: put-admin-order-shipping-address.json#/properties/addr02
  - schema property: put-mypage-address.json#/properties/addr02

### `addressId` ☑︎

- title: 配送先住所ID
- doc: dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施
- usages:
  - parameter: POST /admin/order/shipping-address {addressId}
  - parameter: GET /mypage/address {addressId}
  - parameter: PUT /mypage/address {addressId}
  - parameter: DELETE /mypage/address {addressId}
  - schema property: delete-mypage-address.param.json#/properties/addressId
  - schema property: get-mypage-address.param.json#/properties/addressId
  - schema property: post-admin-order-shipping-address.param.json#/properties/addressId
  - schema property: post-shopping-shipping-multiple.param.json#/properties/allocations/items/properties/addressId
  - schema property: put-mypage-address.param.json#/properties/addressId
  - schema property: delete-mypage-address.json#/properties/addressId
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/addressId
  - schema property: get-mypage-address.json#/properties/addressId
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/addressId
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/addressId
  - schema property: post-mypage-address-list.json#/properties/addressId
  - schema property: put-mypage-address.json#/properties/addressId

### `addresses`

- usages:
  - schema property: get-mypage-address-list.json#/properties/addresses
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses
  - schema property: get-shopping-shipping.json#/properties/addresses

### `adjustedQuantity`

- usages:
  - schema property: post-cart-item.json#/properties/adjustedQuantity
  - schema property: put-cart-item.json#/properties/adjustedQuantity

### `adminAllowHosts`

- usages:
  - parameter: PUT /admin/security {adminAllowHosts}
  - schema property: put-admin-security.param.json#/properties/adminAllowHosts

### `adminDenyHosts`

- usages:
  - parameter: PUT /admin/security {adminDenyHosts}
  - schema property: put-admin-security.param.json#/properties/adminDenyHosts

### `adminId` ☑︎

- title: 管理者ID
- doc: 管理者メンバーを識別する不透明な文字列ハンドル。Fake と SQL の ID 形状差を隠す。
- usages:
  - schema property: delete-admin-member.json#/properties/adminId
  - schema property: get-admin-member-list.json#/properties/members/items/properties/adminId
  - schema property: get-admin-member.json#/properties/adminId
  - schema property: post-admin-authority-role.json#/properties/adminId
  - schema property: post-admin-change-password.json#/properties/adminId
  - schema property: post-admin-login.json#/properties/adminId
  - schema property: post-admin-logout.json#/properties/adminId
  - schema property: post-admin-member.json#/properties/adminId
  - schema property: put-admin-member.json#/properties/adminId

### `allocationCount`

- usages:
  - schema property: post-shopping-shipping-multiple.json#/properties/allocationCount

### `allocations`

- usages:
  - parameter: POST /shopping/shipping-multiple {allocations}
  - schema property: post-shopping-shipping-multiple.param.json#/properties/allocations

### `alreadyAbsent`

- usages:
  - schema property: delete-mypage-favorite.json#/properties/alreadyAbsent

### `alreadyDeleted`

- usages:
  - schema property: delete-admin-member.json#/properties/alreadyDeleted
  - schema property: delete-admin-product.json#/properties/alreadyDeleted
  - schema property: post-admin-delete-customer.json#/properties/alreadyDeleted

### `alreadyExisted`

- usages:
  - schema property: post-mypage-favorite.json#/properties/alreadyExisted

### `alreadyInstalled`

- usages:
  - schema property: post-admin-plugin-list.json#/properties/alreadyInstalled

### `applyDate` ☑︎

- title: 適用日
- doc: この税率ルールが有効になる日時。適用日以降の注文にこの税率が適用される。複数の税率ルールがある場合、注文日時点で最も新しい適用日のルールが使用される。過去の受注には影響しない
- usages:
  - parameter: POST /admin/tax-rule/tax-rule-list {applyDate}
  - schema property: post-admin-tax-rule-tax-rule-list.param.json#/properties/applyDate
  - schema property: get-admin-tax-rule-tax-rule-list.json#/properties/taxRules/items/properties/applyDate
  - schema property: post-admin-tax-rule-tax-rule-list.json#/properties/applyDate

### `archiveName`

- usages:
  - schema property: post-admin-template-template-add.json#/properties/archiveName

### `archiveSize`

- usages:
  - schema property: post-admin-template-template-add.json#/properties/archiveSize

### `arrFileList`

- usages:
  - schema property: get-admin-content-file-manager.json#/properties/arrFileList

### `authKey`

- usages:
  - parameter: PUT /admin/two-factor-auth-set {authKey}
  - schema property: put-admin-two-factor-auth-set.param.json#/properties/authKey
  - schema property: get-admin-two-factor-auth-edit.json#/properties/authKey
  - schema property: get-admin-two-factor-auth-set.json#/properties/authKey

### `authority` ☑︎

- title: 権限
- doc: 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御
- usages:
  - parameter: POST /admin/authority-role {authority}
  - parameter: POST /admin/member {authority}
  - schema property: post-admin-authority-role.param.json#/properties/AuthorityRoles/items/properties/authority
  - schema property: post-admin-authority-role.param.json#/properties/authority
  - schema property: post-admin-member.param.json#/properties/authority
  - schema property: get-admin-authority-role.json#/properties/rules/items/properties/authority
  - schema property: get-admin-member-list.json#/properties/members/items/properties/authority
  - schema property: get-admin-member.json#/properties/authority
  - schema property: post-admin-authority-role.json#/properties/authority
  - schema property: post-admin-authority-role.json#/properties/rules/items/properties/authority
  - schema property: post-admin-login.json#/properties/authority
  - schema property: post-admin-member.json#/properties/authority
  - schema property: put-admin-member.json#/properties/authority

### `authorityOptions`

- usages:
  - schema property: get-admin-authority-role.json#/properties/authorityOptions

### `birth` ☑︎

- title: 生年月日
- def: https://schema.org/birthDate
- doc: 会員の生年月日
- usages:
  - parameter: POST /admin/create-customer {birth}
  - parameter: POST /entry {birth}
  - schema property: post-admin-create-customer.param.json#/properties/birth
  - schema property: post-entry.param.json#/properties/birth
  - schema property: get-admin-customer.json#/properties/birth

### `birth_day`

- usages:
  - parameter: POST /entry {birth_day}
  - schema property: post-entry.param.json#/properties/birth_day

### `birth_month`

- usages:
  - parameter: POST /entry {birth_month}
  - schema property: post-entry.param.json#/properties/birth_month

### `birth_year`

- usages:
  - parameter: POST /entry {birth_year}
  - schema property: post-entry.param.json#/properties/birth_year

### `blockDeletable` ☑︎

- title: ブロック削除可否
- doc: このブロックを管理画面から削除できるか。システム標準ブロックは削除不可
- usages:
  - schema property: get-admin-block-block-list.json#/properties/blocks/items/properties/blockDeletable
  - schema property: get-admin-block-block.json#/properties/blockDeletable
  - schema property: post-admin-block-block-list.json#/properties/blockDeletable
  - schema property: put-admin-block-block.json#/properties/blockDeletable

### `blockFileName` ☑︎

- title: ブロックファイル名
- doc: ブロックのテンプレートファイル名
- usages:
  - parameter: PUT /admin/block/block {blockFileName}
  - parameter: POST /admin/block/block-list {blockFileName}
  - schema property: post-admin-block-block-list.param.json#/properties/blockFileName
  - schema property: put-admin-block-block.param.json#/properties/blockFileName
  - schema property: get-admin-block-block-list.json#/properties/blocks/items/properties/blockFileName
  - schema property: get-admin-block-block.json#/properties/blockFileName
  - schema property: post-admin-block-block-list.json#/properties/blockFileName
  - schema property: put-admin-block-block.json#/properties/blockFileName

### `blockId` ☑︎

- title: ブロックID
- doc: dtb_block.id の不透明な文字列ハンドル。BeMart の BlockEntity 層は数値ではなく文字列として保持する。Fake 実装は `bk-` プレフィックス付きの英数字を生成し（シード `bk-header` を含む）、SQL 実装は dtb_block.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlBlockStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (BlockUpdated / BlockDeleted) を踏むため、シードハンドル `bk-header` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: GET /admin/block/block {blockId}
  - parameter: PUT /admin/block/block {blockId}
  - parameter: DELETE /admin/block/block {blockId}
  - schema property: delete-admin-block-block.param.json#/properties/blockId
  - schema property: get-admin-block-block.param.json#/properties/blockId
  - schema property: put-admin-block-block.param.json#/properties/blockId
  - schema property: delete-admin-block-block.json#/properties/blockId
  - schema property: get-admin-block-block-list.json#/properties/blocks/items/properties/blockId
  - schema property: get-admin-block-block.json#/properties/blockId
  - schema property: post-admin-block-block-list.json#/properties/blockId
  - schema property: put-admin-block-block.json#/properties/blockId

### `blockName` ☑︎

- title: ブロック名
- doc: ブロックの表示名
- usages:
  - parameter: PUT /admin/block/block {blockName}
  - parameter: POST /admin/block/block-list {blockName}
  - schema property: post-admin-block-block-list.param.json#/properties/blockName
  - schema property: put-admin-block-block.param.json#/properties/blockName
  - schema property: get-admin-block-block-list.json#/properties/blocks/items/properties/blockName
  - schema property: get-admin-block-block.json#/properties/blockName
  - schema property: post-admin-block-block-list.json#/properties/blockName
  - schema property: put-admin-block-block.json#/properties/blockName

### `blocks`

- usages:
  - schema property: get-admin-block-block-list.json#/properties/blocks

### `body`

- usages:
  - schema property: get-help-about.json#/properties/staticContent/properties/sections/items/properties/body
  - schema property: get-help-agreement.json#/properties/staticContent/properties/sections/items/properties/body
  - schema property: get-help-guide.json#/properties/staticContent/properties/sections/items/properties/body
  - schema property: get-help-privacy.json#/properties/staticContent/properties/sections/items/properties/body
  - schema property: get-help-trade-law.json#/properties/staticContent/properties/sections/items/properties/body
  - schema property: get-shopping-error.json#/properties/staticContent/properties/sections/items/properties/body

### `businessHour` ☑︎

- title: 営業時間
- doc: ショップの営業時間。フリーフォーマット
- usages:
  - parameter: POST /admin/base-info {businessHour}
  - schema property: post-admin-base-info.param.json#/properties/businessHour
  - schema property: get-admin-base-info.json#/properties/businessHour
  - schema property: post-admin-base-info.json#/properties/businessHour

### `calendarId`

- usages:
  - parameter: POST /admin/calendar {calendarId}
  - parameter: DELETE /admin/calendar {calendarId}
  - schema property: delete-admin-calendar.param.json#/properties/calendarId
  - schema property: post-admin-calendar.param.json#/properties/calendarId
  - schema property: delete-admin-calendar.json#/properties/calendarId
  - schema property: post-admin-calendar.json#/properties/calendarId

### `calendars`

- usages:
  - schema property: get-admin-calendar.json#/properties/calendars

### `canCheckout`

- usages:
  - schema property: get-shopping.json#/properties/canCheckout

### `cartCount`

- usages:
  - schema property: get-cart.json#/properties/cartCount
  - schema property: get-shopping.json#/properties/cartCount

### `cartItems`

- usages:
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems

### `cartKey` ☑︎

- title: カートキー
- doc: カート分離キー。形式: {セッションプレフィックス}_{販売種別ID}。EC-CUBEは販売種別ごとにカートを分離するため、異なる販売種別の商品は別カートになる
- usages:
  - schema property: post-shopping-shipping-multiple.param.json#/properties/allocations/items/properties/cartKey
  - schema property: delete-cart-item.json#/properties/cartKey
  - schema property: get-cart.json#/properties/carts/items/properties/cartKey
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/cartKey
  - schema property: get-shopping.json#/properties/carts/items/properties/cartKey
  - schema property: post-cart-item.json#/properties/cartKey
  - schema property: put-cart-item.json#/properties/cartKey

### `cartKeys`

- usages:
  - schema property: post-mypage-reorder.json#/properties/cartKeys

### `carts`

- usages:
  - schema property: get-cart.json#/properties/carts
  - schema property: get-shopping.json#/properties/carts

### `categories`

- usages:
  - schema property: get-admin-category-category-list.json#/properties/categories
  - schema property: get-admin-category-edit.json#/properties/categories

### `category`

- usages:
  - schema property: get-admin-category-edit.json#/properties/category

### `categoryId` ☑︎

- title: カテゴリID
- doc: dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/category/category {categoryId}
  - parameter: PUT /admin/category/category {categoryId}
  - parameter: DELETE /admin/category/category {categoryId}
  - parameter: GET /admin/category/edit {categoryId}
  - schema property: delete-admin-category-category.param.json#/properties/categoryId
  - schema property: get-admin-category-category.param.json#/properties/categoryId
  - schema property: get-admin-category-edit.param.json#/properties/categoryId
  - schema property: put-admin-category-category.param.json#/properties/categoryId
  - schema property: delete-admin-category-category.json#/properties/categoryId
  - schema property: get-admin-category-category-list.json#/properties/categories/items/properties/categoryId
  - schema property: get-admin-category-category.json#/properties/categoryId
  - schema property: get-admin-category-edit.json#/properties/categories/items/properties/categoryId
  - schema property: get-admin-category-edit.json#/properties/categoryId
  - schema property: post-admin-category-category-list.json#/properties/categoryId
  - schema property: put-admin-category-category.json#/properties/categoryId

### `categoryName` ☑︎

- title: カテゴリ名
- doc: カテゴリの表示名
- usages:
  - parameter: PUT /admin/category/category {categoryName}
  - parameter: POST /admin/category/category-list {categoryName}
  - schema property: post-admin-category-category-list.param.json#/properties/categoryName
  - schema property: put-admin-category-category.param.json#/properties/categoryName
  - schema property: get-admin-category-category-list.json#/properties/categories/items/properties/categoryName
  - schema property: get-admin-category-category.json#/properties/categoryName
  - schema property: get-admin-category-edit.json#/properties/categories/items/properties/categoryName
  - schema property: get-products.json#/properties/filters/properties/categoryName
  - schema property: post-admin-category-category-list.json#/properties/categoryName
  - schema property: put-admin-category-category.json#/properties/categoryName

### `categoryNames`

- usages:
  - schema property: get-admin-product-list.json#/properties/products/items/properties/categoryNames
  - schema property: get-admin-product.json#/properties/categoryNames
  - schema property: get-product.json#/properties/categoryNames
  - schema property: get-products.json#/properties/products/items/properties/categoryNames

### `category_id`

- usages:
  - parameter: GET /products {category_id}
  - schema property: get-products.param.json#/properties/category_id
  - schema property: get-products.json#/properties/filters/properties/category_id

### `changePasswordFirst`

- usages:
  - parameter: POST /admin/change-password {changePasswordFirst}
  - schema property: post-admin-change-password.param.json#/properties/changePasswordFirst

### `changePasswordSecond`

- usages:
  - parameter: POST /admin/change-password {changePasswordSecond}
  - schema property: post-admin-change-password.param.json#/properties/changePasswordSecond

### `changed`

- usages:
  - schema property: post-admin-authority-role.json#/properties/changed
  - schema property: post-admin-base-info.json#/properties/changed
  - schema property: post-admin-mail-template.json#/properties/changed
  - schema property: post-admin-order-status.json#/properties/changed
  - schema property: post-admin-plugin-disable.json#/properties/changed
  - schema property: post-admin-plugin-enable.json#/properties/changed
  - schema property: post-admin-trade-law.json#/properties/changed

### `changedCount`

- usages:
  - schema property: post-admin-order-bulk-delete.json#/properties/changedCount
  - schema property: post-admin-product-bulk-status.json#/properties/changedCount

### `charge` ☑︎

- title: 手数料
- doc: 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる
- usages:
  - parameter: PUT /admin/order {charge}
  - parameter: POST /admin/order/create {charge}
  - parameter: PUT /admin/payment/payment {charge}
  - parameter: POST /admin/payment/payment-list {charge}
  - schema property: post-admin-order-create.param.json#/properties/charge
  - schema property: post-admin-payment-payment-list.param.json#/properties/charge
  - schema property: put-admin-order.param.json#/properties/charge
  - schema property: put-admin-payment-payment.param.json#/properties/charge
  - schema property: get-admin-order.json#/properties/charge
  - schema property: get-admin-payment-payment-list.json#/properties/payments/items/properties/charge
  - schema property: get-mypage-history.json#/properties/charge
  - schema property: get-shopping-confirm.json#/properties/charge
  - schema property: post-admin-order-create.json#/properties/charge
  - schema property: post-admin-payment-payment-list.json#/properties/charge
  - schema property: put-admin-order.json#/properties/charge
  - schema property: put-admin-payment-payment.json#/properties/charge

### `classCategories`

- usages:
  - schema property: get-admin-class-category-class-category-list.json#/properties/classCategories

### `classCategoryId` ☑︎

- title: 規格分類ID
- doc: dtb_class_category.id の不透明な文字列ハンドル。BeMart の ClassCategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格分類の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。classNameId / categoryId / blockId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: PUT /admin/class-category/class-category {classCategoryId}
  - parameter: DELETE /admin/class-category/class-category {classCategoryId}
  - schema property: delete-admin-class-category-class-category.param.json#/properties/classCategoryId
  - schema property: put-admin-class-category-class-category.param.json#/properties/classCategoryId
  - schema property: delete-admin-class-category-class-category.json#/properties/classCategoryId
  - schema property: get-admin-class-category-class-category-list.json#/properties/classCategories/items/properties/classCategoryId
  - schema property: get-admin-product-product-class.json#/properties/classes/items/properties/classCategoryId
  - schema property: post-admin-class-category-class-category-list.json#/properties/classCategoryId
  - schema property: put-admin-class-category-class-category.json#/properties/classCategoryId

### `classCategoryName` ☑︎

- title: 規格分類名
- doc: 商品バリエーション軸の具体的な値（例: 赤、Lサイズ）。EC-CUBEの"classCategory"はOOPのカテゴリではなく規格値を意味する
- usages:
  - parameter: PUT /admin/class-category/class-category {classCategoryName}
  - parameter: POST /admin/class-category/class-category-list {classCategoryName}
  - schema property: post-admin-class-category-class-category-list.param.json#/properties/classCategoryName
  - schema property: put-admin-class-category-class-category.param.json#/properties/classCategoryName
  - schema property: get-admin-product-product-class.json#/properties/classes/items/properties/classCategoryName

### `classCategoryName1`

- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/classCategoryName1

### `classCategoryName2`

- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/classCategoryName2

### `className`

- usages:
  - schema property: get-admin-product-product-class.json#/properties/classes/items/properties/className

### `className1`

- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/className1

### `className2`

- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/className2

### `classNameId` ☑︎

- title: 規格名ID
- doc: dtb_class_name.id の不透明な文字列ハンドル。BeMart の ClassNameEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_name.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassNameStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格名の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/class-category/class-category-export {classNameId}
  - parameter: GET /admin/class-category/class-category-list {classNameId}
  - parameter: POST /admin/class-category/class-category-list {classNameId}
  - parameter: PUT /admin/class-name/class-name {classNameId}
  - parameter: DELETE /admin/class-name/class-name {classNameId}
  - schema property: delete-admin-class-name-class-name.param.json#/properties/classNameId
  - schema property: get-admin-class-category-class-category-export.param.json#/properties/classNameId
  - schema property: get-admin-class-category-class-category-list.param.json#/properties/classNameId
  - schema property: post-admin-class-category-class-category-list.param.json#/properties/classNameId
  - schema property: put-admin-class-name-class-name.param.json#/properties/classNameId
  - schema property: delete-admin-class-name-class-name.json#/properties/classNameId
  - schema property: get-admin-class-category-class-category-list.json#/properties/classCategories/items/properties/classNameId
  - schema property: get-admin-class-category-class-category-list.json#/properties/classNameId
  - schema property: get-admin-class-name-class-name-list.json#/properties/classNames/items/properties/classNameId
  - schema property: get-admin-product-product-class.json#/properties/classes/items/properties/classNameId
  - schema property: post-admin-class-category-class-category-list.json#/properties/classNameId
  - schema property: post-admin-class-name-class-name-list.json#/properties/classNameId
  - schema property: put-admin-class-category-class-category.json#/properties/classNameId
  - schema property: put-admin-class-name-class-name.json#/properties/classNameId

### `classNameLabel` ☑︎

- title: 規格名
- doc: 商品バリエーション軸の名前（例: カラー、サイズ）。EC-CUBEの"class"はOOPのクラスではなく商品規格を意味する
- usages:
  - parameter: PUT /admin/class-name/class-name {classNameLabel}
  - parameter: POST /admin/class-name/class-name-list {classNameLabel}
  - schema property: post-admin-class-name-class-name-list.param.json#/properties/classNameLabel
  - schema property: put-admin-class-name-class-name.param.json#/properties/classNameLabel

### `classNames`

- usages:
  - schema property: get-admin-class-name-class-name-list.json#/properties/classNames
  - schema property: get-admin-product.json#/properties/classNames
  - schema property: get-product.json#/properties/classNames

### `classes`

- usages:
  - schema property: get-admin-product-product-class.json#/properties/classes

### `cleared`

- usages:
  - schema property: post-mypage-withdraw.json#/properties/cleared

### `clientIp` ☑︎

- title: クライアントIP
- doc: ログイン試行元のIPアドレス。セキュリティ監査用
- usages:
  - schema property: get-admin-login-history.json#/properties/entries/items/properties/clientIp

### `color`

- usages:
  - schema property: put-admin-order-status.param.json#/properties/orderStatuses/items/properties/color
  - schema property: get-admin-index.json#/properties/orderStatuses/items/properties/color

### `colorKey`

- usages:
  - schema property: get-admin-order-status.json#/properties/orderStatuses/items/properties/colorKey

### `columnName`

- usages:
  - schema property: post-admin-csv-config.param.json#/properties/columns/items/properties/columnName

### `columns`

- usages:
  - parameter: POST /admin/csv-config {columns}
  - schema property: post-admin-csv-config.param.json#/properties/columns
  - schema property: get-admin-product-csv-category.json#/properties/columns
  - schema property: get-admin-product-csv-class-category.json#/properties/columns
  - schema property: get-admin-product-csv-class-name.json#/properties/columns
  - schema property: get-admin-product-csv-product.json#/properties/columns
  - schema property: post-admin-csv-config.json#/properties/columns

### `companyName` ☑︎

- title: 会社名
- def: https://schema.org/name
- doc: 法人顧客の社名。B2B取引やインボイスで使用
- usages:
  - parameter: POST /admin/base-info {companyName}
  - parameter: POST /admin/create-customer {companyName}
  - parameter: POST /entry {companyName}
  - parameter: PUT /mypage/address {companyName}
  - parameter: POST /mypage/address-list {companyName}
  - parameter: POST /mypage/change {companyName}
  - parameter: POST /shopping/shipping-edit {companyName}
  - parameter: POST /shopping/shipping-multiple-edit {companyName}
  - schema property: post-admin-base-info.param.json#/properties/companyName
  - schema property: post-admin-create-customer.param.json#/properties/companyName
  - schema property: post-entry.param.json#/properties/companyName
  - schema property: post-mypage-address-list.param.json#/properties/companyName
  - schema property: post-mypage-change.param.json#/properties/companyName
  - schema property: post-shopping-non-member.param.json#/properties/companyName
  - schema property: post-shopping-shipping-edit.param.json#/properties/companyName
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/companyName
  - schema property: put-mypage-address.param.json#/properties/companyName
  - schema property: get-admin-base-info.json#/properties/companyName
  - schema property: get-admin-customer.json#/properties/companyName
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/companyName
  - schema property: get-mypage-change.json#/properties/companyName
  - schema property: post-admin-base-info.json#/properties/companyName
  - schema property: post-mypage-address-list.json#/properties/companyName
  - schema property: post-shopping-shipping-edit.json#/properties/companyName
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/companyName
  - schema property: put-mypage-address.json#/properties/companyName

### `completeMessage` ☑︎

- title: 注文完了メッセージ
- doc: 注文完了画面に表示するメッセージ。主に決済プラグインが設定するカスタムメッセージ。複数プラグインからの利用を想定しappendCompleteMesssage()で追記する。HTML使用可
- usages:
  - schema property: get-shopping-complete.json#/properties/completeMessage
  - schema property: post-shopping-checkout.json#/properties/completeMessage

### `contactContents` ☑︎

- title: お問い合わせ内容
- doc: お問い合わせフォームの本文
- usages:
  - parameter: POST /contact {contactContents}
  - schema property: post-contact.param.json#/properties/contactContents

### `contactEmail` ☑︎

- title: お問い合わせメール
- def: https://schema.org/email
- doc: お問い合わせフォームのメールアドレス
- usages:
  - parameter: POST /contact {contactEmail}
  - schema property: post-contact.param.json#/properties/contactEmail
  - schema property: post-contact.json#/properties/contactEmail

### `contactName01` ☑︎

- title: お問い合わせ姓
- doc: お問い合わせフォームの姓。内部的にはNameTypeのname01と同じ仕組み
- usages:
  - parameter: POST /contact {contactName01}
  - schema property: post-contact.param.json#/properties/contactName01
  - schema property: post-contact.json#/properties/contactName01

### `contactName02` ☑︎

- title: お問い合わせ名
- doc: お問い合わせフォームの名。内部的にはNameTypeのname02と同じ仕組み
- usages:
  - parameter: POST /contact {contactName02}
  - schema property: post-contact.param.json#/properties/contactName02
  - schema property: post-contact.json#/properties/contactName02

### `content`

- usages:
  - schema property: get-help-about.json#/properties/staticContent/properties/sections/items/properties/content
  - schema property: get-help-agreement.json#/properties/staticContent/properties/sections/items/properties/content
  - schema property: get-help-guide.json#/properties/staticContent/properties/sections/items/properties/content
  - schema property: get-help-privacy.json#/properties/staticContent/properties/sections/items/properties/content
  - schema property: get-help-trade-law.json#/properties/staticContent/properties/sections/items/properties/content
  - schema property: get-shopping-error.json#/properties/staticContent/properties/sections/items/properties/content

### `count`

- usages:
  - schema property: put-admin-order-status.param.json#/properties/orderStatuses/items/properties/count
  - schema property: get-admin-block-block-list.json#/properties/count
  - schema property: get-admin-category-category-list.json#/properties/count
  - schema property: get-admin-category-edit.json#/properties/count
  - schema property: get-admin-class-category-class-category-list.json#/properties/count
  - schema property: get-admin-class-name-class-name-list.json#/properties/count
  - schema property: get-admin-customer-list.json#/properties/count
  - schema property: get-admin-delivery-delivery-list.json#/properties/count
  - schema property: get-admin-index.json#/properties/orderStatuses/items/properties/count
  - schema property: get-admin-layout-layout-list.json#/properties/count
  - schema property: get-admin-login-history.json#/properties/count
  - schema property: get-admin-mail-template.json#/properties/count
  - schema property: get-admin-member-list.json#/properties/count
  - schema property: get-admin-news-news-list.json#/properties/count
  - schema property: get-admin-order-list.json#/properties/count
  - schema property: get-admin-page-page-list.json#/properties/count
  - schema property: get-admin-payment-payment-list.json#/properties/count
  - schema property: get-admin-plugin-list.json#/properties/count
  - schema property: get-admin-product-csv.json#/properties/count
  - schema property: get-admin-product-list.json#/properties/count
  - schema property: get-admin-tag-tag-list.json#/properties/count
  - schema property: get-admin-tax-rule-tax-rule-list.json#/properties/count
  - schema property: get-admin-template-template-list.json#/properties/count
  - schema property: get-mypage-address-list.json#/properties/count
  - schema property: post-admin-authority-role.json#/properties/count
  - schema property: post-admin-csv-config.json#/properties/count
  - schema property: post-admin-product-csv.json#/properties/count
  - schema property: put-admin-master-data-edit.json#/properties/count
  - schema property: put-admin-order-status.json#/properties/count

### `countCustomers`

- usages:
  - schema property: get-admin-index.json#/properties/countCustomers

### `countNonStockProducts`

- usages:
  - schema property: get-admin-index.json#/properties/countNonStockProducts

### `countProducts`

- usages:
  - schema property: get-admin-index.json#/properties/countProducts

### `csrfToken`

- usages:
  - parameter: POST /admin/authority-role {csrfToken}
  - schema property: delete-admin-tag-tag.param.json#/properties/csrfToken
  - schema property: post-admin-authority-role.param.json#/properties/csrfToken
  - schema property: post-admin-category-csv.param.json#/properties/csrfToken
  - schema property: post-admin-csv-config.param.json#/properties/csrfToken
  - schema property: post-admin-order-shipping-notify-mail.param.json#/properties/csrfToken
  - schema property: post-admin-product-csv-class-category.param.json#/properties/csrfToken
  - schema property: post-admin-product-csv-class-name.param.json#/properties/csrfToken
  - schema property: post-admin-product-csv.param.json#/properties/csrfToken
  - schema property: post-admin-tag-tag-list.param.json#/properties/csrfToken
  - schema property: post-mypage-reorder.param.json#/properties/csrfToken
  - schema property: post-shopping-confirm.param.json#/properties/csrfToken
  - schema property: put-admin-master-data-edit.param.json#/properties/csrfToken
  - schema property: put-admin-master-data.param.json#/properties/csrfToken
  - schema property: get-admin-authority-role.json#/properties/csrfToken
  - schema property: get-admin-block-block.json#/properties/csrfToken
  - schema property: get-admin-calendar.json#/properties/csrfToken
  - schema property: get-admin-content-cache.json#/properties/csrfToken
  - schema property: get-admin-content-css.json#/properties/csrfToken
  - schema property: get-admin-content-js.json#/properties/csrfToken
  - schema property: get-admin-content-maintenance.json#/properties/csrfToken
  - schema property: get-admin-csv-config.json#/properties/csrfToken
  - schema property: get-admin-layout-layout.json#/properties/csrfToken
  - schema property: get-admin-login.json#/properties/csrfToken
  - schema property: get-admin-mail-template.json#/properties/csrfToken
  - schema property: get-admin-member-list.json#/properties/csrfToken
  - schema property: get-admin-member.json#/properties/csrfToken
  - schema property: get-admin-news-news.json#/properties/csrfToken
  - schema property: get-admin-order-edit.json#/properties/csrfToken
  - schema property: get-admin-order-shipping-notify-mail.json#/properties/csrfToken
  - schema property: get-admin-order-status.json#/properties/csrfToken
  - schema property: get-admin-order.json#/properties/csrfToken
  - schema property: get-admin-page-page.json#/properties/csrfToken
  - schema property: get-admin-product-new.json#/properties/csrfToken
  - schema property: get-admin-product.json#/properties/csrfToken
  - schema property: get-admin-security.json#/properties/csrfToken
  - schema property: get-admin-template-template-add.json#/properties/csrfToken
  - schema property: get-admin-template-template-list.json#/properties/csrfToken
  - schema property: get-admin-trade-law.json#/properties/csrfToken
  - schema property: get-admin-two-factor-auth-set.json#/properties/csrfToken
  - schema property: get-admin-two-factor-auth.json#/properties/csrfToken
  - schema property: get-cart.json#/properties/csrfToken
  - schema property: get-contact.json#/properties/csrfToken
  - schema property: get-entry.json#/properties/csrfToken
  - schema property: get-forgot-password.json#/properties/csrfToken
  - schema property: get-login.json#/properties/csrfToken
  - schema property: get-mypage-address.json#/properties/csrfToken
  - schema property: get-mypage-withdraw-confirm.json#/properties/csrfToken
  - schema property: get-mypage-withdraw.json#/properties/csrfToken
  - schema property: get-product.json#/properties/csrfToken
  - schema property: get-products.json#/properties/csrfToken
  - schema property: get-reset.json#/properties/csrfToken
  - schema property: get-shopping-confirm.json#/properties/csrfToken
  - schema property: get-shopping-non-member.json#/properties/csrfToken
  - schema property: get-shopping-shipping-edit.json#/properties/csrfToken
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/csrfToken
  - schema property: get-shopping-shipping.json#/properties/csrfToken
  - schema property: get-shopping.json#/properties/csrfToken

### `css`

- usages:
  - parameter: PUT /admin/content/css {css}
  - schema property: put-admin-content-css.param.json#/properties/css

### `csv`

- usages:
  - parameter: POST /admin/category/csv {csv}
  - parameter: POST /admin/order/import-shipping {csv}
  - parameter: POST /admin/product-csv {csv}
  - parameter: POST /admin/product/csv-class-category {csv}
  - parameter: POST /admin/product/csv-class-name {csv}
  - schema property: post-admin-category-csv.param.json#/properties/csv
  - schema property: post-admin-order-import-shipping.param.json#/properties/csv
  - schema property: post-admin-product-csv-class-category.param.json#/properties/csv
  - schema property: post-admin-product-csv-class-name.param.json#/properties/csv
  - schema property: post-admin-product-csv.param.json#/properties/csv
  - schema property: get-admin-category-csv.json#/properties/csv
  - schema property: get-admin-customer-csv.json#/properties/csv
  - schema property: get-admin-order-export-order.json#/properties/csv
  - schema property: get-admin-order-export-shipping.json#/properties/csv
  - schema property: get-admin-product-csv.json#/properties/csv

### `csvNotOutput`

- usages:
  - parameter: POST /admin/csv-config {csvNotOutput}
  - schema property: post-admin-csv-config.param.json#/properties/csvNotOutput

### `csvOutput`

- usages:
  - parameter: POST /admin/csv-config {csvOutput}
  - schema property: post-admin-csv-config.param.json#/properties/csvOutput

### `csvTitle`

- usages:
  - schema property: get-admin-product-csv-category.json#/properties/csvTitle
  - schema property: get-admin-product-csv-class-category.json#/properties/csvTitle
  - schema property: get-admin-product-csv-class-name.json#/properties/csvTitle
  - schema property: get-admin-product-csv-product.json#/properties/csvTitle

### `csvType` ☑︎

- title: CSV種別
- doc: dtb_csv.csv_type_id — mtb_csv_type への FK（1=注文CSV, 2=会員CSV, 3=商品CSV, 4=出荷CSV）。1つの csvType が複数の列設定行（dtb_csv 行）を所有する。doUpdateCsv は1つの csvType の列ベクタ全体を一括 POST し、SqlCsvColumnConfigStorage::replaceType がその csvType の行集合をアトミックに置換する。mtb_csv_type は structure-only ダンプで空のため SQL テストは seedCsvTypes でシード（seedAdminMasters と同じ空マスタ FK シード規約）
- usages:
  - parameter: GET /admin/csv-config {csvType}
  - parameter: POST /admin/csv-config {csvType}
  - schema property: get-admin-csv-config.param.json#/properties/csvType
  - schema property: post-admin-csv-config.param.json#/properties/csvType
  - schema property: get-admin-csv-config.json#/properties/csvType
  - schema property: post-admin-csv-config.json#/properties/csvType

### `current`

- usages:
  - schema property: get-products.json#/properties/pager/properties/current

### `currentPassword`

- usages:
  - parameter: POST /admin/change-password {currentPassword}
  - schema property: post-admin-change-password.param.json#/properties/currentPassword

### `customer`

- usages:
  - schema property: get-admin-order.json#/properties/customer
  - schema property: get-shopping-confirm.json#/properties/customer

### `customerId` ☑︎

- title: 会員ID
- doc: dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用
- usages:
  - parameter: GET /admin/customer {customerId}
  - parameter: GET /admin/customer-delivery-edit {customerId}
  - parameter: POST /admin/delete-customer {customerId}
  - parameter: POST /admin/order/create {customerId}
  - schema property: get-admin-customer-delivery-edit.param.json#/properties/customerId
  - schema property: get-admin-customer.param.json#/properties/customerId
  - schema property: post-admin-delete-customer.param.json#/properties/customerId
  - schema property: post-admin-order-create.param.json#/properties/customerId
  - schema property: delete-mypage-address.json#/properties/customerId
  - schema property: delete-mypage-favorite.json#/properties/customerId
  - schema property: get-admin-customer-delivery-edit.json#/properties/customerId
  - schema property: get-admin-customer-list.json#/properties/customers/items/properties/customerId
  - schema property: get-admin-customer.json#/properties/customerId
  - schema property: get-admin-order-list.json#/properties/orders/items/properties/customerId
  - schema property: get-admin-order-shipping-notify-mail.json#/properties/customerId
  - schema property: get-admin-order.json#/properties/customerId
  - schema property: get-mypage-address-list.json#/properties/customerId
  - schema property: get-mypage-change.json#/properties/customerId
  - schema property: get-mypage-favorite-list.json#/properties/customerId
  - schema property: get-mypage-order-history.json#/properties/customerId
  - schema property: get-mypage-withdraw.json#/properties/customerId
  - schema property: get-mypage.json#/properties/customerId
  - schema property: get-shopping.json#/properties/customerId
  - schema property: post-admin-create-customer.json#/properties/customerId
  - schema property: post-admin-customer-resend-activation-mail.json#/properties/customerId
  - schema property: post-admin-delete-customer.json#/properties/customerId
  - schema property: post-admin-order-create.json#/properties/customerId
  - schema property: post-admin-order-send-mail.json#/properties/customerId
  - schema property: post-admin-order-shipping-notify-mail.json#/properties/customerId
  - schema property: post-entry.json#/properties/customerId
  - schema property: post-login.json#/properties/customerId
  - schema property: post-logout.json#/properties/customerId
  - schema property: post-mypage-address-list.json#/properties/customerId
  - schema property: post-mypage-change.json#/properties/customerId
  - schema property: post-mypage-favorite.json#/properties/customerId
  - schema property: post-mypage-reorder.json#/properties/customerId
  - schema property: post-mypage-withdraw.json#/properties/customerId
  - schema property: post-reset.json#/properties/customerId
  - schema property: post-shopping-checkout.json#/properties/customerId
  - schema property: put-admin-order.json#/properties/customerId
  - schema property: put-mypage-address.json#/properties/customerId

### `customerName`

- usages:
  - schema property: get-admin-csv-config.json#/properties/outputColumns/properties/customerName

### `customerNameKey`

- usages:
  - schema property: get-admin-order-status.json#/properties/orderStatuses/items/properties/customerNameKey

### `customerStatus` ☑︎

- title: 会員ステータス
- doc: 1=仮会員（メール未認証）, 2=本会員（認証済み）, 3=退会。退会時はメールアドレスが無効化される
- usages:
  - schema property: get-admin-customer-list.json#/properties/customers/items/properties/customerStatus
  - schema property: get-admin-customer.json#/properties/customerStatus
  - schema property: post-admin-create-customer.json#/properties/customerStatus
  - schema property: post-entry.json#/properties/customerStatus
  - schema property: post-login.json#/properties/customerStatus

### `customers`

- usages:
  - schema property: get-admin-customer-list.json#/properties/customers

### `defaultShippingAddress`

- usages:
  - schema property: get-shopping.json#/properties/defaultShippingAddress

### `deleted`

- usages:
  - schema property: post-admin-category-csv.json#/properties/deleted

### `deliveries`

- usages:
  - schema property: get-admin-delivery-delivery-list.json#/properties/deliveries

### `delivery`

- usages:
  - schema property: post-shopping-confirm.param.json#/properties/delivery
  - schema property: get-admin-delivery-delivery.json#/properties/delivery

### `deliveryDate` ☑︎

- title: 配送希望日
- def: https://schema.org/deliveryDate
- doc: 顧客が指定した配送希望日
- usages:
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/deliveryDate

### `deliveryFeeTotal` ☑︎

- title: 送料合計
- doc: 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用
- usages:
  - parameter: POST /admin/order/create {deliveryFeeTotal}
  - schema property: post-admin-order-create.param.json#/properties/deliveryFeeTotal
  - schema property: delete-cart-item.json#/properties/deliveryFeeTotal
  - schema property: get-admin-order.json#/properties/deliveryFeeTotal
  - schema property: get-cart.json#/properties/carts/items/properties/deliveryFeeTotal
  - schema property: get-cart.json#/properties/deliveryFeeTotal
  - schema property: get-mypage-history.json#/properties/deliveryFeeTotal
  - schema property: get-shopping-confirm.json#/properties/deliveryFeeTotal
  - schema property: get-shopping.json#/properties/carts/items/properties/deliveryFeeTotal
  - schema property: get-shopping.json#/properties/deliveryFeeTotal
  - schema property: post-admin-order-create.json#/properties/deliveryFeeTotal
  - schema property: post-cart-item.json#/properties/deliveryFeeTotal
  - schema property: put-admin-order.json#/properties/deliveryFeeTotal
  - schema property: put-cart-item.json#/properties/deliveryFeeTotal

### `deliveryId` ☑︎

- title: 配送方法ID
- doc: dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/delivery/delivery {deliveryId}
  - parameter: PUT /admin/delivery/delivery {deliveryId}
  - parameter: DELETE /admin/delivery/delivery {deliveryId}
  - schema property: delete-admin-delivery-delivery.param.json#/properties/deliveryId
  - schema property: get-admin-delivery-delivery.param.json#/properties/deliveryId
  - schema property: put-admin-delivery-delivery.param.json#/properties/deliveryId
  - schema property: delete-admin-delivery-delivery.json#/properties/deliveryId
  - schema property: get-admin-delivery-delivery-list.json#/properties/deliveries/items/properties/deliveryId
  - schema property: get-admin-delivery-delivery.json#/properties/deliveryId
  - schema property: post-admin-delivery-delivery-list.json#/properties/deliveryId
  - schema property: put-admin-delivery-delivery.json#/properties/deliveryId

### `deliveryName` ☑︎

- title: 配送業者名
- doc: 注文時点の配送業者名スナップショット
- usages:
  - parameter: PUT /admin/delivery/delivery {deliveryName}
  - parameter: POST /admin/delivery/delivery-list {deliveryName}
  - schema property: post-admin-delivery-delivery-list.param.json#/properties/deliveryName
  - schema property: put-admin-delivery-delivery.param.json#/properties/deliveryName
  - schema property: get-admin-csv-config.json#/properties/notOutputColumns/properties/deliveryName
  - schema property: get-admin-delivery-delivery-list.json#/properties/deliveries/items/properties/deliveryName
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/deliveryName
  - schema property: post-admin-delivery-delivery-list.json#/properties/deliveryName
  - schema property: put-admin-delivery-delivery.json#/properties/deliveryName

### `deliveryTime` ☑︎

- title: 配送時間帯
- doc: 顧客が選択した配送希望時間帯（例: 午前中、14-16時）
- usages:
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/deliveryTime

### `delivery_time`

- usages:
  - schema property: post-shopping-confirm.param.json#/properties/delivery_time

### `denyUrl` ☑︎

- title: 拒否URL
- doc: アクセスを拒否する管理画面URLパターン。authority=1（店舗オーナー）に対して適用
- usages:
  - schema property: post-admin-authority-role.param.json#/properties/AuthorityRoles/items/properties/denyUrl
  - schema property: get-admin-authority-role.json#/properties/rules/items/properties/denyUrl
  - schema property: post-admin-authority-role.json#/properties/rules/items/properties/denyUrl

### `deny_url`

- usages:
  - schema property: post-admin-authority-role.param.json#/properties/AuthorityRoles/items/properties/deny_url

### `description`

- usages:
  - parameter: POST /admin/product {description}
  - parameter: PUT /admin/product {description}
  - schema property: post-admin-product.param.json#/properties/description
  - schema property: put-admin-product.param.json#/properties/description
  - schema property: get-admin-product-csv-category.json#/properties/columns/items/properties/description
  - schema property: get-admin-product-csv-class-category.json#/properties/columns/items/properties/description
  - schema property: get-admin-product-csv-class-name.json#/properties/columns/items/properties/description
  - schema property: get-admin-product-csv-product.json#/properties/columns/items/properties/description
  - schema property: get-admin-product.json#/properties/description
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows/items/properties/description
  - schema property: get-product.json#/properties/description
  - schema property: post-admin-product.json#/properties/description
  - schema property: put-admin-product.json#/properties/description

### `descriptionKey`

- usages:
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows/items/properties/descriptionKey

### `descriptionList` ☑︎

- title: 一覧用説明文
- def: https://schema.org/description
- doc: 商品一覧・検索結果に表示する短い説明文
- usages:
  - schema property: get-products.json#/properties/products/items/properties/descriptionList

### `deviceToken`

- usages:
  - parameter: POST /admin/two-factor-auth {deviceToken}
  - parameter: PUT /admin/two-factor-auth-set {deviceToken}
  - schema property: post-admin-two-factor-auth.param.json#/properties/deviceToken
  - schema property: put-admin-two-factor-auth-set.param.json#/properties/deviceToken

### `deviceType` ☑︎

- title: デバイス種別
- doc: デバイス種別マスタ（EC-CUBE 2.xからの名残）。値: 2=モバイル, 10=PC。非連番のIDは旧バージョンのデバイスサポート（ガラケー等）に由来。ページレイアウトのデバイス別表示に使用
- usages:
  - schema property: get-admin-layout-layout-list.json#/properties/layouts/items/properties/deviceType
  - schema property: get-admin-layout-layout.json#/properties/deviceType
  - schema property: get-admin-template-template-list.json#/properties/templates/items/properties/deviceType
  - schema property: put-admin-layout-layout.json#/properties/deviceType

### `discount` ☑︎

- title: 値引き額
- doc: 受注全体の値引き合計額。クーポン等による値引き
- usages:
  - parameter: PUT /admin/order {discount}
  - parameter: POST /admin/order/create {discount}
  - schema property: post-admin-order-create.param.json#/properties/discount
  - schema property: put-admin-order.param.json#/properties/discount
  - schema property: get-admin-order.json#/properties/discount
  - schema property: get-mypage-history.json#/properties/discount
  - schema property: get-shopping-confirm.json#/properties/discount
  - schema property: post-admin-order-create.json#/properties/discount
  - schema property: put-admin-order.json#/properties/discount

### `disp_number`

- usages:
  - parameter: GET /products {disp_number}
  - schema property: get-products.param.json#/properties/disp_number
  - schema property: get-products.json#/properties/filters/properties/disp_number

### `displayOrderCountKey`

- usages:
  - schema property: get-admin-order-status.json#/properties/orderStatuses/items/properties/displayOrderCountKey

### `displayOrderScreen` ☑︎

- title: 注文画面表示
- doc: 注文確認画面にこの項目を表示するか
- usages:
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows/items/properties/displayOrderScreen

### `displayOrderScreenKey`

- usages:
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows/items/properties/displayOrderScreenKey

### `doAddMultipleShippingAddress`

- usages:
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/links/properties/doAddMultipleShippingAddress

### `doCheckout` ☑︎

- title: 注文を確定する
- doc: PurchaseFlow(shoppingフロー)による税計算・送料計算・在庫引当・ポイント減算を実行。PaymentMethod::checkout()で決済確定後、dtb_order を登録し、カート明細を dtb_order_item にスナップショットとして凍結（注文時点の商品名・単価を固定）、注文確認メールを送信しカートをクリア。処理中はorderStatus=PROCESSING(8)->PENDING(7)->NEW(1)と遷移。同じ明細スナップショット凍結は管理画面の doCreateOrder も行う。
- usages:
  - schema property: get-shopping-confirm.json#/properties/links/properties/doCheckout

### `doLogin` ☑︎

- title: ログインする
- doc: 会員認証を行う。成功時はマイページへ遷移、失敗時はログイン画面にエラー表示。Symfony Securityでセッション管理。
- usages:
  - schema property: get-shopping-login.json#/properties/links/properties/doLogin

### `doRegisterCustomer` ☑︎

- title: 会員登録する
- doc: 会員メール認証オプション有効時は仮会員として登録し認証メールを送信。無効時は即座に本会員。
- usages:
  - schema property: get-entry-confirm.json#/properties/links/properties/doRegisterCustomer

### `doSelectShippingAddress` ☑︎

- title: お届け先を選択する
- doc: 注文手続き中の配送先を確定する。会員の登録済みお届け先または新規入力から1件を選択。
- usages:
  - schema property: get-shopping-shipping.json#/properties/links/properties/doSelectShippingAddress

### `doSubmitContact` ☑︎

- title: お問い合わせを送信する
- doc: お問い合わせを送信する。店舗のshopEmail02宛にメール送信し、送信者にも自動返信メールを送る。
- usages:
  - schema property: get-contact-confirm.json#/properties/links/properties/doSubmitContact

### `doUpdateShippingAddress` ☑︎

- title: お届け先を更新する
- doc: 注文手続き中のお届け先情報（住所・氏名・連絡先）を更新する。
- usages:
  - schema property: get-shopping-shipping-edit.json#/properties/links/properties/doUpdateShippingAddress

### `doWithdrawCustomer` ☑︎

- title: 退会する
- doc: メールアドレスをダミー値に置換しカートとセッションをクリア。退会確認メール送信後にログアウト。
- usages:
  - schema property: get-mypage-withdraw-confirm.json#/properties/links/properties/doWithdrawCustomer

### `dummyEmail`

- usages:
  - schema property: post-mypage-withdraw.json#/properties/dummyEmail

### `email` ☑︎

- title: メールアドレス
- def: https://schema.org/email
- doc: 会員のログインIDを兼ねる。有効会員間で一意
- usages:
  - parameter: POST /admin/create-customer {email}
  - parameter: GET /admin/customer {email}
  - parameter: POST /admin/customer/resend-activation-mail {email}
  - parameter: POST /entry {email}
  - parameter: POST /forgot-password {email}
  - parameter: POST /login {email}
  - parameter: POST /mypage/change {email}
  - parameter: POST /shopping/non-member {email}
  - schema property: get-admin-customer.param.json#/properties/email
  - schema property: post-admin-create-customer.param.json#/properties/email
  - schema property: post-admin-customer-resend-activation-mail.param.json#/properties/email
  - schema property: post-entry.param.json#/properties/email
  - schema property: post-forgot-password.param.json#/properties/email
  - schema property: post-login.param.json#/properties/email
  - schema property: post-mypage-change.param.json#/properties/email
  - schema property: post-shopping-non-member.param.json#/properties/email
  - schema property: get-admin-customer-list.json#/properties/customers/items/properties/email
  - schema property: get-admin-customer.json#/properties/email
  - schema property: get-mypage-change.json#/properties/email
  - schema property: get-mypage-withdraw.json#/properties/email
  - schema property: get-mypage.json#/properties/email
  - schema property: get-shopping.json#/properties/email
  - schema property: post-admin-create-customer.json#/properties/email
  - schema property: post-admin-customer-resend-activation-mail.json#/properties/email
  - schema property: post-entry.json#/properties/email
  - schema property: post-login.json#/properties/email
  - schema property: post-mypage-change.json#/properties/email
  - schema property: post-shopping-non-member.json#/properties/email

### `emailKeyword`

- usages:
  - parameter: GET /admin/customer-list {emailKeyword}
  - schema property: get-admin-customer-list.param.json#/properties/emailKeyword
  - schema property: get-admin-customer-list.json#/properties/filters/properties/emailKeyword

### `email_confirm`

- usages:
  - parameter: POST /entry {email_confirm}
  - schema property: post-entry.param.json#/properties/email_confirm
  - schema property: post-shopping-non-member.param.json#/properties/email_confirm

### `enabled`

- usages:
  - parameter: PUT /admin/content/maintenance {enabled}
  - schema property: post-admin-csv-config.param.json#/properties/columns/items/properties/enabled
  - schema property: put-admin-content-maintenance.param.json#/properties/enabled
  - schema property: put-admin-master-data-edit.param.json#/properties/rows/items/properties/enabled
  - schema property: get-admin-index.json#/properties/recommendedPlugins/items/properties/enabled
  - schema property: get-admin-plugin-list.json#/properties/plugins/items/properties/enabled
  - schema property: post-admin-csv-config.json#/properties/columns/items/properties/enabled
  - schema property: post-admin-plugin-disable.json#/properties/enabled
  - schema property: post-admin-plugin-enable.json#/properties/enabled
  - schema property: post-admin-plugin-list.json#/properties/enabled

### `entries`

- usages:
  - schema property: get-admin-login-history.json#/properties/entries

### `errors`

- usages:
  - schema property: get-admin-calendar.json#/properties/errors
  - schema property: get-admin-content-file-manager.json#/properties/errors

### `favoriteCount`

- usages:
  - schema property: get-admin-customer.json#/properties/favoriteCount
  - schema property: get-mypage-favorite-list.json#/properties/favoriteCount
  - schema property: get-mypage.json#/properties/favoriteCount

### `favorites`

- usages:
  - schema property: get-admin-customer.json#/properties/favorites
  - schema property: get-mypage-favorite-list.json#/properties/favorites

### `fields`

- usages:
  - schema property: get-admin-change-password.json#/properties/fields
  - schema property: get-admin-login.json#/properties/fields
  - schema property: get-admin-two-factor-auth-set.json#/properties/fields
  - schema property: get-admin-two-factor-auth.json#/properties/fields
  - schema property: get-contact-complete.json#/properties/fields
  - schema property: get-contact-confirm.json#/properties/fields
  - schema property: get-contact.json#/properties/fields
  - schema property: get-entry-activate.json#/properties/fields
  - schema property: get-entry-complete.json#/properties/fields
  - schema property: get-entry-confirm.json#/properties/fields
  - schema property: get-entry.json#/properties/fields
  - schema property: get-forgot-complete.json#/properties/fields
  - schema property: get-forgot-password.json#/properties/fields
  - schema property: get-help-about.json#/properties/fields
  - schema property: get-help-agreement.json#/properties/fields
  - schema property: get-help-guide.json#/properties/fields
  - schema property: get-help-privacy.json#/properties/fields
  - schema property: get-help-trade-law.json#/properties/fields
  - schema property: get-index.json#/properties/fields
  - schema property: get-login.json#/properties/fields
  - schema property: get-mypage-change-complete.json#/properties/fields
  - schema property: get-mypage-withdraw-complete.json#/properties/fields
  - schema property: get-mypage-withdraw-confirm.json#/properties/fields
  - schema property: get-mypage-withdraw.json#/properties/fields
  - schema property: get-reset.json#/properties/fields
  - schema property: get-shopping-error.json#/properties/fields
  - schema property: get-shopping-login.json#/properties/fields
  - schema property: get-shopping-non-member.json#/properties/fields
  - schema property: get-shopping-shipping-edit.json#/properties/fields
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/fields
  - schema property: get-shopping-shipping-multiple.json#/properties/fields
  - schema property: get-shopping-shipping.json#/properties/fields

### `file`

- usages:
  - parameter: POST /admin/template/template-add {file}
  - schema property: post-admin-template-template-add.param.json#/properties/file

### `fileName` ☑︎

- title: ファイル名
- doc: 商品画像のファイル名
- usages:
  - parameter: POST /admin/mail-template/create {fileName}
  - schema property: post-admin-mail-template-create.param.json#/properties/fileName
  - schema property: delete-admin-mail-template.json#/properties/fileName
  - schema property: get-admin-customer.json#/properties/favorites/items/properties/fileName
  - schema property: get-admin-mail-template.json#/properties/mailTemplates/items/properties/fileName
  - schema property: get-admin-order-export-order-pdf.json#/properties/fileName
  - schema property: get-admin-product-list.json#/properties/products/items/properties/fileName
  - schema property: get-mypage-favorite-list.json#/properties/favorites/items/properties/fileName
  - schema property: get-products.json#/properties/products/items/properties/fileName
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/fileName
  - schema property: post-admin-mail-template-create.json#/properties/fileName
  - schema property: post-admin-mail-template.json#/properties/fileName

### `file_name`

- usages:
  - schema property: get-admin-mail-template.json#/properties/Mail/properties/file_name

### `filters`

- usages:
  - schema property: get-admin-customer-list.json#/properties/filters
  - schema property: get-admin-member-list.json#/properties/filters
  - schema property: get-admin-product-list.json#/properties/filters
  - schema property: get-products.json#/properties/filters

### `form`

- usages:
  - schema property: get-admin-base-info.json#/properties/form
  - schema property: get-admin-block-block.json#/properties/form
  - schema property: get-admin-calendar.json#/properties/calendars/items/properties/form
  - schema property: get-admin-calendar.json#/properties/form
  - schema property: get-admin-category-edit.json#/properties/form
  - schema property: get-admin-change-password.json#/properties/form
  - schema property: get-admin-class-category-class-category-list.json#/properties/form
  - schema property: get-admin-class-name-class-name-list.json#/properties/form
  - schema property: get-admin-content-css.json#/properties/form
  - schema property: get-admin-content-file-manager.json#/properties/form
  - schema property: get-admin-content-js.json#/properties/form
  - schema property: get-admin-csv-config.json#/properties/form
  - schema property: get-admin-customer-delivery-edit.json#/properties/form
  - schema property: get-admin-customer.json#/properties/form
  - schema property: get-admin-delivery-delivery.json#/properties/form
  - schema property: get-admin-layout-layout.json#/properties/form
  - schema property: get-admin-log.json#/properties/form
  - schema property: get-admin-login.json#/properties/form
  - schema property: get-admin-mail-template.json#/properties/form
  - schema property: get-admin-master-data.json#/properties/form
  - schema property: get-admin-member.json#/properties/form
  - schema property: get-admin-news-news.json#/properties/form
  - schema property: get-admin-order-edit.json#/properties/form
  - schema property: get-admin-order-order-pdf.json#/properties/form
  - schema property: get-admin-order-send-mail.json#/properties/form
  - schema property: get-admin-order-shipping-address.json#/properties/form
  - schema property: get-admin-order-status.json#/properties/form
  - schema property: get-admin-page-page.json#/properties/form
  - schema property: get-admin-payment-payment.json#/properties/form
  - schema property: get-admin-product-csv-category.json#/properties/form
  - schema property: get-admin-product-csv-class-category.json#/properties/form
  - schema property: get-admin-product-csv-class-name.json#/properties/form
  - schema property: get-admin-product-csv-product.json#/properties/form
  - schema property: get-admin-product-edit.json#/properties/form
  - schema property: get-admin-product-product-class.json#/properties/form
  - schema property: get-admin-security.json#/properties/form
  - schema property: get-admin-tag-tag-list.json#/properties/form
  - schema property: get-admin-tax-rule-tax-rule-list.json#/properties/form
  - schema property: get-admin-template-template-add.json#/properties/form
  - schema property: get-admin-trade-law.json#/properties/form
  - schema property: get-admin-two-factor-auth-edit.json#/properties/form
  - schema property: get-admin-two-factor-auth-set.json#/properties/form
  - schema property: get-admin-two-factor-auth.json#/properties/form
  - schema property: get-contact-confirm.json#/properties/form
  - schema property: get-contact.json#/properties/form
  - schema property: get-entry-confirm.json#/properties/form
  - schema property: get-entry.json#/properties/form
  - schema property: get-forgot-password.json#/properties/form
  - schema property: get-login.json#/properties/form
  - schema property: get-mypage-address.json#/properties/form
  - schema property: get-mypage-change.json#/properties/form
  - schema property: get-product.json#/properties/form
  - schema property: get-reset.json#/properties/form
  - schema property: get-shopping-login.json#/properties/form
  - schema property: get-shopping-non-member.json#/properties/form
  - schema property: get-shopping-shipping-edit.json#/properties/form
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/form
  - schema property: get-shopping.json#/properties/form
  - schema property: put-admin-master-data.json#/properties/form

### `frontAllowHosts`

- usages:
  - parameter: PUT /admin/security {frontAllowHosts}
  - schema property: put-admin-security.param.json#/properties/frontAllowHosts

### `frontDenyHosts`

- usages:
  - parameter: PUT /admin/security {frontDenyHosts}
  - schema property: put-admin-security.param.json#/properties/frontDenyHosts

### `goCart` ☑︎

- title: カートを見る
- doc: カート内容を表示する。商品明細・小計・送料・手数料・合計と、注文手続きへの導線を含む。販売種別が異なる商品は別カートとして並列表示。
- usages:
  - schema property: get-index.json#/properties/links/properties/goCart
  - schema property: get-products.json#/properties/links/properties/goCart
  - schema property: get-shopping-complete.json#/properties/links/properties/goCart
  - schema property: get-shopping-error.json#/properties/links/properties/goCart

### `goContactForm` ☑︎

- title: お問い合わせフォームを見る
- doc: お問い合わせ入力フォームを表示する。氏名・連絡先・本文（最大3000文字）。
- usages:
  - schema property: get-index.json#/properties/links/properties/goContactForm

### `goCustomerRegistration` ☑︎

- title: 会員登録フォームを見る
- doc: 新規会員登録フォームを表示する。利用規約同意・基本情報・連絡先入力欄を含む。
- usages:
  - schema property: get-index.json#/properties/links/properties/goCustomerRegistration
  - schema property: get-shopping-login.json#/properties/links/properties/goCustomerRegistration

### `goHelpAbout` ☑︎

- title: 当サイトについてを見る
- doc: 「当サイトについて」ページを表示する。shopMessage・goodTraded（取扱商品）を表示。
- usages:
  - schema property: get-index.json#/properties/links/properties/goHelpAbout

### `goHelpAgreement` ☑︎

- title: ご利用規約を見る
- doc: 利用規約ページを表示する（管理画面で本文編集可）。
- usages:
  - schema property: get-index.json#/properties/links/properties/goHelpAgreement

### `goHelpGuide` ☑︎

- title: ご利用ガイドを見る
- doc: ご利用ガイドページを表示する。EC-CUBEデフォルトの汎用案内（管理画面で本文編集可）。
- usages:
  - schema property: get-index.json#/properties/links/properties/goHelpGuide

### `goHelpPrivacy` ☑︎

- title: プライバシーポリシーを見る
- doc: プライバシーポリシーページを表示する（管理画面で本文編集可）。
- usages:
  - schema property: get-index.json#/properties/links/properties/goHelpPrivacy

### `goHelpTradeLaw` ☑︎

- title: 特定商取引法を見る
- doc: 特定商取引法に基づく表記ページを閲覧する。事業者情報・返品ポリシー・送料等の法定表示。
- usages:
  - schema property: get-index.json#/properties/links/properties/goHelpTradeLaw

### `goLogin` ☑︎

- title: ログイン画面を見る
- doc: 会員ログイン画面を表示する。認証フォーム、新規会員登録・パスワード再発行へのリンクを含む。
- usages:
  - schema property: get-forgot-complete.json#/properties/links/properties/goLogin
  - schema property: get-index.json#/properties/links/properties/goLogin

### `goMypage` ☑︎

- title: マイページを見る
- doc: 会員マイページのダッシュボード。注文履歴・会員情報変更・お気に入り・配送先管理への導線を含む。
- usages:
  - schema property: get-index.json#/properties/links/properties/goMypage
  - schema property: get-mypage-change-complete.json#/properties/links/properties/goMypage
  - schema property: get-mypage-withdraw-confirm.json#/properties/links/properties/goMypage
  - schema property: get-shopping-complete.json#/properties/links/properties/goMypage

### `goProduct` ☑︎

- title: 商品詳細を見る
- doc: 商品詳細を表示する。価格・在庫・規格選択・関連カテゴリ・タグ・カートへ追加操作を含む。
- usages:
  - schema property: get-products.json#/properties/links/properties/goProduct

### `goProductList` ☑︎

- title: 商品一覧を見る
- doc: 商品一覧を表示する。フロントは公開商品のみ、管理画面は全商品。フィルタ: カテゴリ・商品名・タグ・在庫状態・販売種別・ページネーション。
- usages:
  - schema property: get-index.json#/properties/links/properties/goProductList

### `goShopping` ☑︎

- title: 注文手続きを見る
- doc: ログイン済み会員、または購入者情報が確定した購入フローの注文手続き画面を表示する。カートからの入口は goCheckoutEntry を使う。
- usages:
  - schema property: get-shopping-shipping-multiple.json#/properties/links/properties/goShopping

### `goShoppingError` ☑︎

- title: 購入エラー画面を表示する
- doc: 在庫不足、決済失敗、セッション切れなどのエラー発生時にリダイレクトされる。
- usages:
  - schema property: get-shopping-confirm.json#/properties/links/properties/goShoppingError

### `goShoppingNonMember` ☑︎

- title: 非会員購入情報入力へ進む
- doc: 非会員購入時の氏名・住所・連絡先入力フォームを表示する。
- usages:
  - schema property: get-shopping-login.json#/properties/links/properties/goShoppingNonMember

### `goShoppingShipping` ☑︎

- title: お届け先を選択する画面を見る
- doc: 登録済みのお届け先一覧から配送先を選択する画面。新規追加・複数配送先指定への導線を含む。
- usages:
  - schema property: get-shopping-shipping-edit.json#/properties/links/properties/goShoppingShipping
  - schema property: get-shopping-shipping-multiple.json#/properties/links/properties/goShoppingShipping

### `goShoppingShippingEdit` ☑︎

- title: お届け先変更画面を見る
- doc: 注文手続き中のお届け先を編集する画面。住所・氏名・連絡先を変更。
- usages:
  - schema property: get-shopping-shipping.json#/properties/links/properties/goShoppingShippingEdit

### `goShoppingShippingMultiple` ☑︎

- title: 複数配送先設定画面を見る
- doc: 複数のお届け先に商品を振り分ける画面。
- usages:
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/links/properties/goShoppingShippingMultiple
  - schema property: get-shopping-shipping.json#/properties/links/properties/goShoppingShippingMultiple

### `goTemplateAdd`

- usages:
  - schema property: get-admin-template-template-list.json#/properties/links/properties/goTemplateAdd

### `goTop` ☑︎

- title: トップページを見る
- doc: トップページを表示する。ショップメッセージ・新着情報・カテゴリナビゲーション・おすすめ商品を含む。
- usages:
  - schema property: get-contact-complete.json#/properties/links/properties/goTop
  - schema property: get-contact-confirm.json#/properties/links/properties/goTop
  - schema property: get-entry-activate.json#/properties/links/properties/goTop
  - schema property: get-entry-complete.json#/properties/links/properties/goTop
  - schema property: get-entry-confirm.json#/properties/links/properties/goTop
  - schema property: get-forgot-complete.json#/properties/links/properties/goTop
  - schema property: get-help-about.json#/properties/links/properties/goTop
  - schema property: get-help-agreement.json#/properties/links/properties/goTop
  - schema property: get-help-guide.json#/properties/links/properties/goTop
  - schema property: get-help-privacy.json#/properties/links/properties/goTop
  - schema property: get-help-trade-law.json#/properties/links/properties/goTop
  - schema property: get-mypage-change-complete.json#/properties/links/properties/goTop
  - schema property: get-mypage-withdraw-complete.json#/properties/links/properties/goTop
  - schema property: get-products.json#/properties/links/properties/goTop
  - schema property: get-shopping-complete.json#/properties/links/properties/goTop

### `hasError`

- usages:
  - schema property: get-admin-calendar.json#/properties/calendars/items/properties/hasError

### `historyLimit`

- usages:
  - parameter: GET /mypage/order-history {historyLimit}
  - schema property: get-mypage-order-history.param.json#/properties/historyLimit

### `holiday`

- usages:
  - parameter: POST /admin/calendar {holiday}
  - schema property: post-admin-calendar.param.json#/properties/holiday
  - schema property: get-admin-calendar.json#/properties/calendars/items/properties/holiday
  - schema property: post-admin-calendar.json#/properties/holiday

### `href`

- usages:
  - schema property: delete-admin-block-block.param.json#/$defs/link/properties/href
  - schema property: delete-admin-calendar.param.json#/$defs/link/properties/href
  - schema property: delete-admin-category-category.param.json#/$defs/link/properties/href
  - schema property: delete-admin-class-category-class-category.param.json#/$defs/link/properties/href
  - schema property: delete-admin-class-name-class-name.param.json#/$defs/link/properties/href
  - schema property: delete-admin-delivery-delivery.param.json#/$defs/link/properties/href
  - schema property: delete-admin-mail-template.param.json#/$defs/link/properties/href
  - schema property: delete-admin-member.param.json#/$defs/link/properties/href
  - schema property: delete-admin-news-news.param.json#/$defs/link/properties/href
  - schema property: delete-admin-page-page.param.json#/$defs/link/properties/href
  - schema property: delete-admin-payment-payment.param.json#/$defs/link/properties/href
  - schema property: delete-admin-plugin.param.json#/$defs/link/properties/href
  - schema property: delete-admin-product.param.json#/$defs/link/properties/href
  - schema property: delete-admin-tag-tag.param.json#/$defs/link/properties/href
  - schema property: delete-admin-tax-rule-tax-rule.param.json#/$defs/link/properties/href
  - schema property: delete-admin-template-template-list.param.json#/$defs/link/properties/href
  - schema property: delete-cart-item.param.json#/$defs/link/properties/href
  - schema property: delete-mypage-address.param.json#/$defs/link/properties/href
  - schema property: delete-mypage-favorite.param.json#/$defs/link/properties/href
  - schema property: get-action-redirect.param.json#/$defs/link/properties/href
  - schema property: get-admin-action-redirect.param.json#/$defs/link/properties/href
  - schema property: get-admin-category-category.param.json#/$defs/link/properties/href
  - schema property: get-admin-category-edit.param.json#/$defs/link/properties/href
  - schema property: get-admin-class-category-class-category-export.param.json#/$defs/link/properties/href
  - schema property: get-admin-class-category-class-category-list.param.json#/$defs/link/properties/href
  - schema property: get-admin-csv-config.param.json#/$defs/link/properties/href
  - schema property: get-admin-customer-delivery-edit.param.json#/$defs/link/properties/href
  - schema property: get-admin-customer-list.param.json#/$defs/link/properties/href
  - schema property: get-admin-customer.param.json#/$defs/link/properties/href
  - schema property: get-admin-delivery-delivery.param.json#/$defs/link/properties/href
  - schema property: get-admin-login-history.param.json#/$defs/link/properties/href
  - schema property: get-admin-master-data.param.json#/$defs/link/properties/href
  - schema property: get-admin-member-list.param.json#/$defs/link/properties/href
  - schema property: get-admin-member.param.json#/$defs/link/properties/href
  - schema property: get-admin-news-news.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-edit.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-export-order-pdf.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-list.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-mail-confirm.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-order-pdf.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-send-mail.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-shipping-address.param.json#/$defs/link/properties/href
  - schema property: get-admin-order-shipping-notify-mail.param.json#/$defs/link/properties/href
  - schema property: get-admin-order.param.json#/$defs/link/properties/href
  - schema property: get-admin-page-page.param.json#/$defs/link/properties/href
  - schema property: get-admin-payment-payment.param.json#/$defs/link/properties/href
  - schema property: get-admin-product-edit.param.json#/$defs/link/properties/href
  - schema property: get-admin-product-list.param.json#/$defs/link/properties/href
  - schema property: get-admin-product-product-class.param.json#/$defs/link/properties/href
  - schema property: get-admin-product.param.json#/$defs/link/properties/href
  - schema property: get-admin-unsupported-route.param.json#/$defs/link/properties/href
  - schema property: get-cart.param.json#/$defs/link/properties/href
  - schema property: get-contact-complete.param.json#/$defs/link/properties/href
  - schema property: get-mypage-address.param.json#/$defs/link/properties/href
  - schema property: get-mypage-history.param.json#/$defs/link/properties/href
  - schema property: get-mypage-order-history.param.json#/$defs/link/properties/href
  - schema property: get-mypage.param.json#/$defs/link/properties/href
  - schema property: get-product.param.json#/$defs/link/properties/href
  - schema property: get-products.param.json#/$defs/link/properties/href
  - schema property: get-reset.param.json#/$defs/link/properties/href
  - schema property: get-shopping-complete.param.json#/$defs/link/properties/href
  - schema property: get-shopping-confirm.param.json#/$defs/link/properties/href
  - schema property: get-shopping.param.json#/$defs/link/properties/href
  - schema property: get-unsupported-route.param.json#/$defs/link/properties/href
  - schema property: post-action-redirect.param.json#/$defs/link/properties/href
  - schema property: post-admin-action-redirect.param.json#/$defs/link/properties/href
  - schema property: post-admin-authority-role.param.json#/$defs/link/properties/href
  - schema property: post-admin-base-info.param.json#/$defs/link/properties/href
  - schema property: post-admin-block-block-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-calendar.param.json#/$defs/link/properties/href
  - schema property: post-admin-category-category-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-category-csv.param.json#/$defs/link/properties/href
  - schema property: post-admin-change-password.param.json#/$defs/link/properties/href
  - schema property: post-admin-class-category-class-category-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-class-name-class-name-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-create-customer.param.json#/$defs/link/properties/href
  - schema property: post-admin-csv-config.param.json#/$defs/link/properties/href
  - schema property: post-admin-customer-resend-activation-mail.param.json#/$defs/link/properties/href
  - schema property: post-admin-delete-customer.param.json#/$defs/link/properties/href
  - schema property: post-admin-delivery-delivery-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-login.param.json#/$defs/link/properties/href
  - schema property: post-admin-mail-template.param.json#/$defs/link/properties/href
  - schema property: post-admin-member.param.json#/$defs/link/properties/href
  - schema property: post-admin-news-news-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-order-bulk-delete.param.json#/$defs/link/properties/href
  - schema property: post-admin-order-create.param.json#/$defs/link/properties/href
  - schema property: post-admin-order-import-shipping.param.json#/$defs/link/properties/href
  - schema property: post-admin-order-send-mail.param.json#/$defs/link/properties/href
  - schema property: post-admin-order-shipping-address.param.json#/$defs/link/properties/href
  - schema property: post-admin-order-shipping-notify-mail.param.json#/$defs/link/properties/href
  - schema property: post-admin-order-status.param.json#/$defs/link/properties/href
  - schema property: post-admin-page-page-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-payment-payment-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-plugin-disable.param.json#/$defs/link/properties/href
  - schema property: post-admin-plugin-enable.param.json#/$defs/link/properties/href
  - schema property: post-admin-plugin-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-product-bulk-status.param.json#/$defs/link/properties/href
  - schema property: post-admin-product-copy.param.json#/$defs/link/properties/href
  - schema property: post-admin-product-csv-class-category.param.json#/$defs/link/properties/href
  - schema property: post-admin-product-csv-class-name.param.json#/$defs/link/properties/href
  - schema property: post-admin-product-csv.param.json#/$defs/link/properties/href
  - schema property: post-admin-product.param.json#/$defs/link/properties/href
  - schema property: post-admin-tag-tag-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-tax-rule-tax-rule-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-template-template-add.param.json#/$defs/link/properties/href
  - schema property: post-admin-template-template-list.param.json#/$defs/link/properties/href
  - schema property: post-admin-trade-law.param.json#/$defs/link/properties/href
  - schema property: post-admin-two-factor-auth.param.json#/$defs/link/properties/href
  - schema property: post-admin-unsupported-route.param.json#/$defs/link/properties/href
  - schema property: post-cart-item.param.json#/$defs/link/properties/href
  - schema property: post-contact.param.json#/$defs/link/properties/href
  - schema property: post-entry-activate.param.json#/$defs/link/properties/href
  - schema property: post-entry.param.json#/$defs/link/properties/href
  - schema property: post-forgot-password.param.json#/$defs/link/properties/href
  - schema property: post-login.param.json#/$defs/link/properties/href
  - schema property: post-mypage-address-list.param.json#/$defs/link/properties/href
  - schema property: post-mypage-change.param.json#/$defs/link/properties/href
  - schema property: post-mypage-favorite.param.json#/$defs/link/properties/href
  - schema property: post-mypage-reorder.param.json#/$defs/link/properties/href
  - schema property: post-mypage-withdraw.param.json#/$defs/link/properties/href
  - schema property: post-reset.param.json#/$defs/link/properties/href
  - schema property: post-shopping-checkout.param.json#/$defs/link/properties/href
  - schema property: post-shopping-confirm.param.json#/$defs/link/properties/href
  - schema property: post-shopping-non-member.param.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping-edit.param.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping-multiple-edit.param.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping-multiple.param.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping.param.json#/$defs/link/properties/href
  - schema property: post-unsupported-route.param.json#/$defs/link/properties/href
  - schema property: put-admin-block-block.param.json#/$defs/link/properties/href
  - schema property: put-admin-category-category.param.json#/$defs/link/properties/href
  - schema property: put-admin-class-category-class-category.param.json#/$defs/link/properties/href
  - schema property: put-admin-class-name-class-name.param.json#/$defs/link/properties/href
  - schema property: put-admin-content-css.param.json#/$defs/link/properties/href
  - schema property: put-admin-content-js.param.json#/$defs/link/properties/href
  - schema property: put-admin-content-maintenance.param.json#/$defs/link/properties/href
  - schema property: put-admin-delivery-delivery.param.json#/$defs/link/properties/href
  - schema property: put-admin-layout-layout.param.json#/$defs/link/properties/href
  - schema property: put-admin-master-data-edit.param.json#/$defs/link/properties/href
  - schema property: put-admin-master-data.param.json#/$defs/link/properties/href
  - schema property: put-admin-member.param.json#/$defs/link/properties/href
  - schema property: put-admin-news-news.param.json#/$defs/link/properties/href
  - schema property: put-admin-order-shipping-address.param.json#/$defs/link/properties/href
  - schema property: put-admin-order-status.param.json#/$defs/link/properties/href
  - schema property: put-admin-order-tracking-number.param.json#/$defs/link/properties/href
  - schema property: put-admin-order.param.json#/$defs/link/properties/href
  - schema property: put-admin-page-page.param.json#/$defs/link/properties/href
  - schema property: put-admin-payment-payment.param.json#/$defs/link/properties/href
  - schema property: put-admin-product.param.json#/$defs/link/properties/href
  - schema property: put-admin-security.param.json#/$defs/link/properties/href
  - schema property: put-admin-sort-no-move.param.json#/$defs/link/properties/href
  - schema property: put-admin-template-template-list.param.json#/$defs/link/properties/href
  - schema property: put-admin-toggle-visible.param.json#/$defs/link/properties/href
  - schema property: put-admin-two-factor-auth-set.param.json#/$defs/link/properties/href
  - schema property: put-cart-item.param.json#/$defs/link/properties/href
  - schema property: put-mypage-address.param.json#/$defs/link/properties/href
  - schema property: delete-admin-block-block.json#/$defs/link/properties/href
  - schema property: delete-admin-calendar.json#/$defs/link/properties/href
  - schema property: delete-admin-category-category.json#/$defs/link/properties/href
  - schema property: delete-admin-class-category-class-category.json#/$defs/link/properties/href
  - schema property: delete-admin-class-name-class-name.json#/$defs/link/properties/href
  - schema property: delete-admin-delivery-delivery.json#/$defs/link/properties/href
  - schema property: delete-admin-mail-template.json#/$defs/link/properties/href
  - schema property: delete-admin-member.json#/$defs/link/properties/href
  - schema property: delete-admin-news-news.json#/$defs/link/properties/href
  - schema property: delete-admin-page-page.json#/$defs/link/properties/href
  - schema property: delete-admin-payment-payment.json#/$defs/link/properties/href
  - schema property: delete-admin-plugin.json#/$defs/link/properties/href
  - schema property: delete-admin-product.json#/$defs/link/properties/href
  - schema property: delete-admin-tag-tag.json#/$defs/link/properties/href
  - schema property: delete-admin-tax-rule-tax-rule.json#/$defs/link/properties/href
  - schema property: delete-admin-template-template-list.json#/$defs/link/properties/href
  - schema property: delete-cart-item.json#/$defs/link/properties/href
  - schema property: delete-mypage-address.json#/$defs/link/properties/href
  - schema property: delete-mypage-favorite.json#/$defs/link/properties/href
  - schema property: get-action-redirect.json#/$defs/link/properties/href
  - schema property: get-admin-action-redirect.json#/$defs/link/properties/href
  - schema property: get-admin-authority-role.json#/$defs/link/properties/href
  - schema property: get-admin-base-info.json#/$defs/link/properties/href
  - schema property: get-admin-block-block-list.json#/$defs/link/properties/href
  - schema property: get-admin-block-block.json#/$defs/link/properties/href
  - schema property: get-admin-calendar.json#/$defs/link/properties/href
  - schema property: get-admin-category-category-list.json#/$defs/link/properties/href
  - schema property: get-admin-category-category.json#/$defs/link/properties/href
  - schema property: get-admin-category-csv.json#/$defs/link/properties/href
  - schema property: get-admin-category-edit.json#/$defs/link/properties/href
  - schema property: get-admin-change-password.json#/$defs/link/properties/href
  - schema property: get-admin-class-category-class-category-export.json#/$defs/link/properties/href
  - schema property: get-admin-class-category-class-category-list.json#/$defs/link/properties/href
  - schema property: get-admin-class-name-class-name-export.json#/$defs/link/properties/href
  - schema property: get-admin-class-name-class-name-list.json#/$defs/link/properties/href
  - schema property: get-admin-content-cache.json#/$defs/link/properties/href
  - schema property: get-admin-content-css.json#/$defs/link/properties/href
  - schema property: get-admin-content-file-manager.json#/$defs/link/properties/href
  - schema property: get-admin-content-js.json#/$defs/link/properties/href
  - schema property: get-admin-content-maintenance.json#/$defs/link/properties/href
  - schema property: get-admin-csv-config.json#/$defs/link/properties/href
  - schema property: get-admin-customer-csv.json#/$defs/link/properties/href
  - schema property: get-admin-customer-delivery-edit.json#/$defs/link/properties/href
  - schema property: get-admin-customer-list.json#/$defs/link/properties/href
  - schema property: get-admin-customer.json#/$defs/link/properties/href
  - schema property: get-admin-delivery-delivery-list.json#/$defs/link/properties/href
  - schema property: get-admin-delivery-delivery.json#/$defs/link/properties/href
  - schema property: get-admin-empty-page.json#/$defs/link/properties/href
  - schema property: get-admin-index.json#/$defs/link/properties/href
  - schema property: get-admin-layout-layout-list.json#/$defs/link/properties/href
  - schema property: get-admin-layout-layout.json#/$defs/link/properties/href
  - schema property: get-admin-log.json#/$defs/link/properties/href
  - schema property: get-admin-login-history.json#/$defs/link/properties/href
  - schema property: get-admin-login.json#/$defs/link/properties/href
  - schema property: get-admin-login.json#/properties/submitTo/properties/href
  - schema property: get-admin-mail-template.json#/$defs/link/properties/href
  - schema property: get-admin-master-data.json#/$defs/link/properties/href
  - schema property: get-admin-master-data.json#/properties/submitTo/properties/href
  - schema property: get-admin-member-list.json#/$defs/link/properties/href
  - schema property: get-admin-member.json#/$defs/link/properties/href
  - schema property: get-admin-news-news-list.json#/$defs/link/properties/href
  - schema property: get-admin-news-news.json#/$defs/link/properties/href
  - schema property: get-admin-order-edit.json#/$defs/link/properties/href
  - schema property: get-admin-order-export-order-pdf.json#/$defs/link/properties/href
  - schema property: get-admin-order-export-order.json#/$defs/link/properties/href
  - schema property: get-admin-order-export-shipping.json#/$defs/link/properties/href
  - schema property: get-admin-order-import-shipping.json#/$defs/link/properties/href
  - schema property: get-admin-order-list.json#/$defs/link/properties/href
  - schema property: get-admin-order-mail-confirm.json#/$defs/link/properties/href
  - schema property: get-admin-order-order-pdf.json#/$defs/link/properties/href
  - schema property: get-admin-order-send-mail.json#/$defs/link/properties/href
  - schema property: get-admin-order-shipping-address.json#/$defs/link/properties/href
  - schema property: get-admin-order-shipping-notify-mail.json#/$defs/link/properties/href
  - schema property: get-admin-order-shipping-notify-mail.json#/properties/submitTo/properties/href
  - schema property: get-admin-order-status.json#/$defs/link/properties/href
  - schema property: get-admin-order.json#/$defs/link/properties/href
  - schema property: get-admin-page-page-list.json#/$defs/link/properties/href
  - schema property: get-admin-page-page.json#/$defs/link/properties/href
  - schema property: get-admin-payment-payment-list.json#/$defs/link/properties/href
  - schema property: get-admin-payment-payment.json#/$defs/link/properties/href
  - schema property: get-admin-plugin-list.json#/$defs/link/properties/href
  - schema property: get-admin-product-csv-category.json#/$defs/link/properties/href
  - schema property: get-admin-product-csv-class-category.json#/$defs/link/properties/href
  - schema property: get-admin-product-csv-class-name.json#/$defs/link/properties/href
  - schema property: get-admin-product-csv-product.json#/$defs/link/properties/href
  - schema property: get-admin-product-csv.json#/$defs/link/properties/href
  - schema property: get-admin-product-edit.json#/$defs/link/properties/href
  - schema property: get-admin-product-list.json#/$defs/link/properties/href
  - schema property: get-admin-product-new.json#/$defs/link/properties/href
  - schema property: get-admin-product-product-class.json#/$defs/link/properties/href
  - schema property: get-admin-product.json#/$defs/link/properties/href
  - schema property: get-admin-security.json#/$defs/link/properties/href
  - schema property: get-admin-system.json#/$defs/link/properties/href
  - schema property: get-admin-tag-tag-list.json#/$defs/link/properties/href
  - schema property: get-admin-tax-rule-tax-rule-list.json#/$defs/link/properties/href
  - schema property: get-admin-template-template-add.json#/$defs/link/properties/href
  - schema property: get-admin-template-template-list.json#/$defs/link/properties/href
  - schema property: get-admin-trade-law.json#/$defs/link/properties/href
  - schema property: get-admin-two-factor-auth-edit.json#/$defs/link/properties/href
  - schema property: get-admin-two-factor-auth-set.json#/$defs/link/properties/href
  - schema property: get-admin-two-factor-auth.json#/$defs/link/properties/href
  - schema property: get-admin-unsupported-route.json#/$defs/link/properties/href
  - schema property: get-cart.json#/$defs/link/properties/href
  - schema property: get-contact-complete.json#/$defs/link/properties/href
  - schema property: get-contact-confirm.json#/$defs/link/properties/href
  - schema property: get-contact-confirm.json#/properties/submitTo/properties/href
  - schema property: get-contact.json#/$defs/link/properties/href
  - schema property: get-contact.json#/properties/submitTo/properties/href
  - schema property: get-entry-activate.json#/$defs/link/properties/href
  - schema property: get-entry-complete.json#/$defs/link/properties/href
  - schema property: get-entry-confirm.json#/$defs/link/properties/href
  - schema property: get-entry-confirm.json#/properties/submitTo/properties/href
  - schema property: get-entry.json#/$defs/link/properties/href
  - schema property: get-entry.json#/properties/submitTo/properties/href
  - schema property: get-forgot-complete.json#/$defs/link/properties/href
  - schema property: get-forgot-password.json#/$defs/link/properties/href
  - schema property: get-forgot-password.json#/properties/submitTo/properties/href
  - schema property: get-help-about.json#/$defs/link/properties/href
  - schema property: get-help-agreement.json#/$defs/link/properties/href
  - schema property: get-help-guide.json#/$defs/link/properties/href
  - schema property: get-help-privacy.json#/$defs/link/properties/href
  - schema property: get-help-trade-law.json#/$defs/link/properties/href
  - schema property: get-index.json#/$defs/link/properties/href
  - schema property: get-login.json#/$defs/link/properties/href
  - schema property: get-login.json#/properties/submitTo/properties/href
  - schema property: get-mypage-address-list.json#/$defs/link/properties/href
  - schema property: get-mypage-address.json#/$defs/link/properties/href
  - schema property: get-mypage-address.json#/properties/submitTo/properties/href
  - schema property: get-mypage-change-complete.json#/$defs/link/properties/href
  - schema property: get-mypage-change.json#/$defs/link/properties/href
  - schema property: get-mypage-change.json#/properties/submitTo/properties/href
  - schema property: get-mypage-favorite-list.json#/$defs/link/properties/href
  - schema property: get-mypage-history.json#/$defs/link/properties/href
  - schema property: get-mypage-order-history.json#/$defs/link/properties/href
  - schema property: get-mypage-withdraw-complete.json#/$defs/link/properties/href
  - schema property: get-mypage-withdraw-confirm.json#/$defs/link/properties/href
  - schema property: get-mypage-withdraw-confirm.json#/properties/submitTo/properties/href
  - schema property: get-mypage-withdraw.json#/$defs/link/properties/href
  - schema property: get-mypage-withdraw.json#/properties/submitTo/properties/href
  - schema property: get-mypage.json#/$defs/link/properties/href
  - schema property: get-product.json#/$defs/link/properties/href
  - schema property: get-products.json#/$defs/link/properties/href
  - schema property: get-reset.json#/$defs/link/properties/href
  - schema property: get-reset.json#/properties/submitTo/properties/href
  - schema property: get-shopping-complete.json#/$defs/link/properties/href
  - schema property: get-shopping-confirm.json#/$defs/link/properties/href
  - schema property: get-shopping-confirm.json#/properties/submitTo/properties/href
  - schema property: get-shopping-error.json#/$defs/link/properties/href
  - schema property: get-shopping-login.json#/$defs/link/properties/href
  - schema property: get-shopping-non-member.json#/$defs/link/properties/href
  - schema property: get-shopping-non-member.json#/properties/submitTo/properties/href
  - schema property: get-shopping-shipping-edit.json#/$defs/link/properties/href
  - schema property: get-shopping-shipping-edit.json#/properties/submitTo/properties/href
  - schema property: get-shopping-shipping-multiple-edit.json#/$defs/link/properties/href
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/submitTo/properties/href
  - schema property: get-shopping-shipping-multiple.json#/$defs/link/properties/href
  - schema property: get-shopping-shipping.json#/$defs/link/properties/href
  - schema property: get-shopping-shipping.json#/properties/submitTo/properties/href
  - schema property: get-shopping.json#/$defs/link/properties/href
  - schema property: get-unsupported-route.json#/$defs/link/properties/href
  - schema property: post-action-redirect.json#/$defs/link/properties/href
  - schema property: post-admin-action-redirect.json#/$defs/link/properties/href
  - schema property: post-admin-authority-role.json#/$defs/link/properties/href
  - schema property: post-admin-base-info.json#/$defs/link/properties/href
  - schema property: post-admin-block-block-list.json#/$defs/link/properties/href
  - schema property: post-admin-calendar.json#/$defs/link/properties/href
  - schema property: post-admin-category-category-list.json#/$defs/link/properties/href
  - schema property: post-admin-category-csv.json#/$defs/link/properties/href
  - schema property: post-admin-change-password.json#/$defs/link/properties/href
  - schema property: post-admin-class-category-class-category-list.json#/$defs/link/properties/href
  - schema property: post-admin-class-name-class-name-list.json#/$defs/link/properties/href
  - schema property: post-admin-create-customer.json#/$defs/link/properties/href
  - schema property: post-admin-csv-config.json#/$defs/link/properties/href
  - schema property: post-admin-customer-resend-activation-mail.json#/$defs/link/properties/href
  - schema property: post-admin-delete-customer.json#/$defs/link/properties/href
  - schema property: post-admin-delivery-delivery-list.json#/$defs/link/properties/href
  - schema property: post-admin-login.json#/$defs/link/properties/href
  - schema property: post-admin-logout.json#/$defs/link/properties/href
  - schema property: post-admin-mail-template.json#/$defs/link/properties/href
  - schema property: post-admin-member.json#/$defs/link/properties/href
  - schema property: post-admin-news-news-list.json#/$defs/link/properties/href
  - schema property: post-admin-order-bulk-delete.json#/$defs/link/properties/href
  - schema property: post-admin-order-create.json#/$defs/link/properties/href
  - schema property: post-admin-order-import-shipping.json#/$defs/link/properties/href
  - schema property: post-admin-order-send-mail.json#/$defs/link/properties/href
  - schema property: post-admin-order-shipping-address.json#/$defs/link/properties/href
  - schema property: post-admin-order-shipping-notify-mail.json#/$defs/link/properties/href
  - schema property: post-admin-order-status.json#/$defs/link/properties/href
  - schema property: post-admin-page-page-list.json#/$defs/link/properties/href
  - schema property: post-admin-payment-payment-list.json#/$defs/link/properties/href
  - schema property: post-admin-plugin-disable.json#/$defs/link/properties/href
  - schema property: post-admin-plugin-enable.json#/$defs/link/properties/href
  - schema property: post-admin-plugin-list.json#/$defs/link/properties/href
  - schema property: post-admin-product-bulk-status.json#/$defs/link/properties/href
  - schema property: post-admin-product-copy.json#/$defs/link/properties/href
  - schema property: post-admin-product-csv-class-category.json#/$defs/link/properties/href
  - schema property: post-admin-product-csv-class-name.json#/$defs/link/properties/href
  - schema property: post-admin-product-csv.json#/$defs/link/properties/href
  - schema property: post-admin-product.json#/$defs/link/properties/href
  - schema property: post-admin-tag-tag-list.json#/$defs/link/properties/href
  - schema property: post-admin-tax-rule-tax-rule-list.json#/$defs/link/properties/href
  - schema property: post-admin-template-template-add.json#/$defs/link/properties/href
  - schema property: post-admin-template-template-list.json#/$defs/link/properties/href
  - schema property: post-admin-trade-law.json#/$defs/link/properties/href
  - schema property: post-admin-two-factor-auth.json#/$defs/link/properties/href
  - schema property: post-admin-unsupported-route.json#/$defs/link/properties/href
  - schema property: post-cart-item.json#/$defs/link/properties/href
  - schema property: post-contact.json#/$defs/link/properties/href
  - schema property: post-entry-activate.json#/$defs/link/properties/href
  - schema property: post-entry.json#/$defs/link/properties/href
  - schema property: post-forgot-password.json#/$defs/link/properties/href
  - schema property: post-login.json#/$defs/link/properties/href
  - schema property: post-logout.json#/$defs/link/properties/href
  - schema property: post-mypage-address-list.json#/$defs/link/properties/href
  - schema property: post-mypage-change.json#/$defs/link/properties/href
  - schema property: post-mypage-favorite.json#/$defs/link/properties/href
  - schema property: post-mypage-reorder.json#/$defs/link/properties/href
  - schema property: post-mypage-withdraw.json#/$defs/link/properties/href
  - schema property: post-reset.json#/$defs/link/properties/href
  - schema property: post-shopping-checkout.json#/$defs/link/properties/href
  - schema property: post-shopping-non-member.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping-edit.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping-multiple-edit.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping-multiple.json#/$defs/link/properties/href
  - schema property: post-shopping-shipping.json#/$defs/link/properties/href
  - schema property: post-unsupported-route.json#/$defs/link/properties/href
  - schema property: put-admin-block-block.json#/$defs/link/properties/href
  - schema property: put-admin-category-category.json#/$defs/link/properties/href
  - schema property: put-admin-class-category-class-category.json#/$defs/link/properties/href
  - schema property: put-admin-class-name-class-name.json#/$defs/link/properties/href
  - schema property: put-admin-content-cache.json#/$defs/link/properties/href
  - schema property: put-admin-content-css.json#/$defs/link/properties/href
  - schema property: put-admin-content-js.json#/$defs/link/properties/href
  - schema property: put-admin-content-maintenance.json#/$defs/link/properties/href
  - schema property: put-admin-delivery-delivery.json#/$defs/link/properties/href
  - schema property: put-admin-layout-layout.json#/$defs/link/properties/href
  - schema property: put-admin-master-data-edit.json#/$defs/link/properties/href
  - schema property: put-admin-master-data.json#/$defs/link/properties/href
  - schema property: put-admin-master-data.json#/properties/submitTo/properties/href
  - schema property: put-admin-member.json#/$defs/link/properties/href
  - schema property: put-admin-news-news.json#/$defs/link/properties/href
  - schema property: put-admin-order-shipping-address.json#/$defs/link/properties/href
  - schema property: put-admin-order-status.json#/$defs/link/properties/href
  - schema property: put-admin-order-tracking-number.json#/$defs/link/properties/href
  - schema property: put-admin-order.json#/$defs/link/properties/href
  - schema property: put-admin-page-page.json#/$defs/link/properties/href
  - schema property: put-admin-payment-payment.json#/$defs/link/properties/href
  - schema property: put-admin-product.json#/$defs/link/properties/href
  - schema property: put-admin-security.json#/$defs/link/properties/href
  - schema property: put-admin-sort-no-move.json#/$defs/link/properties/href
  - schema property: put-admin-template-template-list.json#/$defs/link/properties/href
  - schema property: put-admin-toggle-visible.json#/$defs/link/properties/href
  - schema property: put-admin-two-factor-auth-set.json#/$defs/link/properties/href
  - schema property: put-cart-item.json#/$defs/link/properties/href
  - schema property: put-mypage-address.json#/$defs/link/properties/href

### `id`

- usages:
  - parameter: GET /admin/customer {id}
  - parameter: GET /admin/customer-delivery-edit {id}
  - schema property: get-admin-customer-delivery-edit.param.json#/properties/id
  - schema property: get-admin-customer.param.json#/properties/id
  - schema property: get-admin-authority-role.json#/properties/authorityOptions/items/properties/id
  - schema property: get-admin-calendar.json#/properties/calendars/items/properties/id
  - schema property: get-admin-mail-template.json#/properties/Mail/properties/id
  - schema property: get-admin-mail-template.json#/properties/id
  - schema property: get-admin-master-data.json#/properties/rows/items/properties/id
  - schema property: get-admin-order-status.json#/properties/orderStatuses/items/properties/id
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows/items/properties/id
  - schema property: get-products.json#/properties/products/items/properties/id
  - schema property: put-admin-master-data.json#/properties/rows/items/properties/id

### `ids`

- usages:
  - parameter: POST /admin/order/bulk-delete {ids}
  - schema property: post-admin-order-bulk-delete.param.json#/properties/ids

### `imagePath`

- usages:
  - schema property: get-admin-product-list.json#/properties/products/items/properties/imagePath

### `imported`

- usages:
  - schema property: post-admin-category-csv.json#/properties/imported
  - schema property: post-admin-order-import-shipping.json#/properties/imported

### `info`

- usages:
  - schema property: get-admin-system.json#/properties/info

### `initialPoint`

- usages:
  - schema property: get-admin-customer.json#/properties/initialPoint
  - schema property: post-admin-create-customer.json#/properties/initialPoint
  - schema property: post-entry.json#/properties/initialPoint

### `installed`

- usages:
  - schema property: get-admin-plugin-list.json#/properties/plugins/items/properties/installed
  - schema property: post-admin-plugin-list.json#/properties/installed

### `isDeletable`

- usages:
  - schema property: get-admin-mail-template.json#/properties/Mail/properties/isDeletable
  - schema property: get-admin-mail-template.json#/properties/mailTemplates/items/properties/isDeletable

### `isMaintenance`

- usages:
  - schema property: get-admin-content-maintenance.json#/properties/isMaintenance
  - schema property: put-admin-content-maintenance.json#/properties/isMaintenance

### `isSecureRequest`

- usages:
  - schema property: get-admin-security.json#/properties/isSecureRequest

### `itemCount`

- usages:
  - schema property: get-admin-customer.json#/properties/orders/items/properties/itemCount
  - schema property: get-admin-index.json#/properties/orders/items/properties/itemCount
  - schema property: get-admin-order.json#/properties/itemCount
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/itemCount
  - schema property: post-admin-order-create.json#/properties/itemCount

### `items`

- usages:
  - schema property: get-admin-order-edit.json#/properties/items
  - schema property: get-admin-order.json#/properties/items
  - schema property: get-cart.json#/properties/carts/items/properties/items
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/items
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/items
  - schema property: get-shopping-confirm.json#/properties/items
  - schema property: get-shopping.json#/properties/carts/items/properties/items

### `job` ☑︎

- title: 職業
- doc: 1=公務員〜18=その他の18区分
- usages:
  - parameter: POST /admin/create-customer {job}
  - parameter: POST /entry {job}
  - schema property: post-admin-create-customer.param.json#/properties/job
  - schema property: post-entry.param.json#/properties/job
  - schema property: get-admin-customer.json#/properties/job

### `js`

- usages:
  - parameter: PUT /admin/content/js {js}
  - schema property: put-admin-content-js.param.json#/properties/js

### `kana01` ☑︎

- title: セイ
- doc: 姓のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名
- usages:
  - parameter: POST /admin/create-customer {kana01}
  - parameter: POST /entry {kana01}
  - parameter: PUT /mypage/address {kana01}
  - parameter: POST /mypage/address-list {kana01}
  - parameter: POST /mypage/change {kana01}
  - parameter: POST /shopping/non-member {kana01}
  - parameter: POST /shopping/shipping-edit {kana01}
  - parameter: POST /shopping/shipping-multiple-edit {kana01}
  - schema property: post-admin-create-customer.param.json#/properties/kana01
  - schema property: post-entry.param.json#/properties/kana01
  - schema property: post-mypage-address-list.param.json#/properties/kana01
  - schema property: post-mypage-change.param.json#/properties/kana01
  - schema property: post-shopping-non-member.param.json#/properties/kana01
  - schema property: post-shopping-shipping-edit.param.json#/properties/kana01
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/kana01
  - schema property: put-mypage-address.param.json#/properties/kana01
  - schema property: get-admin-customer.json#/properties/kana01
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/kana01
  - schema property: get-mypage-change.json#/properties/kana01
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/kana01
  - schema property: post-mypage-address-list.json#/properties/kana01
  - schema property: post-shopping-shipping-edit.json#/properties/kana01
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/kana01
  - schema property: put-mypage-address.json#/properties/kana01

### `kana02` ☑︎

- title: メイ
- doc: 名のカタカナ読み。全角カタカナのみ許可（ひらがな入力時は自動変換）。日本の氏名入力に特有の読み仮名
- usages:
  - parameter: POST /admin/create-customer {kana02}
  - parameter: POST /entry {kana02}
  - parameter: PUT /mypage/address {kana02}
  - parameter: POST /mypage/address-list {kana02}
  - parameter: POST /mypage/change {kana02}
  - parameter: POST /shopping/non-member {kana02}
  - parameter: POST /shopping/shipping-edit {kana02}
  - parameter: POST /shopping/shipping-multiple-edit {kana02}
  - schema property: post-admin-create-customer.param.json#/properties/kana02
  - schema property: post-entry.param.json#/properties/kana02
  - schema property: post-mypage-address-list.param.json#/properties/kana02
  - schema property: post-mypage-change.param.json#/properties/kana02
  - schema property: post-shopping-non-member.param.json#/properties/kana02
  - schema property: post-shopping-shipping-edit.param.json#/properties/kana02
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/kana02
  - schema property: put-mypage-address.param.json#/properties/kana02
  - schema property: get-admin-customer.json#/properties/kana02
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/kana02
  - schema property: get-mypage-change.json#/properties/kana02
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/kana02
  - schema property: post-mypage-address-list.json#/properties/kana02
  - schema property: post-shopping-shipping-edit.json#/properties/kana02
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/kana02
  - schema property: put-mypage-address.json#/properties/kana02

### `label`

- usages:
  - schema property: post-admin-csv-config.param.json#/properties/columns/items/properties/label
  - schema property: get-admin-authority-role.json#/properties/authorityOptions/items/properties/label
  - schema property: get-admin-master-data.json#/properties/masterTypes/items/properties/label
  - schema property: post-admin-csv-config.json#/properties/columns/items/properties/label
  - schema property: put-admin-master-data.json#/properties/masterTypes/items/properties/label

### `layoutId` ☑︎

- title: レイアウトID
- doc: CMSレイアウトを識別する不透明な文字列ハンドル。seed 済みレイアウトを Fake/SQL 同型に扱う。
- usages:
  - parameter: GET /admin/layout/layout {layoutId}
  - parameter: PUT /admin/layout/layout {layoutId}
  - schema property: get-admin-layout-layout.param.json#/properties/layoutId
  - schema property: put-admin-layout-layout.param.json#/properties/layoutId
  - schema property: get-admin-layout-layout-list.json#/properties/layouts/items/properties/layoutId
  - schema property: get-admin-layout-layout.json#/properties/layoutId
  - schema property: put-admin-layout-layout.json#/properties/layoutId

### `layoutName` ☑︎

- title: レイアウト名
- doc: レイアウトの表示名
- usages:
  - parameter: PUT /admin/layout/layout {layoutName}
  - schema property: put-admin-layout-layout.param.json#/properties/layoutName
  - schema property: get-admin-layout-layout-list.json#/properties/layouts/items/properties/layoutName
  - schema property: get-admin-layout-layout.json#/properties/layoutName
  - schema property: put-admin-layout-layout.json#/properties/layoutName

### `layouts`

- usages:
  - schema property: get-admin-layout-layout-list.json#/properties/layouts

### `length`

- usages:
  - schema property: put-admin-content-css.json#/properties/length
  - schema property: put-admin-content-js.json#/properties/length

### `limit`

- usages:
  - parameter: GET /admin/customer-list {limit}
  - parameter: GET /admin/login-history {limit}
  - parameter: GET /admin/member-list {limit}
  - parameter: GET /admin/order-list {limit}
  - parameter: GET /admin/product-list {limit}
  - parameter: GET /products {limit}
  - schema property: get-admin-customer-list.param.json#/properties/limit
  - schema property: get-admin-login-history.param.json#/properties/limit
  - schema property: get-admin-member-list.param.json#/properties/limit
  - schema property: get-admin-order-list.param.json#/properties/limit
  - schema property: get-admin-product-list.param.json#/properties/limit
  - schema property: get-products.param.json#/properties/limit
  - schema property: get-admin-member-list.json#/properties/filters/properties/limit
  - schema property: get-admin-order-list.json#/properties/limit
  - schema property: get-admin-product-list.json#/properties/filters/properties/limit
  - schema property: get-mypage-order-history.json#/properties/limit

### `lineCount`

- usages:
  - schema property: post-admin-category-csv.json#/properties/lineCount
  - schema property: post-admin-order-import-shipping.json#/properties/lineCount

### `linkMethod` ☑︎

- title: 新規ウィンドウで開く
- doc: 外部URLのリンク開き方（boolean）。false=同一ウィンドウ, true=新規ウィンドウ（target="_blank"）。テンプレートでtarget属性の出力制御に使用
- usages:
  - parameter: PUT /admin/news/news {linkMethod}
  - parameter: POST /admin/news/news-list {linkMethod}
  - schema property: post-admin-news-news-list.param.json#/properties/linkMethod
  - schema property: put-admin-news-news.param.json#/properties/linkMethod
  - schema property: get-admin-news-news-list.json#/properties/news/items/properties/linkMethod
  - schema property: get-admin-news-news.json#/properties/linkMethod
  - schema property: post-admin-news-news-list.json#/properties/linkMethod
  - schema property: put-admin-news-news.json#/properties/linkMethod

### `links`

- usages:
  - schema property: get-admin-template-template-list.json#/properties/links
  - schema property: get-contact-complete.json#/properties/links
  - schema property: get-contact-confirm.json#/properties/links
  - schema property: get-entry-activate.json#/properties/links
  - schema property: get-entry-complete.json#/properties/links
  - schema property: get-entry-confirm.json#/properties/links
  - schema property: get-forgot-complete.json#/properties/links
  - schema property: get-help-about.json#/properties/links
  - schema property: get-help-agreement.json#/properties/links
  - schema property: get-help-guide.json#/properties/links
  - schema property: get-help-privacy.json#/properties/links
  - schema property: get-help-trade-law.json#/properties/links
  - schema property: get-index.json#/properties/links
  - schema property: get-mypage-change-complete.json#/properties/links
  - schema property: get-mypage-withdraw-complete.json#/properties/links
  - schema property: get-mypage-withdraw-confirm.json#/properties/links
  - schema property: get-products.json#/properties/links
  - schema property: get-shopping-complete.json#/properties/links
  - schema property: get-shopping-confirm.json#/properties/links
  - schema property: get-shopping-error.json#/properties/links
  - schema property: get-shopping-login.json#/properties/links
  - schema property: get-shopping-shipping-edit.json#/properties/links
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/links
  - schema property: get-shopping-shipping-multiple.json#/properties/links
  - schema property: get-shopping-shipping.json#/properties/links

### `log`

- usages:
  - schema property: get-admin-log.json#/properties/log

### `loginId` ☑︎

- title: ログインID
- doc: 管理画面ログイン用のID。一意
- usages:
  - parameter: POST /admin/authority-role {loginId}
  - parameter: POST /admin/login {loginId}
  - parameter: GET /admin/member {loginId}
  - parameter: POST /admin/member {loginId}
  - parameter: PUT /admin/member {loginId}
  - parameter: DELETE /admin/member {loginId}
  - parameter: POST /admin/two-factor-auth {loginId}
  - parameter: PUT /admin/two-factor-auth-set {loginId}
  - schema property: delete-admin-member.param.json#/properties/loginId
  - schema property: get-admin-member.param.json#/properties/loginId
  - schema property: post-admin-authority-role.param.json#/properties/loginId
  - schema property: post-admin-login.param.json#/properties/loginId
  - schema property: post-admin-member.param.json#/properties/loginId
  - schema property: post-admin-two-factor-auth.param.json#/properties/loginId
  - schema property: put-admin-member.param.json#/properties/loginId
  - schema property: put-admin-two-factor-auth-set.param.json#/properties/loginId
  - schema property: delete-admin-member.json#/properties/loginId
  - schema property: get-admin-login-history.json#/properties/entries/items/properties/loginId
  - schema property: get-admin-member-list.json#/properties/members/items/properties/loginId
  - schema property: get-admin-member.json#/properties/loginId
  - schema property: post-admin-authority-role.json#/properties/loginId
  - schema property: post-admin-change-password.json#/properties/loginId
  - schema property: post-admin-login.json#/properties/loginId
  - schema property: post-admin-member.json#/properties/loginId
  - schema property: post-admin-two-factor-auth.json#/properties/loginId
  - schema property: put-admin-member.json#/properties/loginId
  - schema property: put-admin-two-factor-auth-set.json#/properties/loginId

### `mailBody` ☑︎

- title: メール本文
- doc: 送信済みメールのプレーンテキスト本文
- usages:
  - schema property: get-mypage-history.json#/properties/mailHistories/items/properties/mailBody
  - schema property: post-admin-order-send-mail.json#/properties/mailBody

### `mailHistories`

- usages:
  - schema property: get-mypage-history.json#/properties/mailHistories

### `mailSubject` ☑︎

- title: メール件名
- doc: メールの件名。テンプレート変数を含む場合あり
- usages:
  - parameter: POST /admin/mail-template {mailSubject}
  - parameter: POST /admin/mail-template/create {mailSubject}
  - schema property: post-admin-mail-template-create.param.json#/properties/mailSubject
  - schema property: post-admin-mail-template.param.json#/properties/mailSubject
  - schema property: get-admin-mail-template.json#/properties/mailTemplates/items/properties/mailSubject
  - schema property: get-mypage-history.json#/properties/mailHistories/items/properties/mailSubject
  - schema property: post-admin-mail-template-create.json#/properties/mailSubject
  - schema property: post-admin-mail-template.json#/properties/mailSubject
  - schema property: post-admin-order-send-mail.json#/properties/mailSubject

### `mailTemplateId` ☑︎

- title: メールテンプレートID
- doc: dtb_mail_template.id（int unsigned AUTO_INCREMENT）の正の整数主キー。doUpdateMailTemplate の必須入力で、既存行を指す必要がある。SqlMailTemplateStorage は findById / update をこの id で引き、未知 id は MailTemplateNotFoundException（404）。新規テンプレート作成フローは file_name 設定と Twig ファイル書き出しを伴うため Phase 2 scope であり、ID 生成器は存在しない（更新専用契約）
- usages:
  - parameter: GET /admin/mail-template {mailTemplateId}
  - parameter: POST /admin/mail-template {mailTemplateId}
  - parameter: DELETE /admin/mail-template {mailTemplateId}
  - schema property: delete-admin-mail-template.param.json#/properties/mailTemplateId
  - schema property: post-admin-mail-template.param.json#/properties/mailTemplateId
  - schema property: delete-admin-mail-template.json#/properties/mailTemplateId
  - schema property: get-admin-mail-template.json#/properties/mailTemplates/items/properties/mailTemplateId
  - schema property: post-admin-mail-template-create.json#/properties/mailTemplateId
  - schema property: post-admin-mail-template.json#/properties/mailTemplateId

### `mailTemplateName` ☑︎

- title: テンプレート名
- doc: メールテンプレートの表示名
- usages:
  - parameter: POST /admin/mail-template/create {mailTemplateName}
  - schema property: post-admin-mail-template-create.param.json#/properties/mailTemplateName
  - schema property: delete-admin-mail-template.json#/properties/mailTemplateName
  - schema property: get-admin-mail-template.json#/properties/mailTemplates/items/properties/mailTemplateName
  - schema property: post-admin-mail-template-create.json#/properties/mailTemplateName
  - schema property: post-admin-mail-template.json#/properties/mailTemplateName

### `mailTemplates`

- usages:
  - schema property: get-admin-mail-template.json#/properties/mailTemplates

### `mail_subject`

- usages:
  - parameter: POST /admin/mail-template {mail_subject}
  - schema property: post-admin-mail-template.param.json#/properties/mail_subject

### `mainImage`

- usages:
  - schema property: get-admin-customer.json#/properties/favorites/items/properties/mainImage
  - schema property: get-admin-product.json#/properties/mainImage
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/mainImage
  - schema property: get-mypage-favorite-list.json#/properties/favorites/items/properties/mainImage
  - schema property: get-product.json#/properties/mainImage

### `mainListImage`

- usages:
  - schema property: get-products.json#/properties/products/items/properties/mainListImage

### `masterType`

- usages:
  - parameter: GET /admin/master-data {masterType}
  - parameter: PUT /admin/master-data {masterType}
  - parameter: PUT /admin/master-data-edit {masterType}
  - parameter: PUT /admin/sort-no-move {masterType}
  - parameter: PUT /admin/toggle-visible {masterType}
  - schema property: get-admin-master-data.param.json#/properties/masterType
  - schema property: put-admin-master-data-edit.param.json#/properties/masterType
  - schema property: put-admin-master-data.param.json#/properties/masterType
  - schema property: put-admin-sort-no-move.param.json#/properties/masterType
  - schema property: put-admin-toggle-visible.param.json#/properties/masterType
  - schema property: put-admin-master-data-edit.json#/properties/masterType
  - schema property: put-admin-sort-no-move.json#/properties/masterType
  - schema property: put-admin-toggle-visible.json#/properties/masterType

### `masterTypes`

- usages:
  - schema property: get-admin-master-data.json#/properties/masterTypes
  - schema property: put-admin-master-data.json#/properties/masterTypes

### `memberName` ☑︎

- title: 管理者名
- doc: 管理者メンバーの表示名
- usages:
  - schema property: get-admin-two-factor-auth-edit.json#/properties/memberName
  - schema property: get-admin-two-factor-auth-set.json#/properties/memberName

### `members`

- usages:
  - schema property: get-admin-member-list.json#/properties/members

### `message` ☑︎

- title: 注文メッセージ
- doc: 顧客が注文時に入力するお問い合わせ欄。EC-CUBEのラベルは「お問い合わせ」で、備考欄ではない。最大3000文字
- usages:
  - schema property: post-shopping-confirm.param.json#/properties/message
  - schema property: delete-admin-block-block.json#/properties/message
  - schema property: delete-admin-calendar.json#/properties/message
  - schema property: delete-admin-mail-template.json#/properties/message
  - schema property: delete-admin-member.json#/properties/message
  - schema property: delete-admin-page-page.json#/properties/message
  - schema property: delete-admin-product.json#/properties/message
  - schema property: delete-admin-template-template-list.json#/properties/message
  - schema property: get-action-redirect.json#/properties/message
  - schema property: get-admin-action-redirect.json#/properties/message
  - schema property: get-admin-order-export-order-pdf.json#/properties/message
  - schema property: get-admin-order-shipping-notify-mail.json#/properties/message
  - schema property: get-admin-unsupported-route.json#/properties/message
  - schema property: get-mypage-history.json#/properties/message
  - schema property: get-unsupported-route.json#/properties/message
  - schema property: post-action-redirect.json#/properties/message
  - schema property: post-admin-action-redirect.json#/properties/message
  - schema property: post-admin-authority-role.json#/properties/message
  - schema property: post-admin-calendar.json#/properties/message
  - schema property: post-admin-category-csv.json#/properties/message
  - schema property: post-admin-change-password.json#/properties/message
  - schema property: post-admin-csv-config.json#/properties/message
  - schema property: post-admin-customer-resend-activation-mail.json#/properties/message
  - schema property: post-admin-delete-customer.json#/properties/message
  - schema property: post-admin-logout.json#/properties/message
  - schema property: post-admin-order-create.json#/properties/message
  - schema property: post-admin-order-import-shipping.json#/properties/message
  - schema property: post-admin-order-send-mail.json#/properties/message
  - schema property: post-admin-order-shipping-address.json#/properties/message
  - schema property: post-admin-order-shipping-notify-mail.json#/properties/message
  - schema property: post-admin-product-csv-class-category.json#/properties/message
  - schema property: post-admin-product-csv-class-name.json#/properties/message
  - schema property: post-admin-product-csv.json#/properties/message
  - schema property: post-admin-template-template-add.json#/properties/message
  - schema property: post-admin-template-template-list.json#/properties/message
  - schema property: post-admin-two-factor-auth.json#/properties/message
  - schema property: post-admin-unsupported-route.json#/properties/message
  - schema property: post-entry-activate.json#/properties/message
  - schema property: post-forgot-password.json#/properties/message
  - schema property: post-logout.json#/properties/message
  - schema property: post-mypage-change.json#/properties/message
  - schema property: post-mypage-withdraw.json#/properties/message
  - schema property: post-shopping-shipping-edit.json#/properties/message
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/message
  - schema property: post-shopping-shipping-multiple.json#/properties/message
  - schema property: post-shopping-shipping.json#/properties/message
  - schema property: post-unsupported-route.json#/properties/message
  - schema property: put-admin-content-cache.json#/properties/message
  - schema property: put-admin-content-css.json#/properties/message
  - schema property: put-admin-content-js.json#/properties/message
  - schema property: put-admin-content-maintenance.json#/properties/message
  - schema property: put-admin-master-data-edit.json#/properties/message
  - schema property: put-admin-master-data.json#/properties/message
  - schema property: put-admin-order-status.json#/properties/message
  - schema property: put-admin-order-tracking-number.json#/properties/message
  - schema property: put-admin-security.json#/properties/message
  - schema property: put-admin-sort-no-move.json#/properties/message
  - schema property: put-admin-template-template-list.json#/properties/message
  - schema property: put-admin-two-factor-auth-set.json#/properties/message

### `method`

- usages:
  - schema property: delete-admin-block-block.param.json#/$defs/link/properties/method
  - schema property: delete-admin-calendar.param.json#/$defs/link/properties/method
  - schema property: delete-admin-category-category.param.json#/$defs/link/properties/method
  - schema property: delete-admin-class-category-class-category.param.json#/$defs/link/properties/method
  - schema property: delete-admin-class-name-class-name.param.json#/$defs/link/properties/method
  - schema property: delete-admin-delivery-delivery.param.json#/$defs/link/properties/method
  - schema property: delete-admin-mail-template.param.json#/$defs/link/properties/method
  - schema property: delete-admin-member.param.json#/$defs/link/properties/method
  - schema property: delete-admin-news-news.param.json#/$defs/link/properties/method
  - schema property: delete-admin-page-page.param.json#/$defs/link/properties/method
  - schema property: delete-admin-payment-payment.param.json#/$defs/link/properties/method
  - schema property: delete-admin-plugin.param.json#/$defs/link/properties/method
  - schema property: delete-admin-product.param.json#/$defs/link/properties/method
  - schema property: delete-admin-tag-tag.param.json#/$defs/link/properties/method
  - schema property: delete-admin-tax-rule-tax-rule.param.json#/$defs/link/properties/method
  - schema property: delete-admin-template-template-list.param.json#/$defs/link/properties/method
  - schema property: delete-cart-item.param.json#/$defs/link/properties/method
  - schema property: delete-mypage-address.param.json#/$defs/link/properties/method
  - schema property: delete-mypage-favorite.param.json#/$defs/link/properties/method
  - schema property: get-action-redirect.param.json#/$defs/link/properties/method
  - schema property: get-admin-action-redirect.param.json#/$defs/link/properties/method
  - schema property: get-admin-category-category.param.json#/$defs/link/properties/method
  - schema property: get-admin-category-edit.param.json#/$defs/link/properties/method
  - schema property: get-admin-class-category-class-category-export.param.json#/$defs/link/properties/method
  - schema property: get-admin-class-category-class-category-list.param.json#/$defs/link/properties/method
  - schema property: get-admin-csv-config.param.json#/$defs/link/properties/method
  - schema property: get-admin-customer-delivery-edit.param.json#/$defs/link/properties/method
  - schema property: get-admin-customer-list.param.json#/$defs/link/properties/method
  - schema property: get-admin-customer.param.json#/$defs/link/properties/method
  - schema property: get-admin-delivery-delivery.param.json#/$defs/link/properties/method
  - schema property: get-admin-login-history.param.json#/$defs/link/properties/method
  - schema property: get-admin-master-data.param.json#/$defs/link/properties/method
  - schema property: get-admin-member-list.param.json#/$defs/link/properties/method
  - schema property: get-admin-member.param.json#/$defs/link/properties/method
  - schema property: get-admin-news-news.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-edit.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-export-order-pdf.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-list.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-mail-confirm.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-order-pdf.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-send-mail.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-shipping-address.param.json#/$defs/link/properties/method
  - schema property: get-admin-order-shipping-notify-mail.param.json#/$defs/link/properties/method
  - schema property: get-admin-order.param.json#/$defs/link/properties/method
  - schema property: get-admin-page-page.param.json#/$defs/link/properties/method
  - schema property: get-admin-payment-payment.param.json#/$defs/link/properties/method
  - schema property: get-admin-product-edit.param.json#/$defs/link/properties/method
  - schema property: get-admin-product-list.param.json#/$defs/link/properties/method
  - schema property: get-admin-product-product-class.param.json#/$defs/link/properties/method
  - schema property: get-admin-product.param.json#/$defs/link/properties/method
  - schema property: get-admin-unsupported-route.param.json#/$defs/link/properties/method
  - schema property: get-cart.param.json#/$defs/link/properties/method
  - schema property: get-contact-complete.param.json#/$defs/link/properties/method
  - schema property: get-mypage-address.param.json#/$defs/link/properties/method
  - schema property: get-mypage-history.param.json#/$defs/link/properties/method
  - schema property: get-mypage-order-history.param.json#/$defs/link/properties/method
  - schema property: get-mypage.param.json#/$defs/link/properties/method
  - schema property: get-product.param.json#/$defs/link/properties/method
  - schema property: get-products.param.json#/$defs/link/properties/method
  - schema property: get-reset.param.json#/$defs/link/properties/method
  - schema property: get-shopping-complete.param.json#/$defs/link/properties/method
  - schema property: get-shopping-confirm.param.json#/$defs/link/properties/method
  - schema property: get-shopping.param.json#/$defs/link/properties/method
  - schema property: get-unsupported-route.param.json#/$defs/link/properties/method
  - schema property: post-action-redirect.param.json#/$defs/link/properties/method
  - schema property: post-admin-action-redirect.param.json#/$defs/link/properties/method
  - schema property: post-admin-authority-role.param.json#/$defs/link/properties/method
  - schema property: post-admin-base-info.param.json#/$defs/link/properties/method
  - schema property: post-admin-block-block-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-calendar.param.json#/$defs/link/properties/method
  - schema property: post-admin-category-category-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-category-csv.param.json#/$defs/link/properties/method
  - schema property: post-admin-change-password.param.json#/$defs/link/properties/method
  - schema property: post-admin-class-category-class-category-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-class-name-class-name-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-create-customer.param.json#/$defs/link/properties/method
  - schema property: post-admin-csv-config.param.json#/$defs/link/properties/method
  - schema property: post-admin-customer-resend-activation-mail.param.json#/$defs/link/properties/method
  - schema property: post-admin-delete-customer.param.json#/$defs/link/properties/method
  - schema property: post-admin-delivery-delivery-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-login.param.json#/$defs/link/properties/method
  - schema property: post-admin-mail-template.param.json#/$defs/link/properties/method
  - schema property: post-admin-member.param.json#/$defs/link/properties/method
  - schema property: post-admin-news-news-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-order-bulk-delete.param.json#/$defs/link/properties/method
  - schema property: post-admin-order-create.param.json#/$defs/link/properties/method
  - schema property: post-admin-order-import-shipping.param.json#/$defs/link/properties/method
  - schema property: post-admin-order-send-mail.param.json#/$defs/link/properties/method
  - schema property: post-admin-order-shipping-address.param.json#/$defs/link/properties/method
  - schema property: post-admin-order-shipping-notify-mail.param.json#/$defs/link/properties/method
  - schema property: post-admin-order-status.param.json#/$defs/link/properties/method
  - schema property: post-admin-page-page-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-payment-payment-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-plugin-disable.param.json#/$defs/link/properties/method
  - schema property: post-admin-plugin-enable.param.json#/$defs/link/properties/method
  - schema property: post-admin-plugin-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-product-bulk-status.param.json#/$defs/link/properties/method
  - schema property: post-admin-product-copy.param.json#/$defs/link/properties/method
  - schema property: post-admin-product-csv-class-category.param.json#/$defs/link/properties/method
  - schema property: post-admin-product-csv-class-name.param.json#/$defs/link/properties/method
  - schema property: post-admin-product-csv.param.json#/$defs/link/properties/method
  - schema property: post-admin-product.param.json#/$defs/link/properties/method
  - schema property: post-admin-tag-tag-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-tax-rule-tax-rule-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-template-template-add.param.json#/$defs/link/properties/method
  - schema property: post-admin-template-template-list.param.json#/$defs/link/properties/method
  - schema property: post-admin-trade-law.param.json#/$defs/link/properties/method
  - schema property: post-admin-two-factor-auth.param.json#/$defs/link/properties/method
  - schema property: post-admin-unsupported-route.param.json#/$defs/link/properties/method
  - schema property: post-cart-item.param.json#/$defs/link/properties/method
  - schema property: post-contact.param.json#/$defs/link/properties/method
  - schema property: post-entry-activate.param.json#/$defs/link/properties/method
  - schema property: post-entry.param.json#/$defs/link/properties/method
  - schema property: post-forgot-password.param.json#/$defs/link/properties/method
  - schema property: post-login.param.json#/$defs/link/properties/method
  - schema property: post-mypage-address-list.param.json#/$defs/link/properties/method
  - schema property: post-mypage-change.param.json#/$defs/link/properties/method
  - schema property: post-mypage-favorite.param.json#/$defs/link/properties/method
  - schema property: post-mypage-reorder.param.json#/$defs/link/properties/method
  - schema property: post-mypage-withdraw.param.json#/$defs/link/properties/method
  - schema property: post-reset.param.json#/$defs/link/properties/method
  - schema property: post-shopping-checkout.param.json#/$defs/link/properties/method
  - schema property: post-shopping-confirm.param.json#/$defs/link/properties/method
  - schema property: post-shopping-non-member.param.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping-edit.param.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping-multiple-edit.param.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping-multiple.param.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping.param.json#/$defs/link/properties/method
  - schema property: post-unsupported-route.param.json#/$defs/link/properties/method
  - schema property: put-admin-block-block.param.json#/$defs/link/properties/method
  - schema property: put-admin-category-category.param.json#/$defs/link/properties/method
  - schema property: put-admin-class-category-class-category.param.json#/$defs/link/properties/method
  - schema property: put-admin-class-name-class-name.param.json#/$defs/link/properties/method
  - schema property: put-admin-content-css.param.json#/$defs/link/properties/method
  - schema property: put-admin-content-js.param.json#/$defs/link/properties/method
  - schema property: put-admin-content-maintenance.param.json#/$defs/link/properties/method
  - schema property: put-admin-delivery-delivery.param.json#/$defs/link/properties/method
  - schema property: put-admin-layout-layout.param.json#/$defs/link/properties/method
  - schema property: put-admin-master-data-edit.param.json#/$defs/link/properties/method
  - schema property: put-admin-master-data.param.json#/$defs/link/properties/method
  - schema property: put-admin-member.param.json#/$defs/link/properties/method
  - schema property: put-admin-news-news.param.json#/$defs/link/properties/method
  - schema property: put-admin-order-shipping-address.param.json#/$defs/link/properties/method
  - schema property: put-admin-order-status.param.json#/$defs/link/properties/method
  - schema property: put-admin-order-tracking-number.param.json#/$defs/link/properties/method
  - schema property: put-admin-order.param.json#/$defs/link/properties/method
  - schema property: put-admin-page-page.param.json#/$defs/link/properties/method
  - schema property: put-admin-payment-payment.param.json#/$defs/link/properties/method
  - schema property: put-admin-product.param.json#/$defs/link/properties/method
  - schema property: put-admin-security.param.json#/$defs/link/properties/method
  - schema property: put-admin-sort-no-move.param.json#/$defs/link/properties/method
  - schema property: put-admin-template-template-list.param.json#/$defs/link/properties/method
  - schema property: put-admin-toggle-visible.param.json#/$defs/link/properties/method
  - schema property: put-admin-two-factor-auth-set.param.json#/$defs/link/properties/method
  - schema property: put-cart-item.param.json#/$defs/link/properties/method
  - schema property: put-mypage-address.param.json#/$defs/link/properties/method
  - schema property: delete-admin-block-block.json#/$defs/link/properties/method
  - schema property: delete-admin-calendar.json#/$defs/link/properties/method
  - schema property: delete-admin-category-category.json#/$defs/link/properties/method
  - schema property: delete-admin-class-category-class-category.json#/$defs/link/properties/method
  - schema property: delete-admin-class-name-class-name.json#/$defs/link/properties/method
  - schema property: delete-admin-delivery-delivery.json#/$defs/link/properties/method
  - schema property: delete-admin-mail-template.json#/$defs/link/properties/method
  - schema property: delete-admin-member.json#/$defs/link/properties/method
  - schema property: delete-admin-news-news.json#/$defs/link/properties/method
  - schema property: delete-admin-page-page.json#/$defs/link/properties/method
  - schema property: delete-admin-payment-payment.json#/$defs/link/properties/method
  - schema property: delete-admin-plugin.json#/$defs/link/properties/method
  - schema property: delete-admin-product.json#/$defs/link/properties/method
  - schema property: delete-admin-tag-tag.json#/$defs/link/properties/method
  - schema property: delete-admin-tax-rule-tax-rule.json#/$defs/link/properties/method
  - schema property: delete-admin-template-template-list.json#/$defs/link/properties/method
  - schema property: delete-cart-item.json#/$defs/link/properties/method
  - schema property: delete-mypage-address.json#/$defs/link/properties/method
  - schema property: delete-mypage-favorite.json#/$defs/link/properties/method
  - schema property: get-action-redirect.json#/$defs/link/properties/method
  - schema property: get-admin-action-redirect.json#/$defs/link/properties/method
  - schema property: get-admin-authority-role.json#/$defs/link/properties/method
  - schema property: get-admin-base-info.json#/$defs/link/properties/method
  - schema property: get-admin-block-block-list.json#/$defs/link/properties/method
  - schema property: get-admin-block-block.json#/$defs/link/properties/method
  - schema property: get-admin-calendar.json#/$defs/link/properties/method
  - schema property: get-admin-category-category-list.json#/$defs/link/properties/method
  - schema property: get-admin-category-category.json#/$defs/link/properties/method
  - schema property: get-admin-category-csv.json#/$defs/link/properties/method
  - schema property: get-admin-category-edit.json#/$defs/link/properties/method
  - schema property: get-admin-change-password.json#/$defs/link/properties/method
  - schema property: get-admin-class-category-class-category-export.json#/$defs/link/properties/method
  - schema property: get-admin-class-category-class-category-list.json#/$defs/link/properties/method
  - schema property: get-admin-class-name-class-name-export.json#/$defs/link/properties/method
  - schema property: get-admin-class-name-class-name-list.json#/$defs/link/properties/method
  - schema property: get-admin-content-cache.json#/$defs/link/properties/method
  - schema property: get-admin-content-css.json#/$defs/link/properties/method
  - schema property: get-admin-content-file-manager.json#/$defs/link/properties/method
  - schema property: get-admin-content-js.json#/$defs/link/properties/method
  - schema property: get-admin-content-maintenance.json#/$defs/link/properties/method
  - schema property: get-admin-csv-config.json#/$defs/link/properties/method
  - schema property: get-admin-customer-csv.json#/$defs/link/properties/method
  - schema property: get-admin-customer-delivery-edit.json#/$defs/link/properties/method
  - schema property: get-admin-customer-list.json#/$defs/link/properties/method
  - schema property: get-admin-customer.json#/$defs/link/properties/method
  - schema property: get-admin-delivery-delivery-list.json#/$defs/link/properties/method
  - schema property: get-admin-delivery-delivery.json#/$defs/link/properties/method
  - schema property: get-admin-empty-page.json#/$defs/link/properties/method
  - schema property: get-admin-index.json#/$defs/link/properties/method
  - schema property: get-admin-layout-layout-list.json#/$defs/link/properties/method
  - schema property: get-admin-layout-layout.json#/$defs/link/properties/method
  - schema property: get-admin-log.json#/$defs/link/properties/method
  - schema property: get-admin-login-history.json#/$defs/link/properties/method
  - schema property: get-admin-login.json#/$defs/link/properties/method
  - schema property: get-admin-login.json#/properties/submitTo/properties/method
  - schema property: get-admin-mail-template.json#/$defs/link/properties/method
  - schema property: get-admin-master-data.json#/$defs/link/properties/method
  - schema property: get-admin-master-data.json#/properties/submitTo/properties/method
  - schema property: get-admin-member-list.json#/$defs/link/properties/method
  - schema property: get-admin-member.json#/$defs/link/properties/method
  - schema property: get-admin-news-news-list.json#/$defs/link/properties/method
  - schema property: get-admin-news-news.json#/$defs/link/properties/method
  - schema property: get-admin-order-edit.json#/$defs/link/properties/method
  - schema property: get-admin-order-export-order-pdf.json#/$defs/link/properties/method
  - schema property: get-admin-order-export-order.json#/$defs/link/properties/method
  - schema property: get-admin-order-export-shipping.json#/$defs/link/properties/method
  - schema property: get-admin-order-import-shipping.json#/$defs/link/properties/method
  - schema property: get-admin-order-list.json#/$defs/link/properties/method
  - schema property: get-admin-order-mail-confirm.json#/$defs/link/properties/method
  - schema property: get-admin-order-order-pdf.json#/$defs/link/properties/method
  - schema property: get-admin-order-send-mail.json#/$defs/link/properties/method
  - schema property: get-admin-order-shipping-address.json#/$defs/link/properties/method
  - schema property: get-admin-order-shipping-notify-mail.json#/$defs/link/properties/method
  - schema property: get-admin-order-shipping-notify-mail.json#/properties/submitTo/properties/method
  - schema property: get-admin-order-status.json#/$defs/link/properties/method
  - schema property: get-admin-order.json#/$defs/link/properties/method
  - schema property: get-admin-page-page-list.json#/$defs/link/properties/method
  - schema property: get-admin-page-page.json#/$defs/link/properties/method
  - schema property: get-admin-payment-payment-list.json#/$defs/link/properties/method
  - schema property: get-admin-payment-payment.json#/$defs/link/properties/method
  - schema property: get-admin-plugin-list.json#/$defs/link/properties/method
  - schema property: get-admin-product-csv-category.json#/$defs/link/properties/method
  - schema property: get-admin-product-csv-class-category.json#/$defs/link/properties/method
  - schema property: get-admin-product-csv-class-name.json#/$defs/link/properties/method
  - schema property: get-admin-product-csv-product.json#/$defs/link/properties/method
  - schema property: get-admin-product-csv.json#/$defs/link/properties/method
  - schema property: get-admin-product-edit.json#/$defs/link/properties/method
  - schema property: get-admin-product-list.json#/$defs/link/properties/method
  - schema property: get-admin-product-new.json#/$defs/link/properties/method
  - schema property: get-admin-product-product-class.json#/$defs/link/properties/method
  - schema property: get-admin-product.json#/$defs/link/properties/method
  - schema property: get-admin-security.json#/$defs/link/properties/method
  - schema property: get-admin-system.json#/$defs/link/properties/method
  - schema property: get-admin-tag-tag-list.json#/$defs/link/properties/method
  - schema property: get-admin-tax-rule-tax-rule-list.json#/$defs/link/properties/method
  - schema property: get-admin-template-template-add.json#/$defs/link/properties/method
  - schema property: get-admin-template-template-list.json#/$defs/link/properties/method
  - schema property: get-admin-trade-law.json#/$defs/link/properties/method
  - schema property: get-admin-two-factor-auth-edit.json#/$defs/link/properties/method
  - schema property: get-admin-two-factor-auth-set.json#/$defs/link/properties/method
  - schema property: get-admin-two-factor-auth.json#/$defs/link/properties/method
  - schema property: get-admin-unsupported-route.json#/$defs/link/properties/method
  - schema property: get-cart.json#/$defs/link/properties/method
  - schema property: get-contact-complete.json#/$defs/link/properties/method
  - schema property: get-contact-confirm.json#/$defs/link/properties/method
  - schema property: get-contact-confirm.json#/properties/submitTo/properties/method
  - schema property: get-contact.json#/$defs/link/properties/method
  - schema property: get-contact.json#/properties/submitTo/properties/method
  - schema property: get-entry-activate.json#/$defs/link/properties/method
  - schema property: get-entry-complete.json#/$defs/link/properties/method
  - schema property: get-entry-confirm.json#/$defs/link/properties/method
  - schema property: get-entry-confirm.json#/properties/submitTo/properties/method
  - schema property: get-entry.json#/$defs/link/properties/method
  - schema property: get-entry.json#/properties/submitTo/properties/method
  - schema property: get-forgot-complete.json#/$defs/link/properties/method
  - schema property: get-forgot-password.json#/$defs/link/properties/method
  - schema property: get-forgot-password.json#/properties/submitTo/properties/method
  - schema property: get-help-about.json#/$defs/link/properties/method
  - schema property: get-help-agreement.json#/$defs/link/properties/method
  - schema property: get-help-guide.json#/$defs/link/properties/method
  - schema property: get-help-privacy.json#/$defs/link/properties/method
  - schema property: get-help-trade-law.json#/$defs/link/properties/method
  - schema property: get-index.json#/$defs/link/properties/method
  - schema property: get-login.json#/$defs/link/properties/method
  - schema property: get-login.json#/properties/submitTo/properties/method
  - schema property: get-mypage-address-list.json#/$defs/link/properties/method
  - schema property: get-mypage-address.json#/$defs/link/properties/method
  - schema property: get-mypage-address.json#/properties/submitTo/properties/method
  - schema property: get-mypage-change-complete.json#/$defs/link/properties/method
  - schema property: get-mypage-change.json#/$defs/link/properties/method
  - schema property: get-mypage-change.json#/properties/submitTo/properties/method
  - schema property: get-mypage-favorite-list.json#/$defs/link/properties/method
  - schema property: get-mypage-history.json#/$defs/link/properties/method
  - schema property: get-mypage-order-history.json#/$defs/link/properties/method
  - schema property: get-mypage-withdraw-complete.json#/$defs/link/properties/method
  - schema property: get-mypage-withdraw-confirm.json#/$defs/link/properties/method
  - schema property: get-mypage-withdraw-confirm.json#/properties/submitTo/properties/method
  - schema property: get-mypage-withdraw.json#/$defs/link/properties/method
  - schema property: get-mypage-withdraw.json#/properties/submitTo/properties/method
  - schema property: get-mypage.json#/$defs/link/properties/method
  - schema property: get-product.json#/$defs/link/properties/method
  - schema property: get-products.json#/$defs/link/properties/method
  - schema property: get-reset.json#/$defs/link/properties/method
  - schema property: get-reset.json#/properties/submitTo/properties/method
  - schema property: get-shopping-complete.json#/$defs/link/properties/method
  - schema property: get-shopping-confirm.json#/$defs/link/properties/method
  - schema property: get-shopping-confirm.json#/properties/submitTo/properties/method
  - schema property: get-shopping-error.json#/$defs/link/properties/method
  - schema property: get-shopping-login.json#/$defs/link/properties/method
  - schema property: get-shopping-non-member.json#/$defs/link/properties/method
  - schema property: get-shopping-non-member.json#/properties/submitTo/properties/method
  - schema property: get-shopping-shipping-edit.json#/$defs/link/properties/method
  - schema property: get-shopping-shipping-edit.json#/properties/submitTo/properties/method
  - schema property: get-shopping-shipping-multiple-edit.json#/$defs/link/properties/method
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/submitTo/properties/method
  - schema property: get-shopping-shipping-multiple.json#/$defs/link/properties/method
  - schema property: get-shopping-shipping.json#/$defs/link/properties/method
  - schema property: get-shopping-shipping.json#/properties/submitTo/properties/method
  - schema property: get-shopping.json#/$defs/link/properties/method
  - schema property: get-unsupported-route.json#/$defs/link/properties/method
  - schema property: post-action-redirect.json#/$defs/link/properties/method
  - schema property: post-admin-action-redirect.json#/$defs/link/properties/method
  - schema property: post-admin-authority-role.json#/$defs/link/properties/method
  - schema property: post-admin-base-info.json#/$defs/link/properties/method
  - schema property: post-admin-block-block-list.json#/$defs/link/properties/method
  - schema property: post-admin-calendar.json#/$defs/link/properties/method
  - schema property: post-admin-category-category-list.json#/$defs/link/properties/method
  - schema property: post-admin-category-csv.json#/$defs/link/properties/method
  - schema property: post-admin-change-password.json#/$defs/link/properties/method
  - schema property: post-admin-class-category-class-category-list.json#/$defs/link/properties/method
  - schema property: post-admin-class-name-class-name-list.json#/$defs/link/properties/method
  - schema property: post-admin-create-customer.json#/$defs/link/properties/method
  - schema property: post-admin-csv-config.json#/$defs/link/properties/method
  - schema property: post-admin-customer-resend-activation-mail.json#/$defs/link/properties/method
  - schema property: post-admin-delete-customer.json#/$defs/link/properties/method
  - schema property: post-admin-delivery-delivery-list.json#/$defs/link/properties/method
  - schema property: post-admin-login.json#/$defs/link/properties/method
  - schema property: post-admin-logout.json#/$defs/link/properties/method
  - schema property: post-admin-mail-template.json#/$defs/link/properties/method
  - schema property: post-admin-member.json#/$defs/link/properties/method
  - schema property: post-admin-news-news-list.json#/$defs/link/properties/method
  - schema property: post-admin-order-bulk-delete.json#/$defs/link/properties/method
  - schema property: post-admin-order-create.json#/$defs/link/properties/method
  - schema property: post-admin-order-import-shipping.json#/$defs/link/properties/method
  - schema property: post-admin-order-send-mail.json#/$defs/link/properties/method
  - schema property: post-admin-order-shipping-address.json#/$defs/link/properties/method
  - schema property: post-admin-order-shipping-notify-mail.json#/$defs/link/properties/method
  - schema property: post-admin-order-status.json#/$defs/link/properties/method
  - schema property: post-admin-page-page-list.json#/$defs/link/properties/method
  - schema property: post-admin-payment-payment-list.json#/$defs/link/properties/method
  - schema property: post-admin-plugin-disable.json#/$defs/link/properties/method
  - schema property: post-admin-plugin-enable.json#/$defs/link/properties/method
  - schema property: post-admin-plugin-list.json#/$defs/link/properties/method
  - schema property: post-admin-product-bulk-status.json#/$defs/link/properties/method
  - schema property: post-admin-product-copy.json#/$defs/link/properties/method
  - schema property: post-admin-product-csv-class-category.json#/$defs/link/properties/method
  - schema property: post-admin-product-csv-class-name.json#/$defs/link/properties/method
  - schema property: post-admin-product-csv.json#/$defs/link/properties/method
  - schema property: post-admin-product.json#/$defs/link/properties/method
  - schema property: post-admin-tag-tag-list.json#/$defs/link/properties/method
  - schema property: post-admin-tax-rule-tax-rule-list.json#/$defs/link/properties/method
  - schema property: post-admin-template-template-add.json#/$defs/link/properties/method
  - schema property: post-admin-template-template-list.json#/$defs/link/properties/method
  - schema property: post-admin-trade-law.json#/$defs/link/properties/method
  - schema property: post-admin-two-factor-auth.json#/$defs/link/properties/method
  - schema property: post-admin-unsupported-route.json#/$defs/link/properties/method
  - schema property: post-cart-item.json#/$defs/link/properties/method
  - schema property: post-contact.json#/$defs/link/properties/method
  - schema property: post-entry-activate.json#/$defs/link/properties/method
  - schema property: post-entry.json#/$defs/link/properties/method
  - schema property: post-forgot-password.json#/$defs/link/properties/method
  - schema property: post-login.json#/$defs/link/properties/method
  - schema property: post-logout.json#/$defs/link/properties/method
  - schema property: post-mypage-address-list.json#/$defs/link/properties/method
  - schema property: post-mypage-change.json#/$defs/link/properties/method
  - schema property: post-mypage-favorite.json#/$defs/link/properties/method
  - schema property: post-mypage-reorder.json#/$defs/link/properties/method
  - schema property: post-mypage-withdraw.json#/$defs/link/properties/method
  - schema property: post-reset.json#/$defs/link/properties/method
  - schema property: post-shopping-checkout.json#/$defs/link/properties/method
  - schema property: post-shopping-non-member.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping-edit.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping-multiple-edit.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping-multiple.json#/$defs/link/properties/method
  - schema property: post-shopping-shipping.json#/$defs/link/properties/method
  - schema property: post-unsupported-route.json#/$defs/link/properties/method
  - schema property: put-admin-block-block.json#/$defs/link/properties/method
  - schema property: put-admin-category-category.json#/$defs/link/properties/method
  - schema property: put-admin-class-category-class-category.json#/$defs/link/properties/method
  - schema property: put-admin-class-name-class-name.json#/$defs/link/properties/method
  - schema property: put-admin-content-cache.json#/$defs/link/properties/method
  - schema property: put-admin-content-css.json#/$defs/link/properties/method
  - schema property: put-admin-content-js.json#/$defs/link/properties/method
  - schema property: put-admin-content-maintenance.json#/$defs/link/properties/method
  - schema property: put-admin-delivery-delivery.json#/$defs/link/properties/method
  - schema property: put-admin-layout-layout.json#/$defs/link/properties/method
  - schema property: put-admin-master-data-edit.json#/$defs/link/properties/method
  - schema property: put-admin-master-data.json#/$defs/link/properties/method
  - schema property: put-admin-master-data.json#/properties/submitTo/properties/method
  - schema property: put-admin-member.json#/$defs/link/properties/method
  - schema property: put-admin-news-news.json#/$defs/link/properties/method
  - schema property: put-admin-order-shipping-address.json#/$defs/link/properties/method
  - schema property: put-admin-order-status.json#/$defs/link/properties/method
  - schema property: put-admin-order-tracking-number.json#/$defs/link/properties/method
  - schema property: put-admin-order.json#/$defs/link/properties/method
  - schema property: put-admin-page-page.json#/$defs/link/properties/method
  - schema property: put-admin-payment-payment.json#/$defs/link/properties/method
  - schema property: put-admin-product.json#/$defs/link/properties/method
  - schema property: put-admin-security.json#/$defs/link/properties/method
  - schema property: put-admin-sort-no-move.json#/$defs/link/properties/method
  - schema property: put-admin-template-template-list.json#/$defs/link/properties/method
  - schema property: put-admin-toggle-visible.json#/$defs/link/properties/method
  - schema property: put-admin-two-factor-auth-set.json#/$defs/link/properties/method
  - schema property: put-cart-item.json#/$defs/link/properties/method
  - schema property: put-mypage-address.json#/$defs/link/properties/method

### `mode`

- usages:
  - parameter: PUT /admin/content/cache {mode}
  - parameter: PUT /admin/content/css {mode}
  - parameter: PUT /admin/content/js {mode}
  - parameter: PUT /admin/content/maintenance {mode}
  - parameter: POST /admin/login {mode}
  - parameter: POST /admin/member {mode}
  - parameter: PUT /admin/member {mode}
  - parameter: DELETE /admin/member {mode}
  - parameter: POST /admin/order/bulk-delete {mode}
  - parameter: POST /admin/trade-law {mode}
  - parameter: POST /contact {mode}
  - parameter: POST /entry {mode}
  - parameter: POST /login {mode}
  - schema property: delete-admin-member.param.json#/properties/mode
  - schema property: post-admin-login.param.json#/properties/mode
  - schema property: post-admin-member.param.json#/properties/mode
  - schema property: post-admin-order-bulk-delete.param.json#/properties/mode
  - schema property: post-admin-trade-law.param.json#/properties/mode
  - schema property: post-contact.param.json#/properties/mode
  - schema property: post-entry.param.json#/properties/mode
  - schema property: post-login.param.json#/properties/mode
  - schema property: put-admin-content-cache.param.json#/properties/mode
  - schema property: put-admin-content-css.param.json#/properties/mode
  - schema property: put-admin-content-js.param.json#/properties/mode
  - schema property: put-admin-content-maintenance.param.json#/properties/mode
  - schema property: put-admin-member.param.json#/properties/mode

### `name`

- usages:
  - parameter: PUT /admin/layout/layout {name}
  - parameter: POST /admin/member {name}
  - parameter: PUT /admin/member {name}
  - parameter: GET /products {name}
  - schema property: get-products.param.json#/properties/name
  - schema property: post-admin-csv-config.param.json#/properties/columns/items/properties/name
  - schema property: post-admin-member.param.json#/properties/name
  - schema property: put-admin-layout-layout.param.json#/properties/name
  - schema property: put-admin-master-data-edit.param.json#/properties/rows/items/properties/name
  - schema property: put-admin-member.param.json#/properties/name
  - schema property: put-admin-order-status.param.json#/properties/orderStatuses/items/properties/name
  - schema property: get-admin-class-category-class-category-list.json#/properties/classCategories/items/properties/name
  - schema property: get-admin-class-name-class-name-list.json#/properties/classNames/items/properties/name
  - schema property: get-admin-customer.json#/properties/favorites/items/properties/name
  - schema property: get-admin-index.json#/properties/orderStatuses/items/properties/name
  - schema property: get-admin-index.json#/properties/recommendedPlugins/items/properties/name
  - schema property: get-admin-master-data.json#/properties/rows/items/properties/name
  - schema property: get-admin-member-list.json#/properties/members/items/properties/name
  - schema property: get-admin-member.json#/properties/name
  - schema property: get-admin-product-csv-category.json#/properties/columns/items/properties/name
  - schema property: get-admin-product-csv-class-category.json#/properties/columns/items/properties/name
  - schema property: get-admin-product-csv-class-name.json#/properties/columns/items/properties/name
  - schema property: get-admin-product-csv-product.json#/properties/columns/items/properties/name
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows/items/properties/name
  - schema property: get-mypage-favorite-list.json#/properties/favorites/items/properties/name
  - schema property: get-products.json#/properties/filters/properties/name
  - schema property: get-products.json#/properties/products/items/properties/name
  - schema property: post-admin-class-category-class-category-list.json#/properties/name
  - schema property: post-admin-class-name-class-name-list.json#/properties/name
  - schema property: post-admin-csv-config.json#/properties/columns/items/properties/name
  - schema property: post-admin-login.json#/properties/name
  - schema property: post-admin-member.json#/properties/name
  - schema property: put-admin-class-category-class-category.json#/properties/name
  - schema property: put-admin-class-name-class-name.json#/properties/name
  - schema property: put-admin-master-data.json#/properties/rows/items/properties/name
  - schema property: put-admin-member.json#/properties/name

### `name01` ☑︎

- title: 姓
- def: https://schema.org/familyName
- doc: 顧客・受注・配送先・お問い合わせで共通使用される姓
- usages:
  - parameter: POST /admin/create-customer {name01}
  - parameter: PUT /admin/order/shipping-address {name01}
  - parameter: POST /entry {name01}
  - parameter: PUT /mypage/address {name01}
  - parameter: POST /mypage/address-list {name01}
  - parameter: POST /mypage/change {name01}
  - parameter: POST /shopping/non-member {name01}
  - parameter: POST /shopping/shipping-edit {name01}
  - parameter: POST /shopping/shipping-multiple-edit {name01}
  - schema property: post-admin-create-customer.param.json#/properties/name01
  - schema property: post-entry.param.json#/properties/name01
  - schema property: post-mypage-address-list.param.json#/properties/name01
  - schema property: post-mypage-change.param.json#/properties/name01
  - schema property: post-shopping-non-member.param.json#/properties/name01
  - schema property: post-shopping-shipping-edit.param.json#/properties/name01
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/name01
  - schema property: put-admin-order-shipping-address.param.json#/properties/name01
  - schema property: put-mypage-address.param.json#/properties/name01
  - schema property: get-admin-customer-list.json#/properties/customers/items/properties/name01
  - schema property: get-admin-customer.json#/properties/name01
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/name01
  - schema property: get-mypage-change.json#/properties/name01
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/name01
  - schema property: get-mypage-withdraw.json#/properties/name01
  - schema property: get-mypage.json#/properties/name01
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/name01
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/name01
  - schema property: get-shopping.json#/properties/name01
  - schema property: post-admin-create-customer.json#/properties/name01
  - schema property: post-entry.json#/properties/name01
  - schema property: post-login.json#/properties/name01
  - schema property: post-mypage-address-list.json#/properties/name01
  - schema property: post-mypage-change.json#/properties/name01
  - schema property: post-shopping-non-member.json#/properties/name01
  - schema property: post-shopping-shipping-edit.json#/properties/name01
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/name01
  - schema property: put-admin-order-shipping-address.json#/properties/name01
  - schema property: put-mypage-address.json#/properties/name01

### `name02` ☑︎

- title: 名
- def: https://schema.org/givenName
- doc: 顧客・受注・配送先・お問い合わせで共通使用される名
- usages:
  - parameter: POST /admin/create-customer {name02}
  - parameter: PUT /admin/order/shipping-address {name02}
  - parameter: POST /entry {name02}
  - parameter: PUT /mypage/address {name02}
  - parameter: POST /mypage/address-list {name02}
  - parameter: POST /mypage/change {name02}
  - parameter: POST /shopping/non-member {name02}
  - parameter: POST /shopping/shipping-edit {name02}
  - parameter: POST /shopping/shipping-multiple-edit {name02}
  - schema property: post-admin-create-customer.param.json#/properties/name02
  - schema property: post-entry.param.json#/properties/name02
  - schema property: post-mypage-address-list.param.json#/properties/name02
  - schema property: post-mypage-change.param.json#/properties/name02
  - schema property: post-shopping-non-member.param.json#/properties/name02
  - schema property: post-shopping-shipping-edit.param.json#/properties/name02
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/name02
  - schema property: put-admin-order-shipping-address.param.json#/properties/name02
  - schema property: put-mypage-address.param.json#/properties/name02
  - schema property: get-admin-customer-list.json#/properties/customers/items/properties/name02
  - schema property: get-admin-customer.json#/properties/name02
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/name02
  - schema property: get-mypage-change.json#/properties/name02
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/name02
  - schema property: get-mypage-withdraw.json#/properties/name02
  - schema property: get-mypage.json#/properties/name02
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/name02
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/name02
  - schema property: get-shopping.json#/properties/name02
  - schema property: post-admin-create-customer.json#/properties/name02
  - schema property: post-entry.json#/properties/name02
  - schema property: post-login.json#/properties/name02
  - schema property: post-mypage-address-list.json#/properties/name02
  - schema property: post-mypage-change.json#/properties/name02
  - schema property: post-shopping-non-member.json#/properties/name02
  - schema property: post-shopping-shipping-edit.json#/properties/name02
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/name02
  - schema property: put-admin-order-shipping-address.json#/properties/name02
  - schema property: put-mypage-address.json#/properties/name02

### `nameKey`

- usages:
  - schema property: get-admin-order-status.json#/properties/orderStatuses/items/properties/nameKey
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows/items/properties/nameKey

### `nameKeyword`

- usages:
  - parameter: GET /admin/customer-list {nameKeyword}
  - parameter: GET /admin/member-list {nameKeyword}
  - parameter: GET /admin/product-list {nameKeyword}
  - parameter: GET /products {nameKeyword}
  - schema property: get-admin-customer-list.param.json#/properties/nameKeyword
  - schema property: get-admin-member-list.param.json#/properties/nameKeyword
  - schema property: get-admin-product-list.param.json#/properties/nameKeyword
  - schema property: get-products.param.json#/properties/nameKeyword
  - schema property: get-admin-customer-list.json#/properties/filters/properties/nameKeyword
  - schema property: get-admin-member-list.json#/properties/filters/properties/nameKeyword
  - schema property: get-admin-product-list.json#/properties/filters/properties/nameKeyword
  - schema property: get-products.json#/properties/filters/properties/nameKeyword

### `newProductCode`

- usages:
  - parameter: POST /admin/product-copy {newProductCode}
  - schema property: post-admin-product-copy.param.json#/properties/newProductCode
  - schema property: post-admin-product-copy.json#/properties/newProductCode

### `newProductName`

- usages:
  - schema property: post-admin-product-copy.json#/properties/newProductName

### `news`

- usages:
  - schema property: get-admin-news-news-list.json#/properties/news

### `newsDescription` ☑︎

- title: ニュース本文
- def: https://schema.org/articleBody
- doc: ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり
- usages:
  - parameter: PUT /admin/news/news {newsDescription}
  - parameter: POST /admin/news/news-list {newsDescription}
  - schema property: post-admin-news-news-list.param.json#/properties/newsDescription
  - schema property: put-admin-news-news.param.json#/properties/newsDescription
  - schema property: get-admin-news-news-list.json#/properties/news/items/properties/newsDescription
  - schema property: get-admin-news-news.json#/properties/newsDescription
  - schema property: post-admin-news-news-list.json#/properties/newsDescription
  - schema property: put-admin-news-news.json#/properties/newsDescription

### `newsId` ☑︎

- title: ニュースID
- doc: dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: GET /admin/news/news {newsId}
  - parameter: PUT /admin/news/news {newsId}
  - parameter: DELETE /admin/news/news {newsId}
  - schema property: delete-admin-news-news.param.json#/properties/newsId
  - schema property: get-admin-news-news.param.json#/properties/newsId
  - schema property: put-admin-news-news.param.json#/properties/newsId
  - schema property: delete-admin-news-news.json#/properties/newsId
  - schema property: get-admin-news-news-list.json#/properties/news/items/properties/newsId
  - schema property: get-admin-news-news.json#/properties/newsId
  - schema property: post-admin-news-news-list.json#/properties/newsId
  - schema property: put-admin-news-news.json#/properties/newsId

### `newsTitle` ☑︎

- title: ニュースタイトル
- def: https://schema.org/headline
- doc: ニュース記事の見出し
- usages:
  - parameter: PUT /admin/news/news {newsTitle}
  - parameter: POST /admin/news/news-list {newsTitle}
  - schema property: post-admin-news-news-list.param.json#/properties/newsTitle
  - schema property: put-admin-news-news.param.json#/properties/newsTitle
  - schema property: get-admin-news-news-list.json#/properties/news/items/properties/newsTitle
  - schema property: get-admin-news-news.json#/properties/newsTitle
  - schema property: post-admin-news-news-list.json#/properties/newsTitle
  - schema property: put-admin-news-news.json#/properties/newsTitle

### `newsUrl` ☑︎

- title: 外部URL
- def: https://schema.org/url
- doc: 外部リンクURL。設定時はニュース本文の代わりにこのURLへ遷移
- usages:
  - parameter: PUT /admin/news/news {newsUrl}
  - parameter: POST /admin/news/news-list {newsUrl}
  - schema property: post-admin-news-news-list.param.json#/properties/newsUrl
  - schema property: put-admin-news-news.param.json#/properties/newsUrl
  - schema property: get-admin-news-news-list.json#/properties/news/items/properties/newsUrl
  - schema property: get-admin-news-news.json#/properties/newsUrl
  - schema property: post-admin-news-news-list.json#/properties/newsUrl
  - schema property: put-admin-news-news.json#/properties/newsUrl

### `next`

- usages:
  - schema property: get-products.json#/properties/pager/properties/next

### `notOutputColumns`

- usages:
  - schema property: get-admin-csv-config.json#/properties/notOutputColumns

### `note`

- usages:
  - parameter: POST /admin/product {note}
  - parameter: PUT /admin/product {note}
  - schema property: post-admin-product.param.json#/properties/note
  - schema property: put-admin-product.param.json#/properties/note
  - schema property: get-admin-product.json#/properties/note

### `offset`

- usages:
  - parameter: GET /admin/member-list {offset}
  - parameter: GET /admin/order-list {offset}
  - parameter: GET /admin/product-list {offset}
  - parameter: GET /mypage/order-history {offset}
  - parameter: GET /products {offset}
  - schema property: get-admin-member-list.param.json#/properties/offset
  - schema property: get-admin-order-list.param.json#/properties/offset
  - schema property: get-admin-product-list.param.json#/properties/offset
  - schema property: get-mypage-order-history.param.json#/properties/offset
  - schema property: get-products.param.json#/properties/offset
  - schema property: get-admin-member-list.json#/properties/filters/properties/offset
  - schema property: get-admin-order-list.json#/properties/offset
  - schema property: get-admin-product-list.json#/properties/filters/properties/offset
  - schema property: get-mypage-order-history.json#/properties/offset

### `operation`

- usages:
  - parameter: POST /admin/calendar {operation}
  - parameter: POST /cart/item {operation}
  - schema property: post-admin-calendar.param.json#/properties/operation
  - schema property: post-cart-item.param.json#/properties/operation

### `order`

- usages:
  - schema property: get-admin-order-edit.json#/properties/order

### `orderCount`

- usages:
  - schema property: get-admin-customer.json#/properties/orderCount
  - schema property: get-mypage-order-history.json#/properties/orderCount

### `orderDate` ☑︎

- title: 注文日
- def: https://schema.org/orderDate
- doc: 注文確定日時
- usages:
  - schema property: get-admin-csv-config.json#/properties/outputColumns/properties/orderDate
  - schema property: get-admin-customer.json#/properties/orders/items/properties/orderDate
  - schema property: get-admin-index.json#/properties/orders/items/properties/orderDate
  - schema property: get-admin-order-list.json#/properties/orders/items/properties/orderDate
  - schema property: get-admin-order.json#/properties/orderDate
  - schema property: get-mypage-history.json#/properties/orderDate
  - schema property: get-mypage-order-history.json#/properties/orders/items/properties/orderDate
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/orderDate
  - schema property: post-admin-order-create.json#/properties/orderDate
  - schema property: post-shopping-checkout.json#/properties/orderDate

### `orderItems`

- usages:
  - parameter: POST /admin/order/create {orderItems}
  - schema property: post-admin-order-create.param.json#/properties/orderItems

### `orderLimit`

- usages:
  - parameter: GET /mypage {orderLimit}
  - schema property: get-mypage.param.json#/properties/orderLimit

### `orderNo` ☑︎

- title: 注文番号
- def: https://schema.org/orderNumber
- doc: 顧客向けの注文番号。フォーマットはカスタマイズ可能
- usages:
  - parameter: GET /admin/order {orderNo}
  - parameter: PUT /admin/order {orderNo}
  - parameter: POST /admin/order-status {orderNo}
  - parameter: GET /admin/order/edit {orderNo}
  - parameter: GET /admin/order/export-order-pdf {orderNo}
  - parameter: GET /admin/order/mail-confirm {orderNo}
  - parameter: GET /admin/order/order-pdf {orderNo}
  - parameter: GET /admin/order/send-mail {orderNo}
  - parameter: POST /admin/order/send-mail {orderNo}
  - parameter: GET /admin/order/shipping-address {orderNo}
  - parameter: POST /admin/order/shipping-address {orderNo}
  - parameter: PUT /admin/order/shipping-address {orderNo}
  - parameter: GET /admin/order/shipping-notify-mail {orderNo}
  - parameter: POST /admin/order/shipping-notify-mail {orderNo}
  - parameter: PUT /admin/order/tracking-number {orderNo}
  - parameter: GET /mypage/history {orderNo}
  - parameter: POST /mypage/reorder {orderNo}
  - parameter: GET /shopping/complete {orderNo}
  - schema property: get-admin-order-edit.param.json#/properties/orderNo
  - schema property: get-admin-order-export-order-pdf.param.json#/properties/orderNo
  - schema property: get-admin-order-mail-confirm.param.json#/properties/orderNo
  - schema property: get-admin-order-order-pdf.param.json#/properties/orderNo
  - schema property: get-admin-order-send-mail.param.json#/properties/orderNo
  - schema property: get-admin-order-shipping-address.param.json#/properties/orderNo
  - schema property: get-admin-order-shipping-notify-mail.param.json#/properties/orderNo
  - schema property: get-admin-order.param.json#/properties/orderNo
  - schema property: get-mypage-history.param.json#/properties/orderNo
  - schema property: get-shopping-complete.param.json#/properties/orderNo
  - schema property: post-admin-order-send-mail.param.json#/properties/orderNo
  - schema property: post-admin-order-shipping-address.param.json#/properties/orderNo
  - schema property: post-admin-order-shipping-notify-mail.param.json#/properties/orderNo
  - schema property: post-admin-order-status.param.json#/properties/orderNo
  - schema property: post-mypage-reorder.param.json#/properties/orderNo
  - schema property: put-admin-order-shipping-address.param.json#/properties/orderNo
  - schema property: put-admin-order-tracking-number.param.json#/properties/orderNo
  - schema property: put-admin-order.param.json#/properties/orderNo
  - schema property: get-admin-csv-config.json#/properties/outputColumns/properties/orderNo
  - schema property: get-admin-customer.json#/properties/orders/items/properties/orderNo
  - schema property: get-admin-index.json#/properties/orders/items/properties/orderNo
  - schema property: get-admin-order-edit.json#/properties/orderNo
  - schema property: get-admin-order-export-order-pdf.json#/properties/orderNo
  - schema property: get-admin-order-list.json#/properties/orders/items/properties/orderNo
  - schema property: get-admin-order-mail-confirm.json#/properties/orderNo
  - schema property: get-admin-order-order-pdf.json#/properties/orderNo
  - schema property: get-admin-order-send-mail.json#/properties/orderNo
  - schema property: get-admin-order-shipping-address.json#/properties/orderNo
  - schema property: get-admin-order-shipping-notify-mail.json#/properties/orderNo
  - schema property: get-admin-order.json#/properties/orderNo
  - schema property: get-mypage-history.json#/properties/orderNo
  - schema property: get-mypage-order-history.json#/properties/orders/items/properties/orderNo
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/orderNo
  - schema property: get-shopping-complete.json#/properties/orderNo
  - schema property: post-admin-order-create.json#/properties/orderNo
  - schema property: post-admin-order-send-mail.json#/properties/orderNo
  - schema property: post-admin-order-shipping-notify-mail.json#/properties/orderNo
  - schema property: post-admin-order-status.json#/properties/orderNo
  - schema property: post-mypage-reorder.json#/properties/orderNo
  - schema property: post-shopping-checkout.json#/properties/orderNo
  - schema property: put-admin-order-shipping-address.json#/properties/orderNo
  - schema property: put-admin-order-tracking-number.json#/properties/orderNo
  - schema property: put-admin-order.json#/properties/orderNo

### `orderNos`

- usages:
  - parameter: POST /admin/order/bulk-delete {orderNos}
  - parameter: GET /admin/order/export-order-pdf {orderNos}
  - schema property: get-admin-order-export-order-pdf.param.json#/properties/orderNos
  - schema property: post-admin-order-bulk-delete.param.json#/properties/orderNos
  - schema property: get-admin-order-export-order-pdf.json#/properties/orderNos
  - schema property: post-admin-order-bulk-delete.json#/properties/orderNos

### `orderStatus` ☑︎

- title: 受注ステータス
- doc: 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外
- usages:
  - parameter: POST /admin/order-status {orderStatus}
  - schema property: post-admin-order-status.param.json#/properties/orderStatus
  - schema property: get-admin-customer.json#/properties/orders/items/properties/orderStatus
  - schema property: get-admin-index.json#/properties/orders/items/properties/orderStatus
  - schema property: get-admin-order-list.json#/properties/orders/items/properties/orderStatus
  - schema property: get-admin-order.json#/properties/orderStatus
  - schema property: get-mypage-history.json#/properties/orderStatus
  - schema property: get-mypage-order-history.json#/properties/orders/items/properties/orderStatus
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/orderStatus
  - schema property: post-admin-order-create.json#/properties/orderStatus
  - schema property: post-admin-order-status.json#/properties/orderStatus
  - schema property: post-shopping-checkout.json#/properties/orderStatus
  - schema property: put-admin-order.json#/properties/orderStatus

### `orderStatusOptions`

- usages:
  - schema property: get-admin-order.json#/properties/orderStatusOptions

### `orderStatusRows`

- usages:
  - parameter: PUT /admin/order-status {orderStatusRows}
  - schema property: put-admin-order-status.param.json#/properties/orderStatusRows
  - schema property: put-admin-order-status.json#/properties/orderStatusRows

### `orderStatuses`

- usages:
  - parameter: PUT /admin/order-status {orderStatuses}
  - schema property: put-admin-order-status.param.json#/properties/orderStatuses
  - schema property: get-admin-index.json#/properties/orderStatuses
  - schema property: get-admin-order-status.json#/properties/orderStatuses

### `orderby`

- usages:
  - parameter: GET /products {orderby}
  - schema property: get-products.param.json#/properties/orderby
  - schema property: get-products.json#/properties/filters/properties/orderby

### `orders`

- usages:
  - schema property: get-admin-customer.json#/properties/orders
  - schema property: get-admin-index.json#/properties/orders
  - schema property: get-admin-order-list.json#/properties/orders
  - schema property: get-mypage-order-history.json#/properties/orders

### `originalEmail`

- usages:
  - schema property: post-admin-delete-customer.json#/properties/originalEmail

### `outputColumns`

- usages:
  - schema property: get-admin-csv-config.json#/properties/outputColumns

### `page`

- usages:
  - schema property: get-contact-complete.json#/properties/staticContent/properties/page
  - schema property: get-contact-confirm.json#/properties/staticContent/properties/page
  - schema property: get-entry-activate.json#/properties/staticContent/properties/page
  - schema property: get-entry-complete.json#/properties/staticContent/properties/page
  - schema property: get-entry-confirm.json#/properties/staticContent/properties/page
  - schema property: get-help-about.json#/properties/staticContent/properties/page
  - schema property: get-help-agreement.json#/properties/staticContent/properties/page
  - schema property: get-help-guide.json#/properties/staticContent/properties/page
  - schema property: get-help-privacy.json#/properties/staticContent/properties/page
  - schema property: get-help-trade-law.json#/properties/staticContent/properties/page
  - schema property: get-mypage-change-complete.json#/properties/staticContent/properties/page
  - schema property: get-mypage-withdraw-complete.json#/properties/staticContent/properties/page
  - schema property: get-shopping-complete.json#/properties/staticContent/properties/page
  - schema property: get-shopping-error.json#/properties/staticContent/properties/page

### `pageCount`

- usages:
  - schema property: get-products.json#/properties/pager/properties/pageCount

### `pageEditType` ☑︎

- title: ページ編集区分
- doc: ページ編集レベル。0=EDIT_TYPE_USER（ユーザー作成、完全編集/削除可）, 1=EDIT_TYPE_PREVIEW（プレビュー）, 2=EDIT_TYPE_DEFAULT（システムページ、構造ロック・削除不可）, 3=EDIT_TYPE_DEFAULT_CONFIRM（内容編集可能なシステムページ、利用規約等）。editType>=2は削除不可
- usages:
  - schema property: get-admin-page-page-list.json#/properties/pages/items/properties/pageEditType
  - schema property: get-admin-page-page.json#/properties/pageEditType
  - schema property: post-admin-page-page-list.json#/properties/pageEditType
  - schema property: put-admin-page-page.json#/properties/pageEditType

### `pageFileName` ☑︎

- title: テンプレートファイル名
- doc: ページのテンプレートファイル名
- usages:
  - parameter: PUT /admin/page/page {pageFileName}
  - parameter: POST /admin/page/page-list {pageFileName}
  - schema property: post-admin-page-page-list.param.json#/properties/pageFileName
  - schema property: put-admin-page-page.param.json#/properties/pageFileName
  - schema property: get-admin-page-page-list.json#/properties/pages/items/properties/pageFileName
  - schema property: get-admin-page-page.json#/properties/pageFileName
  - schema property: post-admin-page-page-list.json#/properties/pageFileName
  - schema property: put-admin-page-page.json#/properties/pageFileName

### `pageId` ☑︎

- title: ページID
- doc: dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: GET /admin/page/page {pageId}
  - parameter: PUT /admin/page/page {pageId}
  - parameter: DELETE /admin/page/page {pageId}
  - schema property: delete-admin-page-page.param.json#/properties/pageId
  - schema property: get-admin-page-page.param.json#/properties/pageId
  - schema property: put-admin-page-page.param.json#/properties/pageId
  - schema property: delete-admin-page-page.json#/properties/pageId
  - schema property: get-admin-page-page-list.json#/properties/pages/items/properties/pageId
  - schema property: get-admin-page-page.json#/properties/pageId
  - schema property: post-admin-page-page-list.json#/properties/pageId
  - schema property: put-admin-page-page.json#/properties/pageId

### `pageName` ☑︎

- title: ページ名
- doc: 管理画面でのページ表示名
- usages:
  - parameter: PUT /admin/page/page {pageName}
  - parameter: POST /admin/page/page-list {pageName}
  - schema property: post-admin-page-page-list.param.json#/properties/pageName
  - schema property: put-admin-page-page.param.json#/properties/pageName
  - schema property: get-admin-page-page-list.json#/properties/pages/items/properties/pageName
  - schema property: get-admin-page-page.json#/properties/pageName
  - schema property: post-admin-page-page-list.json#/properties/pageName
  - schema property: put-admin-page-page.json#/properties/pageName

### `pageUrl` ☑︎

- title: ページURL
- doc: ページのURLパス（Symfonyルート名。例: homepage, product_list）
- usages:
  - parameter: PUT /admin/page/page {pageUrl}
  - parameter: POST /admin/page/page-list {pageUrl}
  - schema property: post-admin-page-page-list.param.json#/properties/pageUrl
  - schema property: put-admin-page-page.param.json#/properties/pageUrl
  - schema property: get-admin-page-page-list.json#/properties/pages/items/properties/pageUrl
  - schema property: get-admin-page-page.json#/properties/pageUrl
  - schema property: post-admin-page-page-list.json#/properties/pageUrl
  - schema property: put-admin-page-page.json#/properties/pageUrl

### `pageno`

- usages:
  - parameter: GET /products {pageno}
  - schema property: get-products.param.json#/properties/pageno
  - schema property: get-products.json#/properties/filters/properties/pageno

### `pager`

- usages:
  - schema property: get-products.json#/properties/pager

### `pages`

- usages:
  - schema property: get-admin-page-page-list.json#/properties/pages
  - schema property: get-products.json#/properties/pager/properties/pages

### `parentId`

- usages:
  - parameter: PUT /admin/category/category {parentId}
  - parameter: POST /admin/category/category-list {parentId}
  - schema property: post-admin-category-category-list.param.json#/properties/parentId
  - schema property: put-admin-category-category.param.json#/properties/parentId
  - schema property: get-admin-category-category-list.json#/properties/categories/items/properties/parentId
  - schema property: get-admin-category-category.json#/properties/parentId
  - schema property: get-admin-category-edit.json#/properties/categories/items/properties/parentId
  - schema property: post-admin-category-category-list.json#/properties/parentId
  - schema property: put-admin-category-category.json#/properties/parentId

### `password` ☑︎

- title: パスワード
- doc: 書き込み専用（ハッシュ化して保存）
- usages:
  - parameter: POST /admin/create-customer {password}
  - parameter: POST /admin/login {password}
  - parameter: POST /admin/member {password}
  - parameter: POST /entry {password}
  - parameter: POST /login {password}
  - parameter: POST /reset {password}
  - schema property: post-admin-create-customer.param.json#/properties/password
  - schema property: post-admin-login.param.json#/properties/password
  - schema property: post-admin-member.param.json#/properties/password
  - schema property: post-entry.param.json#/properties/password
  - schema property: post-login.param.json#/properties/password
  - schema property: post-reset.param.json#/properties/password

### `passwordConfirm`

- usages:
  - parameter: POST /admin/member {passwordConfirm}
  - schema property: post-admin-member.param.json#/properties/passwordConfirm

### `password_confirm`

- usages:
  - parameter: POST /entry {password_confirm}
  - schema property: post-entry.param.json#/properties/password_confirm

### `payment`

- usages:
  - parameter: POST /shopping/confirm {payment}
  - schema property: post-shopping-confirm.param.json#/properties/payment
  - schema property: get-admin-payment-payment.json#/properties/payment

### `paymentDate` ☑︎

- title: 入金日
- doc: 入金確認日時。入金済みステータスへの変更時に記録
- usages:
  - schema property: get-admin-order.json#/properties/paymentDate
  - schema property: get-mypage-history.json#/properties/paymentDate
  - schema property: get-mypage-order-history.json#/properties/orders/items/properties/paymentDate
  - schema property: post-shopping-checkout.json#/properties/paymentDate

### `paymentId` ☑︎

- title: 支払方法ID
- doc: dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/payment/payment {paymentId}
  - parameter: PUT /admin/payment/payment {paymentId}
  - parameter: DELETE /admin/payment/payment {paymentId}
  - schema property: delete-admin-payment-payment.param.json#/properties/paymentId
  - schema property: get-admin-payment-payment.param.json#/properties/paymentId
  - schema property: put-admin-payment-payment.param.json#/properties/paymentId
  - schema property: delete-admin-payment-payment.json#/properties/paymentId
  - schema property: get-admin-payment-payment-list.json#/properties/payments/items/properties/paymentId
  - schema property: get-admin-payment-payment.json#/properties/paymentId
  - schema property: post-admin-payment-payment-list.json#/properties/paymentId
  - schema property: put-admin-payment-payment.json#/properties/paymentId

### `paymentMethod` ☑︎

- title: 支払方法
- def: https://schema.org/paymentMethod
- doc: 注文時点の支払方法名称（スナップショット）
- usages:
  - schema property: get-admin-csv-config.json#/properties/notOutputColumns/properties/paymentMethod
  - schema property: get-mypage-history.json#/properties/paymentMethod

### `paymentMethodId`

- usages:
  - parameter: POST /admin/order/create {paymentMethodId}
  - parameter: GET /shopping/confirm {paymentMethodId}
  - schema property: get-shopping-confirm.param.json#/properties/paymentMethodId
  - schema property: post-admin-order-create.param.json#/properties/paymentMethodId
  - schema property: get-admin-order.json#/properties/paymentMethodId
  - schema property: get-shopping-confirm.json#/properties/paymentMethodId
  - schema property: get-shopping.json#/properties/paymentMethods/items/properties/paymentMethodId
  - schema property: post-admin-order-create.json#/properties/paymentMethodId
  - schema property: post-shopping-non-member.json#/properties/paymentMethodId

### `paymentMethodName` ☑︎

- title: 支払方法名
- doc: 支払方法の表示名
- usages:
  - parameter: PUT /admin/payment/payment {paymentMethodName}
  - parameter: POST /admin/payment/payment-list {paymentMethodName}
  - schema property: post-admin-payment-payment-list.param.json#/properties/paymentMethodName
  - schema property: put-admin-payment-payment.param.json#/properties/paymentMethodName
  - schema property: get-admin-payment-payment-list.json#/properties/payments/items/properties/paymentMethodName
  - schema property: get-shopping-confirm.json#/properties/paymentMethodName
  - schema property: get-shopping.json#/properties/paymentMethods/items/properties/paymentMethodName
  - schema property: post-admin-payment-payment-list.json#/properties/paymentMethodName
  - schema property: put-admin-payment-payment.json#/properties/paymentMethodName

### `paymentMethods`

- usages:
  - schema property: get-shopping.json#/properties/paymentMethods

### `paymentTotal` ☑︎

- title: 支払合計
- doc: 実際の支払金額。初期値はtotalと同値で、PointProcessorがポイント値引きのOrderItem（type=POINT_DISCOUNT、不課税）を追加後にPurchaseFlow.calculateTotal()で再計算される。計算式: total - (利用ポイント x pointConversionRate)
- usages:
  - schema property: get-admin-csv-config.json#/properties/outputColumns/properties/paymentTotal
  - schema property: get-admin-customer.json#/properties/orders/items/properties/paymentTotal
  - schema property: get-admin-index.json#/properties/orders/items/properties/paymentTotal
  - schema property: get-admin-order-list.json#/properties/orders/items/properties/paymentTotal
  - schema property: get-admin-order.json#/properties/paymentTotal
  - schema property: get-mypage-history.json#/properties/paymentTotal
  - schema property: get-mypage-order-history.json#/properties/orders/items/properties/paymentTotal
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/paymentTotal
  - schema property: get-shopping-confirm.json#/properties/paymentTotal
  - schema property: post-admin-order-create.json#/properties/paymentTotal
  - schema property: post-shopping-checkout.json#/properties/paymentTotal
  - schema property: put-admin-order.json#/properties/paymentTotal

### `payments`

- usages:
  - schema property: get-admin-payment-payment-list.json#/properties/payments

### `pdf`

- usages:
  - schema property: get-admin-order-export-order-pdf.json#/properties/pdf

### `phoneNumber` ☑︎

- title: 電話番号
- def: https://schema.org/telephone
- doc: 日本の電話番号形式（ハイフン区切り）
- usages:
  - parameter: POST /admin/base-info {phoneNumber}
  - parameter: POST /admin/create-customer {phoneNumber}
  - parameter: PUT /admin/order/shipping-address {phoneNumber}
  - parameter: POST /entry {phoneNumber}
  - parameter: PUT /mypage/address {phoneNumber}
  - parameter: POST /mypage/address-list {phoneNumber}
  - parameter: POST /mypage/change {phoneNumber}
  - parameter: POST /shopping/non-member {phoneNumber}
  - parameter: POST /shopping/shipping-edit {phoneNumber}
  - parameter: POST /shopping/shipping-multiple-edit {phoneNumber}
  - schema property: post-admin-base-info.param.json#/properties/phoneNumber
  - schema property: post-admin-create-customer.param.json#/properties/phoneNumber
  - schema property: post-entry.param.json#/properties/phoneNumber
  - schema property: post-mypage-address-list.param.json#/properties/phoneNumber
  - schema property: post-mypage-change.param.json#/properties/phoneNumber
  - schema property: post-shopping-non-member.param.json#/properties/phoneNumber
  - schema property: post-shopping-shipping-edit.param.json#/properties/phoneNumber
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/phoneNumber
  - schema property: put-admin-order-shipping-address.param.json#/properties/phoneNumber
  - schema property: put-mypage-address.param.json#/properties/phoneNumber
  - schema property: get-admin-base-info.json#/properties/phoneNumber
  - schema property: get-admin-customer.json#/properties/phoneNumber
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/phoneNumber
  - schema property: get-mypage-change.json#/properties/phoneNumber
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/phoneNumber
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/phoneNumber
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/phoneNumber
  - schema property: get-shopping.json#/properties/defaultShippingAddress/properties/phoneNumber
  - schema property: post-admin-base-info.json#/properties/phoneNumber
  - schema property: post-mypage-address-list.json#/properties/phoneNumber
  - schema property: post-shopping-shipping-edit.json#/properties/phoneNumber
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/phoneNumber
  - schema property: put-admin-order-shipping-address.json#/properties/phoneNumber
  - schema property: put-mypage-address.json#/properties/phoneNumber

### `phpinfoEnabled`

- usages:
  - schema property: get-admin-system.json#/properties/phpinfoEnabled

### `pluginCode` ☑︎

- title: プラグインコード
- doc: プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする
- usages:
  - parameter: DELETE /admin/plugin {pluginCode}
  - parameter: POST /admin/plugin-disable {pluginCode}
  - parameter: POST /admin/plugin-enable {pluginCode}
  - parameter: POST /admin/plugin-list {pluginCode}
  - schema property: delete-admin-plugin.param.json#/properties/pluginCode
  - schema property: post-admin-plugin-disable.param.json#/properties/pluginCode
  - schema property: post-admin-plugin-enable.param.json#/properties/pluginCode
  - schema property: post-admin-plugin-list.param.json#/properties/pluginCode
  - schema property: delete-admin-plugin.json#/properties/pluginCode
  - schema property: get-admin-index.json#/properties/recommendedPlugins/items/properties/pluginCode
  - schema property: get-admin-plugin-list.json#/properties/plugins/items/properties/pluginCode
  - schema property: post-admin-plugin-disable.json#/properties/pluginCode
  - schema property: post-admin-plugin-enable.json#/properties/pluginCode
  - schema property: post-admin-plugin-list.json#/properties/pluginCode

### `pluginName` ☑︎

- title: プラグイン名
- doc: プラグインの表示名。dtb_plugin.name に格納。PluginEntity の pluginName に対応
- usages:
  - parameter: POST /admin/plugin-list {pluginName}
  - schema property: post-admin-plugin-list.param.json#/properties/pluginName
  - schema property: get-admin-plugin-list.json#/properties/plugins/items/properties/pluginName
  - schema property: post-admin-plugin-list.json#/properties/pluginName

### `pluginVersion` ☑︎

- title: プラグインバージョン
- def: https://schema.org/softwareVersion
- doc: プラグインのバージョン文字列。dtb_plugin.version に格納。PluginEntity の version に対応
- usages:
  - parameter: POST /admin/plugin-list {pluginVersion}
  - schema property: post-admin-plugin-list.param.json#/properties/pluginVersion
  - schema property: get-admin-plugin-list.json#/properties/plugins/items/properties/pluginVersion
  - schema property: post-admin-plugin-list.json#/properties/pluginVersion

### `plugins`

- usages:
  - schema property: get-admin-plugin-list.json#/properties/plugins

### `postalCode` ☑︎

- title: 郵便番号
- def: https://schema.org/postalCode
- doc: 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁
- usages:
  - parameter: POST /admin/base-info {postalCode}
  - parameter: POST /admin/create-customer {postalCode}
  - parameter: PUT /admin/order/shipping-address {postalCode}
  - parameter: POST /entry {postalCode}
  - parameter: PUT /mypage/address {postalCode}
  - parameter: POST /mypage/address-list {postalCode}
  - parameter: POST /mypage/change {postalCode}
  - parameter: POST /shopping/non-member {postalCode}
  - parameter: POST /shopping/shipping-edit {postalCode}
  - parameter: POST /shopping/shipping-multiple-edit {postalCode}
  - schema property: post-admin-base-info.param.json#/properties/postalCode
  - schema property: post-admin-create-customer.param.json#/properties/postalCode
  - schema property: post-entry.param.json#/properties/postalCode
  - schema property: post-mypage-address-list.param.json#/properties/postalCode
  - schema property: post-mypage-change.param.json#/properties/postalCode
  - schema property: post-shopping-non-member.param.json#/properties/postalCode
  - schema property: post-shopping-shipping-edit.param.json#/properties/postalCode
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/postalCode
  - schema property: put-admin-order-shipping-address.param.json#/properties/postalCode
  - schema property: put-mypage-address.param.json#/properties/postalCode
  - schema property: get-admin-base-info.json#/properties/postalCode
  - schema property: get-admin-customer-list.json#/properties/customers/items/properties/postalCode
  - schema property: get-admin-customer.json#/properties/postalCode
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/postalCode
  - schema property: get-mypage-change.json#/properties/postalCode
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/postalCode
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/postalCode
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/postalCode
  - schema property: get-shopping.json#/properties/defaultShippingAddress/properties/postalCode
  - schema property: post-admin-base-info.json#/properties/postalCode
  - schema property: post-mypage-address-list.json#/properties/postalCode
  - schema property: post-shopping-shipping-edit.json#/properties/postalCode
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/postalCode
  - schema property: put-admin-order-shipping-address.json#/properties/postalCode
  - schema property: put-mypage-address.json#/properties/postalCode

### `preOrderId` ☑︎

- title: 仮注文ID
- doc: 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去
- usages:
  - parameter: POST /shopping/checkout {preOrderId}
  - parameter: GET /shopping/confirm {preOrderId}
  - parameter: POST /shopping/confirm {preOrderId}
  - schema property: get-shopping-confirm.param.json#/properties/preOrderId
  - schema property: post-shopping-checkout.param.json#/properties/preOrderId
  - schema property: post-shopping-confirm.param.json#/properties/preOrderId
  - schema property: get-admin-order.json#/properties/preOrderId
  - schema property: get-shopping-confirm.json#/properties/preOrderId
  - schema property: get-shopping.json#/properties/carts/items/properties/preOrderId
  - schema property: get-shopping.json#/properties/preOrderId
  - schema property: post-shopping-non-member.json#/properties/preOrderId

### `pref` ☑︎

- title: 都道府県
- doc: 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用
- usages:
  - parameter: POST /admin/base-info {pref}
  - parameter: POST /admin/create-customer {pref}
  - parameter: PUT /admin/order/shipping-address {pref}
  - parameter: POST /entry {pref}
  - parameter: PUT /mypage/address {pref}
  - parameter: POST /mypage/address-list {pref}
  - parameter: POST /mypage/change {pref}
  - parameter: POST /shopping/non-member {pref}
  - parameter: POST /shopping/shipping-edit {pref}
  - parameter: POST /shopping/shipping-multiple-edit {pref}
  - schema property: post-admin-base-info.param.json#/properties/pref
  - schema property: post-admin-create-customer.param.json#/properties/pref
  - schema property: post-entry.param.json#/properties/pref
  - schema property: post-mypage-address-list.param.json#/properties/pref
  - schema property: post-mypage-change.param.json#/properties/pref
  - schema property: post-shopping-non-member.param.json#/properties/pref
  - schema property: post-shopping-shipping-edit.param.json#/properties/pref
  - schema property: post-shopping-shipping-multiple-edit.param.json#/properties/pref
  - schema property: put-admin-order-shipping-address.param.json#/properties/pref
  - schema property: put-mypage-address.param.json#/properties/pref
  - schema property: get-admin-base-info.json#/properties/pref
  - schema property: get-admin-customer.json#/properties/pref
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/pref
  - schema property: get-mypage-change.json#/properties/pref
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/pref
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/pref
  - schema property: get-shopping.json#/properties/defaultShippingAddress/properties/pref
  - schema property: post-admin-base-info.json#/properties/pref
  - schema property: post-mypage-address-list.json#/properties/pref
  - schema property: post-shopping-shipping-edit.json#/properties/pref
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/pref
  - schema property: put-admin-order-shipping-address.json#/properties/pref
  - schema property: put-mypage-address.json#/properties/pref

### `prefName` ☑︎

- title: 都道府県名
- doc: 都道府県の表示名（mtb_pref.name）。pref（mtb_pref への整数 FK）を住所行に描画するための表示用投影フィールドであり、storage の真の leaf 列ではない。Phase 3 enrichment で追加。SqlAddressStorage が dtb_customer_address.pref_id → mtb_pref を JOIN して充填する（構造のみダンプでは mtb_pref は空のため、未シード時は空文字に degrade）。配送先住所一覧画面（CustomerAddress）が pref の整数 id ではなく都道府県名を表示するために使用
- usages:
  - schema property: get-mypage-address-list.json#/properties/addresses/items/properties/prefName
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/prefName

### `previous`

- usages:
  - schema property: get-products.json#/properties/pager/properties/previous

### `previousAuthority`

- usages:
  - schema property: post-admin-authority-role.json#/properties/previousAuthority

### `previousStatus`

- usages:
  - schema property: post-admin-order-status.json#/properties/previousStatus

### `price`

- usages:
  - schema property: post-admin-order-create.param.json#/properties/orderItems/items/properties/price
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/price
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/price
  - schema property: get-shopping.json#/properties/carts/items/properties/items/items/properties/price

### `price02` ☑︎

- title: 販売価格
- def: https://schema.org/price
- doc: 実際の販売価格（税抜）。税計算・小計計算のベース
- usages:
  - parameter: POST /admin/product {price02}
  - parameter: PUT /admin/product {price02}
  - schema property: post-admin-product.param.json#/properties/price02
  - schema property: put-admin-product.param.json#/properties/price02
  - schema property: get-admin-customer.json#/properties/favorites/items/properties/price02
  - schema property: get-admin-product-list.json#/properties/products/items/properties/price02
  - schema property: get-admin-product-product-class.json#/properties/classes/items/properties/price02
  - schema property: get-admin-product.json#/properties/price02
  - schema property: get-mypage-favorite-list.json#/properties/favorites/items/properties/price02
  - schema property: get-product.json#/properties/price02
  - schema property: get-products.json#/properties/products/items/properties/price02
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/price02
  - schema property: post-admin-product-copy.json#/properties/price02
  - schema property: post-admin-product.json#/properties/price02
  - schema property: put-admin-product.json#/properties/price02

### `product`

- usages:
  - schema property: get-admin-product-edit.json#/properties/product

### `productClassId` ☑︎

- title: 商品規格ID
- doc: dtb_product_class.id — 特定 SKU（規格バリエーション行）の代理キー。EC-CUBE のカート画面では削除/数量増減リンク（cart_handle_item の productClassId パラメータ）のキーとして使われる。CartItem 1件は default class ではなく購入された具体的な product_class を指す
- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/productClassId
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/productClassId

### `productCode` ☑︎

- title: 商品コード
- def: https://schema.org/sku
- doc: SKU/品番。在庫管理や受注明細での識別に使用
- usages:
  - parameter: GET /admin/product {productCode}
  - parameter: POST /admin/product {productCode}
  - parameter: PUT /admin/product {productCode}
  - parameter: DELETE /admin/product {productCode}
  - parameter: POST /admin/product-copy {productCode}
  - parameter: GET /admin/product/edit {productCode}
  - parameter: GET /admin/product/product-class {productCode}
  - parameter: POST /cart/item {productCode}
  - parameter: PUT /cart/item {productCode}
  - parameter: DELETE /cart/item {productCode}
  - parameter: POST /mypage/favorite {productCode}
  - parameter: DELETE /mypage/favorite {productCode}
  - parameter: GET /product {productCode}
  - schema property: delete-admin-product.param.json#/properties/productCode
  - schema property: delete-cart-item.param.json#/properties/productCode
  - schema property: delete-mypage-favorite.param.json#/properties/productCode
  - schema property: get-admin-product-edit.param.json#/properties/productCode
  - schema property: get-admin-product-product-class.param.json#/properties/productCode
  - schema property: get-admin-product.param.json#/properties/productCode
  - schema property: get-product.param.json#/properties/productCode
  - schema property: post-admin-order-create.param.json#/properties/orderItems/items/properties/productCode
  - schema property: post-admin-product-copy.param.json#/properties/productCode
  - schema property: post-admin-product.param.json#/properties/productCode
  - schema property: post-cart-item.param.json#/properties/productCode
  - schema property: post-mypage-favorite.param.json#/properties/productCode
  - schema property: post-shopping-shipping-multiple.param.json#/properties/allocations/items/properties/productCode
  - schema property: put-admin-product.param.json#/properties/productCode
  - schema property: put-cart-item.param.json#/properties/productCode
  - schema property: delete-admin-product.json#/properties/productCode
  - schema property: delete-cart-item.json#/properties/productCode
  - schema property: delete-mypage-favorite.json#/properties/productCode
  - schema property: get-admin-customer.json#/properties/favorites/items/properties/productCode
  - schema property: get-admin-order-edit.json#/properties/items/items/properties/productCode
  - schema property: get-admin-order.json#/properties/items/items/properties/productCode
  - schema property: get-admin-product-edit.json#/properties/productCode
  - schema property: get-admin-product-list.json#/properties/products/items/properties/productCode
  - schema property: get-admin-product-product-class.json#/properties/classes/items/properties/productCode
  - schema property: get-admin-product-product-class.json#/properties/productCode
  - schema property: get-admin-product.json#/properties/productCode
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/productCode
  - schema property: get-mypage-favorite-list.json#/properties/favorites/items/properties/productCode
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/items/items/properties/productCode
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/items/items/properties/productCode
  - schema property: get-product.json#/properties/productCode
  - schema property: get-products.json#/properties/products/items/properties/productCode
  - schema property: get-shopping-confirm.json#/properties/items/items/properties/productCode
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/productCode
  - schema property: get-shopping.json#/properties/carts/items/properties/items/items/properties/productCode
  - schema property: post-admin-product-copy.json#/properties/productCode
  - schema property: post-admin-product.json#/properties/productCode
  - schema property: post-cart-item.json#/properties/productCode
  - schema property: post-mypage-favorite.json#/properties/productCode
  - schema property: put-admin-product.json#/properties/productCode
  - schema property: put-cart-item.json#/properties/productCode

### `productCodes`

- usages:
  - parameter: POST /admin/product-bulk-status {productCodes}
  - schema property: post-admin-product-bulk-status.param.json#/properties/productCodes
  - schema property: post-admin-product-bulk-status.json#/properties/productCodes
  - schema property: post-admin-product-csv.json#/properties/productCodes

### `productId` ☑︎

- title: 商品ID
- doc: dtb_product.id — 商品ヘッダの代理キー。商品詳細リンク（EC-CUBE の url('product_detail', {id})）のターゲットとして画面に露出する。自然キー productCode（dtb_product_class 側）とは別物で、こちらは商品ヘッダ単位の識別子
- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/productId
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/productId

### `productName` ☑︎

- title: 商品名
- def: https://schema.org/name
- doc: 商品の表示名
- usages:
  - parameter: POST /admin/product {productName}
  - parameter: PUT /admin/product {productName}
  - schema property: post-admin-order-create.param.json#/properties/orderItems/items/properties/productName
  - schema property: post-admin-product.param.json#/properties/productName
  - schema property: put-admin-product.param.json#/properties/productName
  - schema property: delete-admin-product.json#/properties/productName
  - schema property: get-admin-customer.json#/properties/favorites/items/properties/productName
  - schema property: get-admin-order-edit.json#/properties/items/items/properties/productName
  - schema property: get-admin-order.json#/properties/items/items/properties/productName
  - schema property: get-admin-product-list.json#/properties/products/items/properties/productName
  - schema property: get-admin-product.json#/properties/productName
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/productName
  - schema property: get-mypage-favorite-list.json#/properties/favorites/items/properties/productName
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/items/items/properties/productName
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/items/items/properties/productName
  - schema property: get-product.json#/properties/productName
  - schema property: get-products.json#/properties/products/items/properties/productName
  - schema property: get-shopping-confirm.json#/properties/items/items/properties/productName
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/productName
  - schema property: post-admin-product.json#/properties/productName
  - schema property: post-mypage-favorite.json#/properties/productName
  - schema property: put-admin-product.json#/properties/productName

### `productStatus` ☑︎

- title: 商品ステータス
- doc: 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示）
- usages:
  - parameter: POST /admin/product {productStatus}
  - parameter: PUT /admin/product {productStatus}
  - parameter: POST /admin/product-bulk-status {productStatus}
  - schema property: post-admin-product-bulk-status.param.json#/properties/productStatus
  - schema property: post-admin-product.param.json#/properties/productStatus
  - schema property: put-admin-product.param.json#/properties/productStatus
  - schema property: get-admin-product-list.json#/properties/products/items/properties/productStatus
  - schema property: get-admin-product.json#/properties/productStatus
  - schema property: post-admin-product-bulk-status.json#/properties/productStatus
  - schema property: post-admin-product.json#/properties/productStatus
  - schema property: put-admin-product.json#/properties/productStatus

### `productStatusOptions`

- usages:
  - schema property: get-admin-product-new.json#/properties/productStatusOptions
  - schema property: get-admin-product.json#/properties/productStatusOptions

### `products`

- usages:
  - schema property: get-admin-product-list.json#/properties/products
  - schema property: get-products.json#/properties/products

### `publishDate` ☑︎

- title: 公開日
- def: https://schema.org/datePublished
- doc: ニュースの公開日時。フロントの表示順を制御
- usages:
  - parameter: PUT /admin/news/news {publishDate}
  - parameter: POST /admin/news/news-list {publishDate}
  - schema property: post-admin-news-news-list.param.json#/properties/publishDate
  - schema property: put-admin-news-news.param.json#/properties/publishDate
  - schema property: get-admin-news-news-list.json#/properties/news/items/properties/publishDate
  - schema property: get-admin-news-news.json#/properties/publishDate
  - schema property: post-admin-news-news-list.json#/properties/publishDate
  - schema property: put-admin-news-news.json#/properties/publishDate

### `quantity` ☑︎

- title: 数量
- doc: 購入数量。カート明細と受注明細で共通使用
- usages:
  - parameter: POST /cart/item {quantity}
  - parameter: PUT /cart/item {quantity}
  - schema property: post-admin-order-create.param.json#/properties/orderItems/items/properties/quantity
  - schema property: post-cart-item.param.json#/properties/quantity
  - schema property: post-shopping-shipping-multiple.param.json#/properties/allocations/items/properties/quantity
  - schema property: put-cart-item.param.json#/properties/quantity
  - schema property: get-admin-order-edit.json#/properties/items/items/properties/quantity
  - schema property: get-admin-order.json#/properties/items/items/properties/quantity
  - schema property: get-cart.json#/properties/carts/items/properties/items/items/properties/quantity
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/items/items/properties/quantity
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/items/items/properties/quantity
  - schema property: get-shopping-confirm.json#/properties/items/items/properties/quantity
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/quantity
  - schema property: get-shopping.json#/properties/carts/items/properties/items/items/properties/quantity

### `recentOrderCount`

- usages:
  - schema property: get-mypage.json#/properties/recentOrderCount

### `recentOrders`

- usages:
  - schema property: get-mypage.json#/properties/recentOrders

### `recommendedPlugins`

- usages:
  - schema property: get-admin-index.json#/properties/recommendedPlugins

### `redirect_to`

- usages:
  - schema property: post-shopping-confirm.param.json#/properties/redirect_to

### `rel`

- usages:
  - schema property: delete-admin-block-block.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-calendar.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-category-category.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-class-category-class-category.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-class-name-class-name.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-delivery-delivery.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-mail-template.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-member.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-news-news.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-page-page.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-payment-payment.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-plugin.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-product.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-tag-tag.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-tax-rule-tax-rule.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-template-template-list.param.json#/$defs/link/properties/rel
  - schema property: delete-cart-item.param.json#/$defs/link/properties/rel
  - schema property: delete-mypage-address.param.json#/$defs/link/properties/rel
  - schema property: delete-mypage-favorite.param.json#/$defs/link/properties/rel
  - schema property: get-action-redirect.param.json#/$defs/link/properties/rel
  - schema property: get-admin-action-redirect.param.json#/$defs/link/properties/rel
  - schema property: get-admin-category-category.param.json#/$defs/link/properties/rel
  - schema property: get-admin-category-edit.param.json#/$defs/link/properties/rel
  - schema property: get-admin-class-category-class-category-export.param.json#/$defs/link/properties/rel
  - schema property: get-admin-class-category-class-category-list.param.json#/$defs/link/properties/rel
  - schema property: get-admin-csv-config.param.json#/$defs/link/properties/rel
  - schema property: get-admin-customer-delivery-edit.param.json#/$defs/link/properties/rel
  - schema property: get-admin-customer-list.param.json#/$defs/link/properties/rel
  - schema property: get-admin-customer.param.json#/$defs/link/properties/rel
  - schema property: get-admin-delivery-delivery.param.json#/$defs/link/properties/rel
  - schema property: get-admin-login-history.param.json#/$defs/link/properties/rel
  - schema property: get-admin-master-data.param.json#/$defs/link/properties/rel
  - schema property: get-admin-member-list.param.json#/$defs/link/properties/rel
  - schema property: get-admin-member.param.json#/$defs/link/properties/rel
  - schema property: get-admin-news-news.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-edit.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-export-order-pdf.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-list.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-mail-confirm.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-order-pdf.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-send-mail.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-shipping-address.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order-shipping-notify-mail.param.json#/$defs/link/properties/rel
  - schema property: get-admin-order.param.json#/$defs/link/properties/rel
  - schema property: get-admin-page-page.param.json#/$defs/link/properties/rel
  - schema property: get-admin-payment-payment.param.json#/$defs/link/properties/rel
  - schema property: get-admin-product-edit.param.json#/$defs/link/properties/rel
  - schema property: get-admin-product-list.param.json#/$defs/link/properties/rel
  - schema property: get-admin-product-product-class.param.json#/$defs/link/properties/rel
  - schema property: get-admin-product.param.json#/$defs/link/properties/rel
  - schema property: get-admin-unsupported-route.param.json#/$defs/link/properties/rel
  - schema property: get-cart.param.json#/$defs/link/properties/rel
  - schema property: get-contact-complete.param.json#/$defs/link/properties/rel
  - schema property: get-mypage-address.param.json#/$defs/link/properties/rel
  - schema property: get-mypage-history.param.json#/$defs/link/properties/rel
  - schema property: get-mypage-order-history.param.json#/$defs/link/properties/rel
  - schema property: get-mypage.param.json#/$defs/link/properties/rel
  - schema property: get-product.param.json#/$defs/link/properties/rel
  - schema property: get-products.param.json#/$defs/link/properties/rel
  - schema property: get-reset.param.json#/$defs/link/properties/rel
  - schema property: get-shopping-complete.param.json#/$defs/link/properties/rel
  - schema property: get-shopping-confirm.param.json#/$defs/link/properties/rel
  - schema property: get-shopping.param.json#/$defs/link/properties/rel
  - schema property: get-unsupported-route.param.json#/$defs/link/properties/rel
  - schema property: post-action-redirect.param.json#/$defs/link/properties/rel
  - schema property: post-admin-action-redirect.param.json#/$defs/link/properties/rel
  - schema property: post-admin-authority-role.param.json#/$defs/link/properties/rel
  - schema property: post-admin-base-info.param.json#/$defs/link/properties/rel
  - schema property: post-admin-block-block-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-calendar.param.json#/$defs/link/properties/rel
  - schema property: post-admin-category-category-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-category-csv.param.json#/$defs/link/properties/rel
  - schema property: post-admin-change-password.param.json#/$defs/link/properties/rel
  - schema property: post-admin-class-category-class-category-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-class-name-class-name-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-create-customer.param.json#/$defs/link/properties/rel
  - schema property: post-admin-csv-config.param.json#/$defs/link/properties/rel
  - schema property: post-admin-customer-resend-activation-mail.param.json#/$defs/link/properties/rel
  - schema property: post-admin-delete-customer.param.json#/$defs/link/properties/rel
  - schema property: post-admin-delivery-delivery-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-login.param.json#/$defs/link/properties/rel
  - schema property: post-admin-mail-template.param.json#/$defs/link/properties/rel
  - schema property: post-admin-member.param.json#/$defs/link/properties/rel
  - schema property: post-admin-news-news-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-order-bulk-delete.param.json#/$defs/link/properties/rel
  - schema property: post-admin-order-create.param.json#/$defs/link/properties/rel
  - schema property: post-admin-order-import-shipping.param.json#/$defs/link/properties/rel
  - schema property: post-admin-order-send-mail.param.json#/$defs/link/properties/rel
  - schema property: post-admin-order-shipping-address.param.json#/$defs/link/properties/rel
  - schema property: post-admin-order-shipping-notify-mail.param.json#/$defs/link/properties/rel
  - schema property: post-admin-order-status.param.json#/$defs/link/properties/rel
  - schema property: post-admin-page-page-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-payment-payment-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-plugin-disable.param.json#/$defs/link/properties/rel
  - schema property: post-admin-plugin-enable.param.json#/$defs/link/properties/rel
  - schema property: post-admin-plugin-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-product-bulk-status.param.json#/$defs/link/properties/rel
  - schema property: post-admin-product-copy.param.json#/$defs/link/properties/rel
  - schema property: post-admin-product-csv-class-category.param.json#/$defs/link/properties/rel
  - schema property: post-admin-product-csv-class-name.param.json#/$defs/link/properties/rel
  - schema property: post-admin-product-csv.param.json#/$defs/link/properties/rel
  - schema property: post-admin-product.param.json#/$defs/link/properties/rel
  - schema property: post-admin-tag-tag-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-tax-rule-tax-rule-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-template-template-add.param.json#/$defs/link/properties/rel
  - schema property: post-admin-template-template-list.param.json#/$defs/link/properties/rel
  - schema property: post-admin-trade-law.param.json#/$defs/link/properties/rel
  - schema property: post-admin-two-factor-auth.param.json#/$defs/link/properties/rel
  - schema property: post-admin-unsupported-route.param.json#/$defs/link/properties/rel
  - schema property: post-cart-item.param.json#/$defs/link/properties/rel
  - schema property: post-contact.param.json#/$defs/link/properties/rel
  - schema property: post-entry-activate.param.json#/$defs/link/properties/rel
  - schema property: post-entry.param.json#/$defs/link/properties/rel
  - schema property: post-forgot-password.param.json#/$defs/link/properties/rel
  - schema property: post-login.param.json#/$defs/link/properties/rel
  - schema property: post-mypage-address-list.param.json#/$defs/link/properties/rel
  - schema property: post-mypage-change.param.json#/$defs/link/properties/rel
  - schema property: post-mypage-favorite.param.json#/$defs/link/properties/rel
  - schema property: post-mypage-reorder.param.json#/$defs/link/properties/rel
  - schema property: post-mypage-withdraw.param.json#/$defs/link/properties/rel
  - schema property: post-reset.param.json#/$defs/link/properties/rel
  - schema property: post-shopping-checkout.param.json#/$defs/link/properties/rel
  - schema property: post-shopping-confirm.param.json#/$defs/link/properties/rel
  - schema property: post-shopping-non-member.param.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping-edit.param.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping-multiple-edit.param.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping-multiple.param.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping.param.json#/$defs/link/properties/rel
  - schema property: post-unsupported-route.param.json#/$defs/link/properties/rel
  - schema property: put-admin-block-block.param.json#/$defs/link/properties/rel
  - schema property: put-admin-category-category.param.json#/$defs/link/properties/rel
  - schema property: put-admin-class-category-class-category.param.json#/$defs/link/properties/rel
  - schema property: put-admin-class-name-class-name.param.json#/$defs/link/properties/rel
  - schema property: put-admin-content-css.param.json#/$defs/link/properties/rel
  - schema property: put-admin-content-js.param.json#/$defs/link/properties/rel
  - schema property: put-admin-content-maintenance.param.json#/$defs/link/properties/rel
  - schema property: put-admin-delivery-delivery.param.json#/$defs/link/properties/rel
  - schema property: put-admin-layout-layout.param.json#/$defs/link/properties/rel
  - schema property: put-admin-master-data-edit.param.json#/$defs/link/properties/rel
  - schema property: put-admin-master-data.param.json#/$defs/link/properties/rel
  - schema property: put-admin-member.param.json#/$defs/link/properties/rel
  - schema property: put-admin-news-news.param.json#/$defs/link/properties/rel
  - schema property: put-admin-order-shipping-address.param.json#/$defs/link/properties/rel
  - schema property: put-admin-order-status.param.json#/$defs/link/properties/rel
  - schema property: put-admin-order-tracking-number.param.json#/$defs/link/properties/rel
  - schema property: put-admin-order.param.json#/$defs/link/properties/rel
  - schema property: put-admin-page-page.param.json#/$defs/link/properties/rel
  - schema property: put-admin-payment-payment.param.json#/$defs/link/properties/rel
  - schema property: put-admin-product.param.json#/$defs/link/properties/rel
  - schema property: put-admin-security.param.json#/$defs/link/properties/rel
  - schema property: put-admin-sort-no-move.param.json#/$defs/link/properties/rel
  - schema property: put-admin-template-template-list.param.json#/$defs/link/properties/rel
  - schema property: put-admin-toggle-visible.param.json#/$defs/link/properties/rel
  - schema property: put-admin-two-factor-auth-set.param.json#/$defs/link/properties/rel
  - schema property: put-cart-item.param.json#/$defs/link/properties/rel
  - schema property: put-mypage-address.param.json#/$defs/link/properties/rel
  - schema property: delete-admin-block-block.json#/$defs/link/properties/rel
  - schema property: delete-admin-calendar.json#/$defs/link/properties/rel
  - schema property: delete-admin-category-category.json#/$defs/link/properties/rel
  - schema property: delete-admin-class-category-class-category.json#/$defs/link/properties/rel
  - schema property: delete-admin-class-name-class-name.json#/$defs/link/properties/rel
  - schema property: delete-admin-delivery-delivery.json#/$defs/link/properties/rel
  - schema property: delete-admin-mail-template.json#/$defs/link/properties/rel
  - schema property: delete-admin-member.json#/$defs/link/properties/rel
  - schema property: delete-admin-news-news.json#/$defs/link/properties/rel
  - schema property: delete-admin-page-page.json#/$defs/link/properties/rel
  - schema property: delete-admin-payment-payment.json#/$defs/link/properties/rel
  - schema property: delete-admin-plugin.json#/$defs/link/properties/rel
  - schema property: delete-admin-product.json#/$defs/link/properties/rel
  - schema property: delete-admin-tag-tag.json#/$defs/link/properties/rel
  - schema property: delete-admin-tax-rule-tax-rule.json#/$defs/link/properties/rel
  - schema property: delete-admin-template-template-list.json#/$defs/link/properties/rel
  - schema property: delete-cart-item.json#/$defs/link/properties/rel
  - schema property: delete-mypage-address.json#/$defs/link/properties/rel
  - schema property: delete-mypage-favorite.json#/$defs/link/properties/rel
  - schema property: get-action-redirect.json#/$defs/link/properties/rel
  - schema property: get-admin-action-redirect.json#/$defs/link/properties/rel
  - schema property: get-admin-authority-role.json#/$defs/link/properties/rel
  - schema property: get-admin-base-info.json#/$defs/link/properties/rel
  - schema property: get-admin-block-block-list.json#/$defs/link/properties/rel
  - schema property: get-admin-block-block.json#/$defs/link/properties/rel
  - schema property: get-admin-calendar.json#/$defs/link/properties/rel
  - schema property: get-admin-category-category-list.json#/$defs/link/properties/rel
  - schema property: get-admin-category-category.json#/$defs/link/properties/rel
  - schema property: get-admin-category-csv.json#/$defs/link/properties/rel
  - schema property: get-admin-category-edit.json#/$defs/link/properties/rel
  - schema property: get-admin-change-password.json#/$defs/link/properties/rel
  - schema property: get-admin-class-category-class-category-export.json#/$defs/link/properties/rel
  - schema property: get-admin-class-category-class-category-list.json#/$defs/link/properties/rel
  - schema property: get-admin-class-name-class-name-export.json#/$defs/link/properties/rel
  - schema property: get-admin-class-name-class-name-list.json#/$defs/link/properties/rel
  - schema property: get-admin-content-cache.json#/$defs/link/properties/rel
  - schema property: get-admin-content-css.json#/$defs/link/properties/rel
  - schema property: get-admin-content-file-manager.json#/$defs/link/properties/rel
  - schema property: get-admin-content-js.json#/$defs/link/properties/rel
  - schema property: get-admin-content-maintenance.json#/$defs/link/properties/rel
  - schema property: get-admin-csv-config.json#/$defs/link/properties/rel
  - schema property: get-admin-customer-csv.json#/$defs/link/properties/rel
  - schema property: get-admin-customer-delivery-edit.json#/$defs/link/properties/rel
  - schema property: get-admin-customer-list.json#/$defs/link/properties/rel
  - schema property: get-admin-customer.json#/$defs/link/properties/rel
  - schema property: get-admin-delivery-delivery-list.json#/$defs/link/properties/rel
  - schema property: get-admin-delivery-delivery.json#/$defs/link/properties/rel
  - schema property: get-admin-empty-page.json#/$defs/link/properties/rel
  - schema property: get-admin-index.json#/$defs/link/properties/rel
  - schema property: get-admin-layout-layout-list.json#/$defs/link/properties/rel
  - schema property: get-admin-layout-layout.json#/$defs/link/properties/rel
  - schema property: get-admin-log.json#/$defs/link/properties/rel
  - schema property: get-admin-login-history.json#/$defs/link/properties/rel
  - schema property: get-admin-login.json#/$defs/link/properties/rel
  - schema property: get-admin-mail-template.json#/$defs/link/properties/rel
  - schema property: get-admin-master-data.json#/$defs/link/properties/rel
  - schema property: get-admin-master-data.json#/properties/submitTo/properties/rel
  - schema property: get-admin-member-list.json#/$defs/link/properties/rel
  - schema property: get-admin-member.json#/$defs/link/properties/rel
  - schema property: get-admin-news-news-list.json#/$defs/link/properties/rel
  - schema property: get-admin-news-news.json#/$defs/link/properties/rel
  - schema property: get-admin-order-edit.json#/$defs/link/properties/rel
  - schema property: get-admin-order-export-order-pdf.json#/$defs/link/properties/rel
  - schema property: get-admin-order-export-order.json#/$defs/link/properties/rel
  - schema property: get-admin-order-export-shipping.json#/$defs/link/properties/rel
  - schema property: get-admin-order-import-shipping.json#/$defs/link/properties/rel
  - schema property: get-admin-order-list.json#/$defs/link/properties/rel
  - schema property: get-admin-order-mail-confirm.json#/$defs/link/properties/rel
  - schema property: get-admin-order-order-pdf.json#/$defs/link/properties/rel
  - schema property: get-admin-order-send-mail.json#/$defs/link/properties/rel
  - schema property: get-admin-order-shipping-address.json#/$defs/link/properties/rel
  - schema property: get-admin-order-shipping-notify-mail.json#/$defs/link/properties/rel
  - schema property: get-admin-order-status.json#/$defs/link/properties/rel
  - schema property: get-admin-order.json#/$defs/link/properties/rel
  - schema property: get-admin-page-page-list.json#/$defs/link/properties/rel
  - schema property: get-admin-page-page.json#/$defs/link/properties/rel
  - schema property: get-admin-payment-payment-list.json#/$defs/link/properties/rel
  - schema property: get-admin-payment-payment.json#/$defs/link/properties/rel
  - schema property: get-admin-plugin-list.json#/$defs/link/properties/rel
  - schema property: get-admin-product-csv-category.json#/$defs/link/properties/rel
  - schema property: get-admin-product-csv-class-category.json#/$defs/link/properties/rel
  - schema property: get-admin-product-csv-class-name.json#/$defs/link/properties/rel
  - schema property: get-admin-product-csv-product.json#/$defs/link/properties/rel
  - schema property: get-admin-product-csv.json#/$defs/link/properties/rel
  - schema property: get-admin-product-edit.json#/$defs/link/properties/rel
  - schema property: get-admin-product-list.json#/$defs/link/properties/rel
  - schema property: get-admin-product-new.json#/$defs/link/properties/rel
  - schema property: get-admin-product-product-class.json#/$defs/link/properties/rel
  - schema property: get-admin-product.json#/$defs/link/properties/rel
  - schema property: get-admin-security.json#/$defs/link/properties/rel
  - schema property: get-admin-system.json#/$defs/link/properties/rel
  - schema property: get-admin-tag-tag-list.json#/$defs/link/properties/rel
  - schema property: get-admin-tax-rule-tax-rule-list.json#/$defs/link/properties/rel
  - schema property: get-admin-template-template-add.json#/$defs/link/properties/rel
  - schema property: get-admin-template-template-list.json#/$defs/link/properties/rel
  - schema property: get-admin-trade-law.json#/$defs/link/properties/rel
  - schema property: get-admin-two-factor-auth-edit.json#/$defs/link/properties/rel
  - schema property: get-admin-two-factor-auth-set.json#/$defs/link/properties/rel
  - schema property: get-admin-two-factor-auth.json#/$defs/link/properties/rel
  - schema property: get-admin-unsupported-route.json#/$defs/link/properties/rel
  - schema property: get-cart.json#/$defs/link/properties/rel
  - schema property: get-contact-complete.json#/$defs/link/properties/rel
  - schema property: get-contact-confirm.json#/$defs/link/properties/rel
  - schema property: get-contact.json#/$defs/link/properties/rel
  - schema property: get-contact.json#/properties/submitTo/properties/rel
  - schema property: get-entry-activate.json#/$defs/link/properties/rel
  - schema property: get-entry-complete.json#/$defs/link/properties/rel
  - schema property: get-entry-confirm.json#/$defs/link/properties/rel
  - schema property: get-entry.json#/$defs/link/properties/rel
  - schema property: get-forgot-complete.json#/$defs/link/properties/rel
  - schema property: get-forgot-password.json#/$defs/link/properties/rel
  - schema property: get-help-about.json#/$defs/link/properties/rel
  - schema property: get-help-agreement.json#/$defs/link/properties/rel
  - schema property: get-help-guide.json#/$defs/link/properties/rel
  - schema property: get-help-privacy.json#/$defs/link/properties/rel
  - schema property: get-help-trade-law.json#/$defs/link/properties/rel
  - schema property: get-index.json#/$defs/link/properties/rel
  - schema property: get-login.json#/$defs/link/properties/rel
  - schema property: get-mypage-address-list.json#/$defs/link/properties/rel
  - schema property: get-mypage-address.json#/$defs/link/properties/rel
  - schema property: get-mypage-change-complete.json#/$defs/link/properties/rel
  - schema property: get-mypage-change.json#/$defs/link/properties/rel
  - schema property: get-mypage-favorite-list.json#/$defs/link/properties/rel
  - schema property: get-mypage-history.json#/$defs/link/properties/rel
  - schema property: get-mypage-order-history.json#/$defs/link/properties/rel
  - schema property: get-mypage-withdraw-complete.json#/$defs/link/properties/rel
  - schema property: get-mypage-withdraw-confirm.json#/$defs/link/properties/rel
  - schema property: get-mypage-withdraw.json#/$defs/link/properties/rel
  - schema property: get-mypage.json#/$defs/link/properties/rel
  - schema property: get-product.json#/$defs/link/properties/rel
  - schema property: get-products.json#/$defs/link/properties/rel
  - schema property: get-reset.json#/$defs/link/properties/rel
  - schema property: get-shopping-complete.json#/$defs/link/properties/rel
  - schema property: get-shopping-confirm.json#/$defs/link/properties/rel
  - schema property: get-shopping-error.json#/$defs/link/properties/rel
  - schema property: get-shopping-login.json#/$defs/link/properties/rel
  - schema property: get-shopping-non-member.json#/$defs/link/properties/rel
  - schema property: get-shopping-shipping-edit.json#/$defs/link/properties/rel
  - schema property: get-shopping-shipping-multiple-edit.json#/$defs/link/properties/rel
  - schema property: get-shopping-shipping-multiple.json#/$defs/link/properties/rel
  - schema property: get-shopping-shipping.json#/$defs/link/properties/rel
  - schema property: get-shopping.json#/$defs/link/properties/rel
  - schema property: get-unsupported-route.json#/$defs/link/properties/rel
  - schema property: post-action-redirect.json#/$defs/link/properties/rel
  - schema property: post-admin-action-redirect.json#/$defs/link/properties/rel
  - schema property: post-admin-authority-role.json#/$defs/link/properties/rel
  - schema property: post-admin-base-info.json#/$defs/link/properties/rel
  - schema property: post-admin-block-block-list.json#/$defs/link/properties/rel
  - schema property: post-admin-calendar.json#/$defs/link/properties/rel
  - schema property: post-admin-category-category-list.json#/$defs/link/properties/rel
  - schema property: post-admin-category-csv.json#/$defs/link/properties/rel
  - schema property: post-admin-change-password.json#/$defs/link/properties/rel
  - schema property: post-admin-class-category-class-category-list.json#/$defs/link/properties/rel
  - schema property: post-admin-class-name-class-name-list.json#/$defs/link/properties/rel
  - schema property: post-admin-create-customer.json#/$defs/link/properties/rel
  - schema property: post-admin-csv-config.json#/$defs/link/properties/rel
  - schema property: post-admin-customer-resend-activation-mail.json#/$defs/link/properties/rel
  - schema property: post-admin-delete-customer.json#/$defs/link/properties/rel
  - schema property: post-admin-delivery-delivery-list.json#/$defs/link/properties/rel
  - schema property: post-admin-login.json#/$defs/link/properties/rel
  - schema property: post-admin-logout.json#/$defs/link/properties/rel
  - schema property: post-admin-mail-template.json#/$defs/link/properties/rel
  - schema property: post-admin-member.json#/$defs/link/properties/rel
  - schema property: post-admin-news-news-list.json#/$defs/link/properties/rel
  - schema property: post-admin-order-bulk-delete.json#/$defs/link/properties/rel
  - schema property: post-admin-order-create.json#/$defs/link/properties/rel
  - schema property: post-admin-order-import-shipping.json#/$defs/link/properties/rel
  - schema property: post-admin-order-send-mail.json#/$defs/link/properties/rel
  - schema property: post-admin-order-shipping-address.json#/$defs/link/properties/rel
  - schema property: post-admin-order-shipping-notify-mail.json#/$defs/link/properties/rel
  - schema property: post-admin-order-status.json#/$defs/link/properties/rel
  - schema property: post-admin-page-page-list.json#/$defs/link/properties/rel
  - schema property: post-admin-payment-payment-list.json#/$defs/link/properties/rel
  - schema property: post-admin-plugin-disable.json#/$defs/link/properties/rel
  - schema property: post-admin-plugin-enable.json#/$defs/link/properties/rel
  - schema property: post-admin-plugin-list.json#/$defs/link/properties/rel
  - schema property: post-admin-product-bulk-status.json#/$defs/link/properties/rel
  - schema property: post-admin-product-copy.json#/$defs/link/properties/rel
  - schema property: post-admin-product-csv-class-category.json#/$defs/link/properties/rel
  - schema property: post-admin-product-csv-class-name.json#/$defs/link/properties/rel
  - schema property: post-admin-product-csv.json#/$defs/link/properties/rel
  - schema property: post-admin-product.json#/$defs/link/properties/rel
  - schema property: post-admin-tag-tag-list.json#/$defs/link/properties/rel
  - schema property: post-admin-tax-rule-tax-rule-list.json#/$defs/link/properties/rel
  - schema property: post-admin-template-template-add.json#/$defs/link/properties/rel
  - schema property: post-admin-template-template-list.json#/$defs/link/properties/rel
  - schema property: post-admin-trade-law.json#/$defs/link/properties/rel
  - schema property: post-admin-two-factor-auth.json#/$defs/link/properties/rel
  - schema property: post-admin-unsupported-route.json#/$defs/link/properties/rel
  - schema property: post-cart-item.json#/$defs/link/properties/rel
  - schema property: post-contact.json#/$defs/link/properties/rel
  - schema property: post-entry-activate.json#/$defs/link/properties/rel
  - schema property: post-entry.json#/$defs/link/properties/rel
  - schema property: post-forgot-password.json#/$defs/link/properties/rel
  - schema property: post-login.json#/$defs/link/properties/rel
  - schema property: post-logout.json#/$defs/link/properties/rel
  - schema property: post-mypage-address-list.json#/$defs/link/properties/rel
  - schema property: post-mypage-change.json#/$defs/link/properties/rel
  - schema property: post-mypage-favorite.json#/$defs/link/properties/rel
  - schema property: post-mypage-reorder.json#/$defs/link/properties/rel
  - schema property: post-mypage-withdraw.json#/$defs/link/properties/rel
  - schema property: post-reset.json#/$defs/link/properties/rel
  - schema property: post-shopping-checkout.json#/$defs/link/properties/rel
  - schema property: post-shopping-non-member.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping-edit.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping-multiple-edit.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping-multiple.json#/$defs/link/properties/rel
  - schema property: post-shopping-shipping.json#/$defs/link/properties/rel
  - schema property: post-unsupported-route.json#/$defs/link/properties/rel
  - schema property: put-admin-block-block.json#/$defs/link/properties/rel
  - schema property: put-admin-category-category.json#/$defs/link/properties/rel
  - schema property: put-admin-class-category-class-category.json#/$defs/link/properties/rel
  - schema property: put-admin-class-name-class-name.json#/$defs/link/properties/rel
  - schema property: put-admin-content-cache.json#/$defs/link/properties/rel
  - schema property: put-admin-content-css.json#/$defs/link/properties/rel
  - schema property: put-admin-content-js.json#/$defs/link/properties/rel
  - schema property: put-admin-content-maintenance.json#/$defs/link/properties/rel
  - schema property: put-admin-delivery-delivery.json#/$defs/link/properties/rel
  - schema property: put-admin-layout-layout.json#/$defs/link/properties/rel
  - schema property: put-admin-master-data-edit.json#/$defs/link/properties/rel
  - schema property: put-admin-master-data.json#/$defs/link/properties/rel
  - schema property: put-admin-master-data.json#/properties/submitTo/properties/rel
  - schema property: put-admin-member.json#/$defs/link/properties/rel
  - schema property: put-admin-news-news.json#/$defs/link/properties/rel
  - schema property: put-admin-order-shipping-address.json#/$defs/link/properties/rel
  - schema property: put-admin-order-status.json#/$defs/link/properties/rel
  - schema property: put-admin-order-tracking-number.json#/$defs/link/properties/rel
  - schema property: put-admin-order.json#/$defs/link/properties/rel
  - schema property: put-admin-page-page.json#/$defs/link/properties/rel
  - schema property: put-admin-payment-payment.json#/$defs/link/properties/rel
  - schema property: put-admin-product.json#/$defs/link/properties/rel
  - schema property: put-admin-security.json#/$defs/link/properties/rel
  - schema property: put-admin-sort-no-move.json#/$defs/link/properties/rel
  - schema property: put-admin-template-template-list.json#/$defs/link/properties/rel
  - schema property: put-admin-toggle-visible.json#/$defs/link/properties/rel
  - schema property: put-admin-two-factor-auth-set.json#/$defs/link/properties/rel
  - schema property: put-cart-item.json#/$defs/link/properties/rel
  - schema property: put-mypage-address.json#/$defs/link/properties/rel

### `requestedCount`

- usages:
  - schema property: post-admin-order-bulk-delete.json#/properties/requestedCount
  - schema property: post-admin-product-bulk-status.json#/properties/requestedCount

### `requestedQuantity`

- usages:
  - schema property: post-cart-item.json#/properties/requestedQuantity
  - schema property: put-cart-item.json#/properties/requestedQuantity

### `resetKey` ☑︎

- title: リセットキー
- doc: パスワードリセット用のワンタイムトークン。リセット要求時に生成、使用後にクリア
- usages:
  - parameter: GET /reset {resetKey}
  - parameter: POST /reset {resetKey}
  - schema property: get-reset.param.json#/properties/resetKey
  - schema property: post-reset.param.json#/properties/resetKey
  - schema property: get-reset.json#/properties/resetKey

### `returnTo`

- usages:
  - parameter: GET /action-redirect {returnTo}
  - parameter: POST /action-redirect {returnTo}
  - parameter: GET /admin/action-redirect {returnTo}
  - parameter: POST /admin/action-redirect {returnTo}
  - parameter: POST /admin/unsupported-route {returnTo}
  - parameter: POST /unsupported-route {returnTo}
  - schema property: get-action-redirect.param.json#/properties/returnTo
  - schema property: get-admin-action-redirect.param.json#/properties/returnTo
  - schema property: post-action-redirect.param.json#/properties/returnTo
  - schema property: post-admin-action-redirect.param.json#/properties/returnTo
  - schema property: post-admin-unsupported-route.param.json#/properties/returnTo
  - schema property: post-unsupported-route.param.json#/properties/returnTo

### `roundingType` ☑︎

- title: 端数処理
- doc: 1=四捨五入, 2=切り捨て, 3=切り上げ。受注明細の税額計算時の端数処理方式。TaxRuleで設定
- usages:
  - parameter: POST /admin/tax-rule/tax-rule-list {roundingType}
  - schema property: post-admin-tax-rule-tax-rule-list.param.json#/properties/roundingType
  - schema property: get-admin-tax-rule-tax-rule-list.json#/properties/taxRules/items/properties/roundingType
  - schema property: post-admin-tax-rule-tax-rule-list.json#/properties/roundingType

### `routeName`

- usages:
  - parameter: GET /admin/unsupported-route {routeName}
  - parameter: POST /admin/unsupported-route {routeName}
  - parameter: GET /unsupported-route {routeName}
  - parameter: POST /unsupported-route {routeName}
  - schema property: get-admin-unsupported-route.param.json#/properties/routeName
  - schema property: get-unsupported-route.param.json#/properties/routeName
  - schema property: post-admin-unsupported-route.param.json#/properties/routeName
  - schema property: post-unsupported-route.param.json#/properties/routeName
  - schema property: get-admin-unsupported-route.json#/properties/routeName
  - schema property: get-unsupported-route.json#/properties/routeName
  - schema property: post-admin-unsupported-route.json#/properties/routeName
  - schema property: post-unsupported-route.json#/properties/routeName

### `rowCount`

- usages:
  - schema property: get-admin-category-csv.json#/properties/rowCount
  - schema property: get-admin-customer-csv.json#/properties/rowCount
  - schema property: get-admin-order-export-order.json#/properties/rowCount
  - schema property: get-admin-order-export-shipping.json#/properties/rowCount

### `rowId`

- usages:
  - parameter: PUT /admin/sort-no-move {rowId}
  - parameter: PUT /admin/toggle-visible {rowId}
  - schema property: put-admin-sort-no-move.param.json#/properties/rowId
  - schema property: put-admin-toggle-visible.param.json#/properties/rowId
  - schema property: put-admin-sort-no-move.json#/properties/rowId
  - schema property: put-admin-toggle-visible.json#/properties/rowId

### `rows`

- usages:
  - parameter: PUT /admin/master-data-edit {rows}
  - schema property: put-admin-master-data-edit.param.json#/properties/rows
  - schema property: get-admin-master-data.json#/properties/rows
  - schema property: put-admin-master-data.json#/properties/rows

### `ruleMax`

- usages:
  - parameter: PUT /admin/payment/payment {ruleMax}
  - parameter: POST /admin/payment/payment-list {ruleMax}
  - schema property: post-admin-payment-payment-list.param.json#/properties/ruleMax
  - schema property: put-admin-payment-payment.param.json#/properties/ruleMax
  - schema property: get-admin-payment-payment-list.json#/properties/payments/items/properties/ruleMax
  - schema property: post-admin-payment-payment-list.json#/properties/ruleMax
  - schema property: put-admin-payment-payment.json#/properties/ruleMax

### `ruleMin`

- usages:
  - parameter: PUT /admin/payment/payment {ruleMin}
  - parameter: POST /admin/payment/payment-list {ruleMin}
  - schema property: post-admin-payment-payment-list.param.json#/properties/ruleMin
  - schema property: put-admin-payment-payment.param.json#/properties/ruleMin
  - schema property: get-admin-payment-payment-list.json#/properties/payments/items/properties/ruleMin
  - schema property: post-admin-payment-payment-list.json#/properties/ruleMin
  - schema property: put-admin-payment-payment.json#/properties/ruleMin

### `rules`

- usages:
  - schema property: get-admin-authority-role.json#/properties/rules
  - schema property: post-admin-authority-role.json#/properties/rules

### `saleTypeId` ☑︎

- title: 販売種別ID
- doc: dtb_product_class.sale_type_id — mtb_sale_type への FK（1=通常販売, 2=予約販売, 3=ダウンロード販売）。cartKey の組み立て(`{sessionPrefix}_{saleTypeId}`)に用いられ、異なる販売種別の商品を別カートに分離する基準。マスタ未登録時は 0 にフォールバック
- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/saleTypeId

### `saleTypeName` ☑︎

- title: 販売種別
- doc: 販売種別の名称。カート分離の基準となる。異なる販売種別の商品は別カート(cartKey)に分離される
- usages:
  - schema property: get-cart.json#/properties/carts/items/properties/saleTypeName
  - schema property: get-shopping.json#/properties/carts/items/properties/saleTypeName
  - schema property: post-cart-item.json#/properties/saleTypeName
  - schema property: put-cart-item.json#/properties/saleTypeName

### `salesThisMonth`

- usages:
  - schema property: get-admin-index.json#/properties/salesThisMonth

### `salesToday`

- usages:
  - schema property: get-admin-index.json#/properties/salesToday

### `salesYesterday`

- usages:
  - schema property: get-admin-index.json#/properties/salesYesterday

### `searchForm`

- usages:
  - schema property: get-admin-customer-list.json#/properties/searchForm
  - schema property: get-admin-order-list.json#/properties/searchForm
  - schema property: get-admin-product-list.json#/properties/searchForm

### `searchWord` ☑︎

- title: 検索ワード
- doc: フロント検索でヒットさせるためのキーワード。画面には表示されない検索補助データ
- usages:
  - parameter: POST /admin/product {searchWord}
  - parameter: PUT /admin/product {searchWord}
  - schema property: post-admin-product.param.json#/properties/searchWord
  - schema property: put-admin-product.param.json#/properties/searchWord
  - schema property: get-admin-product.json#/properties/searchWord

### `secretKey` ☑︎

- title: 認証キー
- doc: 会員アカウントのメール認証トークン。/entry/activate/{secret_key}形式のURLに使用。暗号鍵やAPIシークレットではない。会員登録時にランダム生成
- usages:
  - parameter: POST /entry/activate {secretKey}
  - schema property: post-entry-activate.param.json#/properties/secretKey

### `sections`

- usages:
  - schema property: get-help-about.json#/properties/staticContent/properties/sections
  - schema property: get-help-agreement.json#/properties/staticContent/properties/sections
  - schema property: get-help-guide.json#/properties/staticContent/properties/sections
  - schema property: get-help-privacy.json#/properties/staticContent/properties/sections
  - schema property: get-help-trade-law.json#/properties/staticContent/properties/sections
  - schema property: get-shopping-error.json#/properties/staticContent/properties/sections

### `selectedMaster`

- usages:
  - schema property: get-admin-master-data.json#/properties/selectedMaster
  - schema property: put-admin-master-data.json#/properties/selectedMaster

### `sendDate` ☑︎

- title: 送信日時
- doc: メールの送信日時
- usages:
  - schema property: get-mypage-history.json#/properties/mailHistories/items/properties/sendDate
  - schema property: post-admin-order-send-mail.json#/properties/sendDate

### `sessionPrefix` ☑︎

- title: セッション接頭辞
- doc: 購入フローのカートキーを構成するセッションスコープの接頭辞。saleTypeId と組み合わせて販売種別ごとのカートを分離する。
- usages:
  - parameter: GET /cart {sessionPrefix}
  - parameter: POST /cart/item {sessionPrefix}
  - parameter: PUT /cart/item {sessionPrefix}
  - parameter: DELETE /cart/item {sessionPrefix}
  - parameter: POST /mypage/withdraw {sessionPrefix}
  - parameter: GET /shopping {sessionPrefix}
  - parameter: POST /shopping/non-member {sessionPrefix}
  - schema property: delete-cart-item.param.json#/properties/sessionPrefix
  - schema property: get-cart.param.json#/properties/sessionPrefix
  - schema property: get-shopping.param.json#/properties/sessionPrefix
  - schema property: post-cart-item.param.json#/properties/sessionPrefix
  - schema property: post-mypage-withdraw.param.json#/properties/sessionPrefix
  - schema property: post-shopping-non-member.param.json#/properties/sessionPrefix
  - schema property: put-cart-item.param.json#/properties/sessionPrefix

### `sex` ☑︎

- title: 性別
- def: https://schema.org/gender
- doc: 1=男性, 2=女性, 3=その他, 4=回答しない
- usages:
  - parameter: POST /admin/create-customer {sex}
  - parameter: POST /entry {sex}
  - schema property: post-admin-create-customer.param.json#/properties/sex
  - schema property: post-entry.param.json#/properties/sex
  - schema property: get-admin-customer.json#/properties/sex

### `shippingAddressId`

- usages:
  - parameter: POST /shopping/shipping {shippingAddressId}
  - schema property: post-shopping-shipping-multiple.param.json#/properties/allocations/items/properties/shippingAddressId
  - schema property: post-shopping-shipping.param.json#/properties/shippingAddressId
  - schema property: get-shopping-shipping-multiple.json#/properties/addresses/items/properties/shippingAddressId
  - schema property: get-shopping-shipping.json#/properties/addresses/items/properties/shippingAddressId
  - schema property: post-shopping-shipping.json#/properties/shippingAddressId

### `shipping_delivery_date`

- usages:
  - schema property: post-shopping-confirm.param.json#/properties/shipping_delivery_date

### `shippings`

- usages:
  - schema property: get-mypage-history.json#/properties/shippings

### `shopEmail01` ☑︎

- title: 送信元/BCC メールアドレス
- doc: ほぼ全メール種別の送信元（From）兼ショップ控え（BCC）アドレス。注文確認・会員登録・パスワードリセット等で使用
- usages:
  - parameter: POST /admin/base-info {shopEmail01}
  - schema property: post-admin-base-info.param.json#/properties/shopEmail01
  - schema property: get-admin-base-info.json#/properties/shopEmail01
  - schema property: post-admin-base-info.json#/properties/shopEmail01

### `shopKana` ☑︎

- title: ショップ名フリガナ
- doc: ショップ名のカタカナ読み
- usages:
  - parameter: POST /admin/base-info {shopKana}
  - schema property: post-admin-base-info.param.json#/properties/shopKana
  - schema property: get-admin-base-info.json#/properties/shopKana
  - schema property: post-admin-base-info.json#/properties/shopKana

### `shopMessage` ☑︎

- title: ショップメッセージ
- doc: 「当サイトについて」ページ（Help/about.twig）に表示する店舗からのメッセージ
- usages:
  - parameter: POST /admin/base-info {shopMessage}
  - schema property: post-admin-base-info.param.json#/properties/shopMessage
  - schema property: get-admin-base-info.json#/properties/shopMessage
  - schema property: post-admin-base-info.json#/properties/shopMessage

### `shopName` ☑︎

- title: ショップ名
- def: https://schema.org/name
- doc: ショップの表示名。フロント画面のヘッダやメールに表示
- usages:
  - parameter: POST /admin/base-info {shopName}
  - schema property: post-admin-base-info.param.json#/properties/shopName
  - schema property: get-admin-base-info.json#/properties/shopName
  - schema property: get-admin-two-factor-auth-edit.json#/properties/shopName
  - schema property: get-admin-two-factor-auth-set.json#/properties/shopName
  - schema property: post-admin-base-info.json#/properties/shopName

### `shopNameEng` ☑︎

- title: ショップ名英語
- doc: ショップの英語名。多言語対応やメール署名等で使用
- usages:
  - parameter: POST /admin/base-info {shopNameEng}
  - schema property: post-admin-base-info.param.json#/properties/shopNameEng
  - schema property: get-admin-base-info.json#/properties/shopNameEng
  - schema property: post-admin-base-info.json#/properties/shopNameEng

### `size`

- usages:
  - schema property: get-admin-order-export-order-pdf.json#/properties/size

### `skeletonRoute`

- usages:
  - schema property: get-admin-product-csv-category.json#/properties/skeletonRoute
  - schema property: get-admin-product-csv-class-category.json#/properties/skeletonRoute
  - schema property: get-admin-product-csv-class-name.json#/properties/skeletonRoute
  - schema property: get-admin-product-csv-product.json#/properties/skeletonRoute

### `skipped`

- usages:
  - schema property: post-admin-order-import-shipping.json#/properties/skipped

### `skippedCount`

- usages:
  - schema property: post-mypage-reorder.json#/properties/skippedCount

### `skippedProductCodes`

- usages:
  - schema property: post-mypage-reorder.json#/properties/skippedProductCodes

### `sortNo` ☑︎

- title: 表示順
- doc: 一覧における並び順
- usages:
  - parameter: PUT /admin/category/category {sortNo}
  - parameter: POST /admin/category/category-list {sortNo}
  - parameter: PUT /admin/sort-no-move {sortNo}
  - schema property: post-admin-category-category-list.param.json#/properties/sortNo
  - schema property: post-admin-csv-config.param.json#/properties/columns/items/properties/sortNo
  - schema property: put-admin-category-category.param.json#/properties/sortNo
  - schema property: put-admin-master-data-edit.param.json#/properties/rows/items/properties/sortNo
  - schema property: put-admin-sort-no-move.param.json#/properties/sortNo
  - schema property: get-admin-category-category-list.json#/properties/categories/items/properties/sortNo
  - schema property: get-admin-category-category.json#/properties/sortNo
  - schema property: get-admin-category-edit.json#/properties/categories/items/properties/sortNo
  - schema property: get-admin-member-list.json#/properties/members/items/properties/sortNo
  - schema property: get-admin-member.json#/properties/sortNo
  - schema property: post-admin-category-category-list.json#/properties/sortNo
  - schema property: post-admin-member.json#/properties/sortNo
  - schema property: put-admin-category-category.json#/properties/sortNo
  - schema property: put-admin-member.json#/properties/sortNo
  - schema property: put-admin-sort-no-move.json#/properties/sortNo

### `staticContent`

- usages:
  - schema property: get-contact-complete.json#/properties/staticContent
  - schema property: get-contact-confirm.json#/properties/staticContent
  - schema property: get-entry-activate.json#/properties/staticContent
  - schema property: get-entry-complete.json#/properties/staticContent
  - schema property: get-entry-confirm.json#/properties/staticContent
  - schema property: get-help-about.json#/properties/staticContent
  - schema property: get-help-agreement.json#/properties/staticContent
  - schema property: get-help-guide.json#/properties/staticContent
  - schema property: get-help-privacy.json#/properties/staticContent
  - schema property: get-help-trade-law.json#/properties/staticContent
  - schema property: get-index.json#/properties/staticContent
  - schema property: get-mypage-change-complete.json#/properties/staticContent
  - schema property: get-mypage-withdraw-complete.json#/properties/staticContent
  - schema property: get-shopping-complete.json#/properties/staticContent
  - schema property: get-shopping-error.json#/properties/staticContent
  - schema property: get-shopping-login.json#/properties/staticContent
  - schema property: get-shopping-shipping-edit.json#/properties/staticContent
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/staticContent
  - schema property: get-shopping-shipping-multiple.json#/properties/staticContent
  - schema property: get-shopping-shipping.json#/properties/staticContent

### `status`

- usages:
  - schema property: put-admin-order-tracking-number.json#/properties/status

### `stock` ☑︎

- title: 在庫数
- doc: 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる
- usages:
  - parameter: POST /admin/product {stock}
  - parameter: PUT /admin/product {stock}
  - schema property: post-admin-product.param.json#/properties/stock
  - schema property: put-admin-product.param.json#/properties/stock
  - schema property: get-admin-product-list.json#/properties/products/items/properties/stock
  - schema property: get-admin-product-product-class.json#/properties/classes/items/properties/stock
  - schema property: get-admin-product.json#/properties/stock
  - schema property: get-product.json#/properties/stock
  - schema property: get-products.json#/properties/products/items/properties/stock
  - schema property: post-admin-product-copy.json#/properties/stock
  - schema property: post-admin-product.json#/properties/stock
  - schema property: put-admin-product.json#/properties/stock

### `stockFind`

- usages:
  - schema property: get-product.json#/properties/stockFind
  - schema property: get-products.json#/properties/products/items/properties/stockFind

### `submitTo`

- usages:
  - schema property: get-admin-login.json#/properties/submitTo
  - schema property: get-admin-master-data.json#/properties/submitTo
  - schema property: get-admin-order-shipping-notify-mail.json#/properties/submitTo
  - schema property: get-contact-complete.json#/properties/submitTo
  - schema property: get-contact-confirm.json#/properties/submitTo
  - schema property: get-contact.json#/properties/submitTo
  - schema property: get-entry-activate.json#/properties/submitTo
  - schema property: get-entry-complete.json#/properties/submitTo
  - schema property: get-entry-confirm.json#/properties/submitTo
  - schema property: get-entry.json#/properties/submitTo
  - schema property: get-forgot-complete.json#/properties/submitTo
  - schema property: get-forgot-password.json#/properties/submitTo
  - schema property: get-help-about.json#/properties/submitTo
  - schema property: get-help-agreement.json#/properties/submitTo
  - schema property: get-help-guide.json#/properties/submitTo
  - schema property: get-help-privacy.json#/properties/submitTo
  - schema property: get-help-trade-law.json#/properties/submitTo
  - schema property: get-index.json#/properties/submitTo
  - schema property: get-login.json#/properties/submitTo
  - schema property: get-mypage-address.json#/properties/submitTo
  - schema property: get-mypage-change-complete.json#/properties/submitTo
  - schema property: get-mypage-change.json#/properties/submitTo
  - schema property: get-mypage-withdraw-complete.json#/properties/submitTo
  - schema property: get-mypage-withdraw-confirm.json#/properties/submitTo
  - schema property: get-mypage-withdraw.json#/properties/submitTo
  - schema property: get-reset.json#/properties/submitTo
  - schema property: get-shopping-confirm.json#/properties/submitTo
  - schema property: get-shopping-error.json#/properties/submitTo
  - schema property: get-shopping-login.json#/properties/submitTo
  - schema property: get-shopping-non-member.json#/properties/submitTo
  - schema property: get-shopping-shipping-edit.json#/properties/submitTo
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/submitTo
  - schema property: get-shopping-shipping-multiple.json#/properties/submitTo
  - schema property: get-shopping-shipping.json#/properties/submitTo
  - schema property: put-admin-master-data.json#/properties/submitTo

### `subtotal` ☑︎

- title: 商品小計
- doc: 商品合計金額（税込）。送料・手数料・値引き適用前の商品明細（orderItemType=1）のみの合計。PurchaseFlow.calculateSubTotal()で計算。送料無料条件の判定基準にも使用（お届け先ごとに判定）
- usages:
  - schema property: get-admin-order.json#/properties/subtotal
  - schema property: get-mypage-history.json#/properties/subtotal
  - schema property: get-shopping-confirm.json#/properties/subtotal
  - schema property: post-admin-order-create.json#/properties/subtotal
  - schema property: put-admin-order.json#/properties/subtotal

### `success`

- usages:
  - schema property: get-admin-login-history.json#/properties/entries/items/properties/success

### `table`

- usages:
  - schema property: get-admin-master-data.json#/properties/masterTypes/items/properties/table
  - schema property: put-admin-master-data.json#/properties/masterTypes/items/properties/table

### `tagId` ☑︎

- title: タグID
- doc: dtb_tag.id の不透明な文字列ハンドル。BeMart の TagEntity 層は数値ではなく文字列として保持する。Fake 実装は `tg-` プレフィックス付きの英数字を生成し、SQL 実装は dtb_tag.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTagStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TagDeleted) を踏むため、シードハンドル `tg-new` / `tg-sale` は Fake 専用
- usages:
  - parameter: DELETE /admin/tag/tag {tagId}
  - schema property: delete-admin-tag-tag.param.json#/properties/tagId
  - schema property: delete-admin-tag-tag.json#/properties/tagId
  - schema property: get-admin-tag-tag-list.json#/properties/tags/items/properties/tagId
  - schema property: post-admin-tag-tag-list.json#/properties/tagId

### `tagName` ☑︎

- title: タグ名
- doc: 商品に付与するタグの表示名
- usages:
  - parameter: POST /admin/tag/tag-list {tagName}
  - schema property: post-admin-tag-tag-list.param.json#/properties/tagName
  - schema property: get-admin-tag-tag-list.json#/properties/tags/items/properties/tagName
  - schema property: post-admin-tag-tag-list.json#/properties/tagName

### `tagNames`

- usages:
  - schema property: get-admin-product-list.json#/properties/products/items/properties/tagNames
  - schema property: get-admin-product.json#/properties/tagNames
  - schema property: get-product.json#/properties/tagNames
  - schema property: get-products.json#/properties/products/items/properties/tagNames

### `tags` ☑︎

- usages:
  - schema property: get-admin-tag-tag-list.json#/properties/tags

### `tax` ☑︎

- title: 税額
- doc: 受注全体の税額合計（非推奨）。明細ごとの税額集計と差異が生じる場合があるため、正確な税額はOrderItem明細ごとのtaxを集計すべき
- usages:
  - schema property: post-admin-order-create.param.json#/properties/orderItems/items/properties/tax
  - schema property: get-admin-order.json#/properties/tax
  - schema property: get-mypage-history.json#/properties/tax
  - schema property: get-shopping-confirm.json#/properties/tax
  - schema property: post-admin-order-create.json#/properties/tax
  - schema property: put-admin-order.json#/properties/tax

### `taxRate` ☑︎

- title: 適用税率
- doc: 受注明細（OrderItem）の注文時点の適用税率（%）。taxRuleRate（税率ルールマスタ）のスナップショット。TaxProcessorにより受注作成時にコピーされる。軽減税率（8%）と標準税率（10%）が混在可能。optionProductTaxRule有効時は商品規格単位の個別税率を優先適用
- usages:
  - parameter: POST /admin/tax-rule/tax-rule-list {taxRate}
  - schema property: post-admin-tax-rule-tax-rule-list.param.json#/properties/taxRate
  - schema property: get-admin-tax-rule-tax-rule-list.json#/properties/taxRules/items/properties/taxRate
  - schema property: post-admin-tax-rule-tax-rule-list.json#/properties/taxRate

### `taxRuleId` ☑︎

- title: 税率ルールID
- doc: dtb_tax_rule.id の不透明な文字列ハンドル。BeMart の TaxRuleEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_tax_rule.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTaxRuleStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TaxRuleDeleted) を踏むため、シードハンドル（`nonexistent-zzz` 等）は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: DELETE /admin/tax-rule/tax-rule {taxRuleId}
  - schema property: delete-admin-tax-rule-tax-rule.param.json#/properties/taxRuleId
  - schema property: delete-admin-tax-rule-tax-rule.json#/properties/taxRuleId
  - schema property: get-admin-tax-rule-tax-rule-list.json#/properties/taxRules/items/properties/taxRuleId
  - schema property: post-admin-tax-rule-tax-rule-list.json#/properties/taxRuleId

### `taxRules`

- usages:
  - schema property: get-admin-tax-rule-tax-rule-list.json#/properties/taxRules

### `templateCode` ☑︎

- title: テンプレートコード
- doc: テンプレートの一意識別コード。標準テンプレートは'default'
- usages:
  - parameter: POST /admin/template/template-add {templateCode}
  - schema property: post-admin-template-template-add.param.json#/properties/templateCode

### `templateId` ☑︎

- title: テンプレートID
- doc: dtb_template.id の不透明な文字列ハンドル。BeMart の TemplateEntity 層は数値ではなく文字列として保持する。Fake 実装は `tp-` プレフィックス付きのシードハンドル（tp-default-pc / tp-default-sp）を持ち、SQL 実装は dtb_template.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Template には list アフォーダンスのみ（goTemplateList）で作成・更新・削除が無いため ID ジェネレータは存在せず、テンプレートはインストーラ/fixture が seed した行から読み出すのみ。layoutId / blockId / categoryId と同じ Fake↔SQL 二重性。templateCode（一意の install-time コード）とは別物 — TemplateEntity の投影は id ハンドルを用い templateCode は投影外
- usages:
  - parameter: PUT /admin/template/template-list {templateId}
  - parameter: DELETE /admin/template/template-list {templateId}
  - parameter: POST /admin/template/template-list {templateId}
  - schema property: delete-admin-template-template-list.param.json#/properties/templateId
  - schema property: post-admin-template-template-list.param.json#/properties/templateId
  - schema property: put-admin-template-template-list.param.json#/properties/templateId
  - schema property: delete-admin-template-template-list.json#/properties/templateId
  - schema property: get-admin-template-template-list.json#/properties/templates/items/properties/templateId
  - schema property: post-admin-template-template-add.json#/properties/templateId
  - schema property: put-admin-template-template-list.json#/properties/templateId

### `templateName` ☑︎

- title: テンプレート名
- doc: テンプレートの表示名
- usages:
  - parameter: POST /admin/template/template-add {templateName}
  - schema property: post-admin-template-template-add.param.json#/properties/templateName
  - schema property: get-admin-template-template-list.json#/properties/templates/items/properties/templateName

### `templates`

- usages:
  - schema property: get-admin-template-template-list.json#/properties/templates

### `ticketId` ☑︎

- title: 受付番号
- doc: 問い合わせ送信後に発行される公開受付番号。問い合わせ本文の readback resource ではなく、完了状態の成立証拠として使う。
- usages:
  - parameter: GET /contact/complete {ticketId}
  - schema property: get-contact-complete.param.json#/properties/ticketId
  - schema property: get-contact-complete.json#/properties/ticketId
  - schema property: post-contact.json#/properties/ticketId

### `timestamp`

- usages:
  - schema property: get-admin-login-history.json#/properties/entries/items/properties/timestamp

### `title`

- usages:
  - parameter: POST /admin/calendar {title}
  - schema property: post-admin-calendar.param.json#/properties/title
  - schema property: get-admin-calendar.json#/properties/calendars/items/properties/title
  - schema property: get-admin-system.json#/properties/info/items/properties/title
  - schema property: get-contact-complete.json#/properties/staticContent/properties/title
  - schema property: get-contact-confirm.json#/properties/staticContent/properties/title
  - schema property: get-entry-activate.json#/properties/staticContent/properties/title
  - schema property: get-entry-complete.json#/properties/staticContent/properties/title
  - schema property: get-entry-confirm.json#/properties/staticContent/properties/title
  - schema property: get-help-about.json#/properties/staticContent/properties/sections/items/properties/title
  - schema property: get-help-about.json#/properties/staticContent/properties/title
  - schema property: get-help-agreement.json#/properties/staticContent/properties/sections/items/properties/title
  - schema property: get-help-agreement.json#/properties/staticContent/properties/title
  - schema property: get-help-guide.json#/properties/staticContent/properties/sections/items/properties/title
  - schema property: get-help-guide.json#/properties/staticContent/properties/title
  - schema property: get-help-privacy.json#/properties/staticContent/properties/sections/items/properties/title
  - schema property: get-help-privacy.json#/properties/staticContent/properties/title
  - schema property: get-help-trade-law.json#/properties/staticContent/properties/sections/items/properties/title
  - schema property: get-help-trade-law.json#/properties/staticContent/properties/title
  - schema property: get-mypage-change-complete.json#/properties/staticContent/properties/title
  - schema property: get-mypage-withdraw-complete.json#/properties/staticContent/properties/title
  - schema property: get-shopping-complete.json#/properties/staticContent/properties/title
  - schema property: get-shopping-error.json#/properties/staticContent/properties/sections/items/properties/title
  - schema property: get-shopping-error.json#/properties/staticContent/properties/title
  - schema property: post-admin-calendar.json#/properties/title

### `total` ☑︎

- title: 受注合計
- doc: 受注合計金額。計算式: subtotal(商品税込合計) + deliveryFeeTotal(送料) + charge(手数料) - discount(値引き)。カートのtotalPriceとは別プロパティ
- usages:
  - schema property: get-admin-customer.json#/properties/orders/items/properties/total
  - schema property: get-admin-index.json#/properties/orders/items/properties/total
  - schema property: get-admin-order-list.json#/properties/orders/items/properties/total
  - schema property: get-admin-order.json#/properties/total
  - schema property: get-mypage-history.json#/properties/total
  - schema property: get-mypage-order-history.json#/properties/orders/items/properties/total
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/total
  - schema property: get-shopping-confirm.json#/properties/total
  - schema property: post-admin-order-create.json#/properties/total
  - schema property: post-shopping-checkout.json#/properties/total
  - schema property: put-admin-order.json#/properties/total

### `totalItemCount`

- usages:
  - schema property: get-products.json#/properties/totalItemCount

### `totalPrice` ☑︎

- title: カート合計金額
- doc: カート内の税込合計金額。PurchaseFlow.calculateTotal()で毎回再計算されるキャッシュ値。受注のtotalとは別プロパティ
- usages:
  - schema property: delete-cart-item.json#/properties/totalPrice
  - schema property: get-cart.json#/properties/carts/items/properties/totalPrice
  - schema property: get-cart.json#/properties/totalPrice
  - schema property: get-shopping-confirm.json#/properties/items/items/properties/totalPrice
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/totalPrice
  - schema property: get-shopping.json#/properties/carts/items/properties/totalPrice
  - schema property: get-shopping.json#/properties/totalPrice
  - schema property: post-cart-item.json#/properties/totalPrice
  - schema property: put-cart-item.json#/properties/totalPrice

### `totalSpent`

- usages:
  - schema property: get-admin-customer.json#/properties/totalSpent

### `tplIsTopDir`

- usages:
  - schema property: get-admin-content-file-manager.json#/properties/tplIsTopDir

### `tplNowDir`

- usages:
  - schema property: get-admin-content-file-manager.json#/properties/tplNowDir

### `tplParentDir`

- usages:
  - schema property: get-admin-content-file-manager.json#/properties/tplParentDir

### `trackingNumber` ☑︎

- title: 追跡番号
- doc: 配送業者の荷物追跡番号。confirmUrlと組み合わせて追跡URLを構成
- usages:
  - parameter: PUT /admin/order/tracking-number {trackingNumber}
  - schema property: put-admin-order-tracking-number.param.json#/properties/trackingNumber
  - schema property: get-admin-csv-config.json#/properties/notOutputColumns/properties/trackingNumber
  - schema property: post-admin-order-shipping-notify-mail.json#/properties/trackingNumber
  - schema property: put-admin-order-tracking-number.json#/properties/trackingNumber

### `tradeLawBody` ☑︎

- title: 特定商取引法本文
- doc: 特定商取引法ページ本文の単一ブロブ投影。項目別行ではなく、ページ全体を1本の本文として扱う。
- usages:
  - parameter: POST /admin/trade-law {tradeLawBody}
  - schema property: post-admin-trade-law.param.json#/properties/tradeLawBody
  - schema property: get-admin-trade-law.json#/properties/tradeLawBody
  - schema property: post-admin-trade-law.json#/properties/tradeLawBody

### `tradeLawRows`

- usages:
  - schema property: get-admin-trade-law.json#/properties/tradeLawRows

### `trade_law_1_description`

- usages:
  - parameter: POST /admin/trade-law {trade_law_1_description}

### `trade_law_1_displayOrderScreen`

- usages:
  - parameter: POST /admin/trade-law {trade_law_1_displayOrderScreen}

### `trade_law_1_name`

- usages:
  - parameter: POST /admin/trade-law {trade_law_1_name}

### `trade_law_2_description`

- usages:
  - parameter: POST /admin/trade-law {trade_law_2_description}

### `trade_law_2_displayOrderScreen`

- usages:
  - parameter: POST /admin/trade-law {trade_law_2_displayOrderScreen}

### `trade_law_2_name`

- usages:
  - parameter: POST /admin/trade-law {trade_law_2_name}

### `trade_law_3_description`

- usages:
  - parameter: POST /admin/trade-law {trade_law_3_description}

### `trade_law_3_displayOrderScreen`

- usages:
  - parameter: POST /admin/trade-law {trade_law_3_displayOrderScreen}

### `trade_law_3_name`

- usages:
  - parameter: POST /admin/trade-law {trade_law_3_name}

### `trade_law_4_description`

- usages:
  - parameter: POST /admin/trade-law {trade_law_4_description}

### `trade_law_4_displayOrderScreen`

- usages:
  - parameter: POST /admin/trade-law {trade_law_4_displayOrderScreen}

### `trade_law_4_name`

- usages:
  - parameter: POST /admin/trade-law {trade_law_4_name}

### `trade_law_5_description`

- usages:
  - parameter: POST /admin/trade-law {trade_law_5_description}

### `trade_law_5_displayOrderScreen`

- usages:
  - parameter: POST /admin/trade-law {trade_law_5_displayOrderScreen}

### `trade_law_5_name`

- usages:
  - parameter: POST /admin/trade-law {trade_law_5_name}

### `trade_law_6_description`

- usages:
  - parameter: POST /admin/trade-law {trade_law_6_description}

### `trade_law_6_displayOrderScreen`

- usages:
  - parameter: POST /admin/trade-law {trade_law_6_displayOrderScreen}

### `trade_law_6_name`

- usages:
  - parameter: POST /admin/trade-law {trade_law_6_name}

### `transitionId`

- usages:
  - schema property: delete-admin-calendar.json#/properties/transitionId
  - schema property: delete-admin-mail-template.json#/properties/transitionId
  - schema property: delete-admin-template-template-list.json#/properties/transitionId
  - schema property: get-admin-change-password.json#/properties/transitionId
  - schema property: get-admin-login.json#/properties/transitionId
  - schema property: get-admin-two-factor-auth-set.json#/properties/transitionId
  - schema property: get-admin-two-factor-auth.json#/properties/transitionId
  - schema property: get-contact-complete.json#/properties/transitionId
  - schema property: get-contact-confirm.json#/properties/transitionId
  - schema property: get-contact.json#/properties/transitionId
  - schema property: get-entry-activate.json#/properties/transitionId
  - schema property: get-entry-complete.json#/properties/transitionId
  - schema property: get-entry-confirm.json#/properties/transitionId
  - schema property: get-entry.json#/properties/transitionId
  - schema property: get-forgot-complete.json#/properties/transitionId
  - schema property: get-forgot-password.json#/properties/transitionId
  - schema property: get-help-about.json#/properties/transitionId
  - schema property: get-help-agreement.json#/properties/transitionId
  - schema property: get-help-guide.json#/properties/transitionId
  - schema property: get-help-privacy.json#/properties/transitionId
  - schema property: get-help-trade-law.json#/properties/transitionId
  - schema property: get-index.json#/properties/transitionId
  - schema property: get-login.json#/properties/transitionId
  - schema property: get-mypage-address.json#/properties/transitionId
  - schema property: get-mypage-change-complete.json#/properties/transitionId
  - schema property: get-mypage-withdraw-complete.json#/properties/transitionId
  - schema property: get-mypage-withdraw-confirm.json#/properties/transitionId
  - schema property: get-mypage-withdraw.json#/properties/transitionId
  - schema property: get-products.json#/properties/transitionId
  - schema property: get-reset.json#/properties/transitionId
  - schema property: get-shopping-complete.json#/properties/transitionId
  - schema property: get-shopping-confirm.json#/properties/transitionId
  - schema property: get-shopping-error.json#/properties/transitionId
  - schema property: get-shopping-login.json#/properties/transitionId
  - schema property: get-shopping-non-member.json#/properties/transitionId
  - schema property: get-shopping-shipping-edit.json#/properties/transitionId
  - schema property: get-shopping-shipping-multiple-edit.json#/properties/transitionId
  - schema property: get-shopping-shipping-multiple.json#/properties/transitionId
  - schema property: get-shopping-shipping.json#/properties/transitionId
  - schema property: post-admin-authority-role.json#/properties/transitionId
  - schema property: post-admin-calendar.json#/properties/transitionId
  - schema property: post-admin-category-csv.json#/properties/transitionId
  - schema property: post-admin-change-password.json#/properties/transitionId
  - schema property: post-admin-order-import-shipping.json#/properties/transitionId
  - schema property: post-admin-product-csv-class-category.json#/properties/transitionId
  - schema property: post-admin-product-csv-class-name.json#/properties/transitionId
  - schema property: post-admin-product-csv.json#/properties/transitionId
  - schema property: post-admin-template-template-add.json#/properties/transitionId
  - schema property: post-admin-two-factor-auth.json#/properties/transitionId
  - schema property: post-shopping-shipping-edit.json#/properties/transitionId
  - schema property: post-shopping-shipping-multiple-edit.json#/properties/transitionId
  - schema property: post-shopping-shipping-multiple.json#/properties/transitionId
  - schema property: post-shopping-shipping.json#/properties/transitionId
  - schema property: put-admin-content-cache.json#/properties/transitionId
  - schema property: put-admin-content-css.json#/properties/transitionId
  - schema property: put-admin-content-js.json#/properties/transitionId
  - schema property: put-admin-content-maintenance.json#/properties/transitionId
  - schema property: put-admin-master-data-edit.json#/properties/transitionId
  - schema property: put-admin-master-data.json#/properties/transitionId
  - schema property: put-admin-order-status.json#/properties/transitionId
  - schema property: put-admin-security.json#/properties/transitionId
  - schema property: put-admin-template-template-list.json#/properties/transitionId
  - schema property: put-admin-two-factor-auth-set.json#/properties/transitionId

### `trustedHosts`

- usages:
  - parameter: PUT /admin/security {trustedHosts}
  - schema property: put-admin-security.param.json#/properties/trustedHosts
  - schema property: put-admin-security.json#/properties/trustedHosts

### `unitPrice` ☑︎

- title: 単価
- def: https://schema.org/price
- doc: 明細1件あたりの単価。受注/カート明細・お気に入りスナップショットでは追加時点の price02 をスナップショットして保持する（後の値引きやマスタ改定に影響されない）。BeMart 側では `int` 円整数
- usages:
  - schema property: post-admin-order-create.param.json#/properties/orderItems/items/properties/unitPrice
  - schema property: get-admin-customer.json#/properties/favorites/items/properties/unitPrice
  - schema property: get-admin-order-edit.json#/properties/items/items/properties/unitPrice
  - schema property: get-admin-order.json#/properties/items/items/properties/unitPrice
  - schema property: get-admin-product-list.json#/properties/products/items/properties/unitPrice
  - schema property: get-mypage-favorite-list.json#/properties/favorites/items/properties/unitPrice
  - schema property: get-mypage-history.json#/properties/shippings/items/properties/items/items/properties/unitPrice
  - schema property: get-mypage.json#/properties/recentOrders/items/properties/items/items/properties/unitPrice
  - schema property: get-products.json#/properties/products/items/properties/unitPrice
  - schema property: get-shopping-confirm.json#/properties/items/items/properties/unitPrice
  - schema property: get-shopping-shipping-multiple.json#/properties/cartItems/items/properties/unitPrice
  - schema property: post-cart-item.json#/properties/unitPrice
  - schema property: post-mypage-favorite.json#/properties/unitPrice
  - schema property: put-cart-item.json#/properties/unitPrice

### `usePoint` ☑︎

- title: 使用ポイント
- doc: 注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加
- usages:
  - parameter: PUT /admin/order {usePoint}
  - schema property: put-admin-order.param.json#/properties/usePoint
  - schema property: get-admin-order.json#/properties/usePoint
  - schema property: get-mypage-history.json#/properties/usePoint
  - schema property: get-shopping-confirm.json#/properties/usePoint
  - schema property: put-admin-order.json#/properties/usePoint

### `user_policy_check`

- usages:
  - parameter: POST /entry {user_policy_check}
  - schema property: post-entry.param.json#/properties/user_policy_check

### `value`

- usages:
  - schema property: post-admin-csv-config.param.json#/properties/columns/items/properties/value
  - schema property: put-admin-master-data-edit.param.json#/properties/rows/items/properties/value
  - schema property: get-admin-class-category-class-category-export.json#/properties/value
  - schema property: get-admin-class-name-class-name-export.json#/properties/value
  - schema property: get-admin-master-data.json#/properties/masterTypes/items/properties/value
  - schema property: get-admin-system.json#/properties/info/items/properties/value
  - schema property: post-admin-csv-config.json#/properties/columns/items/properties/value
  - schema property: post-admin-template-template-list.json#/properties/value
  - schema property: put-admin-master-data.json#/properties/masterTypes/items/properties/value

### `version`

- usages:
  - schema property: get-admin-index.json#/properties/recommendedPlugins/items/properties/version

### `visible`

- usages:
  - parameter: PUT /admin/delivery/delivery {visible}
  - parameter: POST /admin/delivery/delivery-list {visible}
  - parameter: PUT /admin/payment/payment {visible}
  - parameter: POST /admin/payment/payment-list {visible}
  - parameter: PUT /admin/toggle-visible {visible}
  - schema property: post-admin-delivery-delivery-list.param.json#/properties/visible
  - schema property: post-admin-payment-payment-list.param.json#/properties/visible
  - schema property: put-admin-delivery-delivery.param.json#/properties/visible
  - schema property: put-admin-payment-payment.param.json#/properties/visible
  - schema property: put-admin-toggle-visible.param.json#/properties/visible
  - schema property: get-admin-delivery-delivery-list.json#/properties/deliveries/items/properties/visible
  - schema property: get-admin-payment-payment-list.json#/properties/payments/items/properties/visible
  - schema property: post-admin-delivery-delivery-list.json#/properties/visible
  - schema property: post-admin-payment-payment-list.json#/properties/visible
  - schema property: put-admin-delivery-delivery.json#/properties/visible
  - schema property: put-admin-payment-payment.json#/properties/visible
  - schema property: put-admin-toggle-visible.json#/properties/visible

### `wasInstalled`

- usages:
  - schema property: delete-admin-plugin.json#/properties/wasInstalled

### `wasLoggedIn`

- usages:
  - schema property: post-admin-logout.json#/properties/wasLoggedIn
  - schema property: post-logout.json#/properties/wasLoggedIn

### `work` ☑︎

- title: 稼働状態
- doc: Work Masterの定数で定義: 0=NON_ACTIVE（非稼働、ログイン不可）, 1=ACTIVE（稼働、ログイン可能）。管理者メンバーの有効/無効を制御
- usages:
  - schema property: get-admin-member-list.json#/properties/members/items/properties/work
  - schema property: get-admin-member.json#/properties/work
  - schema property: post-admin-member.json#/properties/work
  - schema property: put-admin-member.json#/properties/work
