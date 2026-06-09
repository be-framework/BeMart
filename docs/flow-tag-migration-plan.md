---
layout: default
title: "フロータグ移行計画（Flow Tag Migration Plan）"
---

# フロータグ移行計画（Flow Tag Migration Plan）

Last updated: 2026-06-04

この計画は、現行 `flow-*` タグを破壊せずに、Feature matrix 用の機能領域タグと、自然言語で定義された本来の flow タグを分離するための移行方針である。

Current ALPS status:

- `alps.json` は、旧 `flow-*` を互換用に残したまま対応する `feature-*` を併記している。
- `docs/flow-ontology.md` の 13 個の新しい `flow-*` は、最小受け入れ導線に参加する descriptor へ付与済みである。
- HTML / SVG 生成物（root と `docs/` 配下）は `alps.json` から再生成済みである。

---

## 問題（Problem）

移行前の旧 `flow-*` は、ほぼ Feature matrix 用の機能領域として使われていた。

例:

- `flow-manage-product` は商品作成、更新、削除、CSV、カテゴリ、タグ、規格をまとめて含む。
- `flow-purchase` は cart / checkout 系の領域をまとめて含む。
- `flow-manage-order` は受注編集、CSV、PDF、メール送信をまとめて含む。

これはカバー率を把握するには有効だが、ハイパーメディアとして意味ある業務導線を表すには粗い。自然言語シナリオからテスト可能な長いリンクを導出するには、旧 `flow-*` を `feature-*` として退避し、新しい `flow-*` を「業務導線」として定義する必要がある。

---

## 目標語彙（Target Vocabulary）

| Prefix | Role | Example |
|---|---|---|
| no prefix | Domain / business vocabulary | `catalog`, `cart`, `checkout`, `order`, `account` |
| `feature-*` | Feature area / coverage bucket | `feature-admin-catalog`, `feature-purchase` |
| `flow-*` | Semantic business journey | `flow-admin-product-publish`, `flow-customer-purchase` |
| `actor-*` | Actor | `actor-admin`, `actor-customer` |
| `page-*` | Browser-visible page role | `page-admin`, `page-edit`, `page-complete` |

正準の業務領域は既存の domain tag と `actor-*` で表す。`feature-*` は Feature matrix や coverage report が明示的な coverage bucket を必要とする場合の補助軸である。

`feature-*` は今の `flow-*` の置き換え先である。`flow-*` は `docs/flow-ontology.md` で自然言語定義された業務導線にだけ使う。

---

## 監査結果（Audit Findings）

移行前の旧 `flow-*` は 142 descriptor に 143 回付与されていた。大半は transition descriptor で、状態をまたいだ業務導線ではなく、操作索引や Feature matrix の分類として使われていた。

注意すべき混同:

- `flow-manage-*` は domain tag と `actor-admin` の組み合わせで表せる内容を重複している。
- `goProduct` のように、同じ descriptor が storefront browse と admin product 管理の両方に見えるケースがある。
- 一部の `flow-*` 付き transition は `actor-*` が薄く、誰の導線かがタグだけでは読みにくい。
- `flow-admin-auth` は `alps.json` に存在するが、以前のタグ表では明示されていなかった。

---

## 現行タグからの対応表（Mapping From Current Tags）

| Current tag | Target feature tag |
|---|---|
| `flow-browse` | `feature-browse` |
| `flow-purchase` | `feature-purchase` |
| `flow-register` | `feature-register` |
| `flow-account` | `feature-account` |
| `flow-favorite` | `feature-favorite` |
| `flow-inquiry` | `feature-inquiry` |
| `flow-admin-auth` | `feature-admin-auth` |
| `flow-manage-product` | `feature-admin-catalog` |
| `flow-manage-order` | `feature-admin-order` |
| `flow-manage-customer` | `feature-admin-customer` |
| `flow-manage-shop` | `feature-admin-shop` |
| `flow-manage-content` | `feature-admin-content` |
| `flow-manage-cms` | `feature-admin-cms` |
| `flow-manage-system` | `feature-admin-system` |
| `flow-manage-mail` | `feature-admin-mail` |
| `flow-manage-plugin` | `feature-admin-plugin` |

---

## 移行フェーズ（Migration Phases）

### Phase 1 — ドキュメントのみ（Documentation only）

- Add `docs/flow-ontology.md`.
- Add this migration plan.
- Update `docs/tag.md` so current `flow-*` is described as legacy feature-area usage.
- Do not edit `alps.json`.

Status: complete.

### Phase 2 — 機能タグ導入（Feature tag introduction）

- Add `feature-*` tags to `alps.json` alongside existing `flow-*` only where the coverage/reporting layer still needs explicit feature buckets.
- Prefer domain tag + `actor-*` when that is enough to express the feature area.
- Keep Feature matrix working by reading either tag family during transition.
- Regenerate ALPS artifacts after validation.

Status: complete for legacy `flow-*` descriptors.

### Phase 3 — 真のフロータグ付け（True flow tagging）

- Apply new `flow-*` tags from `docs/flow-ontology.md` to the relevant page and transition descriptors.
- Flow tags should be attached only when the descriptor participates in the natural-language journey.
- Avoid adding a flow tag to broad helper descriptors that are not part of the acceptance path.

Status: initial ALPS tagging complete. Executable flow fixtures and tests are still future work.

### Phase 4 — マトリクスとテスト（Matrix and tests）

- Update Feature matrix to use `feature-*`.
- Add flow verification matrix based on `flow-*`.
- Implement corresponding workflow evidence (PHP Resource / HTTP) / Browser checks.

### Phase 5 — 非推奨整理（Deprecation cleanup）

- Remove legacy `flow-*` feature-area tags once `feature-*` and true `flow-*` are both in use and all docs/tests have moved.
- Keep a short migration note in `docs/tag.md`.

---

## 真のフロータグ適用ルール（Rules For Applying True Flow Tags）

- A `flow-*` tag must correspond to a named flow in `docs/flow-ontology.md`.
- A flow may cross multiple feature areas.
- A flow must have a natural-language actor, intent, start condition, goal condition, semantic postcondition, and verification target.
- A flow tag is not a replacement for `actor-*`, `page-*`, `domain`, or `feature-*`; it is an additional acceptance-journey axis.
- If a descriptor can belong to many journeys, tag it only when it is part of the minimal acceptance path for that flow.
- Avoid umbrella flows that only say "maintain data" or "operate admin". CSV exchange, master data update, template lifecycle, and mail template maintenance are separate flows because their evidence and failure modes are different.

---

## 最初の移行候補（First Migration Candidate）

Start with `flow-admin-product-publish`.

Reason:

- It crosses admin and storefront, proving that flow tags can span feature areas.
- It validates a high-value claim: an admin-created product can appear in the customer-facing browse surface.
- It touches current residuals without requiring PDF/CSV/Mail production fidelity.

Expected feature span:

- `feature-admin-catalog`
- `feature-browse`

Expected verification:

- Hypermedia workflow test
- HTTP workflow test
- Browser smoke
