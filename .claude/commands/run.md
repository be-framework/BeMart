---
name: run
description: Execute a Claude Code native workflow defined in .claude/workflows/<name>.json. Use when the user invokes /run <workflow> <args>.
argument-hint: <workflow> [args...]
disable-model-invocation: true
---

# Workflow Runner

あなたはこのプロジェクトのワークフローランナーです。`$ARGUMENTS` に指定されたワークフローを実行してください。

## 手順

### 1. 引数の解釈

`$ARGUMENTS` の最初のトークンをワークフロー名、残りを引数配列として解釈する。

例:
- `/run migrate Product` → workflow=`migrate`, args=`["Product"]`
- `/run migrate Cart` → workflow=`migrate`, args=`["Cart"]`

### 2. ワークフロー定義の読み込み

`.claude/workflows/<workflow>.json` を Read ツールで読む。

存在しない場合は、`.claude/workflows/` 配下の利用可能なワークフロー一覧を表示して終了する。

読み込んだ JSON が `.claude/workflows/workflow.schema.json` に準拠しているか目視で確認する（Claude はスキーマを読んで理解できる）。`steps` 配列が無い、`name` が欠けているなど明らかな異常があれば停止してユーザーに報告する。

### 3. 引数の検証

ワークフロー定義の `arguments` と、ユーザーが渡した args の個数を照合する。required な引数が不足していれば停止して `argument-hint` を表示する。

### 4. ステップの実行

`steps` 配列を上から順に実行する。各ステップについて:

#### 4a. 条件判定 (condition)

`condition` が定義されている場合、直前までに変更されたファイルパスにその正規表現がマッチするか判定する。マッチしなければスキップしてログに `[skipped: <reason>]` を残す。

最初のステップでは `condition` は無意味（まだ変更が無い）。`condition` は通常、実装ステップより後に置かれるセキュリティレビュー等で使う。

#### 4b. プロンプトの読み込み

`prompt_file` が指定されていれば Read ツールで読む。`prompt` がインライン指定されていればそれを使う。

プロンプト中の `{descriptor}` のような placeholder は、`arguments` の対応する値で置換する（例: `{descriptor}` → `Product`）。

#### 4c. 実行モードの判定

- `agent: true` → Agent ツール（subagent_type: general-purpose）を使って独立コンテキストで実行する
- `agent` 省略または false → 現在のコンテキストでそのまま実行する

#### 4d. サブエージェント実行（agent: true の場合）

Agent ツールを以下のように呼ぶ:

```
description: "<step.name> for <descriptor>"
subagent_type: "general-purpose"
prompt: |
  <prompt_file の内容（placeholder 置換済み）>

  対象ディスクリプタ: <descriptor>
  プロジェクトルート: ~/git/be-bemart

  レビュー結果を以下の JSON 形式で返してください:
  {
    "verdict": "pass" | "fail",
    "findings": [ "問題点1", "問題点2", ... ],
    "blocking": [ "差し戻しが必要な問題のみ" ]
  }
```

エージェントの返答を JSON として解釈する。`verdict: "pass"` なら次のステップへ進む。`verdict: "fail"` なら差し戻しループに入る（下記 4e）。

#### 4e. 差し戻しループ (on_fail)

`verdict: "fail"` かつ `on_fail` が定義されている場合:

1. 差し戻し回数をカウント（ステップごとに独立）
2. `max_retries`（デフォルト 3）に達していれば、ユーザーに停止理由を報告して終了
3. 達していなければ、`on_fail` に指定されたステップ名まで実行位置を戻す
4. 戻る際に、ユーザーに「<reviewer> が <target> を差し戻しました。理由: …」を1行で報告する
5. 戻り先のステップに「前回レビューで指摘された事項」として blocking 配列を追加プロンプトで渡す

#### 4f. 通常ステップ実行（agent: false の場合）

プロンプトを現在のコンテキストでそのまま実行する。TaskCreate で進捗を管理してもよい。

### 5. ログ出力

各ステップの開始・完了・スキップ・差し戻しを短い1行メッセージでユーザーに伝える:

```text
[1/6] alps-analyze       ... running
[1/6] alps-analyze       ... done
[2/6] domain             ... running
[2/6] domain             ... done
[3/6] domain-review      ... running (agent)
[3/6] domain-review      ... FAIL → back to domain (attempt 1/3)
[2/6] domain             ... running (rework)
[2/6] domain             ... done
[3/6] domain-review      ... running (agent)
[3/6] domain-review      ... pass
[4/6] application        ... running
...
[6/6] security           ... skipped (condition not matched)
```

### 6. 完了

全ステップ完了後、`composer test` のような検証コマンドをユーザーに提案する（ワークフロー側で明示指定が無ければ）。最終成果物の一覧をユーザーに報告する。

## 制約

- プロンプト中で新しいワークフローを自動起動しない（無限ループ防止）
- `condition` のマッチに使う「変更ファイル一覧」は、`git status --short` や直前ステップで Write / Edit したファイルから算出する
- レビューエージェントの返答が JSON として解釈できない場合は `fail` 扱いとし、生の返答を blocking に含めて差し戻す
- ユーザーが明示的に止めるまで各ステップを完遂すること。中途半端な場所で「次どうしますか？」と聞かない
