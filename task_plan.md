# Task Plan: EC-CUBE to BEAR.Sunday + Be Migration Planning

## Goal
EC-CUBE 4.3 の主要機能を、既存 ALPS プロファイルを契約として用いながら、BEAR.Sunday + Be Framework ベースへ段階的に移植するための実行可能な計画と、その計画を止まりにくく進める最小実行基盤を作る。

## Current Phase
Phase 6

## Phases

### Phase 1: Requirements & Discovery
- [x] ユーザー意図を確認
- [x] リポジトリの制約と既存成果物を確認
- [x] BEAR.Sunday / Be Framework の公式資料を確認
- [x] findings.md に要点を記録
- **Status:** complete

### Phase 2: Planning & Architecture Mapping
- [x] ALPS と BEAR.Sunday / Be の対応関係を整理
- [x] 移行方針をビッグバンではなく段階移行に固定
- [x] 境界づけられたコンテキスト単位のフェーズを定義
- **Status:** complete

### Phase 3: Migration Plan Drafting
- [x] 具体的な移植フェーズを文書化
- [x] 各フェーズの成果物と完了条件を定義
- [x] リスクと未決定事項を整理
- **Status:** complete

### Phase 4: Validation
- [x] 既存 ALPS カバレッジと計画の整合性を確認
- [x] admin 未カバー領域を後半フェーズへ分離
- [x] 初期マイルストーンを storefront 優先に調整
- **Status:** complete

### Phase 5: Delivery
- [x] ユーザー向け要約を返す
- [x] 次の具体アクションを提案する
- [x] 長時間自律実行の運用方針を文書化する
- **Status:** complete

### Phase 6: Orchestrator V1
- [x] JSON-first workflow/packet/task/state schema を定義する
- [x] PHP + Composer + PHPUnit ベースで CLI を実装する
- [x] `task add`, `run next`, `run resume`, `run status`, `run fail`, `validate` を実装する
- [x] packet DSL と generic executor を実装する
- [x] `catalog/ProductList` packet definition を実装する
- [x] `catalog/Product` packet definition を実装する
- [x] `catalog/Category` packet definition を実装する
- [x] `cart/Cart` packet definition を実装する
- [x] `checkout/Shopping` packet definition を実装する
- [x] 実タスクで `task add -> run next -> run status` を確認する
- **Status:** complete

## Key Questions
1. 次の packet を `CategoryList` と `ShoppingConfirm` のどちらに置くか
2. packet DSL を移植先 repo へどう持ち出すか
3. 実移植 repo 側で `phpstan` / `phpcs` まで Day 0 に含めるか
4. storefront inventory を JSON task 群へどう分解するか

## Decisions Made
| Decision | Rationale |
|----------|-----------|
| ALPS を移植契約として扱う | 既に状態遷移と語彙が整理されており、機能退行の比較基準になるため |
| 段階移行を採用する | EC-CUBE は大規模で、front は ALPS カバレッジが高い一方 admin は未カバーが残るため |
| BEAR.Sunday を resource/interface 層、Be を domain transformation 層に置く | 公式資料の責務分離と、このリポジトリの ALPS 構造が自然に対応するため |
| 既存 DB は初期段階では維持する | ドメイン移植とスキーマ刷新を同時に行うとリスクが過大になるため |
| storefront から始め、admin は後ろに送る | front はほぼ 100% カバー、admin は 30 ルート未表現で探索コストが高いため |
| 長時間作業は file-based memory 前提で進める | コンテキスト切れやセッション断絶が起きても再開可能にするため |
| 1 work packet = 1 bounded context / 1明確な完了条件に制限する | 中断時の被害と曖昧さを減らすため |
| 実行基盤は PHP + Composer + PHPUnit で作る | 移植先の BEAR.Sunday/Be と同じ言語圏に揃え、ローカル依存を閉じ込めるため |
| workflow 定義は YAML ではなく JSON に固定する | schema validation と resume state の厳密性を優先するため |
| `src/bootstrap.php` は置かず Composer autoload に統一する | PHP の標準的な依存解決に寄せ、余計な初期化層を増やさないため |

## Errors Encountered
| Error | Attempt | Resolution |
|-------|---------|------------|
| なし | 1 | 調査のみで完了 |

## Notes
- 次に必要なのは「計画の承認」ではなく「移植の前提条件の固定」
- storefront / admin / plugin を分けてスコープ管理する
- ALPS の欠落がある admin 領域は、移植前に補完タスクを挟む
- 長時間運用の手順は `autonomous-execution-runbook.md` に集約した
- 実行基盤の入口は `php bin/orchestrator` と `composer test`
- packet は `.migrate/packets/*.json` の DSL として管理する
- `catalog/ProductList` packet は `.migrate/examples/tasks/001-catalog-product-list.json` で再実行できる
- `catalog/Product` packet は `.migrate/examples/tasks/002-catalog-product.json` で再実行できる
- `catalog/Category` packet は `.migrate/examples/tasks/003-catalog-category.json` で再実行できる
- `cart/Cart` packet は `.migrate/examples/tasks/004-cart-cart.json` で再実行できる
- `checkout/Shopping` packet は `.migrate/examples/tasks/005-checkout-shopping.json` で再実行できる
