# Task Plan — superseded

> **このファイルは古い計画フェーズの記録で、現状を表していない。**
>
> `task_plan.md` は 2026-04-11 の計画セッションのタスクプランだった。当時の
> 目的は「EC-CUBE 移植の段階計画を作り、それを止まりにくく進める最小実行基盤を
> 用意する」こと。Phase 6 では PHP orchestrator を作ったが Claude Code 自体の
> 機能の薄い再実装にすぎないと判明して `git rm` で撤回し、Phase 7 で
> `/run migrate` の Claude Code ネイティブワークフローへ pivot した。
>
> その後、移植本体が **Phase A（Be ドメイン + BEAR JSON リソース）→
> Phase 2（Fake → SQL 永続化）→ Phase 3（HTML 層）** と進行し、この計画ドキュメントは
> 完全に superseded された。`task_plan.md` 最後の未チェック項目
> （`/run migrate Quantity` の dry-run）も、実際の Phase A 移植が走ったことで
> 役目を終えている。
>
> 現在の正しい情報源:
>
> - **移植ステータス（レイヤ別マトリクス・残作業 punch-list）** → [`docs/migration-status.md`](docs/migration-status.md)
> - **構築プロセスの決定ログ** → [`HANDOVER.md`](HANDOVER.md)
> - **引き継ぎ手順と次の作業** → [`HOW_TO_CONTINUE.md`](HOW_TO_CONTINUE.md)
> - **移植全体の段階計画（依然有効な背景資料）** → [`ec-cube-bear-be-migration-plan.md`](ec-cube-bear-be-migration-plan.md)
>
> 旧 `task_plan.md`（Phase 1〜7 のタスク・決定表）が必要な場合は git 履歴
> （`git log -- task_plan.md`）で参照できる。本ファイルは現役のタスクプランでは
> ないため、計画判断に使ってはならない。
