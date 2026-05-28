# BeMart — EC-CUBE 4.3 Semantic Migration

BeMart は、EC-CUBE 4.3 を **意味論と境界へ分解し、再構成する** 移植の **実証プロジェクト** です。Be Framework + BEAR.Sunday による移植手法（ALPS → Be → BEAR）の referent implementation であり、Symfony版EC-CUBEの fork や controller 書き換えではなく、EC-CUBEの意味論とHTML構造を保ちながら ALPS・Ray.MediaQuery・ハイパーメディア・境界テストで組み直します。

> BeMart is not a controller rewrite of EC-CUBE. It is a semantic migration with explicit boundaries.

## Quick Links

| Document | Purpose |
|---|---|
| [`docs/FINAL-REPORT.md`](docs/FINAL-REPORT.md) | 実証総括 — 何を示せたか・知見・限界 |
| [`alps.json`](alps.json) / [`alps.json.html`](alps.json.html) | ALPS profile and HTML documentation |
| [`openapi.yaml`](openapi.yaml) / [`openapi.html`](openapi.html) | OpenAPI export and HTML documentation |
| [`docs/migration-status.md`](docs/migration-status.md) | current migration status |
| [`docs/html-screen-migration-matrix.md`](docs/html-screen-migration-matrix.md) | HTML screen/route migration matrix |
| [`docs/eccube-feature-alps-status.html`](docs/eccube-feature-alps-status.html) | EC-CUBE route/function ↔ ALPS ID ↔ implementation status ↔ migration difficulty |
| [`docs/tag.md`](docs/tag.md) | ALPS tag taxonomy |
| [`docs/README.md`](docs/README.md) | documentation map |

## Difference from Symfony EC-CUBE

BeMart は EC-CUBE の機能や画面構造を否定するものではありません。違いは、どこに境界を引くかです。

| EC-CUBE 4.3 / Symfony版 | BeMart |
|---|---|
| Symfony Controller / Service | ALPS transition + Be use case |
| Doctrine ORM | Ray.MediaQuery + SQL projection |
| Symfony Router | BEAR.Sunday ResourceObject |
| Twig route link | Hypermedia affordance |
| 環境設定 | Context / DI binding |

## Core Ideas

- **Semantics first** — ALPSで descriptor、transition、actor、page role を明示する。
- **Fake → Schema → SQL** — Fakeで契約を固定し、EC-CUBEスキーマ照合後にSQL実装する。
- **Explicit boundaries** — ドメイン、インフラ、リソース、HTML、SQLの境界を隠さない。
- **Context chooses implementation** — アプリケーションは Fake / SQL のどちらが有効かを知らない。
- **Hypermedia is a contract** — 画面は表示だけでなく、リンクとフォームが次状態への affordance になって完了。

## Architecture Boundaries

| Boundary | Role |
|---|---|
| ALPS | アプリケーション意味論・情報構造 |
| Be Framework | ドメイン境界（`Input` / `Being` / `Final`） |
| Ray.MediaQuery | ドメイン ↔ インフラ境界。技術的には PHP interface / return type ↔ SQL query/result |
| BEAR.Sunday | ドメイン ↔ リソース境界（`ResourceObject` / URI / HTTP method） |
| Hypermedia | リソース ↔ クライアント遷移境界（`#[Link]` / `href` / `form action`） |
| Context / DI | 実装選択境界（Fake ↔ SQL、HTML ↔ JSON、test ↔ prod） |
| SQL schema | 永続化境界（table / column / FK / nullable / id shape） |

Taint tracking、cache freshness、DIP / ADP も境界制約として扱いますが、READMEでは詳細化しません。背景は [`docs/methodology/`](docs/methodology/) と [`docs/skills/`](docs/skills/) を参照してください。

## Migration Workflow

実装順は固定です。

```text
Fake → EC-CUBE schema alignment → SQL → Resource/Form → HTML/Browser
```

Fake は後付けmockではなく、最初の契約実装です。SQL は後から同じ契約を満たすことをテストで証明します。実装の選択は Context / DI binding が行うため、アプリケーションコードは Fake / SQL の違いを知りません。

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

BeMart は **実証プロジェクト** です。EC-CUBE 4.3 の全ルート・全機能を [`alps.json`](alps.json) と [`docs/eccube-feature-alps-status.html`](docs/eccube-feature-alps-status.html) に棚卸しした上で、移植手法（ALPS → Be Framework → BEAR.Sunday）の実証として価値のある範囲はすべて完了しています。

> ALPS 144/144 transition · Be domain 144/144 · BEAR Resource 139 · SQL 34/34 · HTML 110 templates（storefront 全ページ + in-scope admin 63/77）

残るのは「実証としては不要だが、本番 EC-CUBE の **完全代替** には必要」な差分だけです。**これは未知の不足ではなく、私たちが EC-CUBE 全体を把握した上で意図的に保留した既知の残作業**です。下記がすべて fix されれば、BeMart は EC-CUBE 4.3 の完全な代替になります。

### A. ドメイン stub（入力は受理するが永続副作用なし）

- 受注確定 — `doCreateOrder`
- CSV インポート — `doImportProductCsv` / `doImportCategoryCsv` / `doImportShippingCsv` / `doUpdateCsv`（行数は数えるが永続化しない Phase 2 stub）
- プラグイン lifecycle — `doInstallPlugin` / enable / disable / uninstall（download・unzip・migrate・container 再生成なし）

### B. EC-CUBE 互換 fidelity 残差（[#24](https://github.com/be-framework/be-mart/issues/24)）

- PDF — 帳票レイアウト完全一致 / `dtb_order_pdf` 保存設定 / 複数配送（到達・download ヘッダ・`%PDF-` 生成は実装済み）
- CSV — EC-CUBE 互換フォーマット + streaming/download 境界
- Mail — 本文生成 / テンプレート解決 / 送信の忠実再現
- Template 管理 — install / download / delete / select の file 副作用
- MasterData adapter

### C. HTML enrichment backlog（resource body が薄く忠実移植に未到達）

- Mypage dashboard / Favorite / Address / Contact

### D. 設計上の out-of-scope

- プラグイン機構・マーケットプレイス（[#3](https://github.com/be-framework/be-mart/issues/3) — Anti-Corruption Layer による恒久 legacy 同居の研究構想）
- 管理 Store/Plugin install/search サブツリー（~14 ページ）

### E. 本番移行

- 本番 DB の bring-up / cutover（seed script と prod `SqlModule` binding は実装済み）

最新の詳細は [`docs/migration-status.md`](docs/migration-status.md) §4 Outstanding work、互換隔離の進行は [#24](https://github.com/be-framework/be-mart/issues/24) を参照してください。

## Repository Map

| Path | Purpose |
|---|---|
| `alps.json` | canonical ALPS profile |
| `be/` | Be Framework domain layer |
| `src/` | BEAR.Sunday application/resource layer |
| `var/templates/` | EC-CUBE Twig HTML ports |
| `public/` | HTTP front controller and static assets |
| `sql/` | EC-CUBE schema, seed data, SQL setup notes |
| `docs/` | project documentation and GitHub Pages artifacts |
| `tests/` | Resource, SQL, HTML render, HTTP hypermedia tests |

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

Runtime entrypoints fix their default context (`bin/fake.php` → `cli-fake-hal-api-app`, `bin/page.php` → `cli-html-hal-app`). `APP_CONTEXT` is only an escape hatch for temporary overrides. SQL tests require a local MariaDB/MySQL database prepared from [`sql/`](sql/).

## References

- [ALPS manual](https://www.app-state-diagram.com/manuals/1.0/ja/index.html)
- [app-state-diagram](https://github.com/alps-asd/app-state-diagram)
- [EC-CUBE 4.3](https://github.com/EC-CUBE/ec-cube)
- [Be Framework](https://be-framework.github.io/llms-full.txt)
- [BEAR.Sunday](https://bearsunday.github.io/llms-full.txt)
