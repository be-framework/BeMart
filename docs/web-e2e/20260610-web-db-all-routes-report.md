# 20260610-web-db-all-routes Web+DB 全ルート検証結果

## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://127.0.0.1:18080`
- DB: `eccubedb_test` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 22 / fail 161 / 対象外 3
- OpenAPI operations: pass 74 / fail 162 / 対象外 0 / total 236
- NG cases: pass 16 / fail 3 / total 19
- screenshots: `docs/web-e2e/screenshots/20260610-web-db-all-routes/`
- results JSON: `docs/web-e2e/results/20260610-web-db-all-routes.json`

## Scope

- 母集団は `docs/api/openapi.json` の 236 operations と `docs/web-e2e/feature-implementation-matrix.md` の 186 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Known Failures

- User 注文履歴詳細: ✘ fail（status=400 final=/mypage/history） final=`http://127.0.0.1:18080/mypage/history` screenshot=`screenshots/20260610-web-db-all-routes/026-注文履歴詳細.png`
- User 再注文: ✘ fail（status=405 final=/mypage/reorder） final=`http://127.0.0.1:18080/mypage/reorder` screenshot=`screenshots/20260610-web-db-all-routes/027-再注文.png`

## New Failures

- 005 User 商品詳細表示: ✘ fail（status=404 final=/product） final=`http://127.0.0.1:18080/product?productCode=web-e2e-20260608-1780851276` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u5546\u54c1\u30b3\u30fc\u30c9\u306b\u8a72\u5f53\u3059\u308b\u5546\u54c1\u304c\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3002"}
- 006 User カート追加: ✘ fail（unsafe operation not executed: POST /cart/item） final=`http://127.0.0.1:18080/cart` reason=Browser navigation reached the page, but POST /cart/item was not executed as an OK scenario.
- 007 User カート数量変更: ✘ fail（unsafe operation not executed: PUT /cart/item） final=`http://127.0.0.1:18080/cart` reason=Browser navigation reached the page, but PUT /cart/item was not executed as an OK scenario.
- 008 User カート商品削除: ✘ fail（unsafe operation not executed: DELETE /cart/item） final=`http://127.0.0.1:18080/cart` reason=Browser navigation reached the page, but DELETE /cart/item was not executed as an OK scenario.
- 011 User 非会員購入情報送信: ✘ fail（unsafe operation not executed: POST /shopping/non-member） final=`http://127.0.0.1:18080/shopping/non-member` reason=Browser navigation reached the page, but POST /shopping/non-member was not executed as an OK scenario.
- 012 User 購入確認: ✘ fail（status=404 final=/shopping/confirm） final=`http://127.0.0.1:18080/shopping/confirm` reason=全ての商品 新規会員登録 お気に入り ログイン 0 ￥0 BeMart 新入荷 ジェラート 彩のデザート CUBE アイスサンド フルーツ ご注文内容のご確認 1 カートの商品 2 ご注文手続き 3 ご注文内容確認 4 完了 お客様情報 様 〒 配送情報 ( ) 様 〒 お支払方法 (￥0) お問い合わせ 小計 ￥0 手数料 ￥0 送料 ￥0 合計￥0税込 お支払い合計￥0税込 注文する ご注文手続きに戻る 当サイトについて プライバシ...
- 013 User 購入完了: ✘ fail（unsafe operation not executed: POST /shopping/checkout） final=`http://127.0.0.1:18080/shopping/complete` reason=Browser navigation reached the page, but POST /shopping/checkout was not executed as an OK scenario.
- 017 User 会員登録完了: ✘ fail（unsafe operation not executed: POST /entry） final=`http://127.0.0.1:18080/entry/complete` reason=Browser navigation reached the page, but POST /entry was not executed as an OK scenario.
- 019 User 会員メール認証: ✘ fail（unsafe operation not executed: POST /entry/activate） final=`http://127.0.0.1:18080/entry/activate` reason=Browser navigation reached the page, but POST /entry/activate was not executed as an OK scenario.
- 020 User ログイン: ✘ fail（unsafe operation not executed: POST /login） final=`http://127.0.0.1:18080/login` reason=Browser navigation reached the page, but POST /login was not executed as an OK scenario.
- 021 User ログアウト: ✘ fail（unsafe operation not executed: POST /logout） final=`http://127.0.0.1:18080/` reason=Browser navigation reached the page, but POST /logout was not executed as an OK scenario.
- 022 User パスワード再発行依頼: ✘ fail（unsafe operation not executed: POST /forgot-password） final=`http://127.0.0.1:18080/forgot-password` reason=Browser navigation reached the page, but POST /forgot-password was not executed as an OK scenario.
- 023 User パスワードリセット: ✘ fail（unsafe operation not executed: POST /reset） final=`http://127.0.0.1:18080/reset` reason=Browser navigation reached the page, but POST /reset was not executed as an OK scenario.
- 024 User マイページ表示: ✘ fail（status=401 final=/mypage） final=`http://127.0.0.1:18080/mypage` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 025 User 注文履歴一覧: ✘ fail（status=401 final=/mypage） final=`http://127.0.0.1:18080/mypage` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 028 User お気に入り一覧: ✘ fail（status=401 final=/mypage/favorite-list） final=`http://127.0.0.1:18080/mypage/favorite-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 029 User お気に入り追加: ✘ fail（status=401 final=/mypage/favorite-list） final=`http://127.0.0.1:18080/mypage/favorite-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 030 User お気に入り削除: ✘ fail（status=401 final=/mypage/favorite-list） final=`http://127.0.0.1:18080/mypage/favorite-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 031 User 会員情報変更: ✘ fail（status=401 final=/mypage/change） final=`http://127.0.0.1:18080/mypage/change` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 033 User お届け先一覧: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 034 User お届け先追加: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 035 User お届け先編集: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 036 User お届け先削除: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 038 User 退会入力/表示: ✘ fail（status=401 final=/mypage/withdraw） final=`http://127.0.0.1:18080/mypage/withdraw` reason=全ての商品 新規会員登録 お気に入り ログイン 0 ￥0 BeMart 新入荷 ジェラート 彩のデザート CUBE アイスサンド フルーツ マイページ/退会手続き ご注文履歴 お気に入り一覧 会員情報編集 お届け先一覧 退会手続き ようこそ さん 退会手続きの前にご確認ください 退会手続きが完了した時点で、現在保存されている購入履歴やお届け先等の情報は、すべて削除されますのでご注意ください。 退会手続きへ 当サイトについて プライバシー...
- 039 User 退会実行: ✘ fail（status=401 final=/mypage/withdraw） final=`http://127.0.0.1:18080/mypage/withdraw` reason=全ての商品 新規会員登録 お気に入り ログイン 0 ￥0 BeMart 新入荷 ジェラート 彩のデザート CUBE アイスサンド フルーツ マイページ/退会手続き ご注文履歴 お気に入り一覧 会員情報編集 お届け先一覧 退会手続き ようこそ さん 退会手続きの前にご確認ください 退会手続きが完了した時点で、現在保存されている購入履歴やお届け先等の情報は、すべて削除されますのでご注意ください。 退会手続きへ 当サイトについて プライバシー...
- 042 User お問い合わせ確認: ✘ fail（unsafe operation not executed: POST /contact） final=`http://127.0.0.1:18080/contact/confirm` reason=Browser navigation reached the page, but POST /contact was not executed as an OK scenario.
- 049 Admin 管理ログイン: ✘ fail（unsafe operation not executed: POST /admin/login） final=`http://127.0.0.1:18080/admin/login` reason=Browser navigation reached the page, but POST /admin/login was not executed as an OK scenario.
- 050 Admin 管理ログアウト: ✘ fail（status=405 final=/admin/logout） final=`http://127.0.0.1:18080/admin/logout` reason={"code":405,"message":"MyVendor\\BeMart\\Resource\\Page\\Admin\\Logout_1184601733::{(get}()"}
- 051 Admin 管理ダッシュボード表示: ✘ fail（status=403 final=/admin/index） final=`http://127.0.0.1:18080/admin/index` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 ホーム 注文状況 売上状況 ￥0 / 0 件 今月の売上金額 / 売上件数 ￥0 / 0 件 今日の売上金額 / 売上件数 ￥0 / 0 件 昨日の売上金額 / 売上件数 週間 月間 年間 ショップ状況 在庫切れ商品数 0 取扱商品数 0 会員数 0 おすすめのプラグイン > オーナーズストア お知らせ
- 052 Admin 商品一覧表示: ✘ fail（status=403 final=/admin/product-list） final=`http://127.0.0.1:18080/admin/product-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 053 Admin 商品検索: ✘ fail（status=403 final=/admin/product-list） final=`http://127.0.0.1:18080/admin/product-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 054 Admin 商品新規登録: ✘ fail（status=403 final=/admin/product-new） final=`http://127.0.0.1:18080/admin/product-new` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 商品登録商品管理 基本情報 商品コード 商品名 販売価格 在庫数 公開状態 説明・管理情報 説明 検索ワード 管理メモ 商品一覧 登録
- 055 Admin 商品詳細表示: ✘ fail（status=403 final=/admin/product） final=`http://127.0.0.1:18080/admin/product?productCode=web-e2e-20260608-1780851276` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 056 Admin 商品編集: ✘ fail（status=403 final=/admin/product） final=`http://127.0.0.1:18080/admin/product?productCode=web-e2e-20260608-1780851276` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 057 Admin 商品削除: ✘ fail（status=403 final=/admin/product） final=`http://127.0.0.1:18080/admin/product?productCode=web-e2e-20260608-1780851276` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 058 Admin 商品コピー: ✘ fail（status=403 final=/admin/product-list） final=`http://127.0.0.1:18080/admin/product-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 059 Admin 商品公開状態一括変更: ✘ fail（status=403 final=/admin/product-list） final=`http://127.0.0.1:18080/admin/product-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 060 Admin 商品CSV出力: ✘ fail（status=403 final=/admin/product-csv） final=`http://127.0.0.1:18080/admin/product-csv` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 061 Admin 商品CSV取込: ✘ fail（status=403 final=/admin/product/csv-product） final=`http://127.0.0.1:18080/admin/product/csv-product` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 商品管理 CSVファイル 雛形ファイルダウンロード CSVファイルフォーマット 項目名 説明 商品管理へ戻る CSVファイルをアップロード
- 062 Admin カテゴリ一覧表示: ✘ fail（status=403 final=/admin/category/category-list） final=`http://127.0.0.1:18080/admin/category/category-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 063 Admin カテゴリ作成: ✘ fail（status=403 final=/admin/category/category-list） final=`http://127.0.0.1:18080/admin/category/category-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 064 Admin カテゴリ編集: ✘ fail（status=403 final=/admin/category/edit） final=`http://127.0.0.1:18080/admin/category/edit?categoryId=1` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 065 Admin カテゴリ削除: ✘ fail（status=403 final=/admin/category/category-list） final=`http://127.0.0.1:18080/admin/category/category-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 066 Admin カテゴリCSV出力: ✘ fail（status=403 final=/admin/category/csv） final=`http://127.0.0.1:18080/admin/category/csv` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 067 Admin カテゴリCSV取込: ✘ fail（status=403 final=/admin/product/csv-category） final=`http://127.0.0.1:18080/admin/product/csv-category` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 商品管理 CSVファイル 雛形ファイルダウンロード CSVファイルフォーマット 項目名 説明 商品管理へ戻る CSVファイルをアップロード
- 068 Admin タグ一覧表示: ✘ fail（status=403 final=/admin/tag/tag-list） final=`http://127.0.0.1:18080/admin/tag/tag-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 069 Admin タグ作成: ✘ fail（status=403 final=/admin/tag/tag-list） final=`http://127.0.0.1:18080/admin/tag/tag-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 070 Admin タグ削除: ✘ fail（status=403 final=/admin/tag/tag-list） final=`http://127.0.0.1:18080/admin/tag/tag-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 071 Admin 規格管理表示: ✘ fail（status=403 final=/admin/class-name/class-name-list） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 072 Admin 規格作成: ✘ fail（status=403 final=/admin/class-name/class-name-list） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 073 Admin 規格編集: ✘ fail（status=403 final=/admin/class-name/class-name-list） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 074 Admin 規格削除: ✘ fail（status=403 final=/admin/class-name/class-name-list） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 075 Admin 規格CSV出力: ✘ fail（status=403 final=/admin/class-name/class-name-export） final=`http://127.0.0.1:18080/admin/class-name/class-name-export` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 076 Admin 規格CSV取込: ✘ fail（status=403 final=/admin/product/csv-class-name） final=`http://127.0.0.1:18080/admin/product/csv-class-name` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 商品管理 CSVファイル 雛形ファイルダウンロード CSVファイルフォーマット 項目名 説明 商品管理へ戻る CSVファイルをアップロード
- 077 Admin 規格分類管理表示: ✘ fail（status=403 final=/admin/class-category/class-category-list） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 078 Admin 規格分類作成: ✘ fail（status=403 final=/admin/class-category/class-category-list） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 079 Admin 規格分類編集: ✘ fail（status=403 final=/admin/class-category/class-category-list） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 080 Admin 規格分類削除: ✘ fail（status=403 final=/admin/class-category/class-category-list） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 081 Admin 規格分類CSV出力: ✘ fail（status=403 final=/admin/class-category/class-category-export） final=`http://127.0.0.1:18080/admin/class-category/class-category-export` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 082 Admin 規格分類CSV取込: ✘ fail（status=403 final=/admin/product/csv-class-category） final=`http://127.0.0.1:18080/admin/product/csv-class-category` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 商品管理 CSVファイル 雛形ファイルダウンロード CSVファイルフォーマット 項目名 説明 商品管理へ戻る CSVファイルをアップロード
- 083 Admin 商品規格編集: ✘ fail（status=403 final=/admin/product/product-class） final=`http://127.0.0.1:18080/admin/product/product-class?productCode=web-e2e-20260608-1780851276` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 商品規格商品管理 規格一覧 商品コード 販売価格 在庫数 在庫無制限 送料 商品一覧へ戻る 登録
- 084 Admin 会員一覧表示: ✘ fail（status=403 final=/admin/customer-list） final=`http://127.0.0.1:18080/admin/customer-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 085 Admin 会員検索: ✘ fail（status=403 final=/admin/customer-list） final=`http://127.0.0.1:18080/admin/customer-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 086 Admin 会員詳細表示: ✘ fail（status=403 final=/admin/customer） final=`http://127.0.0.1:18080/admin/customer?customerId=1` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 087 Admin 会員作成: ✘ fail（status=403 final=/admin/customer-list） final=`http://127.0.0.1:18080/admin/customer-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 088 Admin 会員編集: ✘ fail（status=403 final=/admin/customer） final=`http://127.0.0.1:18080/admin/customer?customerId=1` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 089 Admin 会員削除: ✘ fail（status=403 final=/admin/customer） final=`http://127.0.0.1:18080/admin/customer?customerId=1` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 090 Admin 会員配送先編集: ✘ fail（status=403 final=/admin/customer-delivery-edit） final=`http://127.0.0.1:18080/admin/customer-delivery-edit?customerId=1` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 お届け先編集会員管理 お届け先 お名前 必須 お名前(カナ) 会社名 郵便番号 都道府県 住所 電話番号 会員編集ページに戻る 登録
- 091 Admin 会員認証メール再送: ✘ fail（status=403 final=/admin/customer） final=`http://127.0.0.1:18080/admin/customer?customerId=1` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 092 Admin 会員CSV出力: ✘ fail（status=403 final=/admin/customer-csv） final=`http://127.0.0.1:18080/admin/customer-csv` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 093 Admin 受注一覧表示: ✘ fail（status=403 final=/admin/order-list） final=`http://127.0.0.1:18080/admin/order-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 094 Admin 受注検索: ✘ fail（status=403 final=/admin/order-list） final=`http://127.0.0.1:18080/admin/order-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 095 Admin 受注詳細表示: ✘ fail（status=403 final=/admin/order） final=`http://127.0.0.1:18080/admin/order?orderNo=3aaaf6c72af21076c8cd32ab83434fce` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 096 Admin 受注作成: ✘ fail（status=403 final=/admin/order-list） final=`http://127.0.0.1:18080/admin/order-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 097 Admin 受注編集: ✘ fail（status=403 final=/admin/order） final=`http://127.0.0.1:18080/admin/order?orderNo=3aaaf6c72af21076c8cd32ab83434fce` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 098 Admin 受注削除: ✘ fail（status=403 final=/admin/order-list） final=`http://127.0.0.1:18080/admin/order-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 099 Admin 受注対応状況変更: ✘ fail（status=403 final=/admin/order-status） final=`http://127.0.0.1:18080/admin/order-status` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 受注対応状況設定店舗設定 受注対応状況 ID 名称(マイページ) 名称(受注管理) 色 件数表示 登録
- 100 Admin 配送先編集: ✘ fail（status=403 final=/admin/order/shipping-address） final=`http://127.0.0.1:18080/admin/order/shipping-address?orderNo=3aaaf6c72af21076c8cd32ab83434fce` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 出荷登録受注管理 お届け先 お名前必須 郵便番号 都道府県 市区町村名 番地・ビル名 電話番号 受注編集へ戻る 登録
- 101 Admin 追跡番号更新: ✘ fail（status=403 final=/admin/order-list） final=`http://127.0.0.1:18080/admin/order-list` reason={"code":403,"message":"\u3053\u306e\u64cd\u4f5c\u306b\u306f\u7ba1\u7406\u8005\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 102 Admin 出荷通知メール表示: ✘ fail（status=403 final=/admin/order/shipping-notify-mail） final=`http://127.0.0.1:18080/admin/order/shipping-notify-mail?orderNo=3aaaf6c72af21076c8cd32ab83434fce` reason=BeMart 管理者 様 ホーム 商品管理 受注管理 会員管理 コンテンツ管理 設定 オーナーズストア 情報 出荷通知メール受注管理 出荷通知メール送信 この操作には管理者ログインが必要です。 注文番号 会員ID ゲスト購入 受注詳細へ戻る 出荷通知メールを送信

- ... 79 more failures are in the JSON.

## OpenAPI Operation Failures

- GET /action-redirect: coverage=direct-http-get, status=503, reason=OpenAPI GET operation was probed directly.
- POST /contact: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /entry: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /forgot-password: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /forgot-password: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /login: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /login: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /logout: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /mypage: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /reset: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /reset: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/authority-role: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/base-info: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/base-info: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/create-customer: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer-delivery-edit: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/delete-customer: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/index: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/log: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/login: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/login: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/login-history: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/logout: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/master-data: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data-edit: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/member-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/plugin: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-disable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-enable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-bulk-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-copy: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/security: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/security: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/system: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/trade-law: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/trade-law: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/two-factor-auth: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/two-factor-auth-set: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /cart/item: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /cart/item: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /cart/item: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /contact/confirm: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /entry/activate: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /mypage/address: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /mypage/address: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /mypage/address-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /mypage/address-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /mypage/change: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.

- ... 82 more operation failures are in the JSON.

## Negative Case Failures

- ログイン 形式不正: status=403, final=`http://127.0.0.1:18080/login`, error=BeMart / ログイン 全ての商品 新規会員登録 お気に入り ログイン 0 ￥0 現在カート内に商品はございません。 BeMart 新入荷 ジェラート 彩のデザート CUBE アイスサンド フルーツ ログイン Invalid or missing CSRF token. ログイン ログイン情報をお忘れですか？ 新規会員登録 当サイトについて プライバシーポリシー 特定商取引法に基づく表記 お問い合わせ BeMart copyrigh..., screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-login-invalid-email.png`
- お問い合わせ 必須欠落: status=400, final=`http://127.0.0.1:18080/contact`, error={"code":400,"message":"\u59d3\u306e\u5f62\u5f0f\u304c\u4e0d\u6b63\u3067\u3059\u30021\u301c50 \u6587\u5b57\u3067\u6307\u5b9a\u3057\u3066\u304f\u3060\u3055\u3044\u3002"}, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-contact-required-missing.png`
- 管理CSVアップロード 未ログイン: status=405, final=`http://127.0.0.1:18080/admin/product/csv-product`, error={"code":405,"message":"MyVendor\\BeMart\\Resource\\Page\\Admin\\Product\\CsvProduct_2505070056::{(post}()"}, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-csv-upload-unauthenticated.png`

## Negative Cases

- ✔ pass 会員登録 必須欠落: POST /entry, status=400, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-entry-required-missing.png`
- ✔ pass 会員登録 メール形式不正/確認不一致: POST /entry, status=400, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-entry-invalid-email-mismatch.png`
- ✔ pass 会員登録 CSRF欠落: POST /entry, status=403, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-entry-csrf-missing.png`
- ✔ pass ログイン 認証失敗: POST /login, status=401, final=`http://127.0.0.1:18080/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-login-wrong-credential.png`
- ✘ fail ログイン 形式不正: POST /login, status=403, final=`http://127.0.0.1:18080/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-login-invalid-email.png`
- ✔ pass パスワード再発行 メール形式不正: POST /forgot-password, status=403, final=`http://127.0.0.1:18080/forgot-password`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-forgot-password-invalid-email.png`
- ✔ pass パスワードリセット 不正キー: POST /reset, status=403, final=`http://127.0.0.1:18080/reset`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-reset-invalid-key.png`
- ✘ fail お問い合わせ 必須欠落: POST /contact, status=400, final=`http://127.0.0.1:18080/contact`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-contact-required-missing.png`
- ✔ pass お問い合わせ 形式不正/境界超過: POST /contact, status=403, final=`http://127.0.0.1:18080/contact`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-contact-invalid-email-long-body.png`
- ✔ pass カート投入 数量境界不正: POST /cart/item, status=403, final=`http://127.0.0.1:18080/cart/item`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-cart-item-invalid-quantity.png`
- ✔ pass 非会員購入 必須欠落: POST /shopping/non-member, status=400, final=`http://127.0.0.1:18080/shopping/non-member`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-shopping-non-member-required-missing.png`
- ✔ pass 購入確定 存在しない preOrderId: POST /shopping/checkout, status=403, final=`http://127.0.0.1:18080/shopping/checkout`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-shopping-checkout-nonexistent-preorder.png`
- ✔ pass 会員情報変更 未ログイン: POST /mypage/change, status=403, final=`http://127.0.0.1:18080/mypage/change`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-mypage-change-unauthenticated.png`
- ✔ pass お届け先編集 存在しないID/未ログイン: PUT /mypage/address, status=400, final=`http://127.0.0.1:18080/mypage/address`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-mypage-address-nonexistent-id.png`
- ✔ pass 管理ログイン 認証失敗: POST /admin/login, status=401, final=`http://127.0.0.1:18080/admin/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-login-wrong-credential.png`
- ✔ pass 管理ログイン CSRF不一致: POST /admin/login, status=403, final=`http://127.0.0.1:18080/admin/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-login-csrf-invalid.png`
- ✔ pass 管理2FA チャレンジなし: POST /admin/two-factor-auth, status=403, final=`http://127.0.0.1:18080/admin/two-factor-auth`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-two-factor-no-challenge.png`
- ✔ pass 管理商品 未ログインPOST: POST /admin/product, status=400, final=`http://127.0.0.1:18080/admin/product`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-product-unauthenticated.png`
- ✘ fail 管理CSVアップロード 未ログイン: POST /admin/product/csv-product, status=405, final=`http://127.0.0.1:18080/admin/product/csv-product`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-csv-upload-unauthenticated.png`

## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
