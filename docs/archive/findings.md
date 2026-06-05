---
layout: default
title: "Findings & Decisions — historical record"
---

# Findings & Decisions — historical record

> **これは 2026-04-11 の計画セッションの調査記録であり、移植の現状ではない。**
> 移植方針を固めるまでの findings / decisions を残した歴史的ドキュメント。
> ここに書かれた一部の決定（PHP orchestrator の採用など）は後に撤回・変更された
> （Phase 7 pivot、および Phase 2 で実際に SQL 永続化層を構築した経緯を参照）。
> 移植の**現状・残作業**は [`docs/migration-status.md`](../migration-status.md)、
> **構築プロセスの決定ログ**は [`docs/HANDOVER.md`](../HANDOVER.md) が正。
> 本ファイルは初期判断の経緯を辿るための記録として残している。

## Requirements
- EC-CUBE を BEAR.Sunday + Be Framework へ移植する計画を作る
- 現実的な段階移行にする
- 公式資料として BEAR.Sunday / Be Framework の LLM 向けリファレンスを前提にする
- このリポジトリの ALPS 成果物を移植の基礎資料として使う

## Research Findings
- このリポジトリは実装コードではなく、EC-CUBE 4.3 の ALPS / OpenAPI / GitHub Pages 成果物の保管庫である
- ALPS には 413 ディスクリプタがあり、front の主要フローはかなり強く記述されている
- front はほぼ 100% カバーだが、admin は 30 ルート未カバーで、そのまま全面移植の契約には使いにくい
- BEAR.Sunday では `ResourceObject` の `onGet/onPost/onPut/onPatch/onDelete` が HTTP メソッドに対応し、`page://` と `app://` の URI でリソースを分ける
- BEAR.Sunday は resource client と hypermedia test を前提にできるため、ALPS の状態遷移検証と相性が良い
- Be Framework では `#[Be]`, `#[Input]`, `#[Inject]`, `#[Validate]` を使い、入力から存在への変換を型で表現する
- Be Framework は immutable / `final readonly` / constructor 完結の副作用を前提にしており、業務ルールを service 手続きではなく変換として設計する
- Be の Reason Layer は外部依存を意味ごとに束ねるので、決済、配送、税計算、在庫、通知のような EC-CUBE の副作用群を隔離しやすい
- 長時間作業では、コンテキスト消失対策として plan/findings/progress の 3 ファイルだけでは足りず、再開手順と停止条件を固定した runbook も必要
- この実行環境では、ネットワーク、GUI、sandbox 外書き込み、未承認コマンドが将来の自動継続を止める主因になる
- `be-framework/be-skills` は Be 向けに `be`, `be-semantic`, `semantic-ex` を提供し、Story → ALPS → Schema → Be code の流れを前提にしている
- `bearsunday/BEAR.Skills` は `bear-from-alps`, `bear-to-alps`, `bear-review`, `bear-hypermedia`, `bear-smoke-test` などを提供し、ALPS から resource 生成と BEAR 規約レビューに使える
- この移行用の最小 orchestrator は Python より PHP の方が適合する。移植先と同じ言語圏で、`composer` と `phpunit` に閉じた local toolchain を作れるため
- workflow 定義は YAML より JSON の方が扱いやすい。`JSON Schema` で `workflow`, `task`, `run-state`, `step-result` を厳密に検証できる
- 実装した `catalog/ProductList` packet は、実際に `task add -> run next -> run status` で完走し、`.migrate/runs/<run-id>/packet/*.json` を残せる
- `catalog/Product` packet も同じ形で切り出せる。read-only 表現に加えて `doAddCartItem` の契約を packet artifact に残せる
- `catalog/Product` packet は実際に `task add -> worker once -> run status` で完走した
- `catalog/Category` packet も同じ形で切り出せる。`goProductList`, `doUpdateCategory`, `doDeleteCategory` を packet artifact に残せる
- `catalog/Category` packet は実際に `task add -> worker once -> run status` で完走した
- `cart/Cart` packet も同じ形で切り出せる。`doAddCartItem`, `doUpdateCartItemQuantity`, `doRemoveCartItem`, `goShopping` と PurchaseFlow の注意点を packet artifact に残せる
- `cart/Cart` packet は実際に `task add -> worker once -> run status` で完走した
- planning guard は `queued_at` より後の planning file 更新を要求する。task 追加と実行を同秒に行うと guard に引っかかるので、queue 後に planning files を更新してから `worker once` を回すのが安全
- `checkout/Shopping` packet も同じ形で切り出せる。配送先選択系の遷移と `doConfirmOrder` の checkout flow note を packet artifact に残せる
- `checkout/Shopping` packet は実際に `task add -> worker once -> run status` で完走した
- `packet` は executable script ではなく DSL / 設定ファイルとして持つ方がよい。task は queue 単位、packet は契約定義、workflow は step 遷移、executor は generic command に責務分離できる
- per-resource script を増やすより、`.migrate/packets/*.json` + `php bin/orchestrator packet run <step>` の方が構造が明確
- `resource-contract` packet だけでは Be-first にならない。Be-first には `semantic_variables`, `source_constraints`, `input`, `final`, `reason_dependencies`, `be_targets`, `be_test_targets` を持つ別 kind が必要
- 最小の `be-semantic` packet は `Quantity` にする方がよい。`Quantity` は ALPS の `quantity`, `saleLimit`, `stock`, `stockUnlimited` と直接つながるため、source/ALPS first の検証対象として素直
- `AddCartItemInput` は最初の packet ではなく、`Quantity` を参照する上位 packet として置く方が責務が明確
- `AddCartItemInput` の `be-semantic` packet は実際に `task add -> worker once -> run status <run-id>` で完走した
- `Quantity` の `be-semantic` packet も実際に `task add -> worker once -> run status <run-id>` で完走した
- `review` が exit code `10` を返したときだけ `fix -> review` に遷移する設計で、review/fix loop を単純に保てる
- planning file の mtime を guard に使う方式で、`resume` 前の再読と更新を強制できる
- 「止まらない」は inner loop だけでは実現できない。内側は queue/state machine、外側は `worker loop` / `while true` / scheduler に分ける設計が自然
- supervisor 実例は repo 内にも置ける。shell loop, `systemd`, `cron` の3種類があれば、Codex 固有機能なしでも長時間運用の足場になる
- skill は増やしすぎない方が良い。現時点の最小セットは `planning-with-files`, `be-semantic`, `bear-from-alps`, `bear-review`, `bear-smoke-test`
- `koriym/homebrew-malt` は upstream README で `Claude Code Skill` として案内されている。plugin marketplace 経由で導入でき、macOS + Homebrew なら Docker なしで PHP / MySQL / Nginx / Redis を project-local port で立ち上げる用途に向く
- 今回の移植では `semantic-ex` を主工程に置かない。まず移植元 PHP と ALPS から制約を抽出し、source だけで不足する箇所に限定して補助的に使う
- 最初の実装中心は `semantic variables` で、最初の deliverable は画面ではなく `semantic catalog` である
- 振り返りとして、初期に `BEAR.Sunday + Be` を一体で考えすぎた。今後は `Be-first` を固定し、HTTP/resource 層は後段で薄く載せる
- orchestrator は v1 として十分なので、次からは基盤抽象化より packet 追加と Be 実装の方を優先する

### Phase 7: Claude Code native workflow への pivot（上記 Phase 6 の結論を撤回）
- Phase 6 の PHP orchestrator は、Claude Code が既に持つ機能（custom command / skill / subagent / prompt file）の薄い再実装で、実移植 code を 1 行も生まなかった
- 「停止しない自律実行基盤を自前で作る」より「Claude Code の subagent + custom command を並べる」方が、ほぼ確実に短く、実移植 code を早く生む
- review は必ず subagent（独立 context）で走らせる。同じ context で「書いて → 見直す」と、実装者バイアスが抜けない
- workflow 定義は YAML ではなく JSON Schema 付き JSON に固定する（`.claude/workflows/workflow.schema.json` → `migrate.json`）
- `analyze` step は Symfony コードではなく `alps.json` を source of truth として読む。ALPS モデリングは既に完了しているため
- review subagent は `{verdict: "pass"|"fail", findings: [], blocking: []}` の JSON を返し、`fail` で実装 step に差し戻す（max_retries: 3）
- `security-review` は命名条件（`Auth|Payment|Checkout|Order|Customer`）で発火する条件付き step にしておくと、無害な descriptor でレビュー時間を浪費しない
- Phase 6 の全成果物（`bin/orchestrator`, `src/`, `tests/OrchestratorTest.php`, `composer.json`, `phpunit.xml`, `.migrate/`, `orchestrator/`, `orchestrator-v1.md`, `vendor/`, `.phpunit.cache/`, `.gitignore`）は git rm で削除した。履歴は `git log` で参照可能

## Technical Decisions
| Decision | Rationale |
|----------|-----------|
| `alps.json` を機能契約の一次資料にする | safe / unsafe / idempotent、状態、語彙が既に整理されているため |
| bounded context 単位で移行する | catalog / cart / checkout / order / account / admin で責務を分けやすいため |
| UI 入口は `page://`、内部業務 API は `app://` に寄せる | BEAR.Sunday の責務分離にそのまま乗るため |
| ドメインの不変条件は Be の Semantic / Input / Final に寄せる | EC-CUBE の複雑なバリデーションや状態遷移を型で閉じ込めたいから |
| DB / 外部 API / メール / 決済は Reason または adapter に押し込む | 移植初期は既存スキーマと外部依存を温存したいから |
| 長時間運用では `work packet` 単位で進める | コンテキスト 0% でも再開点を狭く保てるため |
| 再開前に `git status --short` と planning files を必ず読む | 中断中の変更や前回の意図を取り違えないため |
| 可能なら Be / BEAR の skill を標準ワークフローに組み込む | ALPS からの生成、review、smoke test を半自動化できるため |
| orchestrator は PHP で実装する | BEAR.Sunday / Be と同じ言語圏で保守し、global runner 依存を避けるため |
| 依存管理は local `composer.json` に閉じる | repo ごとに PHPUnit バージョンと autoload を固定したいため |
| `src/bootstrap.php` は置かない | Composer autoload だけで十分で、余計な初期化ポイントを増やさないため |
| workflow/task/state は JSON を canonical にする | schema 化と差分追跡を簡単にするため |
| generic executor には `ORCH_*` 環境変数を渡す | task / run state / packet file を疎結合に保てるため |
| outer runner は orchestrator 本体と分離する | inner state machine を単純に保ちつつ、cron/systemd/loop へ差し替え可能にするため |
| skill は `必須 / 推奨 / 任意 / 不要` に分けて固定する | 毎回 skill 選定で迷わず、packet ごとの手順を揃えるため |
| 初期移行は `Be-first` で進める | 最初に検証したいのは HTTP 層ではなく業務意味の再定義だから |
| 振り返りは感想ではなく運用知識として残す | 次回の判断速度を上げ、同じ迷いを減らすため |

## Issues Encountered
| Issue | Resolution |
|-------|------------|
| admin 領域は ALPS 契約が未完成 | storefront 先行の段階移行に変更 |
| この repo に EC-CUBE 実装コードはない | まず計画レベルに留め、実装段階で別途本体 repo を読む前提にした |

## Resources
- BEAR.Sunday LLM reference: https://bearsunday.github.io/llms-full.txt
- Be Framework LLM reference: https://be-framework.github.io/llms-full.txt
- Be Skills: https://github.com/be-framework/be-skills
- BEAR.Skills: https://github.com/bearsunday/BEAR.Skills
- Day 0 workflow: `~/git/BeMart/day0-workflow.md`
- Project summary: `~/git/BeMart/README.md`
- Coverage / next-ai notes: `~/git/BeMart/HANDOVER.md`
- Domain tags: `~/git/BeMart/tag.md`
- Migration plan draft: `~/git/BeMart/ec-cube-bear-be-migration-plan.md`
- Autonomous execution runbook: `~/git/BeMart/autonomous-execution-runbook.md`
- Claude Code native workflow: `~/git/BeMart/.claude/commands/run.md`
- Migration workflow definition: `~/git/BeMart/.claude/workflows/migrate.json`
- Workflow JSON Schema: `~/git/BeMart/.claude/workflows/workflow.schema.json`
- Step prompts: `~/git/BeMart/.claude/prompts/`
- Skills matrix: `~/git/BeMart/skills-matrix.md`
- Be-first method: `~/git/BeMart/be-first-migration-method.md`

## Visual/Browser Findings
- BEAR.Sunday 公式資料では、`page://` が外部リクエスト向け、`app://` が内部 API 的 resource として説明されている
- BEAR.Sunday 公式資料では、resource client による resource 呼び出しと hypermedia test が明示されている
- Be 公式資料では、オブジェクトは「何をするか」ではなく「何になるか」を表し、変換先は `#[Be]` で宣言する
- Be 公式資料では、Reason Layer が外部依存をひとまとまりに保持し、constructor 内で変換を完結させる設計になっている
- 今回の移植では、resource/hypermedia/semantic テストを先に置けば、途中で文脈が切れても「何が未達か」をテストが教えてくれる
- Be Skills README では、`be-semantic` が Story → ALPS → Fake → Agreement → Schema → Be の流れを明示している
- BEAR.Skills README では、`bear-from-alps` と `bear-smoke-test` が ALPS 生成と TDD 運用に直結する
- Day 0 では、最初の work packet を `catalog/ProductList` に固定すると scope explosion を避けやすい
- Phase 7 の現在は、`/run migrate <descriptor-id>` で `alps-analyze → domain → domain-review → application → application-review → (security-review)` の step 列が走る
- review step は subagent（独立 context）で走り、`fail` 時は実装 step に差し戻す（max_retries: 3）

---
*Update this file after every 2 view/browser/search operations*
*This prevents visual information from being lost*
