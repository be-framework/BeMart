# docs/ — ドキュメントマップ

`docs/` はこのリポジトリのドキュメント置き場であり、同時に GitHub Pages の
publish root（`docs/_config.yml`）でもある。本ファイルは `docs/` 配下に何があるかの索引。

プロジェクト全体の入口は repo ルートの [`README.md`](../README.md)、
規約は [`CLAUDE.md`](../CLAUDE.md) を参照。

## トップレベル（canonical）

| ファイル | 役割 |
|---|---|
| [`migration-status.md`](migration-status.md) | **移植ステータスの正**。レイヤ別マトリクスと残作業 punch-list。「今どこまで出来ているか」はここが正。 |
| [`HANDOVER.md`](HANDOVER.md) | 構築プロセスの記録。Phase A / Phase 2 / Phase 3 の決定ログ、Pilot/Wave 報告、skill gap 発見の経緯。 |
| [`HOW_TO_CONTINUE.md`](HOW_TO_CONTINUE.md) | 引き継ぎ手順。再開時に読むファイル順・次にやること・リポジトリ構造。 |
| [`tag.md`](tag.md) | タグ分類体系（`flow-*` / `src-*` 等、ワークフロー・ドメイン・アクター・情報源の命名規則）。 |

### GitHub Pages 公開物（編集・移動しない）

`index.md`（公開ブログ記事）/ `_config.yml`（Jekyll 設定）/ `alps.json.html` /
`openapi.html` / `alps.svg` は公開 URL を持つ生成物・設定。手動移動禁止。

## サブディレクトリ

| ディレクトリ | 内容 |
|---|---|
| [`methodology/`](methodology/) | 再利用可能な方法論・原則。`hypermedia-test-principle.md`（hypermedia テスト＝移植契約）、`FRAMEWORK_REVIEW.md`（フレームワーク評価）。 |
| [`phases/`](phases/) | フェーズ別の成果物。`alps-audit-phase3.md`（Phase 3 準備の ALPS 監査）、`admin-fanout-plan.md`（admin 移植のファンアウト計画）。 |
| [`skills/`](skills/) | 移植で発見した skill gap（G-14 〜 G-23）。各エントリは独立して読める移植知見。索引は [`skills/index.md`](skills/index.md)。 |
| [`quality/`](quality/) | Phase 1 の ALPS 監査ノート（`verify-*.md` / `improvements-*.md`）。 |
| [`archive/`](archive/) | superseded された旧トラッカー・初期計画。歴史的記録として保持。現状の判断には使わない。 |

## archive/ の中身

計画初期（2026-04 前後）の作業メモ。`progress.md` / `task_plan.md` / `findings.md` は
現状とは乖離しているため進捗判断に使わない。`ec-cube-bear-be-migration-plan.md` /
`be-first-migration-method.md` / `day0-workflow.md` / `autonomous-execution-runbook.md` /
`skills-matrix.md` / `analysis-report.md` も同じく初期版。現状は `migration-status.md` を参照。
