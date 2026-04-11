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
  schemas/      # workflow/packet/task/run-state/step-result の JSON Schema
  workflows/    # JSON workflow 定義
  packets/      # packet DSL 定義
  examples/tasks/
  tasks/        # 実行キュー
  runs/         # state.json, events.ndjson, artifacts, packet outputs
```

## 現在の workflow

標準 workflow は [`packet-lifecycle.json`](/Users/akihito/git/ec-cube-alps/.migrate/workflows/packet-lifecycle.json) です。`semantic -> generate -> implement -> review` を通し、`review` が exit code `10` を返した場合だけ `fix -> review` へ遷移します。

`workflow` は resource ごとに分けず、すべて [`php bin/orchestrator packet run <step>`](/Users/akihito/git/ec-cube-alps/bin/orchestrator) の generic executor を呼びます。

## 実装済み packet DSL

packet は実行ファイルではなく、`.migrate/packets/*.json` にある宣言的な定義です。task は packet id を参照し、executor がその定義を読んで `packet/*.json` 成果物を生成します。

- [`catalog-product-list.json`](/Users/akihito/git/ec-cube-alps/.migrate/packets/catalog-product-list.json)
- [`catalog-product.json`](/Users/akihito/git/ec-cube-alps/.migrate/packets/catalog-product.json)
- [`catalog-category.json`](/Users/akihito/git/ec-cube-alps/.migrate/packets/catalog-category.json)
- [`cart.json`](/Users/akihito/git/ec-cube-alps/.migrate/packets/cart.json)
- [`checkout-shopping.json`](/Users/akihito/git/ec-cube-alps/.migrate/packets/checkout-shopping.json)

## 止まらない運用の位置づけ

この CLI 自体は「完全に止まらない task runner」ではありません。内側では queue と run state を管理し、外側では `worker loop`, `while true`, `systemd`, `cron`, `Codex App Automations` のどれかで再起動・監視する前提が現実的です。

実例は [`orchestrator/README.md`](/Users/akihito/git/ec-cube-alps/orchestrator/README.md) にまとめています。
