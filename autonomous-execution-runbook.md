# Autonomous Execution Runbook

## 目的

長時間の移植作業を、人間の都度許可に依存しすぎず継続し、context が 0% になっても安全に再開できるようにする。

この runbook は、作業中断、セッション終了、モデル切替、コンテキスト消失を通常運転として扱う。

## 基本方針

- すべての作業は `work packet` 単位で進める
- すべての packet は TDD で開始する
- すべての packet は再開可能な状態で止める
- 実行前に「承認が必要か」を判定し、不要な承認を避ける
- 承認が必要な操作はまとめて前倒しで取得する
- Be Skills / BEAR.Skills が使える環境では skill 主導で packet を進める

## Work Packet ルール

1 packet は以下を満たすこと。

- 対象が 1 つ
  - 例: `catalog/ProductList`
  - 例: `checkout/confirm-order`
- 完了条件が 1 つ
  - 例: `ProductList resource test が通る`
- 主要テスト群が明確
  - resource test
  - hypermedia test
  - semantic test
- 中断時に未コミット差分を読めば状態が理解できる

大きすぎる packet の例:

- `storefront 全部`
- `cart と checkout と order をまとめて`

## Context 0% 復帰手順

新しいセッションは必ず以下の順で復帰する。

1. `task_plan.md` を読む
2. `findings.md` を読む
3. `progress.md` を読む
4. `ec-cube-bear-be-migration-plan.md` を読む
5. `autonomous-execution-runbook.md` を読む
6. `git status --short` を確認する
7. `git diff --stat` を確認する
8. 現在の `work packet` を 1 行で言い直す
9. 対応する failing test から再開する

禁止事項:

- planning files を読まずに実装再開しない
- 未読の差分があるのに設計判断しない
- どの packet か曖昧なまま編集しない

## 毎回更新するファイル

各 packet の開始時または終了時に最低限更新する。

- `task_plan.md`
- `findings.md`
- `progress.md`

必要に応じて更新する。

- `ec-cube-bear-be-migration-plan.md`
- packet 固有の設計メモ

## 実装済み v1

この repo には、JSON-first の最小 orchestrator が入っている。

- 入口: `php bin/orchestrator`
- テスト: `composer test`
- workflow 定義: `.migrate/workflows/*.json`
- schema: `.migrate/schemas/*.json`
- 実行状態: `.migrate/tasks/`, `.migrate/runs/`
- 実装済み adapter: `bin/catalog-product-list-packet`

現在は `catalog/ProductList` packet を end-to-end で回せる。

## 外側 supervisor の考え方

この repo の orchestrator は「内側の state machine」であり、無限に動き続ける公式 runner そのものではない。長時間運用では外側 supervisor を分ける。

- 最小: `while true` で `php bin/orchestrator worker once`
- 常駐: `php bin/orchestrator worker loop --sleep=5`
- より堅牢: `systemd`, `cron`, CI, queue worker
- 将来的な選択肢: Codex App Automations

repo 内の実例:

- [`orchestrator/run-worker-loop.sh`](/Users/akihito/git/ec-cube-alps/orchestrator/run-worker-loop.sh)
- [`orchestrator/orchestrator-worker.service.example`](/Users/akihito/git/ec-cube-alps/orchestrator/orchestrator-worker.service.example)
- [`orchestrator/orchestrator-worker.timer.example`](/Users/akihito/git/ec-cube-alps/orchestrator/orchestrator-worker.timer.example)
- [`orchestrator/orchestrator-worker.crontab.example`](/Users/akihito/git/ec-cube-alps/orchestrator/orchestrator-worker.crontab.example)

重要なのは、止まらないことそのものではなく、止まっても `.migrate/runs/<run-id>/state.json` と planning files から復帰できること。

## 停止条件

以下のどれかに当たったら、実装を止めて planning files に残す。

- 人間の判断が必要なスコープ変更
- 承認が必要なコマンド実行
- 既存 EC-CUBE の仕様がソースなしでは確定しない
- 3回別アプローチを試しても同じブロッカーに当たる
- packet が肥大化して、1セッションで説明不能になった

## 承認を避けるための運用

通常は以下に限定して進める。

- workspace 内の読取/編集
- 既存ファイルの解析
- ローカルテスト
- ローカル diff / status / grep / sed / rg

承認が必要になりやすいもの:

- ネットワークアクセス
- sandbox 外への書き込み
- GUI 起動
- 破壊的 git 操作

## 事前承認の候補

長期実装の前に、必要なら以下をまとめて承認対象として整理する。

- `git clone ...`
- `composer install`
- `composer test`
- `vendor/bin/phpunit`
- `vendor/bin/phpcs`
- `vendor/bin/phpstan`

注意:

- 何でも通る広すぎる承認は取らない
- prefix は目的別に分ける
- 破壊的操作は事前承認に含めない

## テスト駆動の再開点

context 0% 後は、説明文ではなくテストを真実の起点にする。

- まず failing test を 1つ選ぶ
- その test が表す契約を確認する
- 実装はその test を通す最小差分に限定する
- 通ったら planning files を更新する

## Skill 主導の運用

利用可能なら以下を優先する。

- semantic 設計: `be-semantic`
- Be 実装の流れ整理: `be`
- ALPS から resource 起こし: `bear-from-alps`
- resource 規約レビュー: `bear-review`
- hypermedia 補完: `bear-hypermedia`
- packet ごとの薄い回帰確認: `bear-smoke-test`

rule:

- skill は packet を小さく保つために使う
- skill 実行前後で `findings.md` と `progress.md` を更新する
- skill の出力を盲信せず、必ず test を真実とする

一覧は `skills-matrix.md` を基準にする。

## 1セッション終了前チェック

終了前に必ず確認する。

1. 今の packet 名は明確か
2. 次の1手は `progress.md` に書いてあるか
3. 何がブロッカーか明記されているか
4. テストの現在地が残っているか
5. 次セッションの最初のコマンドが決まっているか

## 推奨する次セッション開始コマンド

```bash
sed -n '1,220p' task_plan.md
sed -n '1,260p' findings.md
sed -n '1,260p' progress.md
composer test
php bin/orchestrator run status
git status --short
git diff --stat
```

新規 repo の立ち上げ時は `day0-workflow.md` も読む。

## 今回の移植での推奨 packet 順

1. storefront contract extraction
2. skeleton app bootstrap
3. catalog read-only ProductList
4. catalog Product detail
5. cart add/update/remove
6. checkout confirm
7. checkout order complete
8. account basic flows
9. admin contract補完
10. admin core migration
