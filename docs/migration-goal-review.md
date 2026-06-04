# Migration Goal Review — EC-CUBE semantic overhaul

Last updated: 2026-06-04
Evidence snapshot: `1ccc7d5` (`origin/be-first-migration-bootstrap` after PR #34)

この文書は、BeMart を「EC-CUBE 互換実装」ではなく、巨大既存アプリケーションを意味と境界へ分解して再構成する **semantic overhaul** として再確認するためのレビューである。

`FINAL-REPORT.md` は実証総括、`migration-status.md` は到達点の正、`HANDOVER.md` は作業履歴である。本書はその上に立ち、プロジェクトの目標、達成証拠、残差分類、実証実験としての結論、次フェーズの進め方を一枚に束ねる。

---

## 1. ゴール再定義

BeMart の目標は、EC-CUBE 4.3 を別フレームワークへ単純に移すことではない。

目標は、EC-CUBE が持つ業務語彙、状態遷移、永続化制約、HTTP ルート、HTML affordance をいったん分解し、ALPS / Be Framework / BEAR.Sunday / Ray.MediaQuery / Twig HTML の境界へ再配置することである。

言い換えると、これは controller rewrite ではなく、車や時計のオーバーホールに近い。外形だけを移すのではなく、内部の意味を部品として取り出し、磨き直し、役割ごとの境界へ置き直す。その結果として、同じ業務語彙がより読める契約と交換可能な実装として現れる。

したがって、このプロジェクトの成功条件は「EC-CUBE の全画面が完全に同じ見た目で動くこと」だけではない。より重要なのは、以下が成立したかである。

| 成功条件 | 判定 |
|---|---|
| EC-CUBE の意味を機械可読な契約へ逆算できたか | `alps.json` と route/status 表で成立 |
| 契約からドメイン、リソース、SQL、HTML を投影できたか | 5 レイヤの移植で成立 |
| Fake と SQL を同じ契約の実装として交換可能にできたか | Ray.MediaQuery とテスト境界で成立 |
| HTTP / HTML の遷移を affordance として残せたか | route-gate / hypermedia / fallback 方針で成立 |
| 未完了を未知の穴ではなく名前付き残差へ分類できたか | 本書と `migration-status.md` §4 で成立 |

---

## 2. 達成証拠

最新の正は `migration-status.md` である。ここでは、現 worktree の棚卸しとステータス文書に基づき、実証の根拠を並べる。

| レイヤ | 証拠 |
|---|---|
| ALPS | `alps.json` は 532 descriptor / 207 transition descriptor。147 behavioral transition と 60 route-gate transition に分かれ、機能とルート接続の両方を契約化している。 |
| Be domain | `be/src` は 147 Input / 148 Final / 14 Being を持つ。Final は「状態遷移が成立した証明」として使われる。Semantic は現ファイル棚卸しで 155、`migration-status.md` 記録では 154。ここは後述のドキュメント不整合として扱う。 |
| BEAR Resource | `src/Resource/Page` は 147 resource file。EC-CUBE route name / URL path / resource URI を接続し、BEAR ResourceObject を HTTP 境界として使っている。 |
| SQL / Ray.MediaQuery | `MediaQueryRuntimeModule::queryClasses()` が 51 query interface を登録する。`var/sql` は現棚卸しで 143 SQL file、`migration-status.md` 記録では 142 SQL file。Fake から SQL への移植後、境界は interface + SQL file へ整理済み。 |
| HTML | `var/templates` は 131 Twig template。storefront と in-scope admin editor waves、共有 Block / frame を含む。 |
| Hypermedia / HTTP | `tests/Hypermedia/WorkflowTest.php` と `tests/Http/WorkflowTest.php` が同じ workflow を in-process と実 HTTP / cookie boundary で検証する。 |
| Browser / site exploration | `docs/html-screen-migration-matrix.md` と `docs/ec-cube-site-exploration-gaps-2026-05-23.md` が、実 HTTP 探索で EC-CUBE と BeMart の到達・欠落を確認している。 |
| Test baseline | `migration-status.md` は 2026-06-01 の非 SQL suite 検証として 1322 tests / 22258 assertions / 220 skipped を記録する。SQL suite は MariaDB / `DATABASE_URL` 依存。 |

この証拠から言えることは、BeMart は「少数のサンプル移植」ではなく、EC-CUBE の広い面を ALPS 契約から多層に投影した実装付き実証である、ということだ。

---

## 3. 残差の分類

未完了を単に「残り」と呼ぶと、実証価値と完全代替への差分が混ざる。ここでは残差を5種類に分ける。

### A. 本質的残差

本番 EC-CUBE の完全代替に必要で、実装コストも意味的な重さもあるもの。

- PDF / CSV / Mail / Template / MasterData の EC-CUBE 互換 fidelity
- `doCreateOrder` / `doCheckout` の durable SQL path に対する target MariaDB engine での確認
- `order_item_register.sql` の `JSON_TABLE` 使用に関する MariaDB 10.11 portability 確認または書き換え
- production DB bring-up / cutover

これらは「移植手法の証明」を超えて、実運用互換を詰める領域である。

詳細な台帳は [`complete-replacement-residuals.md`](complete-replacement-residuals.md) に分離する。Feature matrix と Phase log は、残っている問題がカバー率ではなく、完全代替に必要な互換 fidelity と production verification であることを示している。

### B. 意図的スコープ外

実証の範囲から外したもの。

- プラグイン runtime / marketplace / install-search subtree
- `doInstallPlugin`
- Store/Plugin install/search の admin 画面群
- EC-CUBE runtime を恒久的に同居させる Anti-Corruption Layer の研究領域

これは未把握ではなく、境界として切ったもの。

### C. 薄い実装

ルートや画面は到達するが、EC-CUBE 忠実移植には resource body enrichment が足りないもの。

- Mypage dashboard
- Favorite
- Address
- Contact
- 一部 admin editor の細部、画像アップロード、検索条件、履歴、byte-exact な export 表現

これは「存在しない」ではなく「意味は接続済みだが厚みが足りない」状態である。

### D. 検証未完了

実装がある、または方針はあるが、強い証拠がまだ不足しているもの。

- SQL suite の target MariaDB green run
- production context の実 DB bring-up
- render-diff tests の `tools/ec-cube-source/` あり環境での継続実行
- Browser smoke の最新状態での再実施
- 2FA setup pre-auth challenge state の production cutover

ここは追加実装よりも、検証環境と受け入れ条件を整えることが先である。

### E. ドキュメント不整合

作業が進んだ結果、ステータス文書と現ファイル棚卸しに小さなズレが出ているもの。

- `migration-status.md` は Semantic 154 と記録するが、現棚卸しは `be/src/Semantic` 155 file。
- `migration-status.md` は SQL 142 file と記録するが、現棚卸しは `var/sql` 143 file。
- `docs/html-screen-migration-matrix.md` は 2026-05-22/23 時点の古い route/template 数を含む。

これらは実装の欠落ではないが、プロジェクトを方法論として提示するには先に揃えるべきである。

---

## 4. 実証実験としての結論

結論は、かなり強く **可能だった** でよい。

EC-CUBE 4.3 のような巨大な業務アプリケーションでも、ソースコードから意味を逆算し、ALPS を契約にして、ドメイン、HTTP リソース、SQL、HTML へ再投影できることは示せた。

ただし、最後に残るコストは controller の書き換えではない。残るのは、HTML 忠実度、業務詳細、帳票や CSV の byte-level fidelity、production DB cutover、プラグイン境界の扱いである。これは semantic migration の失敗ではなく、意味のオーバーホールが完了した後に残る「完全代替」のコストである。

この区別が重要である。

- 実証の問い: 巨大アプリの意味を分解し、境界ごとに再構成できるか。
- 完全代替の問い: EC-CUBE 4.3 の全副作用、全画面、全互換を本番運用レベルで再現できるか。

BeMart は前者に対して、実装とテストと文書で肯定的な答えを出した。後者に対しては、残差を分類し、次に何を詰めれば完全代替へ進むかを示した。

---

## 5. 次フェーズ計画

次は新機能を増やす前に、プロジェクトを方法論として提示できる状態へ整える。

### Phase 1 — Evidence alignment

- `migration-status.md`、`README.md`、`docs/README.md`、`html-screen-migration-matrix.md` の数値を現棚卸しへ揃える。
- Semantic / SQL file 数の差分を調べ、正しいカウントルールを文書化する。
- `eccube-feature-alps-status.html` の route/function status と `migration-status.md` §4 の残差分類を対応させる。

### Phase 2 — Verification gate

- `DATABASE_URL` ありの MariaDB 10.11 で SQL suite を実行する。
- `order_item_register.sql` の `JSON_TABLE` portability を確認し、必要なら MariaDB 互換の INSERT に書き換える。
- `tools/ec-cube-source/` ありで render-diff tests を再実行する。
- Hypermedia / HTTP / smoke suite を最新 base で再実行する。

### Phase 3 — Residual hardening

- 本質的残差から優先順位を付ける。最初は PDF/CSV/Mail より、SQL durability と production DB bring-up を先に置く。
- 薄い実装は Mypage dashboard / Favorite / Address / Contact の順で body enrichment する。
- 2FA pre-auth challenge state を production cutover の security residual として閉じる。

### Phase 4 — Methodology packaging

- `FINAL-REPORT.md`、本書、`skills/`、`methodology/` を、外部読者が追える順序へ整理する。
- 「semantic migration の実行手順」と「完全代替へ残る互換作業」を分けて説明する。
- Be Framework / BEAR.Sunday / Ray.MediaQuery に再利用可能な改善提案を切り出す。

---

## 6. 判断基準

今後の作業は、次の問いに答える形で進める。

| 問い | 判断 |
|---|---|
| これは semantic overhaul の証拠を強くするか | 強くするなら優先 |
| これは完全代替に必要な互換 residual か | 必要なら分類して進める |
| これは意図的スコープ外を曖昧に戻していないか | 戻しているなら避ける |
| これは残差の名前を増やすだけか、残差を閉じるか | 閉じるものを優先 |
| これは実装の追加か、証拠の強化か | まず証拠の強化を優先 |

BeMart の価値は、単に動くコードの量ではない。巨大な既存アプリケーションを「読める意味」「明示された境界」「検証可能な契約」へ変換できたことにある。次フェーズは、その価値を崩さず、完全代替に必要な残差を順に閉じていく。
