# HOW_TO_CONTINUE.md

別マシン / 別セッションでこの **BeMart** プロジェクト（EC-CUBE 4.3 → BEAR.Sunday +
Be Framework 移植）の作業を再開するための引き継ぎガイド。
最終更新: 2026-05-23（EC-CUBE実サイト探索 / HTTP導線安定化 / Ray.MediaQuery境界ルール追加）

---

## 0. 現状サマリ

- **ブランチ**: `be-first-migration-bootstrap`
- **リモート**: `https://github.com/be-framework/be-mart.git`
- **PR**: #2（draft、`be-first-migration-bootstrap` → `1.x`）
- **テスト**: `vendor/bin/phpunit` → `docs/migration-status.md` の現行ベースライン参照。HTTP workflow は `tests/Hypermedia/WorkflowTest.php` と `tests/Http/WorkflowTest.php` で同一シナリオを in-process / 実HTTP の2トランスポートで検証

移植は ALPS を契約として 3 フェーズ進行している:

| フェーズ | 内容 | 状態 |
|---|---|---|
| **Phase A** | ALPS 状態遷移 → Be ドメイン層 + BEAR JSON リソース | 完了（139 transition、stub 7 件あり） |
| **Phase 2** | 全 34 ストレージ Fake → SQL（MariaDB/MySQL）、本番カットオーバー | 完了 |
| **Phase 3** | HTML プレゼンテーション層（EC-CUBE テンプレート忠実移植） | Storefrontは全ページ移植済みで、共有Block/商品一覧カート投入まで拡張。Adminは **63 of 77 page templates**（Tier-1 + in-scope Tier-2 editor waves）まで移植済み。残りは主に Store/Plugin install/search subtree（今回スコープ外）と、body enrichment が必要な周辺機能。 |

> **現在の移植ステータス（レイヤ別マトリクス・残作業 punch-list）の正は
> [`docs/migration-status.md`](migration-status.md)**。本ファイルは「引き継いだ人が
> 次に何をするか」を示す。数値はステータスマトリクス側を必ず参照すること。

---

## 1. 新マシンでのセットアップ

### 1.1 取得

```bash
git clone https://github.com/be-framework/be-mart.git
cd be-mart
git checkout be-first-migration-bootstrap
```

Phase 3 の render-diff テスト（`tests/Resource/*HtmlRenderTest.php`）は EC-CUBE 4.3 の
**実テンプレート**と差分を取る。その参照元として EC-CUBE 4.3 のソースを
`tools/ec-cube-source/`（gitignore 対象、リポジトリには含まれない）に clone する。
**これが無いと全 HTML render テストが動かない**:

```bash
git clone --depth 1 -b 4.3 https://github.com/EC-CUBE/ec-cube.git tools/ec-cube-source
```

テストが参照するのは `tools/ec-cube-source/src/Eccube/Resource/template/`（`default`
テーマ + `admin` テーマの Twig）と `.../locale/messages.ja.yaml` のみ。`composer install`
や DB セットアップは EC-CUBE 側では不要。

### 1.2 依存インストール

```bash
composer install
```

`require` の主要 dep:

- `bear/sunday`, `bear/package` — BEAR.Sunday
- `be-framework/be` — Be Framework core
- `my-vendor/be-mart-be` — このリポジトリのドメイン層（`be/` サブツリーに同居、path repo 参照）
- `madapaja/twig-module` — HTML context の Twig レンダリング（Phase 3）
- `ray/web-form-module` — HTML フォームページ（Phase 3）
- `ray/media-query` — 今後の新規SQL境界で使うinterface-driven SQL mapper（既存PDO実装の移行は別フェーズ）

PHP 8.4 で開発・テストしている（8.5 でも問題なし）。

`be/` は path repository として参照しているので、`be/` 内でも `composer install` が必要な場合がある（`composer.json` の `repositories` を参照）。

### 1.3 データベース（Phase 2 以降に必要）

SQL テストスイートと本番 context は MariaDB 10.11 / MySQL を使う。
`DATABASE_URL` 未設定なら SQL スイートは clean skip するので、ドメイン層だけ触るなら不要。

```bash
sudo service mariadb start
sudo mysql -e "GRANT ALL PRIVILEGES ON \`eccubedb_test\`.* TO 'dbuser'@'127.0.0.1';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

本番 DB の再現可能なセットアップ手順・seed は `sql/README.md` と `sql/setup-db.sh` を参照。
デフォルト接続は host `127.0.0.1` / port `3306` / user `dbuser` / pass `secret`。

### 1.4 動作確認

```bash
vendor/bin/phpunit                          # 全テスト（OK なら緑）
vendor/bin/phpunit tests/Hypermedia/WorkflowTest.php tests/Http/WorkflowTest.php  # 同一workflowをin-process/実HTTPで検証
vendor/bin/phpunit --testsuite bemart-sql   # SQL ストレージ + Final-direct（DATABASE_URL 要）
composer psalm                              # 型解析
composer psalm-taint                        # taint mode
```

---

## 2. 必読ファイル（再開時に読む順）

1. **`docs/migration-status.md`** — レイヤ別ステータスマトリクスと残作業 punch-list。
   「今どこまで出来ているか」はここが正。最初に読む。
2. **`docs/HANDOVER.md`** — 構築プロセスの全記録。Phase A（Pilot 1-15 + Wave 1-9）/
   Phase B（Slice 1-9）/ Phase 2（SQL）/ Phase 3（HTML）の決定ログと積み残し。
3. **`CLAUDE.md`** — プロジェクト規約（ALPS が source of truth、5 レイヤ構成、`/run migrate`）。
4. **`alps.json`** — EC-CUBE 4.3 のセマンティクス定義。移植の契約。
5. レイヤ別の詳細: `sql/README.md`（Phase 2）/ `var/templates/README.md`（Phase 3）/
   `docs/phases/alps-audit-phase3.md`（ALPS 監査）/ `docs/skills/`（G-14 〜 G-24 の skill gap）。

---

## 3. 引き継いだ人が次にやること

残作業の punch-list は `docs/migration-status.md` の「Outstanding work」が正。
おおまかな優先度順:

1. **Product / Order / Customer の重い編集画面** — `/admin/product/new`、first-slice `/admin/order`、`/admin/customer?customerId=...` は接続済み。次はEC-CUBE実サイト探索で確認した商品規格行列・画像アップロード・受注新規/編集・会員新規/詳細検索を進める。
   - Product: `Product/product`, `Product/product_class`, 画像、カテゴリ/タグ、在庫無制限、販売種別、通常価格、販売制限、発送日目安。
   - Order: `Order/edit`, `Order/shipping`, 受注新規、検索条件、配送/明細/支払/対応状況/メール履歴。
   - Customer: 管理会員新規、詳細検索、購入履歴、配送先一覧、お気に入り、ステータス操作。
   - Content/Setting: ファイル管理、メンテナンス、特商法、定休日、ログイン履歴、ログ表示、システム情報、マスタデータ。
   admin ページ移植のレシピは `var/templates/README.md`「Admin pages」節、最新の画面マトリクスは `docs/html-screen-migration-matrix.md` が正。
2. **Storefront enrichment backlog** — 商品一覧はカテゴリ/表示件数/並び順/一覧カート投入まで接続済み。残: 商品詳細の規格選択/favorite、Shopping confirm/complete、Mypage dashboard、Favorite、Address、Contactのbody enrichment。
3. **`Block/*` ウィジェット** — header/search/logo/login/cart/category-nav/footer first sliceは追加済み。残: cart totals/customer-auth/category-treeの動的化。
4. **1 ALPS-only 遷移のドメイン実装** — Phase 3 の ALPS 是正で追加された 5 遷移のうち、`doSortNoMove` / `doToggleVisible` / `doUpdateTrackingNumber` / `doSendShippingNotifyMail` は実装済み。未実装は `doResendActivationMail` のみ。
5. **Phase A の stub / compatibility 残差** — `goExportOrderPdf` は Issue #24 の
   PDF pilot で ActionRedirect/stub から compatibility service 経由の実PDF
   `%PDF-` 出力まで進めた。ただし EC-CUBE 完全忠実度（帳票レイアウト、
   `dtb_order_pdf` 保存設定、複数配送テンプレート再現）は意図的に後続残差として
   残している。未着手またはstub残りは `doImportProductCsv` /
   `doImportCategoryCsv` / `doImportShippingCsv` / `doInstallPlugin` /
   `doCreateOrder` / `doUpdateCsv`。

各項目の詳細・コミット・unverified 注記は `docs/migration-status.md` を参照。

### 3.1 Phase 3 の検証ゲート

Admin Tier-2 以降は、ページ単位で次の4層を完了条件にする。

1. **Resource hypermedia** — `tests/Resource/*ResourceTest.php` で `ResourceInterface` 経由の `page://self/...` 契約を検証。
2. **HTML render-diff** — `tests/Resource/*HtmlRenderTest.php` で EC-CUBE 実テンプレートとの差分を residual allowlist で説明。
3. **HTTP workflow** — `tests/Hypermedia/WorkflowTest.php` の同一 assertion を `tests/Http/WorkflowTest.php` が継承し、`HttpResource` 経由で実HTTP / Cookie境界を越えて検証する。HTTP サーバは `koriym/php-server` でテスト内起動する（手動起動しない）。POST成功後に `Location` を返すものは `303 See Other + Location` として固定する。
4. **Browser smoke** — storefront は実ブラウザでトップ → 商品一覧 → 商品詳細 → カート/ログイン/問い合わせ導線を確認。商品一覧は populated branch / category / sort / list add-cart まで接続済み。Admin はログイン後に商品・受注・会員・カテゴリ・テンプレート周辺の主要導線を確認する。


**Admin section-wave を並列で回す場合** — per-section ja-message split（`1e91e92`）に
より、admin の section 単位 wave は共有ファイル衝突なしで並列実行できる（各 wave が
触るのは自 section の templates / tests / `src/Form/` + 自前の
`tests/Resource/Admin/<Section>JaMessages.php` のみ）。並列 agent には**ページ単位の
逐次 commit**を指示すること — 長時間 agent が session limit でカットオフされても
commit 済み分は失われない（バッチ 1 で 2 agent がカットオフされた実例あり。
未 commit の WIP は手動 salvage で回収した。HANDOVER「Admin HTML — section-wave
並列移植」参照）。

---

## 3.2 新規SQL境界のルール

今後の新規 Query / Command は **Ray.MediaQuery** を使う。PHP実クラスにPDO prepared statementを直接書かない。

- PHP側は `#[DbQuery('sql_id')]` 付きinterfaceを定義する。
- SQLは `{sqlDir}/{sql_id}.sql` に置く。
- メソッド引数名とSQLの `:named` placeholderを一致させる。
- return typeでfetch/hydration/exec結果を決める。
- 既存 `Sql*Query` / `Sql*Command` の移行は別フェーズでまとめて行い、今回の次作業では触らない。

詳細は `docs/skills/G-24-ray-media-query-boundary.md`。

---

## 4. リポジトリ構造の要点

```text
be-mart/
├── alps.json                 # source of truth（EC-CUBE 4.3 ALPS）
├── CLAUDE.md                 # プロジェクト規約
├── README.md                 # エントリポイント
├── docs/                     # ドキュメント（GitHub Pages publish root）
│   ├── README.md             #   docs/ 配下のドキュメントマップ
│   ├── migration-status.md   #   移植ステータスの正（レイヤ別マトリクス）
│   ├── HANDOVER.md           #   全工程の決定記録
│   ├── HOW_TO_CONTINUE.md    #   このファイル
│   ├── tag.md                #   タグ分類体系
│   ├── methodology/          #   再利用可能な方法論・原則
│   ├── phases/               #   フェーズ別の成果物（ALPS 監査・admin fan-out 計画）
│   ├── skills/               #   G-14 〜 G-23 の skill gap ドキュメント
│   ├── quality/              #   Phase 1 ALPS 監査ノート
│   └── archive/              #   旧トラッカー・初期計画（参考・現状とは乖離）
├── src/                      # BEAR.Sunday アプリ層
│   ├── Resource/Page/        #   ResourceObject（page://*、storefront + Admin/*）
│   ├── Module/               #   AppModule / SqlModule / HtmlModule
│   └── Form/                 #   Ray.WebFormModule のフォーム定義（Phase 3）
├── be/                       # Be Framework ドメイン層（my-vendor/be-mart-be）
│   └── src/{Input,Being,Final,Reason,Semantic,Entity,Exception}/
│       └── Reason/Query/Sql*.php   # SQL 永続化（Phase 2）
├── sql/                      # EC-CUBE スキーマダンプ・mtb_* seed・setup-db.sh（Phase 2）
├── var/templates/            # HTML テンプレート（EC-CUBE 移植、Phase 3）
├── tests/                    # BEAR 層のテスト（render-diff / hypermedia 含む）
└── .claude/                  # /run migrate ワークフロー（commands / workflows / prompts）
```

context は `APP_CONTEXT` で切替（`app`/`prod` は JSON、`html` は Twig HTML）。

---

## 5. よく使うコマンド

### ALPS

```bash
asd --validate alps.json                  # バリデーション
asd -f html -o alps.json.html alps.json   # HTML 再生成（docs/ への同期は手動）
asd -f svg -o alps.svg alps.json          # SVG 状態遷移図
```

### 移植ワークフロー

```text
/run migrate <descriptor-id>
```

`alps-analyze → domain → domain-review → application → application-review → (security)` の
ステップが走る。レビューステップは subagent（独立 context）。

### テスト・型

```bash
vendor/bin/phpunit                          # 全テスト
vendor/bin/phpunit --testsuite bemart-sql   # SQL ストレージスイート
composer psalm / composer psalm-taint       # 型 / taint 解析
```

---

## 6. 移行で持ち越せないもの（新マシンで再構築が必要）

- **Claude Code のセッション履歴** — ローカル保存。`docs/HANDOVER.md` + `docs/migration-status.md`
  経由で文脈を再ロードするのが唯一の正解。
- **インストール済 skill**（`be-framework-skills`, `alps-skills`, `bear-skills` 等）—
  `.claude/prompts/` がこれらを名指しで呼ぶので、欠けていると `/run migrate` が動かない。
- **`~/.claude/CLAUDE.md`**（個人 global 規約）・IDE 設定 — 別マシンで個別セットアップ。

リポジトリ内 `.claude/`（workflows, prompts）は git 管理されているので自動で付いてくる。

---

## 7. トラブルシュート

### `composer install` が失敗する

`be/` を path repository として参照している。`composer.json` の `repositories` を確認。

### SQL スイートが skip / fail する

`DATABASE_URL` 未設定なら clean skip（正常）。設定済みでサーバ不達なら fail-fast。
MariaDB の起動とグラント（§1.3）を確認。

### テンプレート編集が反映されない

TwigModule は `var/tmp/<context>/twig` にコンパイル結果をキャッシュし `auto_reload` off。
`.html.twig` を編集したら `rm -rf var/tmp/html/twig` で clear する。
