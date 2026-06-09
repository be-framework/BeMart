# Web E2E証跡の保持方針

## 方針

- リポジトリに保持する画像証跡は、レビュー対象となる最新の全件検証runに限定する。
- 途中確認run、旧fail可視化run、同一内容PNGは重複証跡として保持しない。
- 同じ画面へ到達する複数機能は、1枚のスクリーンショットを共有してよい。画像が同一であれば、証拠能力は失われない。
- 削除した途中runの画像は未確認扱いではなく、最終run `20260608-canonical-resource-routes-web-e2e` に置き換えられた証跡である。

## 現在保持する証跡

- 結果JSON: `docs/web-e2e/results/20260608-canonical-resource-routes-web-e2e.json`
- 結果レポート: `docs/web-e2e/20260608-canonical-resource-routes-web-e2e-report.md`
- 機能表: `docs/web-e2e/feature-implementation-matrix.md`
- スクリーンショット: `docs/web-e2e/screenshots/20260608-canonical-resource-routes-web-e2e/`

## 追加時のルール

1. 新しい全件runを追加する場合は、古い全件run画像を残す必要があるかを先に判断する。
2. 同一SHA-256のPNGは1枚だけ残し、機能表と結果JSONの参照を代表画像へ寄せる。
3. 途中調査用の画像はローカルまたはCI artifactに置き、リポジトリへは入れない。

## 最新run

- 最新の全件run: `20260610-web-db-all-routes`
- 結果JSON: `docs/web-e2e/results/20260610-web-db-all-routes.json`
- 結果レポート: `docs/web-e2e/20260610-web-db-all-routes-report.md`
- スクリーンショット: `docs/web-e2e/screenshots/20260610-web-db-all-routes/`
- `20260608-canonical-resource-routes-web-e2e` は比較用ベースラインとして参照する。
