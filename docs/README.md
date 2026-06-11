---
layout: default
title: "docs/ — ドキュメント索引"
---

# docs/ — ドキュメント索引

`docs/` はプロジェクト文書の置き場であり、同時に GitHub Pages の publish root
（`docs/_config.yml`）でもある。

全体の入口は repo ルートの [`README.md`](../README.md)。このファイルは
`docs/` 配下の文書を「何を知りたいか」で引けるようにする索引である。

## まず読む順

| 目的 | 読むもの |
|---|---|
| 最短で全体像を知る | [`../README.md`](../README.md) |
| 実装規模と証拠を見る | [`feature-evidence.md`](feature-evidence.md) |
| プロジェクトの意義を知る | [`migration-goal-review.md`](migration-goal-review.md) → [`PROJECT-REPORT.md`](PROJECT-REPORT.md) |
| 最新の到達点を知る | [`migration-status.md`](migration-status.md) |
| 完全代替への残差を知る | [`complete-replacement-residuals.md`](complete-replacement-residuals.md) |
| 次の作業を始める | [`HOW_TO_CONTINUE.md`](HOW_TO_CONTINUE.md) → [`migration-status.md`](migration-status.md) §4 |
| flow / workflow の意味を知る | [`flow-ontology.md`](flow-ontology.md) → [`../tests/README.md`](../tests/README.md) |
| ALPS 生成の背景を読む | [`archive/alps-profile-generation.md`](archive/alps-profile-generation.md) |
| 境界ルールを再利用する | [`methodology/standard-first-rule.md`](methodology/standard-first-rule.md) → [`methodology/architecture-audit-baseline.md`](methodology/architecture-audit-baseline.md) → [`skills/index.md`](skills/index.md) → [`methodology/`](methodology/) |

## Canonical Documents

| ファイル | 役割 |
|---|---|
| [`feature-evidence.md`](feature-evidence.md) | **証拠入口**。ALPS、Resource、SQL、API、HTML、Web E2E screenshot の規模とリンクをまとめる。外部読者にはまずここを見せる。 |
| [`migration-goal-review.md`](migration-goal-review.md) | **ゴール再確認**。BeMart を semantic overhaul として再定義し、達成証拠・残差分類・実証結論・次フェーズ計画を整理する。 |
| [`complete-replacement-residuals.md`](complete-replacement-residuals.md) | **完全代替残差台帳**。カバー率ではなく互換 fidelity と production verification として残っている項目を詳細化する。 |
| [`flow-ontology.md`](flow-ontology.md) | **Flow ontology**。自然言語で定義された業務導線としての新しい `flow-*` 語彙を整理する。 |
| [`flow-tag-migration-plan.md`](flow-tag-migration-plan.md) | **Flow tag 移行計画**。現行 `flow-*` を feature-area usage として扱い、`feature-*` と新しい `flow-*` に分離する手順。 |
| [`PROJECT-REPORT.md`](PROJECT-REPORT.md) | **実証総括**。何を学び、何を成し遂げ、どこに限界を引いたかを一枚に束ねた documentary。各論への入口。 |
| [`migration-status.md`](migration-status.md) | **移植ステータスの正**。ALPS / Be / Resource / Ray.MediaQuery / HTML のレイヤ別マトリクスと残作業 punch-list。「今どこまで出来ているか」はここが正。 |
| [`eccube-feature-alps-status.html`](eccube-feature-alps-status.html) | EC-CUBE route/function ↔ ALPS ID ↔ BeMart実装状態 ↔ 移植難易度の生成HTML表。 |
| [`HANDOVER.md`](HANDOVER.md) | 構築プロセスの記録。Phase A / Phase 2 / Phase 3 の決定ログ、Pilot/Wave 報告、skill gap 発見の経緯。 |
| [`HOW_TO_CONTINUE.md`](HOW_TO_CONTINUE.md) | 引き継ぎ手順。再開時に読むファイル順・次にやること・リポジトリ構造。`migration-status.md` を正として読む。 |
| [`tag.md`](tag.md) | タグ分類体系（`flow-*` / `src-*` 等、ワークフロー・ドメイン・アクター・情報源の命名規則）。 |

## Published / Generated Files

`index.html`（GitHub Pages 入口）/ `index.alps.json`（入口ページの profile）/
`archive/alps-profile-generation.md`（旧公開ブログ記事）/ `_config.yml`（Jekyll 設定）/
`alps.json.html` / `openapi.html` / `alps.svg` / `api/index.html` / `api/terms.html` /
`api/openapi.json` / `api/llms.txt` は公開 URL を持つ生成物・設定。手動移動禁止。

## サブディレクトリ

| ディレクトリ | 内容 |
|---|---|
| [`methodology/`](methodology/) | 再利用可能な方法論・原則。`standard-first-rule.md`（BEAR境界を触る前の標準参照ルール）、`architecture-audit-baseline.md`（BEAR境界標準逸脱の監査baseline）、`ai-standardization-retrospective.md`（AI独自実装の反省）、`hypermedia-test-principle.md`（状態遷移契約を PHP Resource / HTTP / HTML affordance へ投影する考え方）、`FRAMEWORK_REVIEW.md`（元139 transition sliceの歴史的フレームワーク実務レビュー）、`be-bear-ai-era.md`（Be + BEAR の AI コーディング時代における潜在能力の論評）。 |
| [`alps-doc/`](alps-doc/) | ALPS descriptor の `link rel="describedby"` から参照される長文補足の正本。root の `../alps-doc` は既存の `alps.json` 相対リンクを保つための互換 symlink。 |
| [`web-e2e/`](web-e2e/) | 実DB/prod context のWeb E2E証跡。`completion-evidence-rules.md`（完成判定の証跡ルール）、`repeated-failure-patterns.md`（再発防止メモ）、機能表、run report、結果JSON、最新runのスクリーンショットを保持する。 |
| [`phases/`](phases/) | フェーズ別の成果物。`alps-audit-phase3.md`（Phase 3 準備の ALPS 監査）、`admin-fanout-plan.md`（admin 移植のファンアウト計画）。 |
| [`skills/`](skills/) | 移植で発見した skill gap（G-14 〜 G-25）。各エントリは独立して読める移植知見。索引は [`skills/index.md`](skills/index.md)。 |
| [`quality/`](quality/) | Phase 1 の ALPS 監査ノート（`verify-*.md` / `improvements-*.md`）。 |
| [`assets/`](assets/) | README / GitHub Pages で使う BeMart branding 画像。 |
| [`archive/`](archive/) | superseded された旧トラッカー・初期計画。歴史的記録として保持。現状の判断には使わない。 |

## archive/ の中身

計画初期（2026-04 前後）の作業メモ。`progress.md` / `task_plan.md` / `findings.md` は
現状とは乖離しているため進捗判断に使わない。`ec-cube-bear-be-migration-plan.md` /
`be-first-migration-method.md` / `day0-workflow.md` / `autonomous-execution-runbook.md` /
`skills-matrix.md` / `analysis-report.md` も同じく初期版。現状は `migration-status.md` を参照。
