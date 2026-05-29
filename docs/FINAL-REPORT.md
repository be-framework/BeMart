# BeMart 実証総括 — Final Report

BeMart は EC-CUBE 4.3 を **ALPS → Be Framework → BEAR.Sunday** で組み直す移植の **実証プロジェクト** です。本書は「何を示せたか・何を学んだか・どこに限界を引いたか」を一枚に束ねた総括であり、各論は本文中のリンク先（`migration-status.md` / `HANDOVER.md` / `skills/` / `methodology/`）が正です。

> 主張: 私たちは EC-CUBE 4.3 の全機能を [`alps.json`](../alps.json) と [`eccube-feature-alps-status.html`](eccube-feature-alps-status.html) に棚卸しし、移植手法の実証として価値のある範囲を完了した。残差分は未知の不足ではなく、意図的に保留した既知の境界である。

## 1. 何を実証したか

- **意味論ファースト移植** — `alps.json` を契約（source of truth）とし、Be ドメイン・BEAR リソース・SQL・HTML はその投影として実装する。144 transition / 444 descriptor を ALPS 起点で展開しきった。
- **Fake → Schema → SQL の契約先行永続化** — まず Fake で契約を固定し、EC-CUBE スキーマ照合の後に同じ契約を満たす SQL を実装。34 storage interface すべてに Fake/SQL の双子が存在する（[G-23](skills/G-23-hypermedia-test-is-migration-contract.md) / [G-24](skills/G-24-ray-media-query-boundary.md) / [G-25](skills/G-25-bdr-domain-noun-values.md)）。
- **ハイパーメディア＝移植契約** — リソースの link / form を移植契約として扱い、Final 直叩きの統合テストを書かない（[methodology/hypermedia-test-principle.md](methodology/hypermedia-test-principle.md)）。
- **Context が実装を選ぶ** — アプリケーションコードは Fake/SQL・HTML/JSON・test/prod の別を知らない。選択は DI binding が行う。
- **明示的境界** — ドメイン / インフラ / リソース / HTML / SQL の境界を隠さず、テストで境界契約を固定する。
- **Be Framework を実規模で運用** — 8 つの Be パターンを 144 transition に適用した実務知見は [methodology/FRAMEWORK_REVIEW.md](methodology/FRAMEWORK_REVIEW.md)。

## 2. 規模（スナップショット）

| レイヤ | 到達 |
|---|---|
| ALPS spec | 144/144 transition · 444 descriptor |
| Be domain (`be/src`) | 144/144 transition |
| BEAR Resource (`src/Resource`) | 139 page resource（admin 93 + storefront 46） |
| SQL persistence (`be/src/Reason/Query/Sql*`) | 34/34 storage interface |
| HTML (`var/templates`) | 110 template（storefront 全ページ + in-scope admin 63/77） |
| Test | phpunit 1893 tests / 4002 assertions |

最新値は [`migration-status.md`](migration-status.md) が正（本表は総括時点のスナップショット）。

## 3. 得られた知見

- **移植 skill gap（G-14 〜 G-25）** — 同型の問題が 2 度現れた時点で「ad-hoc メモ」から「named G-NN」へ昇格した、独立して読める移植ルール集。索引は [skills/index.md](skills/index.md)。Ray.Di binding、Final 設計判断、Semantic 命名、idempotent DELETE、Ray.MediaQuery 境界など。`be-framework-skills` / `alps-skills` への上流貢献候補も同ファイルに整理。
- **方法論** — [methodology/](methodology/) に再利用可能な原則（hypermedia-test、query-naming、sql-test-baseline、html-route-coverage、csrf-protection）。
- **AI 時代の移植・API 設計** — [methodology/be-bear-ai-era.md](methodology/be-bear-ai-era.md)。意味論を URL/契約に載せることで LLM が外部参照ゼロで理解できる（AX）という副産物。
- **構築プロセスの決定ログ** — Pilot 1–15 / Wave 1–6 / Ray.MediaQuery cutover の経緯は [HANDOVER.md](HANDOVER.md)。

## 4. 限界と「完全代替」への差分

実証として不要だが本番 EC-CUBE の完全代替には必要な差分は、[README](../README.md) の "Scope" 節と [`migration-status.md`](migration-status.md) §4 が正:

- **ドメイン stub** — `doCreateOrder` / CSV インポート 4 種 / プラグイン lifecycle（入力は受理するが永続副作用なし）
- **EC-CUBE 互換 fidelity 残差**（[#24](https://github.com/be-framework/be-mart/issues/24)）— PDF 完全一致 / CSV フォーマット / Mail / Template file 副作用 / MasterData adapter
- **HTML enrichment backlog** — Mypage dashboard / Favorite / Address / Contact
- **設計上の out-of-scope** — プラグイン機構（[#3](https://github.com/be-framework/be-mart/issues/3)）/ Store・Plugin install・search サブツリー
- **本番移行** — 本番 DB の bring-up / cutover

これらが fix されれば BeMart は EC-CUBE 4.3 の完全な代替になる。差分は把握済みであり、未知ではない。

## 5. このプロジェクトが示すもの

BeMart は Be Framework + BEAR.Sunday + ALPS による移植手法の **referent implementation** である。既存の巨大な Symfony アプリケーションを、意味論を契約として保ちながらクリーンな核へ組み直せること、そしてその過程を AI 駆動で進められることを、実装と境界テストとドキュメントで示した。

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
