# Term Usage Index

This index reports lexical identifier matches only; it does not prove semantic equivalence.

## Summary

- Terms used in API: 114
- Terms with same-name ALPS descriptor: 87
- Lexical ALPS coverage: 76.3%
- Reserved representation fields: 0
- ☑︎ = ALPS descriptor binding

## Terms

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

### `addressId` ☑︎

- title: 配送先住所ID
- doc: dtb_customer_address.id の不透明な文字列ハンドル。BeMart の AddressEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_customer_address.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。所有者は customerId、AUTHZ 検査は CustomerAddressUpdated / CustomerAddressDeleted で getById → customerId 一致確認の順で実施
- usages:
  - parameter: POST /admin/order/shipping-address {addressId}
  - parameter: GET /mypage/address {addressId}
  - parameter: PUT /mypage/address {addressId}
  - parameter: DELETE /mypage/address {addressId}

### `applyDate` ☑︎

- title: 適用日
- doc: この税率ルールが有効になる日時。適用日以降の注文にこの税率が適用される。複数の税率ルールがある場合、注文日時点で最も新しい適用日のルールが使用される。過去の受注には影響しない
- usages:
  - parameter: POST /admin/tax-rule/tax-rule-list {applyDate}

### `authority` ☑︎

- title: 権限
- doc: 管理者権限レベル。0=システム管理者（最高権限、全機能アクセス可能）, 1=店舗オーナー（制限あり、denyUrlで制限されたURLにアクセス不可）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御
- usages:
  - parameter: POST /admin/authority-role {authority}
  - parameter: POST /admin/member {authority}

### `birth` ☑︎

- title: 生年月日
- def: https://schema.org/birthDate
- doc: 会員の生年月日
- usages:
  - parameter: POST /admin/create-customer {birth}
  - parameter: POST /entry {birth}

### `blockFileName` ☑︎

- title: ブロックファイル名
- doc: ブロックのテンプレートファイル名
- usages:
  - parameter: PUT /admin/block/block {blockFileName}
  - parameter: POST /admin/block/block-list {blockFileName}

### `blockId` ☑︎

- title: ブロックID
- doc: dtb_block.id の不透明な文字列ハンドル。BeMart の BlockEntity 層は数値ではなく文字列として保持する。Fake 実装は `bk-` プレフィックス付きの英数字を生成し（シード `bk-header` を含む）、SQL 実装は dtb_block.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlBlockStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (BlockUpdated / BlockDeleted) を踏むため、シードハンドル `bk-header` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: PUT /admin/block/block {blockId}
  - parameter: DELETE /admin/block/block {blockId}

### `blockName` ☑︎

- title: ブロック名
- doc: ブロックの表示名
- usages:
  - parameter: PUT /admin/block/block {blockName}
  - parameter: POST /admin/block/block-list {blockName}

### `businessHour` ☑︎

- title: 営業時間
- doc: ショップの営業時間。フリーフォーマット
- usages:
  - parameter: POST /admin/base-info {businessHour}

### `categoryId` ☑︎

- title: カテゴリID
- doc: dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/category/category {categoryId}
  - parameter: PUT /admin/category/category {categoryId}
  - parameter: DELETE /admin/category/category {categoryId}
  - parameter: GET /admin/category/edit {categoryId}

### `categoryName` ☑︎

- title: カテゴリ名
- doc: カテゴリの表示名
- usages:
  - parameter: PUT /admin/category/category {categoryName}
  - parameter: POST /admin/category/category-list {categoryName}

### `category_id`

- usages:
  - parameter: GET /products {category_id}

### `charge` ☑︎

- title: 手数料
- doc: 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる
- usages:
  - parameter: PUT /admin/order {charge}
  - parameter: POST /admin/order/create {charge}
  - parameter: PUT /admin/payment/payment {charge}
  - parameter: POST /admin/payment/payment-list {charge}

### `classCategoryId` ☑︎

- title: 規格分類ID
- doc: dtb_class_category.id の不透明な文字列ハンドル。BeMart の ClassCategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格分類の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。classNameId / categoryId / blockId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: PUT /admin/class-category/class-category {classCategoryId}
  - parameter: DELETE /admin/class-category/class-category {classCategoryId}

### `classCategoryName` ☑︎

- title: 規格分類名
- doc: 商品バリエーション軸の具体的な値（例: 赤、Lサイズ）。EC-CUBEの"classCategory"はOOPのカテゴリではなく規格値を意味する
- usages:
  - parameter: PUT /admin/class-category/class-category {classCategoryName}
  - parameter: POST /admin/class-category/class-category-list {classCategoryName}

### `classNameId` ☑︎

- title: 規格名ID
- doc: dtb_class_name.id の不透明な文字列ハンドル。BeMart の ClassNameEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_name.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassNameStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格名の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/class-category/class-category-list {classNameId}
  - parameter: POST /admin/class-category/class-category-list {classNameId}
  - parameter: PUT /admin/class-name/class-name {classNameId}
  - parameter: DELETE /admin/class-name/class-name {classNameId}

### `classNameLabel` ☑︎

- title: 規格名
- doc: 商品バリエーション軸の名前（例: カラー、サイズ）。EC-CUBEの"class"はOOPのクラスではなく商品規格を意味する
- usages:
  - parameter: PUT /admin/class-name/class-name {classNameLabel}
  - parameter: POST /admin/class-name/class-name-list {classNameLabel}

### `columns`

- usages:
  - parameter: POST /admin/csv-config {columns}

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

### `contactContents` ☑︎

- title: お問い合わせ内容
- doc: お問い合わせフォームの本文
- usages:
  - parameter: POST /contact {contactContents}

### `contactEmail` ☑︎

- title: お問い合わせメール
- def: https://schema.org/email
- doc: お問い合わせフォームのメールアドレス
- usages:
  - parameter: POST /contact {contactEmail}

### `contactName01` ☑︎

- title: お問い合わせ姓
- doc: お問い合わせフォームの姓。内部的にはNameTypeのname01と同じ仕組み
- usages:
  - parameter: POST /contact {contactName01}

### `contactName02` ☑︎

- title: お問い合わせ名
- doc: お問い合わせフォームの名。内部的にはNameTypeのname02と同じ仕組み
- usages:
  - parameter: POST /contact {contactName02}

### `csv`

- usages:
  - parameter: POST /admin/category/csv {csv}
  - parameter: POST /admin/order/import-shipping {csv}

### `csvType` ☑︎

- title: CSV種別
- doc: dtb_csv.csv_type_id — mtb_csv_type への FK（1=注文CSV, 2=会員CSV, 3=商品CSV, 4=出荷CSV）。1つの csvType が複数の列設定行（dtb_csv 行）を所有する。doUpdateCsv は1つの csvType の列ベクタ全体を一括 POST し、SqlCsvColumnConfigStorage::replaceType がその csvType の行集合をアトミックに置換する。mtb_csv_type は structure-only ダンプで空のため SQL テストは seedCsvTypes でシード（seedAdminMasters と同じ空マスタ FK シード規約）
- usages:
  - parameter: POST /admin/csv-config {csvType}

### `customerId` ☑︎

- title: 会員ID
- doc: dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用
- usages:
  - parameter: GET /admin/customer {customerId}
  - parameter: GET /admin/customer-delivery-edit {customerId}
  - parameter: POST /admin/delete-customer {customerId}
  - parameter: POST /admin/order/create {customerId}

### `deliveryFeeTotal` ☑︎

- title: 送料合計
- doc: 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用
- usages:
  - parameter: POST /admin/order/create {deliveryFeeTotal}

### `deliveryId` ☑︎

- title: 配送方法ID
- doc: dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/delivery/delivery {deliveryId}
  - parameter: PUT /admin/delivery/delivery {deliveryId}
  - parameter: DELETE /admin/delivery/delivery {deliveryId}

### `deliveryName` ☑︎

- title: 配送業者名
- doc: 注文時点の配送業者名スナップショット
- usages:
  - parameter: PUT /admin/delivery/delivery {deliveryName}
  - parameter: POST /admin/delivery/delivery-list {deliveryName}

### `description`

- usages:
  - parameter: POST /admin/product {description}
  - parameter: PUT /admin/product {description}

### `discount` ☑︎

- title: 値引き額
- doc: 受注全体の値引き合計額。クーポン等による値引き
- usages:
  - parameter: PUT /admin/order {discount}
  - parameter: POST /admin/order/create {discount}

### `disp_number`

- usages:
  - parameter: GET /products {disp_number}

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

### `emailKeyword`

- usages:
  - parameter: GET /admin/customer-list {emailKeyword}

### `historyLimit`

- usages:
  - parameter: GET /mypage/order-history {historyLimit}

### `id`

- usages:
  - parameter: GET /admin/csv-config {id}
  - parameter: GET /admin/customer {id}
  - parameter: GET /admin/customer-delivery-edit {id}

### `job` ☑︎

- title: 職業
- doc: 1=公務員〜18=その他の18区分
- usages:
  - parameter: POST /admin/create-customer {job}
  - parameter: POST /entry {job}

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

### `layoutId` ☑︎

- title: レイアウトID
- doc: dtb_layout.id の不透明な文字列ハンドル。BeMart の LayoutEntity 層は数値ではなく文字列として保持する。Fake 実装は `lo-` プレフィックス付きのシードハンドル（lo-pc-default / lo-sp-default）を持ち、SQL 実装は dtb_layout.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。Layout には作成・削除アフォーダンスが無い（goLayoutList + doUpdateLayout のみ）ため ID ジェネレータは存在せず、レイアウトはインストーラ/fixture が seed した行から読み出すのみ。非数値 ID は SqlLayoutStorage では miss として扱われ getById / put のいずれも 404 経路 (LayoutUpdated) を踏むため、シードハンドル `lo-pc-default` や `nonexistent` は Fake / SQL 双方で 404 が同形。blockId / categoryId と同じ Fake↔SQL 二重性
- usages:
  - parameter: PUT /admin/layout/layout {layoutId}

### `layoutName` ☑︎

- title: レイアウト名
- doc: レイアウトの表示名
- usages:
  - parameter: PUT /admin/layout/layout {layoutName}

### `limit`

- usages:
  - parameter: GET /admin/customer-list {limit}
  - parameter: GET /admin/login-history {limit}
  - parameter: GET /admin/member-list {limit}
  - parameter: GET /admin/order-list {limit}
  - parameter: GET /admin/product-list {limit}
  - parameter: GET /products {limit}

### `linkMethod` ☑︎

- title: 新規ウィンドウで開く
- doc: 外部URLのリンク開き方（boolean）。false=同一ウィンドウ, true=新規ウィンドウ（target="_blank"）。テンプレートでtarget属性の出力制御に使用
- usages:
  - parameter: PUT /admin/news/news {linkMethod}
  - parameter: POST /admin/news/news-list {linkMethod}

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

### `mailSubject` ☑︎

- title: メール件名
- doc: メールの件名。テンプレート変数を含む場合あり
- usages:
  - parameter: POST /admin/mail-template {mailSubject}

### `mailTemplateId` ☑︎

- title: メールテンプレートID
- doc: dtb_mail_template.id（int unsigned AUTO_INCREMENT）の正の整数主キー。doUpdateMailTemplate の必須入力で、既存行を指す必要がある。SqlMailTemplateStorage は findById / update をこの id で引き、未知 id は MailTemplateNotFoundException（404）。新規テンプレート作成フローは file_name 設定と Twig ファイル書き出しを伴うため Phase 2 scope であり、ID 生成器は存在しない（更新専用契約）
- usages:
  - parameter: POST /admin/mail-template {mailTemplateId}

### `masterType`

- usages:
  - parameter: GET /admin/master-data {masterType}
  - parameter: PUT /admin/sort-no-move {masterType}
  - parameter: PUT /admin/toggle-visible {masterType}

### `name`

- usages:
  - parameter: POST /admin/member {name}
  - parameter: PUT /admin/member {name}
  - parameter: GET /products {name}

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

### `nameKeyword`

- usages:
  - parameter: GET /admin/customer-list {nameKeyword}
  - parameter: GET /admin/member-list {nameKeyword}
  - parameter: GET /admin/product-list {nameKeyword}
  - parameter: GET /products {nameKeyword}

### `newProductCode`

- usages:
  - parameter: POST /admin/product-copy {newProductCode}

### `newsDescription` ☑︎

- title: ニュース本文
- def: https://schema.org/articleBody
- doc: ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり
- usages:
  - parameter: PUT /admin/news/news {newsDescription}
  - parameter: POST /admin/news/news-list {newsDescription}

### `newsId` ☑︎

- title: ニュースID
- doc: dtb_news.id の不透明な文字列ハンドル。BeMart の NewsEntity 層は数値ではなく文字列として保持する。Fake 実装は `nw-` プレフィックス付きの英数字を生成し（シード `nw-welcome` を含む）、SQL 実装は dtb_news.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlNewsStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminNewsFetched / NewsUpdated / NewsDeleted) を踏むため、シードハンドル `nw-welcome` や `nonexistent` は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: GET /admin/news/news {newsId}
  - parameter: PUT /admin/news/news {newsId}
  - parameter: DELETE /admin/news/news {newsId}

### `newsTitle` ☑︎

- title: ニュースタイトル
- def: https://schema.org/headline
- doc: ニュース記事の見出し
- usages:
  - parameter: PUT /admin/news/news {newsTitle}
  - parameter: POST /admin/news/news-list {newsTitle}

### `newsUrl` ☑︎

- title: 外部URL
- def: https://schema.org/url
- doc: 外部リンクURL。設定時はニュース本文の代わりにこのURLへ遷移
- usages:
  - parameter: PUT /admin/news/news {newsUrl}
  - parameter: POST /admin/news/news-list {newsUrl}

### `note`

- usages:
  - parameter: POST /admin/product {note}
  - parameter: PUT /admin/product {note}

### `offset`

- usages:
  - parameter: GET /admin/member-list {offset}
  - parameter: GET /admin/order-list {offset}
  - parameter: GET /admin/product-list {offset}
  - parameter: GET /mypage/order-history {offset}
  - parameter: GET /products {offset}

### `orderLimit`

- usages:
  - parameter: GET /mypage {orderLimit}

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
  - parameter: POST /admin/order/shipping-notify-mail {orderNo}
  - parameter: PUT /admin/order/tracking-number {orderNo}
  - parameter: GET /mypage/history {orderNo}
  - parameter: POST /mypage/reorder {orderNo}
  - parameter: GET /shopping/complete {orderNo}

### `orderNos`

- usages:
  - parameter: POST /admin/order/bulk-delete {orderNos}

### `orderStatus` ☑︎

- title: 受注ステータス
- doc: 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外
- usages:
  - parameter: POST /admin/order-status {orderStatus}

### `orderby`

- usages:
  - parameter: GET /products {orderby}

### `pageFileName` ☑︎

- title: テンプレートファイル名
- doc: ページのテンプレートファイル名
- usages:
  - parameter: PUT /admin/page/page {pageFileName}
  - parameter: POST /admin/page/page-list {pageFileName}

### `pageId` ☑︎

- title: ページID
- doc: dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: GET /admin/page/page {pageId}
  - parameter: PUT /admin/page/page {pageId}
  - parameter: DELETE /admin/page/page {pageId}

### `pageName` ☑︎

- title: ページ名
- doc: 管理画面でのページ表示名
- usages:
  - parameter: PUT /admin/page/page {pageName}
  - parameter: POST /admin/page/page-list {pageName}

### `pageUrl` ☑︎

- title: ページURL
- doc: ページのURLパス（Symfonyルート名。例: homepage, product_list）
- usages:
  - parameter: PUT /admin/page/page {pageUrl}
  - parameter: POST /admin/page/page-list {pageUrl}

### `pageno`

- usages:
  - parameter: GET /products {pageno}

### `parentId`

- usages:
  - parameter: PUT /admin/category/category {parentId}
  - parameter: POST /admin/category/category-list {parentId}

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

### `paymentId` ☑︎

- title: 支払方法ID
- doc: dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性
- usages:
  - parameter: GET /admin/payment/payment {paymentId}
  - parameter: PUT /admin/payment/payment {paymentId}
  - parameter: DELETE /admin/payment/payment {paymentId}

### `paymentMethodId`

- usages:
  - parameter: POST /admin/order/create {paymentMethodId}
  - parameter: GET /shopping/confirm {paymentMethodId}

### `paymentMethodName` ☑︎

- title: 支払方法名
- doc: 支払方法の表示名
- usages:
  - parameter: PUT /admin/payment/payment {paymentMethodName}
  - parameter: POST /admin/payment/payment-list {paymentMethodName}

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

### `pluginCode` ☑︎

- title: プラグインコード
- doc: プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする
- usages:
  - parameter: DELETE /admin/plugin {pluginCode}
  - parameter: POST /admin/plugin-disable {pluginCode}
  - parameter: POST /admin/plugin-enable {pluginCode}
  - parameter: POST /admin/plugin-list {pluginCode}

### `pluginName` ☑︎

- title: プラグイン名
- doc: プラグインの表示名。dtb_plugin.name に格納。PluginEntity の pluginName に対応
- usages:
  - parameter: POST /admin/plugin-list {pluginName}

### `pluginVersion` ☑︎

- title: プラグインバージョン
- def: https://schema.org/softwareVersion
- doc: プラグインのバージョン文字列。dtb_plugin.version に格納。PluginEntity の version に対応
- usages:
  - parameter: POST /admin/plugin-list {pluginVersion}

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

### `preOrderId` ☑︎

- title: 仮注文ID
- doc: 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去
- usages:
  - parameter: POST /shopping/checkout {preOrderId}
  - parameter: GET /shopping/confirm {preOrderId}

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

### `price02` ☑︎

- title: 販売価格
- def: https://schema.org/price
- doc: 実際の販売価格（税抜）。税計算・小計計算のベース
- usages:
  - parameter: POST /admin/product {price02}
  - parameter: PUT /admin/product {price02}

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

### `productCodes`

- usages:
  - parameter: POST /admin/product-bulk-status {productCodes}

### `productName` ☑︎

- title: 商品名
- def: https://schema.org/name
- doc: 商品の表示名
- usages:
  - parameter: POST /admin/product {productName}
  - parameter: PUT /admin/product {productName}

### `productStatus` ☑︎

- title: 商品ステータス
- doc: 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示）
- usages:
  - parameter: POST /admin/product {productStatus}
  - parameter: PUT /admin/product {productStatus}
  - parameter: POST /admin/product-bulk-status {productStatus}

### `publishDate` ☑︎

- title: 公開日
- def: https://schema.org/datePublished
- doc: ニュースの公開日時。フロントの表示順を制御
- usages:
  - parameter: PUT /admin/news/news {publishDate}
  - parameter: POST /admin/news/news-list {publishDate}

### `quantity` ☑︎

- title: 数量
- doc: 購入数量。カート明細と受注明細で共通使用
- usages:
  - parameter: POST /cart/item {quantity}
  - parameter: PUT /cart/item {quantity}

### `resetKey` ☑︎

- title: リセットキー
- doc: パスワードリセット用のワンタイムトークン。リセット要求時に生成、使用後にクリア
- usages:
  - parameter: GET /reset {resetKey}
  - parameter: POST /reset {resetKey}

### `roundingType` ☑︎

- title: 端数処理
- doc: 1=四捨五入, 2=切り捨て, 3=切り上げ。受注明細の税額計算時の端数処理方式。TaxRuleで設定
- usages:
  - parameter: POST /admin/tax-rule/tax-rule-list {roundingType}

### `rowId`

- usages:
  - parameter: PUT /admin/sort-no-move {rowId}
  - parameter: PUT /admin/toggle-visible {rowId}

### `ruleMax`

- usages:
  - parameter: PUT /admin/payment/payment {ruleMax}
  - parameter: POST /admin/payment/payment-list {ruleMax}

### `ruleMin`

- usages:
  - parameter: PUT /admin/payment/payment {ruleMin}
  - parameter: POST /admin/payment/payment-list {ruleMin}

### `searchWord` ☑︎

- title: 検索ワード
- doc: フロント検索でヒットさせるためのキーワード。画面には表示されない検索補助データ
- usages:
  - parameter: POST /admin/product {searchWord}
  - parameter: PUT /admin/product {searchWord}

### `secretKey` ☑︎

- title: 認証キー
- doc: 会員アカウントのメール認証トークン。/entry/activate/{secret_key}形式のURLに使用。暗号鍵やAPIシークレットではない。会員登録時にランダム生成
- usages:
  - parameter: POST /entry/activate {secretKey}

### `sessionPrefix`

- usages:
  - parameter: GET /cart {sessionPrefix}
  - parameter: POST /cart/item {sessionPrefix}
  - parameter: PUT /cart/item {sessionPrefix}
  - parameter: DELETE /cart/item {sessionPrefix}
  - parameter: POST /mypage/withdraw {sessionPrefix}
  - parameter: GET /shopping {sessionPrefix}

### `sex` ☑︎

- title: 性別
- def: https://schema.org/gender
- doc: 1=男性, 2=女性, 3=その他, 4=回答しない
- usages:
  - parameter: POST /admin/create-customer {sex}
  - parameter: POST /entry {sex}

### `shopEmail01` ☑︎

- title: 送信元/BCC メールアドレス
- doc: ほぼ全メール種別の送信元（From）兼ショップ控え（BCC）アドレス。注文確認・会員登録・パスワードリセット等で使用
- usages:
  - parameter: POST /admin/base-info {shopEmail01}

### `shopKana` ☑︎

- title: ショップ名フリガナ
- doc: ショップ名のカタカナ読み
- usages:
  - parameter: POST /admin/base-info {shopKana}

### `shopMessage` ☑︎

- title: ショップメッセージ
- doc: 「当サイトについて」ページ（Help/about.twig）に表示する店舗からのメッセージ
- usages:
  - parameter: POST /admin/base-info {shopMessage}

### `shopName` ☑︎

- title: ショップ名
- def: https://schema.org/name
- doc: ショップの表示名。フロント画面のヘッダやメールに表示
- usages:
  - parameter: POST /admin/base-info {shopName}

### `shopNameEng` ☑︎

- title: ショップ名英語
- doc: ショップの英語名。多言語対応やメール署名等で使用
- usages:
  - parameter: POST /admin/base-info {shopNameEng}

### `sortNo` ☑︎

- title: 表示順
- doc: 一覧における並び順
- usages:
  - parameter: PUT /admin/category/category {sortNo}
  - parameter: POST /admin/category/category-list {sortNo}
  - parameter: PUT /admin/sort-no-move {sortNo}

### `stock` ☑︎

- title: 在庫数
- doc: 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる
- usages:
  - parameter: POST /admin/product {stock}
  - parameter: PUT /admin/product {stock}

### `subtotal` ☑︎

- title: 商品小計
- doc: 商品合計金額（税込）。送料・手数料・値引き適用前の商品明細（orderItemType=1）のみの合計。PurchaseFlow.calculateSubTotal()で計算。送料無料条件の判定基準にも使用（お届け先ごとに判定）
- usages:
  - parameter: POST /admin/order/create {subtotal}

### `tagId` ☑︎

- title: タグID
- doc: dtb_tag.id の不透明な文字列ハンドル。BeMart の TagEntity 層は数値ではなく文字列として保持する。Fake 実装は `tg-` プレフィックス付きの英数字を生成し、SQL 実装は dtb_tag.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTagStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TagDeleted) を踏むため、シードハンドル `tg-new` / `tg-sale` は Fake 専用
- usages:
  - parameter: DELETE /admin/tag/tag {tagId}

### `tagName` ☑︎

- title: タグ名
- doc: 商品に付与するタグの表示名
- usages:
  - parameter: POST /admin/tag/tag-list {tagName}

### `tax` ☑︎

- title: 税額
- doc: 受注全体の税額合計（非推奨）。明細ごとの税額集計と差異が生じる場合があるため、正確な税額はOrderItem明細ごとのtaxを集計すべき
- usages:
  - parameter: POST /admin/order/create {tax}

### `taxRate` ☑︎

- title: 適用税率
- doc: 受注明細（OrderItem）の注文時点の適用税率（%）。taxRuleRate（税率ルールマスタ）のスナップショット。TaxProcessorにより受注作成時にコピーされる。軽減税率（8%）と標準税率（10%）が混在可能。optionProductTaxRule有効時は商品規格単位の個別税率を優先適用
- usages:
  - parameter: POST /admin/tax-rule/tax-rule-list {taxRate}

### `taxRuleId` ☑︎

- title: 税率ルールID
- doc: dtb_tax_rule.id の不透明な文字列ハンドル。BeMart の TaxRuleEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_tax_rule.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTaxRuleStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TaxRuleDeleted) を踏むため、シードハンドル（`nonexistent-zzz` 等）は Fake / SQL 双方で 404 が同形
- usages:
  - parameter: DELETE /admin/tax-rule/tax-rule {taxRuleId}

### `trackingNumber` ☑︎

- title: 追跡番号
- doc: 配送業者の荷物追跡番号。confirmUrlと組み合わせて追跡URLを構成
- usages:
  - parameter: PUT /admin/order/tracking-number {trackingNumber}

### `tradeLawBody` ☑︎

- title: 特定商取引法本文
- doc: Wave 8 単一ブロブ投影での特定商取引法ページ本文。EC-CUBE 4.3 では dtb_tradelaw が項目ごとの行（最大15行、tradeLawName / tradeLawDescription / sortNo / displayOrderScreen を持つ）だが、Wave 8 の TradeLawStorageInterface はページ全体を 1 本のテキスト本文として扱う（get は現在の全文を返し、update は全文を置換）。非空・65535文字以内（防御的な MySQL TEXT 上限）。FakeTradeLawStorage はインストーラ既定本文（販売業者 / 所在地 / 連絡先 の3行）をシードする。SQL 実装 SqlTradeLawStorage はこの本文ブロブを単一キャリア行 dtb_tradelaw.id=1 の description 列に格納し（SqlBaseInfoStorage が dtb_base_info.id=1 に対して行うのと同じ単一行シングルトンパターン）、行不在時は Fake と同一のインストーラ既定本文へフォールバックするため Fake/SQL 双方のハイパーメディア契約が同形。TradeLawUpdated の冪等判定（get()->body !== newBody）はバイト単位ロスレス往復を要求するため、改行・コロンを含む本文も単一列格納で完全往復する。Phase 2 で項目ごとの行へ分割予定（その時点で tradeLawName / sortNo / displayOrderScreen が独立して投影される）
- usages:
  - parameter: POST /admin/trade-law {tradeLawBody}

### `usePoint` ☑︎

- title: 使用ポイント
- doc: 注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加
- usages:
  - parameter: PUT /admin/order {usePoint}

### `visible`

- usages:
  - parameter: PUT /admin/delivery/delivery {visible}
  - parameter: POST /admin/delivery/delivery-list {visible}
  - parameter: PUT /admin/payment/payment {visible}
  - parameter: POST /admin/payment/payment-list {visible}
  - parameter: PUT /admin/toggle-visible {visible}
