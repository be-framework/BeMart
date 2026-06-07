# 20260607-full-web-e2e 完成度批評

## 結論

`20260607-full-web-e2e` は、機能表 186 行すべてに実ブラウザ確認結果と証跡を付けたという意味では完了している。未実施行は 0、実操作対象 183 行のスクリーンショットも揃っている。

ただし、これは「全機能が正常に動く」ことの証明ではない。現在の完成度は、**全体の到達可能性を可視化し、壊れている導線を隠さず分類した段階**である。

## 数値サマリ

- 総機能数: 186
- `✔ pass`: 144
- `✘ fail`: 39
- `— 対象外`: 3
- `未実施`: 0
- スクリーンショット: 183
- 結果JSON: `docs/web-e2e/results/20260607-full-web-e2e.json`
- スクリーンショット: `docs/web-e2e/screenshots/20260607-full-web-e2e/`

## 良い点

- 1機能=1行の機能表が、実装状態だけでなく実ブラウザ結果まで持つようになった。
- すべての行が `✔ pass` / `✘ fail` / `— 対象外` のどれかになり、未確認が残っていない。
- failを成功扱いにせず、スクリーンショットと再現URLを残している。
- 事前確認として `composer test:http` と `composer apidoc:audit` は green で、HTTPフローとOpenAPI母集団の土台は壊れていない。
- 外部送信・実決済・本番運用ファイル破壊は実行せず、デモ安全性を保っている。

## まだ弱い点

### 1. failの大半は「Resource未実装」ではなく「Web導線/route互換」の問題

39件のfailのうち、多くはResource自体の不存在ではなく、TwigやEC-CUBE互換route名が現在のResource URLへ解決されていない問題である。

代表例:

- `admin_product_csv` → `/admin/product-csv` に到達しない
- `admin_content_news_new` → `/admin/news/news` に到達しない
- `admin_setting_shop_calendar` → `/admin/calendar` に到達しない
- `admin_setting_system_member_new` → `/admin/member` に到達しない

これは「OpenAPI/Resourceは存在するが、画面上の導線として壊れている」ため、Webアプリ完成度としては重要なfailである。

### 2. POST/PUT/DELETEの実操作は安全性のため限定的

破壊的操作や外部送信は実行していない。したがって、今回のpassは主に以下を意味する。

- 画面に到達できる
- フォームまたは操作導線が見える
- fake/adapter境界として安全な応答が確認できる

一方で、すべてのPOST/PUT/DELETEを最終送信して副作用まで確認したわけではない。業務副作用の完全E2Eは、別途テストデータprefixとロールバック可能な環境で行う必要がある。

### 3. ダウンロード系はブラウザdownloadイベント検証が必要

CSV/PDF出力は、通常のnavigationでは `ERR_ABORTED` として観測されることがある。今回の結果ではfailとして記録したが、次は `download` イベントを捕捉して、ファイル名・MIME・最低限の内容を検証すべきである。

### 4. マイページ注文履歴にURL/テンプレート不整合がある

`/mypage/order-history` は `Page/Mypage/OrderHistory.html.twig` が見つからずFatalになる。一方、`/mypage/history` は詳細Resourceで `orderNo` が必要になる。機能表・Resource・テンプレート・ナビの対応を再整理する必要がある。

### 5. カート追加は商品名不一致が残っている

商品詳細からカートへ進む動き自体は確認できたが、選択商品とカート明細の商品名が一致しない。これは単なるスクリーンショット差分ではなく、購入体験の意味的不整合として優先度が高い。

## fail分類

| 分類 | 件数 | 意味 |
|---|---:|---|
| `admin-legacy-ui-route-mismatch` | 26 | EC-CUBE互換route名/画面リンクがResource URLへ解決されず404 |
| `download-export-boundary-aborted` | 4 | CSV/PDF downloadをnavigationで開き、ブラウザ側で中断 |
| `mypage-order-history-route-template-mismatch` | 3 | 注文履歴のURL/Resource/template対応が不整合 |
| `admin-navigation-route-mismatch` | 2 | 管理ナビが一覧ではなく詳細Resourceへ向き400 |
| `cart-content-mismatch` | 1 | カート追加後の商品名不一致 |
| `checkout-seed-or-preorder-missing` | 1 | 購入確認用preOrderIdが検証環境に存在せず404 |
| `admin-seed-id-mismatch` | 1 | 検証IDがfake/実データと一致せず404 |
| `post-only-action-has-no-browser-get-screen` | 1 | POST専用ResourceをGETで開いて405 |

## 次に直すべき順序

1. 管理画面のroute alias / URL helper対応を直す。
2. 商品一覧・受注一覧の管理ナビを一覧Resourceへ向ける。
3. カート追加の商品不一致を再現テスト化して修正する。
4. マイページ注文履歴の一覧/詳細URLとテンプレート名を整理する。
5. CSV/PDFはdownloadイベント検証に切り替える。
6. POST/PUT/DELETEは `web-e2e-*` prefixの作成データと後始末を用意して、副作用込みのE2Eへ拡張する。

## 現時点の自己評価

完成度は **台帳・証跡としては高い**。全件が見える状態になり、未確認を残していない。

一方、アプリケーションとしての完成度は **まだ green とは言えない**。特に管理画面のlegacy route導線と、カート/注文履歴の意味的不整合は、ユーザーがWebで操作すると直接ぶつかる問題である。

したがって、この成果物は「全機能が動く証明」ではなく、**Web機能の現実の完成度を、証跡付きで正直に測った監査結果**である。
