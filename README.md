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

まず EC-CUBE から集めた意味論・情報構造を ALPS に固定します。そこから Be Framework が
ドメイン状態遷移を表し、BEAR.Sunday Resource が HTTP / PHP 共通の Resource 境界を作ります。
Ray.MediaQuery は SQL を interface 境界に閉じ込め、Context / DI が Fake / SQL、HTML / JSON、
test / prod の実装選択を担います。Fake は最初の契約実装であり、SQL 実装は同じ Resource 契約を
満たすものとして検証されます。Twig HTML は EC-CUBE の affordance をできるだけ保持し、
Hypermedia test は controller 内部ではなく、link / form を辿って workflow を証明します。

Taint tracking、DIP / ADP も境界制約として扱いますが、
README では詳細化しません。背景は [`docs/methodology/`](docs/methodology/) と
[`docs/skills/`](docs/skills/) を参照してください。

## 学び

- 仕様は実装より長く生きる。Framework が変わっても「商品」「注文」「顧客」の語彙は残る。
- 移植は境界の宣言である。残作業は未知の不足ではなく、既知の境界として分類できる。
- Hypermedia は UI 補助ではなく契約である。link / form が次状態への affordance になる。
- ALPS、Be、Resource、SQL file は、AI エージェントによる並列作業でも drift を抑える制約になる。

## ドキュメント

詳細なドキュメントは [`docs/`](docs/) にまとめています。
公開版は [GitHub Pages](https://be-framework.github.io/BeMart/) で確認できます。

## ディレクトリ

```text
.
├── alps.json        # SSOT: ALPS profile
├── alps-doc/        # descriptor ごとの補足ドキュメント
├── be/src/          # Be Framework domain
├── src/Resource/    # BEAR.Sunday Resource
├── var/sql/         # Ray.MediaQuery SQL files
├── sql/             # EC-CUBE schema / seed / SQL bring-up
├── var/templates/   # Twig HTML ports
├── public/          # HTTP entrypoints
├── tests/           # Resource / SQL / HTML / HTTP / workflow tests
└── docs/            # Project documentation
```

生成 HTML / SVG / API docs は生成物です。生成元がある場合は手で編集しません。

## 起動

SQL-backed context は [`malt`](https://github.com/koriym/homebrew-malt) と `DATABASE_URL` を使います。
DB 初期化の詳細は [`sql/README.md`](sql/README.md) を参照してください。

```bash
# First-time setup
brew tap shivammathur/php
brew tap shivammathur/extensions
brew tap koriym/malt
brew install malt
malt install

# SQL-backed local site
malt start
source <(malt env)
export DATABASE_URL='mysql://dbuser:secret@127.0.0.1:3306/eccubedb?charset=utf8mb4'
sql/setup-db.sh "$DATABASE_URL"  # drops + recreates the target DB
composer serve                   # http://127.0.0.1:8080 JSON/API
composer serve:page              # http://127.0.0.1:8081 HTML
```

## その他のコマンド

```bash
composer run -l
```

SQL テストは `DATABASE_URL` と MariaDB 環境に依存します。詳細は
[`docs/complete-replacement-residuals.md`](docs/complete-replacement-residuals.md) と
[`docs/migration-status.md`](docs/migration-status.md) を参照してください。

## 外部参照

- [ALPS manual](https://www.app-state-diagram.com/manuals/1.0/ja/index.html)
- [app-state-diagram](https://github.com/alps-asd/app-state-diagram)
- [EC-CUBE 4.3](https://github.com/EC-CUBE/ec-cube)
- [Be Framework](https://be-framework.github.io/llms-full.txt)
- [BEAR.Sunday](https://bearsunday.github.io/llms-full.txt)
