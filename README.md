# BeMart — EC-CUBE 4.3 Application Overhaul

BeMart は、EC-CUBE 4.3 を **意味論と境界へ分解し、再構成する** アプリケーション・オーバーホールの実証プロジェクトです。Symfony版EC-CUBEの fork や controller 書き換えではなく、EC-CUBE が持つ語彙・状態遷移・HTML構造・永続化の制約を `alps.json` に逆算し、Be Framework + BEAR.Sunday + Ray.MediaQuery + Twig HTML へ投影し直します。

> BeMart is not a controller rewrite of EC-CUBE. It is a semantic migration with explicit boundaries.

このリポジトリが示したいのは「EC-CUBEを別フレームワークへ移す」ことだけではありません。大きな既存アプリケーションを、**意味論を正、境界を契約、テストを証明**として組み直せるか。その問いへの実装付きの答えです。実証の全体像（何を学び、何を成し遂げ、何をまだ残しているか）は [`docs/FINAL-REPORT.md`](docs/FINAL-REPORT.md) に束ねています。

## Current State

| Boundary | Current evidence |
|---|---|
| ALPS | `alps.json` は 532 descriptor / 207 transition descriptor。うち 147 は振る舞いの移植契約、60 は `alps-route-gate` のルート接続契約。 |
| Be domain | 147 Input / 148 Final / 155 Semantic / 14 Being。Final は状態遷移が成立した証明として扱う。 |
| BEAR Resource | `src/Resource/Page` に 147 page resource/support resource。Aura route から EC-CUBE route name ↔ URL path ↔ resource URI を接続する。 |
| SQL | Ray.MediaQuery の 51 interface / 143 `#[DbQuery]` / 143 SQL file。PHP実クラスのPDO adapterではなく、interface + SQL file が永続化境界。 |
| HTML | `var/templates` に 131 Twig template。storefront 全ページと in-scope admin editor waves を移植済み。 |
| Tests | `docs/migration-status.md` のベースラインを正とする。非SQL suite はDBなしで動く。SQL suite は MariaDB/MySQL を必要とする。 |

## What We Learned

- **仕様は実装より長く生きる。** EC-CUBE は framework を変えてきたが、「商品」「注文」「顧客」「配送」の語彙と遷移は残る。ALPS はその長寿命な部分を取り出す。
- **移植は境界の宣言である。** ドメイン、リソース、HTML、SQL、compatibility adapter、production cutover を混ぜないと、残作業が「未知の不足」ではなく「既知の境界」になる。
- **Fake は mock ではなく最初の実装である。** Fake で契約を固定し、Ray.MediaQuery/SQL が同じ Resource 契約を満たすことを hypermedia test で示す。
- **AI エージェントには構造が効く。** ALPS、Be の `Input → Being → Final`、BEAR の URI/resource、MediaQuery の interface/SQL は、並列作業でも drift を抑える制約になる。

## What Is Done / Not Done

**Done:** EC-CUBE の機能を ALPS と route-status 表に棚卸しし、Be domain、BEAR resource、Ray.MediaQuery SQL 境界、storefront/admin HTML の主要面を実装した。移植中に得た再利用可能な知見は [`docs/skills/`](docs/skills/) と [`docs/methodology/`](docs/methodology/) に分離した。

**Not done:** 本番 EC-CUBE の完全代替に必要な互換 fidelity と cutover は残る。中心は `doCreateOrder` / `doCheckout` の target-engine SQL 検証、PDF/CSV/Mail/Template/MasterData の byte/副作用互換、Mypage/Favorite/Address/Contact の HTML enrichment、production DB bring-up。これは未把握の穴ではなく、意図して切った境界です。

## Difference from Symfony EC-CUBE

BeMart は EC-CUBE の機能や画面構造を否定するものではありません。違いは、振る舞いの定義がどこにあるかです。

| 観点 | EC-CUBE 4.3（Symfony） | BeMart |
|---|---|---|
| 振る舞いの定義 | Controller のコード | ALPS の意味論（契約） |
| 「何ができるか」を知る | コードを読む | 契約を読む |
| 層の境界 | 慣習として存在する | テストで固定された契約 |
| 実装の選択 | 環境設定 | Context / DI が選ぶ。コードは知らない |
| 仕様の所在 | 実装の中 | 実装の外（`alps.json`） |

意味論は実装より長く生きる。EC-CUBE は 2.x → 3.x → 4.x とフレームワークを変えてきたが、「商品」「注文」「顧客」の語彙は変わっていない。

## Core Ideas

- **Semantics first** — ALPSで descriptor、transition、actor、page role を明示する。
- **Fake → Schema → Ray.MediaQuery** — Fakeで契約を固定し、EC-CUBEスキーマ照合後に interface + SQL file として永続化境界を実装する。
- **Explicit boundaries** — ドメイン、インフラ、リソース、HTML、SQLの境界を隠さない。
- **Context chooses implementation** — アプリケーションは Fake / SQL のどちらが有効かを知らない。
- **Hypermedia is a contract** — 画面は表示だけでなく、リンクとフォームが次状態への affordance になって完了。

## Architecture Boundaries

| Boundary | Role |
|---|---|
| ALPS | アプリケーション意味論・情報構造 |
| Be Framework | ドメイン境界（`Input` / `Being` / `Final`） |
| Ray.MediaQuery | ドメイン ↔ インフラ境界。PHP interface / return type ↔ SQL file / result |
| BEAR.Sunday | ドメイン ↔ リソース境界（`ResourceObject` / URI / HTTP method） |
| Hypermedia | リソース ↔ クライアント遷移境界（`#[Link]` / `href` / `form action`） |
| Context / DI | 実装選択境界（Fake ↔ SQL、HTML ↔ JSON、test ↔ prod） |
| SQL schema | 永続化境界（table / column / FK / nullable / id shape） |

Taint tracking、cache freshness、DIP / ADP も境界制約として扱いますが、READMEでは詳細化しません。背景は [`docs/methodology/`](docs/methodology/) と [`docs/skills/`](docs/skills/) を参照してください。

## Migration Workflow

移植は2つの動きでできています —— 逆算と投影。

**逆算 — レガシーから契約を取り出す。**

まず EC-CUBE 4.3 のソースから情報設計をやり直します。Doctrine Entity（語彙）・`@Route`（状態遷移）・Controller（操作の意味）・Twig テンプレート（画面のデータ）—— 4つの情報源を読み、ソースに散らばっていた「何ができて、データがどう流れるか」を機械可読な契約 `alps.json` に束ねる。全ディスクリプタに情報源タグ（`src-entity` / `src-router` / `src-controller` / `src-template`）が付き、どのフィールドがどのソースに由来するか追跡できる。この逆算の経緯は記事 [ソースコードから情報設計を逆算する](docs/index.md) に詳しい。

**投影 — 契約から実装を起こす。**

確定した ALPS を起点に、下のレイヤを順に実装します。実装順は固定：

```text
ALPS → Fake → EC-CUBE schema alignment → Ray.MediaQuery SQL → Resource/Form → HTML/Browser
```

Fake は後付け mock ではなく、最初の契約実装。Ray.MediaQuery SQL は後から同じ契約を満たすことをテストで証明する。実装の選択は Context / DI binding が行うため、アプリケーションコードは Fake / SQL の違いを知らない。

契約はレガシーから生まれ、実装は契約から生まれる。

## Hypermedia and HTML Policy

HTML は EC-CUBE 4.3 の Twig 構造をできるだけ忠実に移植します。ただし、BeMartに存在しないデータは捏造せず、差分は residual として明示します。

未対応機能のリンクやボタンは隠しません。JS有効時は alert で未対応を説明し、JS無効時は安全な `501 Not Implemented` にフォールバックします。これはEC-CUBEの情報構造と affordance を画面上に残すためです。

ハイパーメディアテストの考え方は [`docs/methodology/hypermedia-test-principle.md`](docs/methodology/hypermedia-test-principle.md) を参照してください。

## Testing

BeMartでは、テストを単なる回帰確認ではなく **境界契約** として扱います。

| Test | Checks |
|---|---|
| Resource tests | ResourceObject status / body / headers / links |
| SQL tests | SQL実装がFake契約と同型であること |
| HTML render tests | EC-CUBE Twig忠実度とresidual |
| Hypermedia tests | PHP内と実HTTPで link / form / route が解決されること |
| Browser smoke | 実ブラウザで主要導線が成立すること |

Static analysis / taint tracking と cache freshness check は導入中の品質ゲートです。

## Scope — 実証完了と「完全代替」への差分

BeMart は **実証プロジェクト** です。EC-CUBE 4.3 の全ルート・全機能を [`alps.json`](alps.json) と [`docs/eccube-feature-alps-status.html`](docs/eccube-feature-alps-status.html) に棚卸しした上で、移植手法（ALPS → Be Framework → BEAR.Sunday → Ray.MediaQuery → HTML）の実証として価値のある範囲を完了しています。

> ALPS 532 descriptor / 207 transition descriptor · Be domain 147 Input / 148 Final / 155 Semantic · Ray.MediaQuery 51 interface / 143 SQL query · HTML 131 Twig templates

残るのは「実証としては不要だが、本番 EC-CUBE の **完全代替** には必要」な差分だけです。**これは未知の不足ではなく、私たちが EC-CUBE 全体を把握した上で意図的に保留した既知の残作業**です。下記がすべて fix されれば、BeMart は EC-CUBE 4.3 の完全な代替になります。

### A. ドメイン stub（入力は受理するが永続副作用なし）

- 受注確定 — `doCreateOrder` / `doCheckout`（PurchaseFlow + `dtb_order_item` snapshot writes は実装済み。残りは `order_item_register.sql` の MariaDB 10.11 target-engine 検証または `JSON_TABLE` なしの INSERT への置換）
- 商品CSV — `doImportProductCsv`（この移植では export-only として扱い、意図的に未移植）
- CSV設定 — `doUpdateCsv`（column config は保持するが、その設定を消費する全 export Final の互換 fidelity は残る）
- プラグイン lifecycle — `doInstallPlugin`（download・unzip・migrate・container 再生成なし。plugin scope は out-of-scope）

### B. EC-CUBE 互換 fidelity 残差（[#24](https://github.com/koriym/ec-cube-alps/issues/24)）

- PDF — 帳票レイアウト完全一致 / `dtb_order_pdf` 保存設定 / 複数配送（到達・download ヘッダ・`%PDF-` 生成は実装済み）
- CSV — EC-CUBE 互換フォーマット + streaming/download 境界
- Mail — 本文生成 / テンプレート解決 / 送信の忠実再現
- Template 管理 — install / download / delete / select の file 副作用
- MasterData adapter

### C. HTML enrichment backlog（resource body が薄く忠実移植に未到達）

- Mypage dashboard / Favorite / Address / Contact

### D. 設計上の out-of-scope

- プラグイン機構・マーケットプレイス（[#3](https://github.com/koriym/ec-cube-alps/issues/3) — Anti-Corruption Layer による恒久 legacy 同居の研究構想）
- 管理 Store/Plugin install/search サブツリー（~14 ページ）

### E. 本番移行

- 本番 DB の bring-up / cutover（seed script と prod `SqlModule` binding は実装済み）

最新の詳細は [`docs/migration-status.md`](docs/migration-status.md) §4 Outstanding work、互換隔離の進行は [#24](https://github.com/koriym/ec-cube-alps/issues/24) を参照してください。

## Quick Links

| Document | Purpose |
|---|---|
| [`docs/FINAL-REPORT.md`](docs/FINAL-REPORT.md) | 実証総括 — 何を示せたか・知見・限界 |
| [`docs/skills/`](docs/skills/) | 移植中に発見した再利用可能な境界ルール（G-14〜G-25） |
| [`docs/methodology/`](docs/methodology/) | Hypermedia test、Ray.MediaQuery、AI時代の Be/BEAR などの方法論 |
| [`alps.json`](alps.json) / [`alps.json.html`](alps.json.html) | ALPS プロファイルと HTML ドキュメント |
| [`openapi.yaml`](openapi.yaml) / [`openapi.html`](openapi.html) | OpenAPI 出力と HTML ドキュメント |
| [`docs/migration-status.md`](docs/migration-status.md) | 移植ステータス（正） |
| [`docs/html-screen-migration-matrix.md`](docs/html-screen-migration-matrix.md) | 画面 / ルート移植マトリクス |
| [`docs/eccube-feature-alps-status.html`](docs/eccube-feature-alps-status.html) | EC-CUBE route/function ↔ ALPS ID ↔ 実装状態 ↔ 移植難易度 |
| [`docs/tag.md`](docs/tag.md) | ALPS タグ分類 |
| [`docs/README.md`](docs/README.md) | ドキュメント索引 |

## Repository Map

| Path | Purpose |
|---|---|
| `alps.json` | 正典 ALPS プロファイル |
| `be/` | Be Framework ドメイン層 |
| `src/` | BEAR.Sunday アプリケーション / リソース層 |
| `var/templates/` | EC-CUBE Twig HTML 移植 |
| `public/` | HTTP フロントコントローラと静的アセット |
| `sql/` | EC-CUBE スキーマ・seed データ・SQL setup |
| `docs/` | プロジェクトドキュメントと GitHub Pages 成果物 |
| `tests/` | Resource / SQL / HTML render / HTTP hypermedia テスト |

最新ステータスは [`docs/migration-status.md`](docs/migration-status.md)、画面移植状況は [`docs/html-screen-migration-matrix.md`](docs/html-screen-migration-matrix.md) を参照してください。

## Commands

```bash
# Validate ALPS
asd --validate alps.json

# Generate ALPS HTML / SVG
asd -f html -o alps.json.html alps.json
asd -f svg -o alps.svg alps.json

# Serverless request runner (method + path/query)
composer fake -- get '/products/list'
composer dev -- post '/shopping/checkout?preOrderId=aaaa00000000000000000000000000000000aaaa&csrfToken=fake-csrf-token-bemart-2026'
composer page -- get '/'

# Run tests
vendor/bin/phpunit
vendor/bin/phpunit tests/Http/HttpHypermediaTest.php
vendor/bin/phpunit tests/Resource/Sql
```

各エントリポイントは既定の context を固定します（`bin/fake.php` → `cli-fake-hal-api-app`、`bin/page.php` → `cli-html-hal-app`）。`APP_CONTEXT` は一時的な上書き用の escape hatch にすぎません。SQL テストは [`sql/`](sql/) から用意したローカル MariaDB/MySQL を必要とします。

## References

- [ALPS manual](https://www.app-state-diagram.com/manuals/1.0/ja/index.html)
- [app-state-diagram](https://github.com/alps-asd/app-state-diagram)
- [EC-CUBE 4.3](https://github.com/EC-CUBE/ec-cube)
- [Be Framework](https://be-framework.github.io/llms-full.txt)
- [BEAR.Sunday](https://bearsunday.github.io/llms-full.txt)
