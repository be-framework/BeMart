# EC-CUBE実サイト探索によるBeMart機能欠落レポート（2026-05-23）

## 目的

コード上のRouteMap/Twig棚卸しだけではなく、起動中のEC-CUBE参照サイト `http://127.0.0.1:8081` とBeMart `http://127.0.0.1:8080` を実HTTPで探索し、画面・フォーム・リンクから見える機能欠落を確認した。

## 探索方法

- Storefront匿名導線: トップ、商品一覧、商品詳細、カート、会員登録、問い合わせ、ログイン、ヘルプ系を同一ホスト内でクロール。
- Admin導線: `test-admin / local-dev-admin-password` でEC-CUBE/BeMart双方にログインし、管理画面リンクをクロール。
- 除外: `delete` / `copy` / `enable` / `disable` / `install` / `uninstall` / `export` / `download` / `pdf` / `csv` / `mail` など、状態変更または非HTMLの可能性が高いリンク。
- 判定: HTTP status、画面タイトル、主要見出し、フォーム数、リンク数、フォームinput名を比較。

> Codex in-app browser の自動操作APIは `No active Codex browser pane available` で接続できなかったため、今回は実ブラウザ相当のHTTPクロールで探索した。コード静的解析ではない。

## 探索カバレッジ

| 対象 | 探索ページ数 | エラー | 備考 |
|---|---:|---|---|
| EC-CUBE storefront匿名 | 21 | 0 | 商品一覧/詳細、会員登録、問い合わせ、ヘルプを確認 |
| BeMart storefront匿名 | 26 | 1 → 修正後0相当 | `/mypage/favorite-list` が匿名で401だったため、EC-CUBE同様ログイン誘導へ修正 |
| EC-CUBE adminログイン済み | 145 | 5 | 参照側自体の `page/46/edit` 500 と visible系GET 405を検出 |
| BeMart adminログイン済み | 60 | 0 | 探索中に見つけた `/admin/customer` bare link 400 は修正済み |

## 重要な欠落

### Storefront

| 領域 | EC-CUBE実サイトで確認した機能 | BeMart現状 | 欠落/差分 |
|---|---|---|---|
| 商品一覧 | 検索フォームに `category_id`, `name`。一覧操作に `disp_number`, `orderby`, `pageno`。各商品カードにカート投入POST。 | 修正後、カテゴリフィルタ、表示件数/並び順hidden、一覧カート投入フォーム、モーダル枠を追加。 | EC-CUBEの完全な商品クラス連動、Ajaxカートブロック更新、pager partial忠実度は残差。 |
| 商品詳細 | `classcategory_id1/2`, `ProductClass`, `quantity`, `_token` のカートフォーム。お気に入り追加フォームあり。 | `quantity`, `product_id`, `csrfToken` のみ。 | 規格選択、商品クラス連動、favorite導線が不足。 |
| 匿名MYページ系 | `/mypage/favorite` はログイン画面200へ誘導。 | 修正後、`/mypage` / `/mypage/favorite-list` / `/mypage/favorite` / `/mypage/address-list` / `/mypage/change` は `/login` へ303。 | ログイン後の戻り先復元は残差。 |
| 会員登録 | 姓名/カナ/会社/郵便番号/住所/電話/email確認/password確認/生年月日/性別/職業/規約同意。 | 主要項目はかなり近い。 | EC-CUBEのネスト名とは異なる。確認画面/完了/メール認証の忠実度は追加確認が必要。 |
| 問い合わせ | 姓名/カナ/郵便番号/住所/電話/email/問い合わせ内容。 | 氏名、email、内容中心。 | カナ、住所、電話などEC-CUBE標準項目が不足。 |
| カート/購入 | EC-CUBEはカート→購入手続き導線が前提。 | カート表示はあるが、ログイン/非会員購入/購入確認までの状態遷移はまだ薄い。 | 購入フローは継続探索・実装対象。 |

### Admin Product

| 領域 | EC-CUBE実サイトで確認した機能 | BeMart現状 | 欠落/差分 |
|---|---|---|---|
| 商品登録/編集 | `sale_type`, `product_image[]`, `description_detail`, `description_list`, `price01`, `price02`, `stock`, `stock_unlimited`, `search_word`, `code`, `sale_limit`, `delivery_duration`, `free_area`, `Category[]`, `Tag[]`, `note`, `Status`。 | `productCode`, `productName`, `price02`, `stock`, `productStatus`, `description`, `searchWord`, `note`。 | 画像アップロード、販売種別、通常価格、在庫無制限、販売制限、発送日目安、フリーエリア、カテゴリ/タグの実編集、一覧説明が不足。 |
| 商品規格 | `/admin/product/product/class/{id}` に商品クラス行列。規格名1/2、各規格組み合わせごとのコード/在庫/価格/販売種別等。 | 規格名/規格分類の一覧画面はあるが、商品ごとの規格行列編集は未接続。 | EC-CUBE商品管理の中核機能として優先実装が必要。 |
| 商品一覧 | 検索、ページング、編集リンク、CSV/状態変更など。 | 一覧表示と検索はあるが、検索条件/一括処理は薄い。 | EC-CUBEの検索条件・一括操作・CSV導線の整理が必要。 |

### Admin Order

| 領域 | EC-CUBE実サイトで確認した機能 | BeMart現状 | 欠落/差分 |
|---|---|---|---|
| 受注一覧検索 | `multi`, `status[]`, `name`, `payment[]`, `kana`, 日付範囲, `company_name`, `email`, `phone_number`, `order_no`, 金額範囲等。 | `multi` と簡易filter中心。 | 実務検索条件が大幅不足。 |
| 受注新規登録 | `/admin/order/new` が存在。顧客検索、購入者情報、配送先、支払、配送方法、商品明細を同一画面で編集。 | 受注編集first-sliceのみ。 | 新規受注登録が未実装。 |
| 受注編集 | EC-CUBEは購入者/配送/明細/支払/対応状況/出荷/メール導線が厚い。 | `discount`, `charge`, `usePoint` など最小の金額編集のみ。 | 受注管理としては未完成。配送・支払・明細・ステータス・メール履歴が必要。 |

### Admin Customer

| 領域 | EC-CUBE実サイトで確認した機能 | BeMart現状 | 欠落/差分 |
|---|---|---|---|
| 会員一覧検索 | EC-CUBEは詳細検索条件を持つ。 | `multi` 中心。 | 氏名/メール/電話/購入金額/登録日/ステータス等の検索が不足。 |
| 会員新規登録 | `/admin/customer/new` が存在。姓名/カナ/会社/住所/email/電話/password/性別/職業/生年月日/ポイント/メモ/ステータス。 | 既存会員編集はあるが、新規登録専用URL/導線は未整備。 | 管理者による会員新規作成が不足。 |
| 会員編集 | EC-CUBEはステータス、ポイント、購入履歴/配送先/お気に入りなど周辺文脈を持つ。 | 基本項目とお届け先編集への薄い導線。 | 購入履歴、配送先一覧、お気に入り、ステータス/仮会員系操作が不足。 |

### Admin Content / Setting / Store

| 領域 | EC-CUBE実サイトで確認した機能 | BeMart現状 | 欠落/差分 |
|---|---|---|---|
| ファイル管理 | `/admin/content/file_manager` にファイルアップロード/作成/ツリー操作。 | 該当画面なし。 | コンテンツ管理の大きな欠落。 |
| ページ/ブロック/レイアウト | EC-CUBEは多数の既存ページ/ブロック編集画面へ遷移。 | 一覧/編集shellはあるが内容は薄い。 | Twig編集、レイアウト配置、ブロック差し込みの忠実度が不足。 |
| メンテナンス管理 | `/admin/content/maintenance` が存在。 | nav/route接続が薄い。 | 画面接続と状態表示が必要。 |
| 店舗設定 | 基本設定、特商法、支払、配送、税率、受注対応状況、定休日。 | 一部画面はあるが、特商法/定休日/メール設定などが薄い。 | 店舗設定一式のフォーム項目をEC-CUBEに寄せる必要あり。 |
| システム設定 | メンバー、権限、セキュリティ、ログイン履歴、ログ表示、マスタデータ、システム情報。 | メンバー/権限/セキュリティ中心。 | ログイン履歴、ログ表示、システム情報、マスタデータ編集の忠実度不足。 |
| オーナーズストア | プラグイン一覧、認証キー設定、テンプレート。 | プラグイン/テンプレート一覧はあるが外部連携は未対応扱い。 | 外部連携は画面だけ表示し、安全な未対応説明へ流す方針を維持。 |

## 探索中に見つけて修正した問題

- BeMart管理画面のお届け先編集から「会員編集ページに戻る」が `admin_customer_edit` のIDなしURLになり、クリック相当クロールで `/admin/customer` 400になる問題を修正。
- `admin_customer_delivery_edit` / `admin_customer_delivery_new` の `id` → `customerId` aliasを追加。
- 修正後、BeMart admin探索は 60ページ、エラー0。
- 匿名MYページ系の401を、EC-CUBE相当のログイン誘導へ修正。
- 商品一覧にカテゴリフィルタ、表示件数/並び順フォーム値、一覧カート投入フォームを追加。

## 優先タスク

1. **Product本体**: 商品画像アップロード、商品規格行列、カテゴリ/タグ実編集、在庫無制限、販売種別、通常価格/販売価格、販売制限、発送日目安を実装。
2. **Product storefront**: 商品一覧のEC-CUBE pager partial忠実化、Ajaxカートブロック更新、詳細favorite/規格選択を実装。
3. **Order本体**: 受注新規登録、受注検索条件、購入者/配送先/明細/支払/対応状況/出荷情報/メール履歴を実装。
4. **Customer本体**: 管理会員新規登録、詳細検索、購入履歴/配送先一覧/お気に入り、ステータス操作を実装。
5. **Content/Setting補完**: ファイル管理、メンテナンス、特商法、定休日、ログイン履歴、ログ表示、システム情報、マスタデータをEC-CUBE実画面に合わせる。
6. **非画面アクション**: CSV/PDF/export/delete/plugin外部連携はリンクを隠さず、未対応説明に安全に落とす方針を維持。

## 結論

BeMartは、主要なURLがraw Fatal/Unboundを出さない段階には近づいている。ただし、EC-CUBE実サイトを探索すると、特に **商品規格・画像・受注編集/新規・会員管理・ファイル管理・詳細検索条件** がまだ大きく不足している。したがって「基本機能がEC-CUBEとほぼ同じ」とはまだ言えない。次はProduct/Order/Customerのフォーム項目と状態遷移を、実サイトで確認したinput/リンク単位で潰す必要がある。
