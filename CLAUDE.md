# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EC-CUBE 4.3 の ALPS プロファイル (`alps.json`, 413 descriptors) と、EC-CUBE → Be/BEAR.Sunday 移植ワークフローを駆動する JSON-first orchestrator の2つで構成される。

- **主成果物**: `alps.json` — EC-CUBE 4.3 のセマンティクス（データ語彙 276 + 状態遷移 137）を機械可読に定義
- **補助ツール**: `.migrate/` 以下の packet DSL と `src/` の PHP orchestrator

## Commands

### ALPS プロファイル

```bash
asd --lint alps.json        # バリデーション
asd -e alps.json            # HTML ドキュメント再生成
asd -s alps.json            # SVG 状態遷移図生成
```

HTML 再生成時は `docs/` 配下のコピーも同期すること。

### PHP Orchestrator

```bash
composer test                               # 全テスト実行
./vendor/bin/phpunit --colors=always        # 同上
./vendor/bin/phpunit --filter=testMethodName # 単一テスト実行
```

### CLI (bin/orchestrator)

```bash
bin/orchestrator validate <file> [kind]              # JSON スキーマ検証
bin/orchestrator task add <task.json>                 # タスクキューイング
bin/orchestrator run next                             # 次タスク実行
bin/orchestrator run resume <run-id>                  # 中断再開
bin/orchestrator run status [run-id]                  # ステータス確認
bin/orchestrator worker once                          # 単発ワーカー
bin/orchestrator worker loop [--sleep=5] [--max-idle] # ポーリングワーカー
```

## Architecture

### 2層構造

1. **ALPS 層** — `alps.json` が正。`openapi.yaml`, HTML は生成物。`tag.md` がタグ分類体系、`handover.json` が構築プロセス記録
2. **Orchestrator 層** — `.migrate/` 配下の JSON DSL でワークフローを宣言的に定義し、`src/` の PHP で実行

### Orchestrator のクラス構成 (`MigrationOrchestrator\` namespace)

```
CliApplication          CLI ディスパッチャ
  ├─ RunEngine          ワークフロー実行（リトライ・状態遷移）
  │   ├─ TaskRepository   タスクキュー管理
  │   ├─ RunRepository    実行状態永続化（state.json, events.ndjson）
  │   ├─ PacketRepository パケット読み込み・検証
  │   └─ PlanningGuard    resume 時の planning file 更新チェック
  ├─ PacketExecutor     パケット処理ペイロード構築
  ├─ QueueWorker        バックグラウンドポーリング
  └─ SchemaValidator    JSON Schema 検証
```

ユーティリティ: `ProjectPaths`（ディレクトリ抽象化）, `JsonFile`（アトミック JSON/NDJSON I/O）, `FileLock`（排他ロック）

### Packet Types

- **resource-contract-packet**: ALPS ディスクリプタから BEAR.Sunday リソースを生成
- **be-semantic-packet**: Be Framework のセマンティックバリデータを生成

### ワークフロー

`.migrate/workflows/packet-lifecycle.json` が定義。ステップ: `semantic` → `generate` → `implement` → `review` → `fix`(retry) → `COMPLETE`

### 状態永続化 (.migrate/runs/)

- `state.json` — 現在のステップ・ステータス
- `events.ndjson` — 追記のみのイベントログ
- `artifacts/` — ステップごとの成果物

### RunEngine が設定する環境変数

`ORCH_PROJECT_ROOT`, `ORCH_RUN_ID`, `ORCH_RUN_DIR`, `ORCH_TASK_ID`, `ORCH_TASK_FILE`, `ORCH_PACKET_ID`, `ORCH_PACKET_TYPE`, `ORCH_PACKET_BOUNDED_CONTEXT`, `ORCH_SUCCESS_CRITERIA_JSON`

## Conventions

- JSON/YAML: 2スペースインデント
- ALPS descriptor ID: lowerCamelCase (`productName`)
- ノートファイル: kebab-case (`verify-similar-names.md`)
- Markdown: ATX 見出し。ユーザー向け散文は日本語
- 全ディスクリプタに情報源タグ（`src-router`, `src-entity`, `src-controller`, `src-template`）を付与
- 生成済み HTML は手動編集しない

## JSON Schema

`.migrate/schemas/` に各種スキーマ定義:
- `packet.schema.json` — パケット構造
- `task.schema.json` — タスク構造
- `workflow.schema.json` — ワークフロー定義
- `run-state.schema.json` — 実行状態
- `step-result.schema.json` — ステップ結果
