---
layout: default
title: "ALPS Phase 3 監査 — back-form 検出 & ハイパーメディア反映ギャップ"
---

# ALPS Phase 3 監査 — back-form 検出 & ハイパーメディア反映ギャップ

Phase 3（HTML プレゼンテーション層）の準備として、`alps.json`（~420 ディスクリプタ）が
EC-CUBE 4.3 から忠実に導出されているかを 2 軸で監査した記録。Phase 2b で多数の
ディスクリプタが BeMart の実装 Entity から逆生成（back-form）された疑いがあり、
`CartItem`（commits `a44f296` / `9d06ec3` で修正済み）をキャリブレーション基準とする。

監査対象: `alps.json` 全ディスクリプタ（Audit 1）、および
`tools/ec-cube-source/src/Eccube/Resource/template/default/` 配下のストアフロント
Twig テンプレート（Audit 2）。

## 1. サマリ

| 指標 | 件数 |
|---|---|
| **Audit 1** — `src-entity` 誤タグ（実体は `src-template` 投影） | **3**（`Favorite` / `ShippingAddress` / `Address`、うち `Favorite` は明白、他 2 は要判断） |
| **Audit 2(a)** — 対応 ALPS 遷移ディスクリプタが存在しないアフォーダンス | **6**（`product_delete_favorite` GET 経路 / `admin_customer_resend` / `admin_shipping_update_tracking_number` / `admin_shipping_notify_mail` / `admin_shipping_update_order_status` / `*_sort_no_move`・`*_visible(ity)` の admin 系インライン操作群） |
| **Audit 2(b)** — 遷移は存在するが粒度（配置先ディスクリプタ）が誤り | **5**（`doRemoveFavorite` / `doReorder` / `doCopyProduct` / `goMypageHistory` / `doSelectShippingAddress`+`doUpdateShippingAddress` の per-row 配置欠落） |

監査の徹底度: **ストアフロント顧客向けテンプレートは網羅的に監査**（Cart / Product
detail / Product list / Mypage 全 9 画面 / Shopping 全 9 画面 / Entry / Forgot /
Contact / Help / 共通 Block = 計 **約 38 テンプレートを精査**）。**管理画面は
サンプリング**（Order index/edit, Product index/product, Customer index/edit,
Content news/page, Setting/Shop payment/delivery/tax_rule, Store plugin, Product
tag/class_name = **約 14 テンプレート**）。残りの admin テンプレート(~50)は未監査。

**最も意外だった発見**: `CartItem` と全く同じ back-form パターンが `Favorite` に
残存している。doc に「FavoriteEntity と1:1 (4項目)」と書かれているが、本文の続きで
「productName と unitPrice は表示用スナップショットで dtb_product / dtb_product_class
への JOIN で取得」と自白しており、明らかに 3 テーブルをまたぐ投影。`CartItem` 修正の
完全な双子だが今回まで見落とされていた。

---

## 2. Audit 1 — データ leaf/composition 誤タグ

判定基準: `src-entity` は単一の永続化テーブル（`dtb_*`/`mtb_*`）に grounding された
leaf。複数テーブルをまたぐ投影は `src-template`。

### 誤タグ確定 / 要再タグ

| id | 現タグ | 問題 | あるべきタグ | 実際にまたぐテーブル |
|---|---|---|---|---|
| `Favorite` | `favorite src-entity` | doc が「FavoriteEntity と1:1 (4項目)」と称しつつ「`productName` と `unitPrice` は表示用スナップショットで dtb_product / dtb_product_class への JOIN で取得」と自白。`CartItem` と同型の back-form。productCode/customerId のみが真の dtb_customer_favorite_product 列で、productName/unitPrice は他 2 表からの表示投影。 | `favorite src-template` | `dtb_customer_favorite_product` + `dtb_product` + `dtb_product_class` |

### 要判断（境界ケース — グレーゾーン）

| id | 現タグ | 観察 | 判断 |
|---|---|---|---|
| `ShippingAddress` | `order src-entity` | doc「dtb_shipping 1行... ShippingAddressEntity と1:1 (8項目)」。ただし `orderNo` は `dtb_shipping` の列ではなく `dtb_order.order_no` であり、doc 自身が「SQL 層は order_no → order_id を解決して読み書きする」と記載。1 個の FK ハンドルを露出するだけなので厳密には単一テーブル leaf として許容可能。 | **`src-entity` 維持可**。ただし「Order 配下のインライン Shipping 記述子はテンプレート視点」という doc 注記どおり、`ShippingAddress`（src-entity）と `Order/Shipping`（インライン）の二重定義は整理対象。 |
| `Address` | `account src-entity` | doc「dtb_customer_address 1行... 1:1 (12項目)」。`customerId` は所有者 FK ハンドルだが値は dtb_customer_address.customer_id 列に実在。全 12 項目が単一表の列。 | **`src-entity` 維持**。真正な単一テーブル leaf。 |
| `Product` | `catalog src-entity src-template` | doc「dtb_product と既定 dtb_product_class 1行を平坦化した投影」。明確に 2 表をまたぐが**既に `src-template` を併記済み**。 | **対応済み**（dual-tag）。`CartItem` 修正前の `Product` がこの dual-tag 先例。 |
| `ProductClass` | `catalog src-entity` | doc は「dtb_product_class 1行」で単一表を主張。ただし descriptor に `productName` を composeしている（`productName` は `dtb_product` の列）。 | **要確認**。SQL 実装が productName を JOIN 取得しているなら `Favorite` と同じ back-form。Phase 3 で `ProductClass` を使う画面が出た時点で再検証。今回は **保留**（doc が単一表 grounding を明示し、productName は規約上 product_class 経由で解決という Product の doc 記述と整合するため、明確な違反とまでは言えない）。 |

### 偽陽性として除外したもの

`*Id` 系（`categoryId` / `tagId` / `blockId` / `paymentId` 等）の atomic フィールド
ディスクリプタは「Fake↔SQL 二重性」の長い doc を持つが、いずれも単一の `dtb_*.id`
列に grounding された真正な leaf。`LoginHistory` / `CsvColumnConfig` も単一表
（`dtb_login_history` / `dtb_csv`）。これらは `src-entity` で正しい。

> Audit 1 の正味の結論: **明白な誤タグは `Favorite` 1 件**。`CartItem` 修正の
> 直接の双子であり、Phase 3 の Favorite 系画面（`Mypage/favorite.twig`）着手時に
> 必ず `src-entity`→`src-template` 再タグ + 表示フィールド composition の見直しが必要。

---

## 3. Audit 2 — ハイパーメディア反映ギャップ

ALPS の主目的は状態遷移の捕捉。EC-CUBE テンプレートがレンダリングする
`url()`/`path()`/`<form>`/`<a href>` を列挙し、ALPS の `go*`/`do*` 遷移との
(a) カバレッジ / (b) 配置粒度 を照合した。

### 3.1 ストアフロント（網羅監査）

| EC-CUBE テンプレート:行 | アフォーダンス（route / 操作） | ALPS 遷移 | 配置 | 判定 |
|---|---|---|---|---|
| `Cart/index.twig:147` | `cart_handle_item` operation=remove（per-row） | `doRemoveCartItem` | `CartItem` + `Cart` | OK（Cart 修正済み — 粒度の基準ケース） |
| `Cart/index.twig:176,184` | `cart_handle_item` op=down/up（per-row） | `doUpdateCartItemQuantity` | `CartItem` + `Cart` | OK（Cart 修正済み） |
| `Cart/index.twig:153,159` | `product_detail`（per-row 商品リンク） | `goProduct` | `CartItem` に未配置 | **(b) 粒度** — カート行は商品詳細リンクを per-row で出すが `CartItem` に `goProduct` が無い。`productId` ハンドルだけ追加され遷移が欠落。 |
| `Cart/index.twig:223` | `cart_buystep`（注文手続きへ） | `goShopping` | `Cart` | OK |
| `Product/detail.twig:383` | `product_add_cart` | `doAddCartItem` | `Product` | OK |
| `Product/detail.twig:434` | `product_add_favorite` | `doAddFavorite` | `Product` | OK |
| `Product/detail.twig:443` | `product_delete_favorite`（既お気に入り時の解除） | `doRemoveFavorite` | `Product` に未配置 | **(a)+(b)** — 商品詳細は「お気に入り済み」状態で解除リンクを出すが `Product` に `doRemoveFavorite` が無い。遷移自体は存在するが `CustomerFavoriteProduct` のみに配置され、商品詳細画面の文脈が欠落。 |
| `Product/list.twig:178` | `product_add_cart`（per-row） | `doAddCartItem` | `Product` | OK（`ProductList`→`Product` 経由） |
| `Product/list.twig:155` | `product_detail`（per-row） | `goProduct` | `ProductList`/`Product` | OK |
| `Mypage/index.twig:46` | `mypage_history`（per-row 注文詳細） | `goMypageHistory` | `Mypage` | OK |
| `Mypage/history.twig:191` | `mypage_order`（再注文） | `doReorder` | `MypageHistory` | OK |
| `Mypage/history.twig:78` | `product_detail` | `goProduct` | `MypageHistory` に未配置 | **(b)** — 注文履歴明細から商品詳細へのリンクがあるが `MypageHistory` に `goProduct` 無し。軽微。 |
| `Mypage/favorite.twig:38` | `mypage_favorite_delete`（per-row） | `doRemoveFavorite` | `CustomerFavoriteProduct` | OK（per-row 配置済み） |
| `Mypage/favorite.twig:44` | `product_detail`（per-row） | `goProduct` | `CustomerFavoriteProduct` | OK |
| `Mypage/delivery.twig:38` | `mypage_delivery_new` | `doCreateCustomerAddress` | `CustomerAddressList` | OK |
| `Mypage/delivery.twig:48` | `mypage_delivery_delete`（per-row） | `doDeleteCustomerAddress` | `CustomerAddress` | OK（per-row 配置済み） |
| `Mypage/delivery.twig:59` | `mypage_delivery_edit`（per-row） | `doUpdateCustomerAddress` 相当の編集画面遷移 | `CustomerAddress` | OK |
| `Mypage/change.twig:35` | `mypage_change` | `doUpdateCustomer` | `MypageChange` | OK |
| `Mypage/withdraw.twig:26` | `mypage_withdraw` | `doWithdrawCustomer` | `MypageWithdraw` | OK |
| `Mypage/login.twig:64` | `entry` | `goCustomerRegistration` | `Login` に未配置 | **(b)** — 軽微。`Mypage/login.twig` は会員登録リンクを出すが `Login` ディスクリプタに `goCustomerRegistration` 無し。 |
| `Mypage/login.twig:64` | `forgot` | `doRequestPasswordReset` の画面遷移 | `Login` に未配置 | **(b)** — 軽微。`Login` にパスワード再発行への導線無し（`goForgot` 相当の safe 遷移が ALPS に存在しない）。 |
| `Shopping/index.twig:332` | `shopping_shipping`（per-shipping 変更） | `doSelectShippingAddress` / `goShoppingShipping` | `Shopping` | OK（粗粒度だが Shopping は単一配送先前提なら許容） |
| `Shopping/index.twig:334` | `shopping_shipping_edit`（per-shipping 編集） | `goShoppingShippingEdit` | `Shopping` | OK |
| `Shopping/index.twig:386` | `shopping_shipping_multiple` | `goShoppingShippingMultiple` | `Shopping` | OK |
| `Shopping/confirm.twig:63` | `shopping_checkout` | `doCheckout` | `ShoppingConfirm` | OK |
| `Shopping/login.twig:77` | `shopping_nonmember` | `goShoppingNonMember` | `ShoppingLogin` | OK |
| `Entry/index.twig:28` | `entry` | `doRegisterCustomer` | `CustomerRegistration` | OK |
| `Entry/activate.twig` | (`entry/activate/{secret_key}` 経由の到達) | `doActivateCustomer` | — | OK（URL 直アクセス、テンプレ内リンク無し） |
| `Forgot/index.twig:22` | `forgot` | `doRequestPasswordReset` | `PasswordReset` 系 | OK |
| `Forgot/reset.twig:22` | `forgot/reset/{token}`（POST） | `doResetPassword` | `PasswordReset` | OK |
| `Contact/index.twig:27` | `contact` | `doSubmitContact` | `ContactForm` | OK |
| `Block/footer.twig:15-24` | `help_about`/`help_privacy`/`help_tradelaw`/`contact` | `goHelpAbout` 他 / `goContactForm` | （Block は ALPS 非モデル化） | 注記 — グローバルナビ。Block は ALPS にディスクリプタ無し（設計上）。 |
| `Block/login.twig:29` | `logout` | `doLogout` | （Block 非モデル化） | OK（遷移は存在） |
| `Block/search_product.twig:14` | `product_list`（検索 GET） | `goProductList` | （Block 非モデル化） | OK |

### 3.2 管理画面（サンプリング監査）

| EC-CUBE テンプレート:行 | アフォーダンス（route） | ALPS 遷移 | 判定 |
|---|---|---|---|
| `admin/Order/index.twig:498` | `admin_order_edit`（per-row） | `goOrder` | OK |
| `admin/Order/index.twig:540` | `admin_shipping_update_tracking_number`（per-row インライン伝票番号更新） | **なし** | **(a)** — 出荷伝票番号のインライン更新遷移が ALPS に無い。`doImportShippingCsv` は CSV 一括版のみ。 |
| `admin/Order/index.twig:493,557` | `admin_shipping_notify_mail` / `_preview_notify_mail`（per-row 出荷通知メール） | **なし** | **(a)** — 出荷通知メール送信遷移が無い。`doSendOrderMail` は受注メール手動送信で別物。 |
| `admin/Order/index.twig:494,572` | `admin_shipping_update_order_status`（per-row ステータス更新） | `doUpdateOrderStatus` 相当 | **(b)** — Order レベルの `doUpdateOrderStatus` はあるが、一覧の per-shipping インライン操作としては未モデル化。`Shipping` インライン記述子に遷移無し。 |
| `admin/Order/index.twig:440,443` | `admin_order_export_order` / `_export_shipping` | `goExportOrder` / `goExportShipping` | OK |
| `admin/Order/index.twig:40,47` | `admin_order_bulk_delete` / `_export_pdf` | `doBulkDeleteOrder` / `goExportOrderPdf` | OK |
| `admin/Order/edit.twig:1028` | `admin_order_mail` | `doSendOrderMail` | OK |
| `admin/Product/index.twig:276-283` | `admin_product_bulk_product_status` | `doBulkUpdateProductStatus` | OK |
| `admin/Product/index.twig:424` | `admin_product_product_copy`（per-row） | `doCopyProduct` | **(b)** — `doCopyProduct` は ALPS に存在するが、どの `src-template` ディスクリプタの descriptor 配列にも `#doCopyProduct` への href が無い（`Product` も `ProductList` も未参照）。孤立した遷移。 |
| `admin/Product/index.twig:301,304` | `admin_product_export` / CSV 設定 | `goExportProduct` / `doUpdateCsv` 経由 | OK |
| `admin/Product/product.twig:639` | `admin_product_product_class` | （規格編集ポップアップ） | 注記 — `ProductClass` の編集 UI。ALPS に専用遷移無し（`doUpdateProduct` に内包の解釈も可）。サンプリング外の判断保留。 |
| `admin/Customer/index.twig:304` | `admin_customer_resend`（per-row 仮会員へ認証メール再送） | **なし** | **(a)** — 仮会員への認証メール再送遷移が ALPS に無い。 |
| `admin/Customer/index.twig:335` | `admin_customer_delete`（per-row） | `doDeleteCustomer` | OK |
| `admin/Customer/edit.twig:327` | `admin_customer_delivery_delete`（per-row） | `doDeleteCustomerAddress` 相当 | OK（粒度は per-row だが ALPS は account 文脈で配置済み） |
| `admin/Content/news.twig` | `admin_content_news_new/edit/delete` | `doCreateNews`/`doUpdateNews`/`doDeleteNews` | OK |
| `admin/Content/page.twig` | `admin_content_page_new/edit/delete` | `doCreatePage`/`doUpdatePage`/`doDeletePage` | OK |
| `admin/Setting/Shop/payment.twig:48,178` | `admin_setting_shop_payment_sort_no_move` / `_visible` | **なし** | **(a)** — 並び順 D&D 更新と表示/非表示トグルの遷移が無い。`doUpdatePayment` に内包の解釈も可だが、専用 idempotent 操作として未モデル化。 |
| `admin/Setting/Shop/delivery.twig:37,134` | `admin_setting_shop_delivery_sort_no_move` / `_visibility` | **なし** | **(a)** — 同上（配送方法）。 |
| `admin/Setting/Shop/tax_rule.twig` | `admin_setting_shop_tax_new/delete` | `doCreateTaxRule`/`doDeleteTaxRule` | OK |
| `admin/Store/plugin.twig:53` | `admin_store_plugin_install` | `doInstallPlugin` | OK |
| `admin/Product/tag.twig:45` | `admin_product_tag_sort_no_move` | **なし** | **(a)** — タグ並び順更新の遷移無し（`*_sort_no_move` 系の共通欠落）。 |
| `admin/Product/class_name.twig:59` | `admin_product_class_name_sort_no_move` | **なし** | **(a)** — 同上。 |

> **`*_sort_no_move` / `*_visible(ity)` 系の体系的欠落**: 管理画面の一覧画面は
> ほぼ全てに「D&D 並び替え」と「表示トグル」のインライン idempotent 操作を持つが、
> ALPS にはこれらの遷移ディスクリプタが一切無い（payment / delivery / tag /
> class_name / class_category / news 等で確認）。Audit 2(a) のカウントでは
> 「`*_sort_no_move`・`*_visible` 系」として 1 件に集約しているが、実際には
> 10+ ルートにわたる。Phase 3 で admin 一覧画面を移植する際、まとめて新規遷移
> ディスクリプタ群が必要になる。

---

## 4. リメディエーション・グルーピング（Phase 3 ページバッチ計画用）

Phase 3 の ~138 ページバッチを「重いスライス」と「軽いテンプレ移植」に切り分ける
ためのグルーピング。

### グループ A — `src-entity`→`src-template` 再タグ + 表示フィールド composition 見直し（重い垂直スライス）

| 対象画面バッチ | 必要作業 | 根拠 |
|---|---|---|
| **Favorite 系**（`Mypage/favorite.twig`, `CustomerFavoriteProduct*`） | `Favorite` を `src-template` へ再タグ。productName/unitPrice が JOIN 投影であることを doc に明記（back-form 言語を除去）。`CartItem` 修正（`a44f296`）と完全に同型の作業 — SQL JOIN / Fake 再導出 / Entity 表示フィールドの enrich を伴う。 | Audit 1 |

> グループ A は **1 スライスのみ**。Cart 修正の経験がそのまま適用できるため、
> 重いが手順は確立済み。

### グループ B — 新規遷移ディスクリプタが必要（中量スライス）

| 対象画面バッチ | 必要な新規 `go*`/`do*` | 根拠 |
|---|---|---|
| **admin 一覧画面群**（payment / delivery / tag / class_name / class_category list） | `doMovePaymentSortNo` 系（並び替え）、`doTogglePaymentVisible` 系（表示トグル）— 各マスタごと、または汎用 `doSortNoMove` / `doToggleVisible` の抽象遷移 | Audit 2(a) `*_sort_no_move`/`*_visible` |
| **admin 受注一覧**（`admin/Order/index.twig`） | `doUpdateTrackingNumber`（出荷伝票番号インライン更新）、`doSendShippingNotifyMail`（出荷通知メール）。配置先は `Order` 配下の `Shipping` インライン記述子。 | Audit 2(a) `admin_shipping_*` |
| **admin 会員一覧**（`admin/Customer/index.twig`） | `doResendActivationMail`（仮会員への認証メール再送）。配置先 `Customer` / `CustomerList`。 | Audit 2(a) `admin_customer_resend` |

### グループ C — 既存遷移の再配置のみ（軽量 — テンプレ移植時に href 追加するだけ）

| 対象画面バッチ | 必要な再配置（既存遷移を descriptor 配列へ追加） | 根拠 |
|---|---|---|
| **Cart**（`Cart/index.twig`） | `CartItem` に `#goProduct` を追加（per-row 商品詳細リンク — `productId` ハンドルは追加済みだが遷移が欠落） | Audit 2(b) |
| **Product 詳細**（`Product/detail.twig`） | `Product` に `#doRemoveFavorite` を追加（お気に入り済み状態の解除リンク） | Audit 2(b) |
| **Mypage 注文履歴詳細**（`Mypage/history.twig`） | `MypageHistory` に `#goProduct` を追加 | Audit 2(b) |
| **顧客ログイン**（`Mypage/login.twig`） | `Login` に `#goCustomerRegistration` を追加（パスワード再発行への safe 遷移 `goForgot` が ALPS に無いため、それは別途グループ B 扱い） | Audit 2(b) |
| **admin 商品一覧**（`admin/Product/index.twig`） | `Product`（または `ProductList`）に `#doCopyProduct` を追加 — 遷移は定義済みだが孤立 | Audit 2(b) |

### バッチ・スコープ見積り

- **重スライス**: 1（グループ A — Favorite。Cart 修正の再演）
- **中スライス**: 3 バッチ（グループ B — admin 一覧の sort/visible 群 / 出荷操作 / 会員再送）
- **軽スライス**: 5 画面（グループ C — 既存遷移の href 追加のみ。テンプレ移植と同時に処理可能）

> Phase 3 の大半（ストアフロント顧客向け）は ALPS カバレッジが健全で、軽量な
> 再配置で足りる。重い再タグ作業は **Favorite 1 件に限定**される。管理画面の
> `*_sort_no_move`/`*_visible` 欠落は体系的だが、汎用遷移ディスクリプタ 2 個
> （`doSortNoMove` / `doToggleVisible`）を導入すれば一括解消できる。

---

## 付録 — 監査カバレッジの明示

- **ストアフロント（網羅）**: Cart/index, Product/detail, Product/list,
  Mypage/{index,navi,history,favorite,delivery,delivery_edit,change,withdraw,login},
  Shopping/{index,confirm,complete,login,nonmember,shipping,shipping_edit,
  shipping_multiple,shopping_error}, Entry/{index,confirm,complete,activate},
  Forgot/{index,reset,complete}, Contact/{index,confirm,complete},
  Help/{about,guide,tradelaw,agreement,privacy}, index,
  Block/{header,footer,cart,login,search_product,category,news,new_item}。
- **管理画面（サンプリング）**: admin/index, Order/{index,edit},
  Product/{index,product}, Customer/{index,edit}, Content/{news,page},
  Setting/Shop/{payment,delivery,tax_rule}, Store/plugin, Product/{tag,class_name}。
- **未監査**: admin の残り ~50 テンプレート（Content/layout・block 編集系、
  Store/plugin 詳細系、Setting/System 系、Mail 系、CSV 系の細部）。これらは
  Phase 3 で当該ページを移植する際に同じ手法で個別監査が必要。
- メールテンプレート（`Mail/*.twig`）はハイパーメディアを持たないため対象外。
