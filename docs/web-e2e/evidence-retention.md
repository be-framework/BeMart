# Web E2E証跡の保持方針

## 方針

- リポジトリに保持する画像証跡は、レビュー対象となる最新の全件検証runに限定する。
- 完成判定の証跡ルールは [`completion-evidence-rules.md`](completion-evidence-rules.md) を正とする。途中run画像を保持するかどうかは、このルールで必要な readback / error UI / boundary evidence を満たすかで判断する。
- 途中確認run、旧fail可視化run、同一内容PNGは重複証跡として保持しない。
- 同じ画面へ到達する複数機能は、1枚のスクリーンショットを共有してよい。画像が同一であれば、証拠能力は失われない。
- 削除した途中runの画像は未確認扱いではなく、最新の全件run `20260610-web-db-all-routes` に置き換えられた証跡である。
- 比較用ベースラインとして `20260608-canonical-resource-routes-web-e2e` は参照してよいが、完成判定の最新証跡は 20260610 run を正とする。

## 現在保持する証跡

- 結果JSON: `docs/web-e2e/results/20260610-web-db-all-routes.json`
- 結果レポート: `docs/web-e2e/20260610-web-db-all-routes-report.md`
- 機能表: `docs/web-e2e/feature-implementation-matrix.md`
- スクリーンショット: `docs/web-e2e/screenshots/20260610-web-db-all-routes/`
- Follow-up台帳: `docs/web-e2e/20260610-web-db-followups.md`

## 追加時のルール

1. 新しい全件runを追加する場合は、古い全件run画像を残す必要があるかを先に判断する。
2. 同一SHA-256のPNGは1枚だけ残し、機能表と結果JSONの参照を代表画像へ寄せる。
3. 途中調査用の画像はローカルまたはCI artifactに置き、リポジトリへは入れない。

## 最新run

- 最新の全件run: `20260611-web-db-all-routes`
- 結果JSON: `docs/web-e2e/results/20260611-web-db-all-routes.json`
- 結果レポート: `docs/web-e2e/20260611-web-db-all-routes-report.md`
- スクリーンショット: `docs/web-e2e/screenshots/20260611-web-db-all-routes/`
- `20260608-canonical-resource-routes-web-e2e` は比較用ベースラインとして参照する。

## 限定回帰run

- `20260611-form-negative-visible-ui-regression-1278080` は、フォームNG時の可視エラーUIを確認する限定run。
- `--only-negative` で実行しているため、feature matrix / OpenAPI coverage / setup evidence は完成判定に使わない。NG 19件の HTTP status、日本語エラー文、可視エラーUI数、screenshot だけを証跡として扱う。
- このrunは Codex 実行環境側の `127.0.0.1:8080` に対する runner 証跡であり、ローカルChromeの手動証跡とは分けて扱う。
- `20260611-admin-delivery-browser-regression` は、全件run `20260611-web-db-all-routes` で fail だった配送方法作成/編集/削除を閉じるための限定run。
- `--limit=120` で実行しているため、121 以降の未実行 fail は完成判定には使わない。
- このrunの配送CRUDスクリーンショットは、次の全件runで同じ配送行が pass になるまで一時的に保持する。
- `20260611-admin-base-info-browser-regression` は、全件run `20260611-web-db-all-routes` で fail だった基本情報更新を閉じるための限定run。
- `--limit=112` で実行しているため、113 以降の未実行 fail は完成判定には使わない。
- このrunの基本情報更新スクリーンショットは、次の全件runで同じ基本情報更新行が pass になるまで一時的に保持する。
- `20260611-admin-tax-rule-browser-regression-fixed2` は、全件run `20260611-web-db-all-routes` で fail だった税率設定作成/削除を閉じるための限定run。
- `--limit=123` で実行しているため、124 以降の未実行 fail は完成判定には使わない。
- このrunの税率設定CRUDスクリーンショットは、次の全件runで同じ税率設定行が pass になるまで一時的に保持する。
- `20260611-admin-calendar-browser-regression-fixed` は、全件run `20260611-web-db-all-routes` で fail だった定休日作成/削除を閉じるための限定run。
- `--limit=125` で実行しているため、126 以降の未実行 fail は完成判定には使わない。
- このrunの定休日CRUDスクリーンショットは、次の全件runで同じ定休日行が pass になるまで一時的に保持する。
- `20260611-admin-news-browser-regression-fixed` は、全件run `20260611-web-db-all-routes` で fail だったニュース作成/編集/削除を閉じるための限定run。
- `--limit=160` で実行しているため、161 以降の未実行 fail は完成判定には使わない。
- このrunのニュースCRUDスクリーンショットは、次の全件runで同じニュース行が pass になるまで一時的に保持する。
- `20260611-admin-page-browser-regression-fixed` は、全件run `20260611-web-db-all-routes` で fail だったページ作成/編集/削除を閉じるための限定run。
- `--limit=164` で実行しているため、165 以降の未実行 fail は完成判定には使わない。
- このrunのページCRUDスクリーンショットは、次の全件runで同じページ行が pass になるまで一時的に保持する。
- `20260611-admin-block-browser-regression` は、全件run `20260611-web-db-all-routes` で fail だったブロック作成/削除を閉じ、ブロック編集を未完成 fail として明確化するための限定run。
- `--limit=168` で実行しているため、169 以降の未実行 fail は完成判定には使わない。167 ブロック編集はこのrunでも fail であり、既存行 prefill 用の Be Input / Final / SQL read model がない限り pass にしない。
- このrunのブロック作成/削除スクリーンショットは、次の全件runで同じブロック行が pass になるまで一時的に保持する。
- `20260611-admin-class-browser-regression-fixed` は、全件run `20260611-web-db-all-routes` で fail だった規格作成/編集/削除、規格分類作成/編集/削除を閉じるための限定run。
- `--limit=83` で実行しているため、084 以降の未実行 fail は完成判定には使わない。061/067/076/082/083 の unsafe 未実行 fail はこのrunでも残っている。
- このrunの規格/規格分類CRUDスクリーンショットは、次の全件runで同じ行が pass になるまで一時的に保持する。
