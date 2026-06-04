# BeMart — EC-CUBE セマンティックオーバーホール

BeMart は、EC-CUBE 4.3 を意味論と境界へ分解し、ALPS / Be Framework /
BEAR.Sunday / Ray.MediaQuery SQL / Twig HTML へ再構成する実証プロジェクトです。

Symfony 版 EC-CUBE の controller rewrite ではありません。EC-CUBE が持つ業務語彙、
状態遷移、永続化制約、HTTP affordance、HTML 表現を、実装から取り出して読める契約
として配置し直すことが目的です。

> BeMart is not a controller rewrite of EC-CUBE. It is a semantic migration
> with explicit boundaries.

## 現時点の答え

このプロジェクトの問いは、単に「EC-CUBE を別フレームワークで動かせるか」ではありません。

> 巨大な既存 EC アプリケーションを、実装の移し替えではなく、意味の分解と境界の再構成として
> 移植できるか。

現時点の答えは、かなり強く「可能」です。ALPS に逆算した意味を、ドメイン、Resource、
SQL、HTML、workflow test へ投影できることは示せました。

残っている中心は、もう広い機能カバー率の問題ではありません。本番 EC-CUBE の完全代替に必要な
互換 fidelity と production verification の問題です。

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

正典の ALPS profile は [`alps.json`](alps.json) です。`alps.json.html` や
`docs/alps.json.html` は生成物です。

## ステータス要約

詳細な数値と判断は [`docs/migration-status.md`](docs/migration-status.md) を正とします。
README では全体像だけを示します。

| 境界 | 証拠 |
|---|---|
| ALPS | 532 descriptor / 207 transition descriptor |
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

## 基本方針

- ALPS を意味論の source of truth とする。
- Be Framework でドメイン状態遷移を表す。
- BEAR.Sunday Resource で HTTP / PHP 共通の Resource 境界を作る。
- Ray.MediaQuery で SQL を interface 境界に閉じ込める。
- Twig HTML は EC-CUBE の affordance をできるだけ保持する。
- Hypermedia test は controller 内部ではなく、link / form を辿って workflow を証明する。

workflow test の方針は
[`docs/methodology/hypermedia-test-principle.md`](docs/methodology/hypermedia-test-principle.md) と
[`tests/README.md`](tests/README.md) を参照してください。

## よく使うコマンド

```bash
# ALPS validation
asd --validate alps.json

# ALPS generated docs
asd -f html -o alps.json.html alps.json
asd -f svg -o alps.svg alps.json

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

GitHub Pages root: <https://koriym.github.io/ec-cube-alps/>

| 成果物 | 役割 |
|---|---|
| [`alps.json.html`](alps.json.html) | ALPS 生成ドキュメント |
| [`openapi.yaml`](openapi.yaml) / [`openapi.html`](openapi.html) | OpenAPI projection |
| [`docs/alps.json.html`](docs/alps.json.html) | GitHub Pages 用 ALPS ドキュメント |
| [`docs/openapi.html`](docs/openapi.html) | GitHub Pages 用 OpenAPI ドキュメント |
| [`docs/index.md`](docs/index.md) | 初期記事: EC-CUBE ソースから ALPS を逆算した記録 |

生成 HTML は、生成元がある場合は手で編集しません。

## 外部参照

- [ALPS manual](https://www.app-state-diagram.com/manuals/1.0/ja/index.html)
- [app-state-diagram](https://github.com/alps-asd/app-state-diagram)
- [EC-CUBE 4.3](https://github.com/EC-CUBE/ec-cube)
- [Be Framework](https://be-framework.github.io/llms-full.txt)
- [BEAR.Sunday](https://bearsunday.github.io/llms-full.txt)
