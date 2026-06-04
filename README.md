<p align="center">
  <img src="docs/assets/bemart-title.png" alt="BeMart" width="760">
</p>

# BeMart — EC-CUBE 4.3 Application Overhaul

BeMart は、EC-CUBE 4.3 を意味論と境界へ分解し、ALPS / Be Framework /
BEAR.Sunday / Ray.MediaQuery SQL / Twig HTML へ再構成する
アプリケーション・オーバーホールの実証プロジェクトです。

Symfony 版 EC-CUBE の単なる書き直しではありません。EC-CUBE が持つ業務語彙、
状態遷移、永続化制約、HTTP affordance、HTML 表現を、実装から取り出して読める契約
として配置し直すことが目的です。外から見える振る舞いは残したまま、各要素を分解して
境界と責任を確かめ、一つずつ組み直す——いわば意味論のオーバーホールです。

機械のオーバーホールのように、分解、点検、再配置を通じて、元のプロジェクトをより高品質で持続性の高いものにすることを目標としました。

## 2パスマイグレーション

移植は 2 つの動きでできています。最初に EC-CUBE の Entity、Route、Controller、Twig から
語彙と状態遷移を逆算し、`alps.json` という意味構造の契約へ束ねます。次に、その契約を Be domain、
BEAR Resource、Ray.MediaQuery SQL、Twig HTML、Hypermedia test へ投影します。

```text
EC-CUBE source → ALPS contract → Be / Resource / SQL / HTML / Test
```

Fake は後付けの mock ではなく、最初の契約実装です。SQL 実装はあとから同じ Resource 契約を
満たすものとして差し替えられ、Context / DI がどちらを使うかを選びます。

## アーキテクチャの境界線

各境界線は、意味論を実装へ写像するときの依存方向、責務、表現変換の接続点です。

| 境界 | 役割 |
|---|---|
| ALPS | アプリケーション意味論・情報構造 |
| Be Framework | ドメイン境界（`Input` schema / `Being` / `Final`） |
| Ray.MediaQuery | ドメイン ↔ インフラ境界。PHP interface ↔ SQL file、SQL row/result ↔ domain object |
| BEAR.Sunday | HTTP request ↔ リソース境界（URI / HTTP method / `on*` method parameter / `ResourceObject`） |
| OpenAPI / API schema | PHP の `on*` method から生成される公開 HTTP 契約（parameter / status / representation shape） |
| Hypermedia | リソース ↔ クライアント遷移境界（`#[Link]` / `href` / `form action`） |
| Cache / freshness | Resource 表現 ↔ browser / proxy / CDN の鮮度境界（`CacheableResponse` / `Cache-Control` / `ETag` / `Vary` / invalidation） |
| Context / DI | 実装選択境界（Fake ↔ SQL、HTML ↔ JSON、test ↔ prod） |
| SQL schema | 永続化境界（table / column / FK / nullable / id shape） |

BeMart では、ALPS を意味論の source of truth とします。Be Framework はドメイン状態遷移を表し、
BEAR.Sunday Resource は HTTP / PHP 共通の Resource 境界を作ります。Ray.MediaQuery は SQL を
interface 境界に閉じ込め、Context / DI が Fake / SQL、HTML / JSON、test / prod の実装選択を担います。
Fake は最初の契約実装であり、SQL 実装は同じ Resource 契約を満たすものとして検証されます。
Twig HTML は EC-CUBE の affordance をできるだけ保持し、Hypermedia test は controller 内部ではなく、
link / form を辿って workflow を証明します。

OpenAPI の契約は PHP の Resource `on*` method から生成されます。入力 shape は
`on*` method parameter と Be `Input` schema で表します。

Taint tracking、DIP / ADP も境界制約として扱いますが、
README では詳細化しません。背景は [`docs/methodology/`](docs/methodology/) と
[`docs/skills/`](docs/skills/) を参照してください。

## 学び

- 仕様は実装より長く生きる。Framework が変わっても「商品」「注文」「顧客」の語彙は残る。
- 移植は境界の宣言である。残作業は未知の不足ではなく、既知の境界として分類できる。
- Hypermedia は UI 補助ではなく契約である。link / form が次状態への affordance になる。
- ALPS、Be、Resource、SQL file は、AI エージェントによる並列作業でも drift を抑える制約になる。

## まず読む順

| 知りたいこと | 読むもの |
|---|---|
| プロジェクトのゴール | [`docs/migration-goal-review.md`](docs/migration-goal-review.md) |
| 実証として何を示せたか | [`docs/FINAL-REPORT.md`](docs/FINAL-REPORT.md) |
| 現在の到達点 | [`docs/migration-status.md`](docs/migration-status.md) |
| 完全代替への残差 | [`docs/complete-replacement-residuals.md`](docs/complete-replacement-residuals.md) |
| flow / workflow の考え方 | [`docs/flow-ontology.md`](docs/flow-ontology.md) |
| ドキュメント全体の索引 | [`docs/README.md`](docs/README.md) |

## リポジトリの見方

| レイヤ | 主な成果物 |
|---|---|
| 意味論 | [`alps.json`](alps.json), [`alps.json.html`](alps.json.html), [`alps-doc/`](alps-doc/) |
| ドメイン | [`be/src`](be/src) |
| Resource | [`src/Resource`](src/Resource) |
| SQL 境界 | [`var/sql`](var/sql), [`sql/`](sql) |
| HTML | [`var/templates`](var/templates), [`public/`](public) |
| テスト | [`tests`](tests), [`tests/README.md`](tests/README.md) |
| 文書 | [`docs`](docs), [`docs/README.md`](docs/README.md) |

正典の ALPS profile は [`alps.json`](alps.json) です。HTML ドキュメントは生成物です。

## ステータス要約

詳細な数値と判断は [`docs/migration-status.md`](docs/migration-status.md) を正とします。
README では全体像だけを示します。

| 境界 | 証拠 |
|---|---|
| ALPS | descriptor と transition descriptor で語彙と状態遷移を機械可読にする |
| Be domain | Input / Being / Final が状態遷移の意味を表す |
| BEAR Resource | EC-CUBE route が ResourceObject 境界へ接続されている |
| SQL | Ray.MediaQuery interface と SQL file が永続化境界を分離する |
| HTML | storefront と in-scope admin の Twig 構造を移植済み |
| Tests | Resource / SQL / HTML render / Hypermedia / HTTP / Browser 系で境界を検証する |

重要な区別は次の通りです。

- セマンティック移植の実証: ほぼ成立。
- EC-CUBE 完全代替: 互換 fidelity と production proof が残る。

残差の詳細は
[`docs/complete-replacement-residuals.md`](docs/complete-replacement-residuals.md) に分離しています。
PDF、CSV、Mail、Template、MasterData、SQL target-engine 検証、production DB bring-up、
一部 HTML enrichment が主な対象です。

workflow test の方針は
[`docs/methodology/hypermedia-test-principle.md`](docs/methodology/hypermedia-test-principle.md) と
[`tests/README.md`](tests/README.md) を参照してください。

## コマンド

```bash
# All generated docs
composer doc

# ALPS only
composer doc:alps

# BEAR.ApiDoc only
composer doc:api

# Lightweight consistency checks
composer cs

# Serverless request runner
composer fake -- get '/products/list'
composer page -- get '/'

# Tests
vendor/bin/phpunit
vendor/bin/phpunit tests/Resource/Sql
composer test:http
```

この環境では PHP 8.5 を使います。

```bash
zsh -ic 'sphp85; composer tests'
```

SQL テストは `DATABASE_URL` と MariaDB 環境に依存します。詳細は
[`docs/complete-replacement-residuals.md`](docs/complete-replacement-residuals.md) と
[`docs/migration-status.md`](docs/migration-status.md) を参照してください。

## 生成・公開物

| 成果物 | 役割 |
|---|---|
| [ALPS docs](https://be-framework.github.io/BeMart/alps.json.html) | ALPS 生成ドキュメント |
| [BEAR.ApiDoc](https://be-framework.github.io/BeMart/api/) | BEAR.Sunday Page Resource API ドキュメント |
| [API terms](https://be-framework.github.io/BeMart/api/terms.html) | BEAR.ApiDoc の Term Usage Index |
| [API OpenAPI JSON](https://be-framework.github.io/BeMart/api/openapi.json) | BEAR.ApiDoc から生成される OpenAPI 3.1 JSON |
| [API llms.txt](https://be-framework.github.io/BeMart/api/llms.txt) | BEAR.ApiDoc から生成される LLM 向け API 要約 |
| [初期記事](https://be-framework.github.io/BeMart/) | EC-CUBE ソースから ALPS を逆算した記録 |

生成 HTML は、生成元がある場合は手で編集しません。

## 外部参照

- [ALPS manual](https://www.app-state-diagram.com/manuals/1.0/ja/index.html)
- [app-state-diagram](https://github.com/alps-asd/app-state-diagram)
- [EC-CUBE 4.3](https://github.com/EC-CUBE/ec-cube)
- [Be Framework](https://be-framework.github.io/llms-full.txt)
- [BEAR.Sunday](https://bearsunday.github.io/llms-full.txt)
