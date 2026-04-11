# JSON-First Orchestrator V1

## 目的

EC-CUBE 移植用の `work packet` を、`resume` 可能な最小単位で回すためのローカル CLI です。汎用ワークフロー基盤ではなく、このリポジトリ内の移植計画を止まりにくくするための実装に絞っています。

## 入口

- `composer test`: PHPUnit を実行
- `php bin/orchestrator validate <file> [kind]`: schema 検証
- `php bin/orchestrator task add .migrate/examples/tasks/001-catalog-product-list.json`: task をキューへ投入
- `php bin/orchestrator run next`: 次の queued task を実行
- `php bin/orchestrator run resume <run-id>`: failed run を再開
- `php bin/orchestrator run status [run-id]`: summary と完全 state を表示
- `php bin/orchestrator worker once`: queued task を 1 件だけ処理
- `php bin/orchestrator worker loop --sleep=5 --max-idle=12`: 外側 supervisor としてキューを回す

## ディレクトリ

```text
.migrate/
  schemas/      # workflow/task/run-state/step-result の JSON Schema
  workflows/    # JSON workflow 定義
  examples/tasks/
  tasks/        # 実行キュー
  runs/         # state.json, events.ndjson, artifacts, packet outputs
```

## 現在の workflow

標準 workflow は [`storefront-packet.json`](~/git/ec-cube-alps/.migrate/workflows/storefront-packet.json) です。`semantic -> generate -> implement -> review` を通し、`review` が exit code `10` を返した場合だけ `fix -> review` へ遷移します。

## 実装済み packet

[`bin/catalog-product-list-packet`](~/git/ec-cube-alps/bin/catalog-product-list-packet) は `catalog/ProductList` 用の packet adapter です。`alps.json` を読んで、run ごとの `packet/*.json` 成果物を生成します。

[`bin/catalog-product-packet`](~/git/ec-cube-alps/bin/catalog-product-packet) は `catalog/Product` 用の packet adapter です。`goProduct`, `goProductList`, `goCategory`, `doAddCartItem` を含む契約を packet artifact に落とします。

## 止まらない運用の位置づけ

この CLI 自体は「完全に止まらない task runner」ではありません。内側では queue と run state を管理し、外側では `worker loop`, `while true`, `systemd`, `cron`, `Codex App Automations` のどれかで再起動・監視する前提が現実的です。

実例は [`orchestrator/README.md`](~/git/ec-cube-alps/orchestrator/README.md) にまとめています。
