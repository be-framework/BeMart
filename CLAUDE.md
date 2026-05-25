# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EC-CUBE 4.3 の ALPS プロファイル (`alps.json`, 403 descriptors) と、EC-CUBE → BEAR.Sunday + Be Framework への移植を駆動する Claude Code ネイティブワークフローの 2 つで構成される。

- **主成果物**: `alps.json` — EC-CUBE 4.3 のセマンティクス（データ語彙 266 + 状態遷移 137）を機械可読に定義
- **移植ワークフロー**: `.claude/` 以下の `/run` コマンド + JSON workflow + prompts

## Commands

### ALPS プロファイル

```bash
asd --lint alps.json        # バリデーション
asd -e alps.json            # HTML ドキュメント再生成
asd -s alps.json            # SVG 状態遷移図生成
```

HTML 再生成時は `docs/` 配下のコピーも同期すること。

### 移植ワークフロー

```text
/run migrate <descriptor-id>
```

例:
- `/run migrate Product` — catalog の Product リソースを移植
- `/run migrate Cart` — cart リソースを移植
- `/run migrate AddCartItemInput` — 個別の Be Input を移植

## Architecture

### 2 層構造

1. **ALPS 層** — `alps.json` が正（source of truth）。`openapi.yaml`, HTML は生成物。`tag.md` がタグ分類体系、`HANDOVER.md` が構築プロセス記録（Pilot 1/2 完了報告含む）
2. **ワークフロー層** — `.claude/` 配下に、Claude Code のネイティブ機能（custom command + subagent + prompts）でワークフローを定義

### `.claude/` の構成

```text
.claude/
├── commands/
│   └── run.md                      # /run <workflow> <args> コマンド
├── workflows/
│   ├── workflow.schema.json        # JSON Schema（構造検証）
│   └── migrate.json                # 移植ワークフロー定義
└── prompts/
    ├── alps-analyze.md              # ALPS 読み取り → Be/BEAR マッピング案
    ├── domain-implement.md          # Be Framework ドメイン層実装
    ├── be-review.md                 # Be 原則レビュー（subagent）
    ├── application-implement.md     # BEAR.Sunday リソース層実装
    ├── integration-review.md        # 2層境界レビュー（subagent）
    └── security-review.md           # 条件付きセキュリティレビュー（subagent）
```

### ワークフローステップ

`.claude/workflows/migrate.json` が定義するステップ:

1. **alps-analyze** — ALPS から対象ディスクリプタを読み取り、Be / BEAR マッピング案を作成
2. **domain** — Be Framework でドメイン層（Input / Semantic / Final / Reason）を実装
3. **domain-review** — subagent が Be 原則違反をチェック（不合格 → domain に差し戻し、最大 3 回）
4. **application** — BEAR.Sunday でアプリケーション層（Resource）を実装
5. **application-review** — subagent が 2 層境界違反をチェック（不合格 → application に差し戻し）
6. **security** — 変更ファイルに認証・セッション・CSRF・決済・注文・顧客・管理系の語がマッチした場合のみ発火

レビューの subagent は `{ "verdict": "pass"|"fail", "findings": [...], "blocking": [...] }` の JSON を返す。`blocking` が空なら pass、1 件以上で fail。

### 前提とする外部スキル

`.claude/prompts/` は以下のスキルを名指しで呼ぶ:

- `be-framework-skills:be` — Be 実装ルールとプロジェクト構造
- `be-framework-skills:be-semantic` — Story → ALPS → Schema → Be の流れ
- `be-framework-skills:semantic-ex` — Fake データから制約発見
- `alps-skills:alps-to-bear` — ALPS → BEAR.Sunday
- `alps-skills:alps-to-jsonschema` — ALPS → JSON Schema
- `bear-skills:bear-resource-generator` — リソース一式生成
- `bear-skills:bear-hypermedia` — `#[Link]` とハイパーメディアテスト
- `bear-skills:bear-cleancode-review` / `bear-skills:php-cleancode-review` — コード品質レビュー
- `bear-skills:bear-security-setup` — Psalm taint 設定

### 参照パターン

Be Framework の実装は `~/git/be-patterns/demos/` の 8 種を正解パターンとして踏襲する:

| パターン | 用途 |
|---|---|
| hello-world | Minimal: Input → Final |
| contact-form | Linear: Input → Being → Final |
| user-registration | Sequential Chain |
| order-processing | Diamond（Moment 注入） |
| blog-publishing | Multi-Reason Being |
| medical-triage | Branching |
| loan-application | Cascade Diamond |
| insurance-claim | Complex Convergence |

## Conventions

- JSON: 2 スペースインデント
- ALPS descriptor ID: lowerCamelCase (`productName`)
- ノートファイル: kebab-case (`verify-similar-names.md`)
- Markdown: ATX 見出し。ユーザー向け散文は日本語
- 全ディスクリプタに情報源タグ（`src-router`, `src-entity`, `src-controller`, `src-template`）を付与
- 生成済み HTML は手動編集しない

## JSON Schema

- `.claude/workflows/workflow.schema.json` — `/run` が解釈するワークフロー定義のスキーマ
