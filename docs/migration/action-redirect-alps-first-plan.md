# ActionRedirect 45件の ALPS-first 実装計画

作成日: 2026-05-27  
前提commit: `5078c8f Restore original routes and document coverage`

## Summary

`docs/migration/original-route-coverage.md` の現状では、original route name 250件のうち **ActionRedirect残りは45 route rows**。
次の作業単位では、これらを **ALPS descriptor → RouteTable → Resource/Twig → test** の順で実装し、`page://self/action-redirect` / `page://self/admin/action-redirect` への通常到達をなくす。

現状値:

| 指標 | 件数 |
|---|---:|
| original route name | 250 |
| 完了 | 110 |
| 部分 | 54 |
| 未実装 | 86 |
| ActionRedirect method entry | 56 |
| ActionRedirect route rows | 45 |

## ゴール

```text
ActionRedirect残り45 route rowsについて、各routeがALPS上の状態/遷移に対応し、RouteTableから実Resourceへ直接dispatchされること。coverage上の「ActionRedirect残り」が0になり、HTML公開methodはGET/POSTのみ、ブラウザリンク/フォームクロールで404/405/5xx/Fatal/未実装表示が0であること。
```

## ALPS-first のルール

1. **先に ALPS を確認・追加する**
   - GET表示は `go*` safe transition。
   - 作成・実行は `do*` unsafe transition。
   - 更新・削除・並び替え・表示切替は `do*` idempotent transition。
   - ALPSにはURL/HTTP method/PHP class名を書かない。
2. **RouteTable は ALPS descriptor を持つ実Resourceだけへ向ける**
   - `ActionRedirect` への新規追加は禁止。
   - HTML公開methodはGET/POSTのみ。
   - original PUT/DELETE は POST route + `dispatchMethod: put/delete` にする。
3. **Resource は状態遷移を語る**
   - 汎用redirectではなく、遷移名に対応するResourceへ接続する。
   - 既存Final/Query/Commandがある場合は流用し、ない場合はFake/SQL parityを明記して追加する。
4. **完了判定はcoverageで見る**
   - `php /Users/akihito/git/be-bemart/bin/generate-original-route-coverage.php`
   - `grep -c 'ActionRedirect残り' docs/migration/original-route-coverage.md` が0。

## 推奨Stacked PR構成

| PR | 対象 | route数 | 狙い |
|---|---|---:|---|
| AR-1 | shopping shipping flow | 4 | フロント購入フローのredirect撤去。path mismatchも同時に潰す |
| AR-2 | admin low-risk forms | 5 | 変更パスワード、キャッシュ、メンテ、2FA。副作用が局所的 |
| AR-3 | admin content/customer/order edit | 5 | 既存編集画面のPOSTを実Resourceへ接続 |
| AR-4 | product/catalog operations | 8 | 商品・規格・タグのcopy/export/sort/visibility |
| AR-5 | shop setting operations | 10 | カレンダー、配送、メール、受注ステータス、支払、特商法 |
| AR-6 | system setting operations | 6 | 権限、マスタデータ、メンバー順序、セキュリティ |
| AR-7 | shipping/template operations | 7 | 出荷通知/更新とテンプレート選択/削除/DL/install |

各PRの作業順:

1. 対象routeのALPS descriptorを確認。不足なら `alps.json` に追加。
2. `AlpsRouteMap` の mapping を確定。
3. `RouteTable` の対象routeを `ActionRedirect` から実Resourceへ置換。
4. Resource / Final / Query / Command / Twig を必要最小限で実装。
5. coverage再生成。
6. 以下を通してcommit。

```bash
asd --validate alps.json
php /Users/akihito/git/be-bemart/bin/generate-original-route-coverage.php
vendor/bin/phpunit --filter 'RouterTest|TemplateRouteCoverageTest|AlpsRouteCoverageTest' --colors=never
composer test:http -- --colors=never
composer test:fake -- --colors=never
composer psalm -- --output-format=console
```

## 対象route一覧

| PR | route name | ALPS ID | original method | original path | 現在のActionRedirect | 実装方針 |
|---|---|---|---|---|---|---|
| AR-1 | `shopping_shipping` | `goShoppingShipping` / `doSelectShippingAddress` | GET/POST | `/shopping/shipping/{id}` | `page://self/action-redirect` | original pathへ寄せ、POSTは配送先選択Resourceへ直接dispatch |
| AR-1 | `shopping_shipping_edit` | `goShoppingShippingEdit` / `doUpdateShippingAddress` | GET/POST | `/shopping/shipping_edit/{id}` | `page://self/action-redirect` | original pathへ寄せ、POSTは配送先更新Resourceへ直接dispatch |
| AR-1 | `shopping_shipping_multiple` | `goShoppingShippingMultiple` / `doSelectShippingAddress` | GET/POST | `/shopping/shipping_multiple` | `page://self/action-redirect` | 複数配送の表示/反映Resourceを明示化 |
| AR-1 | `shopping_shipping_multiple_edit` | `goShoppingShippingMultiple` / `doUpdateShippingAddress` | GET/POST | `/shopping/shipping_multiple_edit` | `page://self/action-redirect` | 複数配送先編集POSTを専用Resourceへ接続 |
| AR-2 | `admin_change_password` | `goChangePassword` / `doChangePassword` | GET/POST | `/admin/change_password` | `page://self/admin/action-redirect` | POSTを変更パスワードFinal/Resourceへ接続 |
| AR-2 | `admin_content_cache` | `goContentCache` / `doClearCache` | GET/POST | `/admin/content/cache` | `page://self/admin/action-redirect` | キャッシュ削除をno-opではなく明示Resourceにする |
| AR-2 | `admin_content_maintenance` | `goMaintenance` / `doToggleMaintenance` | GET/POST | `/admin/content/maintenance` | `page://self/admin/action-redirect` | メンテナンス状態更新Resourceを実装 |
| AR-2 | `admin_two_factor_auth` | `goTwoFactorAuth` / `doVerifyTwoFactorAuth` | GET/POST | `/admin/two_factor_auth` | `page://self/admin/action-redirect` | 2FA検証POSTを専用Resourceへ接続 |
| AR-2 | `admin_two_factor_auth_set` | `goTwoFactorAuthSet` / `doSetTwoFactorAuth` | GET/POST | `/admin/two_factor_auth/set` | `page://self/admin/action-redirect` | 2FA設定POSTを専用Resourceへ接続 |
| AR-3 | `admin_content_layout_new` | `goLayout` / `doUpdateLayout` | GET/POST | `/admin/content/layout/new` | `page://self/admin/action-redirect` | 新規レイアウトPOSTをLayout Resourceへ接続。ALPS IDはcreate/update分離を検討 |
| AR-3 | `admin_customer_delivery_new` | `goCustomerAddress` / `doCreateCustomerAddress` | GET/POST | `/admin/customer/{id}/delivery/new` | `page://self/admin/action-redirect` | 顧客配送先新規Resourceへ接続 |
| AR-3 | `admin_customer_edit` | `goCustomer` / `doUpdateCustomer` | GET/POST | `/admin/customer/{id}/edit` | `page://self/admin/action-redirect` | 顧客更新POSTを既存Customer Resourceへ接続 |
| AR-3 | `admin_order_edit` | `goOrder` / `doUpdateOrder` | GET/POST | `/admin/order/{id}/edit` | `page://self/admin/action-redirect` | 受注編集POSTをOrder edit/update Resourceへ接続 |
| AR-3 | `admin_order_bulk_delete` | `goOrderList` / `doBulkDeleteOrder` | POST | `/admin/order/bulk_delete` | `page://self/admin/action-redirect` | 一括削除POSTをBulkDelete Resourceへ直接dispatch |
| AR-4 | `admin_product_bulk_product_status` | `goProductList` / `doBulkUpdateProductStatus` | POST | `/admin/product/bulk/product-status/{id}` | `page://self/admin/action-redirect` | 商品ステータス一括更新Resourceへ接続 |
| AR-4 | `admin_product_class_category_export` | `goExportClassCategory` | GET | `/admin/product/class_category/export/{class_name_id}` | `page://self/admin/action-redirect` | CSV body/headerを返すexport Resourceにする |
| AR-4 | `admin_product_class_category_sort_no_move` | `goClassCategoryList` / `doSortNoMove` | POST | `/admin/product/class_category/sort_no/move` | `page://self/admin/action-redirect` | 汎用SortNoMoveではなくclassCategory文脈のparamを固定 |
| AR-4 | `admin_product_class_category_visibility` | `goClassCategoryList` / `doToggleVisible` | PUT→POST | `/admin/product/class_category/{class_name_id}/{id}/visibility` | `page://self/admin/action-redirect` | 表示切替ResourceへPOST→PUT dispatch |
| AR-4 | `admin_product_class_name_export` | `goExportClassName` | GET | `/admin/product/class_name/export` | `page://self/admin/action-redirect` | CSV export Resourceを実装 |
| AR-4 | `admin_product_class_name_sort_no_move` | `goClassNameList` / `doSortNoMove` | POST | `/admin/product/class_name/sort_no/move` | `page://self/admin/action-redirect` | className順序更新として接続 |
| AR-4 | `admin_product_product_copy` | `goProduct` / `doCopyProduct` | POST | `/admin/product/product/{id}/copy` | `page://self/admin/action-redirect` | GET退避をやめ、POST copy Resourceのみへ整理 |
| AR-4 | `admin_product_tag_sort_no_move` | `goTagList` / `doSortNoMove` | POST | `/admin/product/tag/sort_no/move` | `page://self/admin/action-redirect` | tag順序更新Resourceへ接続 |
| AR-5 | `admin_setting_shop_calendar` | `goCalendar` / `doUpdateCalendar` | GET/POST | `/admin/setting/shop/calendar` | `page://self/admin/action-redirect` | カレンダー更新Resourceへ接続 |
| AR-5 | `admin_setting_shop_calendar_delete` | `goCalendar` / `doDeleteCalendarHoliday` | DELETE→POST | `/admin/setting/shop/calendar/{id}/delete` | `page://self/admin/action-redirect` | original pathへ寄せ、削除Resourceへ接続 |
| AR-5 | `admin_setting_shop_calendar_new` | `goCalendar` / `doCreateCalendarHoliday` | GET/POST | `/admin/setting/shop/calendar/new` | `page://self/admin/action-redirect` | 休日作成Resourceへ接続 |
| AR-5 | `admin_setting_shop_delivery_sort_no_move` | `goDeliveryList` / `doSortNoMove` | POST | `/admin/setting/shop/delivery/sort_no/move` | `page://self/admin/action-redirect` | delivery順序更新へ接続 |
| AR-5 | `admin_setting_shop_delivery_visibility` | `goDeliveryList` / `doToggleVisible` | PUT→POST | `/admin/setting/shop/delivery/{id}/visibility` | `page://self/admin/action-redirect` | delivery表示切替へ接続 |
| AR-5 | `admin_setting_shop_mail_delete` | `goMailTemplateList` / `doDeleteMailTemplate` | DELETE→POST | `/admin/setting/shop/mail/{id}/delete` | `page://self/admin/action-redirect` | original pathへ寄せ、メールテンプレ削除Resourceへ接続 |
| AR-5 | `admin_setting_shop_order_status` | `goOrderStatusList` / `doUpdateOrderStatusList` | GET/POST | `/admin/setting/shop/order_status` | `page://self/admin/action-redirect` | 受注ステータス一覧更新Resourceへ接続 |
| AR-5 | `admin_setting_shop_payment_sort_no_move` | `goPaymentList` / `doSortNoMove` | POST | `/admin/setting/shop/payment/sort_no/move` | `page://self/admin/action-redirect` | payment順序更新へ接続 |
| AR-5 | `admin_setting_shop_payment_visible` | `goPaymentList` / `doToggleVisible` | PUT→POST | `/admin/setting/shop/payment/{id}/visible` | `page://self/admin/action-redirect` | payment表示切替へ接続 |
| AR-5 | `admin_setting_shop_tradelaw` | `goTradeLawList` / `doUpdateTradeLaw` | GET/POST | `/admin/setting/shop/tradelaw` | `page://self/admin/action-redirect` | 特商法更新Resourceへ接続 |
| AR-6 | `admin_setting_system_authority` | `goAuthorityRole` / `doUpdateAuthorityRole` | GET/POST | `/admin/setting/system/authority` | `page://self/admin/action-redirect` | 権限ロール更新Resourceへ接続 |
| AR-6 | `admin_setting_system_masterdata` | `goMasterData` / `doSelectMasterData` | GET/POST | `/admin/setting/system/masterdata` | `page://self/admin/action-redirect` | マスタデータ選択POSTを表示状態へ反映 |
| AR-6 | `admin_setting_system_masterdata_edit` | `goMasterData` / `doUpdateMasterData` | GET/POST | `/admin/setting/system/masterdata/edit` | `page://self/admin/action-redirect` | マスタデータ更新Resourceへ接続 |
| AR-6 | `admin_setting_system_member_down` | `goMemberList` / `doSortNoMove` | PUT→POST | `/admin/setting/system/member/{id}/down` | `page://self/admin/action-redirect` | original pathへ寄せ、member順序更新Resourceへ接続 |
| AR-6 | `admin_setting_system_member_up` | `goMemberList` / `doSortNoMove` | PUT→POST | `/admin/setting/system/member/{id}/up` | `page://self/admin/action-redirect` | original pathへ寄せ、member順序更新Resourceへ接続 |
| AR-6 | `admin_setting_system_security` | `goSecurity` / `doUpdateSecurity` | GET/POST | `/admin/setting/system/security` | `page://self/admin/action-redirect` | セキュリティ設定更新Resourceへ接続 |
| AR-7 | `admin_shipping_notify_mail` | `goOrder` / `doSendShippingNotifyMail` | PUT→POST | `/admin/shipping/notify_mail/{id}` | `page://self/admin/action-redirect` | 出荷通知メール送信Resourceへ接続 |
| AR-7 | `admin_shipping_update_order_status` | `goOrderList` / `doUpdateOrderStatus` | PUT→POST | `/admin/shipping/{id}/order_status` | `page://self/admin/action-redirect` | 出荷起点の受注ステータス更新へ接続 |
| AR-7 | `admin_shipping_update_tracking_number` | `goOrder` / `doUpdateTrackingNumber` | PUT→POST | `/admin/shipping/{id}/tracking_number` | `page://self/admin/action-redirect` | 追跡番号更新Resourceへ接続 |
| AR-7 | `admin_store_template` | `goTemplateList` / `doSelectTemplate` | GET/POST | `/admin/store/template` | `page://self/admin/action-redirect` | 使用テンプレート選択Resourceへ接続 |
| AR-7 | `admin_store_template_delete` | `goTemplateList` / `doDeleteTemplate` | DELETE→POST | `/admin/store/template/{id}/delete` | `page://self/admin/action-redirect` | テンプレート削除Resourceへ接続 |
| AR-7 | `admin_store_template_download` | `goTemplateList` / `doDownloadTemplate` | GET | `/admin/store/template/{id}/download` | `page://self/admin/action-redirect` | download body/headerを返すResourceへ接続 |
| AR-7 | `admin_store_template_install` | `goTemplateInstall` / `doInstallTemplate` | GET/POST | `/admin/store/template/install` | `page://self/admin/action-redirect` | template install Resourceへ接続 |

## 各PRの完了条件

- 対象routeに対応する ALPS descriptor が存在し、`AlpsRouteMap` で参照される。
- 対象routeの `Resource` に `action-redirect` が含まれない。
- `docs/migration/original-route-coverage.md` 上で対象routeの「ActionRedirect残り」が消える。
- 変更対象のHTMLに `method="put"`, `method="delete"`, `data-method="put"`, `data-method="delete"` が増えない。
- `composer test:http -- --colors=never` が通る。
- 対象画面をブラウザで開き、Fatal / 404 / 405 / 未実装表示がない。

## 先に作るべき追加gate

次PRの最初に、次のcoverage testを追加すると以後の作業が安全になる。

1. `RouteTable` 内の `page://self/action-redirect` / `page://self/admin/action-redirect` 件数を数えるテスト。
2. `docs/migration/original-route-coverage.md` 再生成後に `ActionRedirect残り` 件数が増えていないことを検知するテスト。
3. `RouteTable` の path に unresolved placeholder が残る場合、`Route::generate()` のdefaultかsample paramで埋まることを検証するテスト。

## 今回やらないもの

- original route name未実装86件の全面実装。
- SQL suite のMariaDB依存green化。
- 外部オーナーズストア通信や実ファイルアップロードの完全再現。
- `#[Alps]` 属性をResource class/methodへ付けるWave。今回のgateは `RouteTable` ↔ `alps.json` を先に固定する。
