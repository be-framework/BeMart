---
layout: default
title: "BeMart 実証総括 — Final Report"
---

# BeMart 実証総括 — Final Report

BeMart は EC-CUBE 4.3 を **ALPS → Be Framework → BEAR.Sunday → Ray.MediaQuery → HTML** で組み直すアプリケーション・オーバーホールの実証プロジェクトです。本書は「何を示せたか・何を学んだか・どこに限界を引いたか」を一枚に束ねた総括であり、各論は本文中のリンク先（`migration-status.md` / `HANDOVER.md` / `skills/` / `methodology/`）が正です。

> 主張: 私たちは EC-CUBE 4.3 の全機能を [`alps.json`](../alps.json) と [`eccube-feature-alps-status.html`](eccube-feature-alps-status.html) に棚卸しし、移植手法の実証として価値のある範囲を完了した。残差分は未知の不足ではなく、意図的に保留した既知の境界である。

## 1. 何を実証したか

- **意味論ファースト移植** — `alps.json` を契約（source of truth）とし、Be ドメイン・BEAR リソース・SQL・HTML はその投影として実装する。現行 `alps.json` は 532 descriptor / 207 transition descriptor を持ち、147 の振る舞い契約と 60 の `alps-route-gate` 契約に分かれる。
- **Fake → Schema → Ray.MediaQuery の契約先行永続化** — まず Fake で契約を固定し、EC-CUBE スキーマ照合の後に同じ契約を満たす SQL を実装。SQL境界は PHP PDO adapter ではなく、51 interface / 143 `#[DbQuery]` / 143 SQL file の Ray.MediaQuery 境界へ切り替えた（[G-23](skills/G-23-hypermedia-test-is-migration-contract.md) / [G-24](skills/G-24-ray-media-query-boundary.md) / [G-25](skills/G-25-bdr-domain-noun-values.md)）。
- **ハイパーメディア＝移植契約** — リソースの link / form を移植契約として扱い、Final 直叩きの統合テストを書かない（[methodology/hypermedia-test-principle.md](methodology/hypermedia-test-principle.md)）。
- **Context が実装を選ぶ** — アプリケーションコードは Fake/SQL・HTML/JSON・test/prod の別を知らない。選択は DI binding が行う。
- **明示的境界** — ドメイン / リソース / HTML / SQL / compatibility adapter / production cutover の境界を隠さず、テストで境界契約を固定する。
- **Be Framework を実規模で運用** — `Input → Being → Final` の構造を実アプリ規模で運用し、Final を状態遷移成立の証明として扱った。実務知見は [methodology/FRAMEWORK_REVIEW.md](methodology/FRAMEWORK_REVIEW.md)。

## 2. 規模（スナップショット）

| レイヤ | 到達 |
|---|---|
| ALPS spec | 532 descriptor · 207 transition descriptor（147 behavioral + 60 route-gate） |
| Be domain (`be/src`) | 147 Input · 148 Final · 155 Semantic · 14 Being |
| BEAR Resource (`src/Resource`) | 147 page/support resource files |
| SQL persistence (`var/sql` + MediaQuery) | 51 interface · 143 `#[DbQuery]` · 143 SQL file |
| HTML (`var/templates`) | 131 Twig template（storefront + in-scope admin + shared blocks/frames） |
| Test | `migration-status.md` のベースラインを正とする。SQL suite は MariaDB/MySQL 依存。 |

最新値は [`migration-status.md`](migration-status.md) が正（本表は総括時点のスナップショット）。

## 3. 得られた知見

- **移植 skill gap（G-14 〜 G-25）** — 同型の問題が 2 度現れた時点で「ad-hoc メモ」から「named G-NN」へ昇格した、独立して読める移植ルール集。索引は [skills/index.md](skills/index.md)。Ray.Di binding、Final 設計判断、Semantic 命名、idempotent DELETE、Ray.MediaQuery 境界など。`be-framework-skills` / `alps-skills` への上流貢献候補も同ファイルに整理。
- **方法論** — [methodology/](methodology/) に再利用可能な原則（hypermedia-test、query-naming、sql-test-baseline、html-route-coverage、csrf-protection）。
- **AI 時代の移植・API 設計** — [methodology/be-bear-ai-era.md](methodology/be-bear-ai-era.md)。意味論を URL/契約に載せることで LLM が外部参照ゼロで理解できる（AX）という副産物。
- **構築プロセスの決定ログ** — Pilot 1–15 / Wave 1–6 / Ray.MediaQuery cutover の経緯は [HANDOVER.md](HANDOVER.md)。
- **境界の切り方** — 未完了を「できていない画面」ではなく、compatibility fidelity、HTML enrichment、production cutover、out-of-scope plugin runtime として分類できた。これは移植作業そのものの成果である。

## 4. 限界と「完全代替」への差分

実証として不要だが本番 EC-CUBE の完全代替には必要な差分は、[README](../README.md) の "Scope" 節と [`migration-status.md`](migration-status.md) §4 が正:

- **ドメイン/互換 residual** — `doCreateOrder` / `doCheckout` は PurchaseFlow + `dtb_order_item` snapshot writes まで実装済み。残るのは order-item SQL の MariaDB 10.11 target-engine 検証または `JSON_TABLE` なしの INSERT への置換、`doImportProductCsv` の意図的未移植、`doUpdateCsv` を消費する export fidelity、`doInstallPlugin` の plugin runtime。
- **EC-CUBE 互換 fidelity 残差**（[#24](https://github.com/be-framework/BeMart/issues/24)）— PDF 完全一致 / CSV フォーマット / Mail / Template file 副作用 / MasterData adapter。Category CSV と Shipping CSV は永続化面まで実装済みだが、byte-exact な完全互換は別境界として残る。
- **HTML enrichment backlog** — Mypage dashboard / Favorite / Address / Contact
- **設計上の out-of-scope** — プラグイン機構（[#3](https://github.com/be-framework/BeMart/issues/3)）/ Store・Plugin install・search サブツリー
- **本番移行** — 本番 DB の bring-up / cutover

これらが fix されれば BeMart は EC-CUBE 4.3 の完全な代替になる。差分は把握済みであり、未知ではない。

## 5. このプロジェクトが示すもの

BeMart は Be Framework + BEAR.Sunday + ALPS による移植手法の **referent implementation** である。既存の巨大な Symfony アプリケーションを、意味論を契約として保ちながらクリーンな核へ組み直せること、そしてその過程を AI 駆動で進められることを、実装と境界テストとドキュメントで示した。

単なる移植ではない。EC-CUBE の業務語彙を保ち、実装の境界を引き直し、互換性の残差を名前付きで隔離することで、古いアプリケーションを「読める契約」と「交換可能な実装」へ変換した。この変換そのものが BeMart の成果である。

## 関連ドキュメント

| 知りたいこと | 参照 |
|---|---|
| 機能一覧（正） | [`alps.json`](../alps.json) · [`eccube-feature-alps-status.html`](eccube-feature-alps-status.html) |
| 移植ステータス（正） | [`migration-status.md`](migration-status.md) |
| 構築プロセスの決定ログ | [`HANDOVER.md`](HANDOVER.md) |
| 移植知見（G-14〜G-25） | [`skills/`](skills/) |
| 方法論・原則 | [`methodology/`](methodology/) |
| 画面移植マトリクス | [`html-screen-migration-matrix.md`](html-screen-migration-matrix.md) |
| ドキュメント索引 | [`README.md`](README.md) |
