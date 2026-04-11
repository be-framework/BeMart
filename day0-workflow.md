# Day 0 Workflow

## 目的

BEAR.Sunday + Be Framework ベースの移植先リポジトリを立ち上げ、承認待ちや context 切れで止まりにくい初日運用を確立する。

## Day 0 の完了条件

- 移植先 repo が作成済み
- planning files が配置済み
- Be Skills / BEAR.Skills の利用方針が決定済み
- 事前承認が必要なコマンド群が整理済み
- 最初の `work packet` が 1 つ開始済み
- failing test から再開できる状態になっている

## 事前に決めること

- v1 スコープは storefront のみに固定する
- 既存 DB スキーマは当面維持する
- plugin 互換は v1 対象外とする
- packet は 1 bounded context / 1完了条件に制限する

## 事前承認リスト

移植先 repo の作業開始前に、必要なら以下をまとめて承認対象として整理する。

- `git clone ...`
- `composer install`
- `composer test`
- `vendor/bin/phpunit`
- `vendor/bin/phpcs`
- `vendor/bin/phpstan`

承認を広げすぎない。目的別に prefix を分ける。

## 初期セットアップ

1. 移植先 repo を作る
2. `task_plan.md`, `findings.md`, `progress.md` を repo 直下に置く
3. この repo の以下を参照資料として固定する
   - `alps.json`
   - `tag.md`
   - `handover.json`
   - `ec-cube-bear-be-migration-plan.md`
   - `autonomous-execution-runbook.md`
4. 可能なら Be Skills / BEAR.Skills を使える状態にする
5. `composer test` の入口だけ先に作る
6. `skills-matrix.md` で必須 skill を確認する
7. macOS + Homebrew 前提なら plugin marketplace 経由の skill として `malt` を使うか決める

## Day 0 の実行順

### Step 1: Contract Freeze

- `alps.json` から storefront 対象だけ抜き出す
- `catalog`, `cart`, `checkout` の resource inventory を作る
- admin は backlog に分離する

成果物:

- storefront inventory
- packet backlog

### Step 2: Skeleton

- BEAR.Sunday app skeleton を作る
- Be 用のディレクトリを切る
- test runner を通す
- macOS 環境では必要なら `malt.json` を置き、native の PHP / MySQL / Nginx / Redis を立ち上げる

最低限の構成:

```text
src/
  Resource/Page/
  Resource/App/
  Input/
  Being/
  Final/
  Reason/
  Semantic/
  Exception/
  Module/
tests/
```

### Step 3: Tooling

- `composer test` を定義
- PHPUnit を通す
- coding standard と static analysis の入口を作る
- smoke test の置き場を決める

### Step 4: Skills Integration

- semantic 設計には `be-semantic`
- ALPS から resource 起こしには `bear-from-alps`
- review には `bear-review`
- packet 閉じには `bear-smoke-test`

skill を使っても、真実は test に置く。

### Step 5: First Packet

最初の packet はこれに固定する。

- 対象: `catalog/ProductList`
- 完了条件: `ProductList` の resource test が通る
- 補助: 必要なら `bear-from-alps`
- 追加テスト: 最小の hypermedia test 1 本

これより大きい packet を Day 0 に持ち込まない。

## Session Start Checklist

毎セッションの開始時に必ず実行する。

```bash
sed -n '1,220p' task_plan.md
sed -n '1,260p' findings.md
sed -n '1,260p' progress.md
git status --short
git diff --stat
```

続いて確認する。

- 今の packet 名
- 完了条件
- failing test
- ブロッカーの有無

## Session End Checklist

終了前に必ず残す。

- 今の packet 名
- 次の 1 手
- failing / passing test の状態
- ブロッカー
- 変更ファイル一覧

更新対象:

- `task_plan.md`
- `findings.md`
- `progress.md`

## Context 0% での再開点

再開時は説明から入らない。以下を真実の順番にする。

1. planning files
2. git diff
3. failing test
4. 最小修正

## Day 0 の禁止事項

- storefront 全体を一気に始めない
- admin を最初に触らない
- DB リファクタを同時に始めない
- plugin 互換を初日に考えない
- 承認が必要な操作を途中で場当たり的に増やさない

## Day 1 に持ち越してよいもの

- Product detail
- Category
- Hypermedia link 拡張
- Semantic の追加
- Cart packet 開始
