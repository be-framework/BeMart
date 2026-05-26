# このセッションで終了させるべき機能実装

作成日: 2026-05-27

## 結論

このセッションでは、全 250 original route name の完全制覇ではなく、次の **2本** を終了条件にするのが妥当。

1. **EC-CUBE original path 互換の復元**
   - 既に Resource / ALPS があるのに、BeMart 側 path が `/admin_product_*` のような仮 path のままになっている箇所を、Symfony original path に戻す。
   - これは「機能実装」そのものではないが、以後の機能実装・ブラウザ確認・ALPS対応の前提になる。

2. **フロント / マイページ / 購入フローの missing route を完了**
   - 既に Resource や Be Final が存在するのに RouteTable / ALPS 連携から漏れている顧客向け route を閉じる。
   - 管理画面の大きな未実装群より、5時間で完了確認まで持っていける可能性が高い。

## 実行前の現状

`docs/migration/original-route-coverage.md` 生成時点:

| 指標 | 件数 |
|---|---:|
| original route name | 250 |
| 完了 | 50 |
| 部分 | 106 |
| 未実装 | 94 |
| ActionRedirect method entry | 56 |

## 実行結果

今回の対象だった original path 互換復元と顧客向け missing route 7件は完了した。

`php /Users/akihito/git/be-bemart/bin/generate-original-route-coverage.php` 再生成後:

| 指標 | 件数 |
|---|---:|
| original route name | 250 |
| 完了 | 110 |
| 部分 | 54 |
| 未実装 | 86 |
| original path未一致 | 16 |
| ActionRedirect method entry | 56 |
| ActionRedirect route rows | 45 |

今回完了した顧客向け route:

| route name | ALPS ID | 実装状況 |
|---|---|---|
| `product_add_favorite` | `goProduct` / `doAddFavorite` | original path `/products/add_favorite/{id}` を GET/POST で登録し、既存商品表示・お気に入り追加Resourceへ接続 |
| `product_delete_favorite` | `doRemoveFavorite` | HTML POST route として登録し、内部 dispatch は `onDelete()` に接続 |
| `mypage_order` | `doReorder` | original PUT を HTML POST に正規化し、再注文Resourceへ接続 |
| `mypage_withdraw_confirm` | `goMypageWithdrawConfirm` / `doWithdrawCustomer` | `mode=confirm` POST を退会確認画面へ安全に振り分け |
| `shopping_customer` | `doSubmitNonMember` | original POST `/shopping/customer` を非会員購入者情報Resourceへ接続 |
| `shopping_redirect_to` | `doShoppingRedirectTo` | 購入フロー内のローカル安全redirect専用Resourceを追加 |
| `cart_buystep` | `doSelectCartForCheckout` | 選択カートから購入手続きへ進む専用Resourceを追加 |

追加で完了に上げた admin route:

| route name | ALPS ID | 実装状況 |
|---|---|---|
| `admin_store_plugin` | `goPluginList` | original path `/admin/store/plugin` をPlugin一覧Resourceへ接続 |

追加したALPS descriptor:

- `doSelectCartForCheckout`
- `doShoppingRedirectTo`
- `goMypageWithdrawConfirm`
- `MypageWithdrawConfirm`

検証済み:

- `asd --validate alps.json`
- `vendor/bin/phpunit --filter 'RouterTest|TemplateRouteCoverageTest|AlpsRouteCoverageTest' --colors=never`
- `vendor/bin/phpunit --filter 'ProductResourceTest|Favorite|Reorder|Withdraw|ShoppingNonMember|Cart' --colors=never`
- `vendor/bin/phpunit tests/Resource --filter HtmlRender --colors=never`

次PR/作業単位:

- [ActionRedirect 45件の ALPS-first 実装計画](action-redirect-alps-first-plan.md)

## 今回終了対象 A: original path 互換

### 対象

`RouteTable` に route name はあるが、path が Symfony original と違うもの。

例:

| route name | original path | 現状 BeMart path |
|---|---|---|
| `admin_product_product_edit` | `/admin/product/product/{id}/edit` | `/admin_product_product_edit` |
| `admin_content_block_edit` | `/admin/content/block/{id}/edit` | `/admin_content_block_edit` |
| `admin_setting_shop_payment_edit` | `/admin/setting/shop/payment/{id}/edit` | `/admin_setting_shop_payment_edit` |

### なぜ今回やるか

- ALPSはURLを持たないため、このズレはALPS検証では検出できない。
- ブラウザリンククロールは現行 `RouteTable` の世界では通るが、EC-CUBE互換URLでは通らない。
- route/path互換を直してからでないと、「機能が実装済みか未実装か」の判定が歪む。

### 終了条件

- `docs/migration/original-route-coverage.md` の `original path未一致` が大幅に減る。
- 既存の `url()/path()` helper が新pathを生成しても、主要画面リンククロールが通る。
- HTML公開methodは引き続き GET/POST のみ。

## 今回終了対象 B: 顧客向け missing route

5時間内に完了を狙う候補は次の7 route。

| route name | original method | original path | 既存資産 | 必要対応 | 1行説明 |
|---|---|---|---|---|---|
| `product_add_favorite` | GET/POST | `/products/add_favorite/{id}` | `Mypage\Favorite::onPost`, `doAddFavorite` | RouteTable + AlpsRouteMap + Product側導線確認 | 商品をお気に入りに追加する |
| `product_delete_favorite` | DELETE | `/products/delete_favorite/{id}` | `Mypage\Favorite::onDelete`, `doRemoveFavorite` | HTML POST化 + dispatch delete | 商品をお気に入りから削除する |
| `mypage_order` | PUT | `/mypage/order/{order_no}` | `Mypage\Reorder::onPost`, `doReorder` | HTML POST化 + route追加 | 購入履歴から再注文する |
| `mypage_withdraw_confirm` | GET/POST | `/mypage/withdraw` | `Mypage\WithdrawConfirm` Resource / template | `mode=confirm`相当のroute/ALPS整理 | 退会実行前の最終確認を表示する |
| `shopping_customer` | POST | `/shopping/customer` | `Shopping\NonMember::onPost`, `doSubmitNonMember` | route alias追加 | 非会員購入者情報を更新する |
| `shopping_redirect_to` | POST | `/shopping/redirect_to` | `Shopping` formの `redirect_to` | 安全redirect Resourceまたは既存Resource接続 | 購入フロー内の戻り先へ遷移する |
| `cart_buystep` | GET | `/cart/buystep/{cart_key}` | `Cart` / `Shopping` | カート選択後の安全遷移を実装 | 選択したカートで購入手続きへ進む |

### なぜ今回やるか

- 顧客が触る導線で、管理画面のCSV/ファイル/プラグイン系より優先度が高い。
- 既に Resource / Be Final / ALPS descriptor が存在するものが多く、RouteTable漏れが主因。
- 実装完了後にブラウザで確認しやすい。

### 終了条件

- 上記7 route が `未実装` から `完了` または少なくとも `部分` ではなく実Resource到達になる。
- `product_add_favorite`, `product_delete_favorite`, `mypage_order`, `shopping_customer` は既存 Be Final を通る。
- `mypage_withdraw_confirm` はALPS descriptorを追加し、確認画面として到達できる。
- `cart_buystep`, `shopping_redirect_to` は `ActionRedirect` ではなく、意図したResourceまたは専用安全遷移Resourceへ接続する。

## 今回やらない方がよいもの

### 管理画面の重い未実装

| 領域 | 理由 |
|---|---|
| `admin_content_file*` | ファイル管理、アップロード、削除、閲覧、ダウンロードを扱うため、権限・パス安全性・保存先設計が必要 |
| `admin_product_csv_split*` / `*_csv_import` | CSV分割・取込・進捗管理・SQL/fake parity が必要 |
| `admin_product_image_*` / `admin_payment_image_*` | 画像一時保存、revert、upload lifecycle が必要 |
| `admin_order_search_*` | 受注編集画面内の検索API群。画面JSとの結合確認が必要 |
| `admin_store_plugin_api_*` | 外部オーナーズストア連携。現行方針では対象外候補 |
| `install_*` | インストーラー。BeMartランタイム移植とは別スコープ |

### ActionRedirect 46件の一括解消

一括でやると、配送、受注、商品、設定、2FA、テンプレート管理が混在する。
今回の5時間では、顧客向け route と path互換を先に閉じる方がレビュー可能性が高い。

## 推奨作業順

1. `RouteTable` の existing route path を Symfony original pathへ寄せる。
2. `AlpsRouteMap` に顧客向け missing route 7件を追加する。
3. `alps.json` に不足descriptorを追加する。
4. 既存Resourceへroute接続する。
5. `php bin/generate-original-route-coverage.php` を再実行。
6. `vendor/bin/phpunit --filter 'RouterTest|TemplateRouteCoverageTest|AlpsRouteCoverageTest' --colors=never`
7. ブラウザで `/products/detail/1`, `/mypage`, `/mypage/withdraw`, `/cart`, `/shopping/nonmember` 周辺を確認。

## 成功の見込み

このスコープなら、5時間で **実装・テスト・ブラウザ確認・コミット** まで到達できる可能性が高い。
逆に、admin CSV / file manager / plugin / install に手を出すと、5時間では完了条件が曖昧になりやすい。
