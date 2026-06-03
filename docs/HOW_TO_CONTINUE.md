# HOW_TO_CONTINUE.md

別マシン / 別セッションでこの **BeMart** プロジェクト（EC-CUBE 4.3 → BEAR.Sunday +
Be Framework 移植）の作業を再開するための引き継ぎガイド。
最終更新: 2026-06-01（ALPS route-gate / Ray.MediaQuery cutover / documentation refresh）

---

## 0. 現状サマリ

- **ブランチ**: セッションごとに異なる。まず `git branch --show-current` と `git status --short` を確認する。
- **リモート**: `https://github.com/koriym/ec-cube-alps.git`
- **テスト**: `docs/migration-status.md` の現行ベースライン参照。HTTP workflow は `tests/Hypermedia/WorkflowTest.php` と `tests/Http/WorkflowTest.php` で同一シナリオを in-process / 実HTTP の2トランスポートで検証。

移植は ALPS を契約として進行している:

| フェーズ | 内容 | 状態 |
|---|---|---|
| **Phase A** | ALPS 状態遷移 → Be ドメイン層 + BEAR JSON リソース | 完了。現在の `be/src` は 147 Input / 148 Final / 155 Semantic / 14 Being。 |
| **Phase 2** | Fake → SQL → Ray.MediaQuery 境界 | 完了。現在は 51 MediaQuery interface / 143 `#[DbQuery]` / 143 SQL file。 |
| **Phase 3** | HTML プレゼンテーション層（EC-CUBE テンプレート忠実移植） | in-scope 完了。`var/templates` は 131 Twig template。Storefront と admin editor waves は移植済み。Store/Plugin install/search subtree は plugin runtime 除外により out of scope。 |
| **Route-gate / compatibility** | EC-CUBE route と安全退避 / 互換 adapter 境界の明示 | `alps-route-gate` descriptor と `docs/eccube-feature-alps-status.html` で追跡。Hard ActionRedirect は接続済みだが、byte/fidelity 完全互換は residual。 |

> **現在の移植ステータス（レイヤ別マトリクス・残作業 punch-list）の正は
> [`docs/migration-status.md`](migration-status.md)**。本ファイルは「引き継いだ人が
> 次に何をするか」を示す。数値はステータスマトリクス側を必ず参照すること。

---

## 1. 新マシンでのセットアップ

### 1.1 取得

```bash
git clone https://github.com/koriym/ec-cube-alps.git
cd ec-cube-alps
git checkout <work-branch>
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
- `ray/media-query` — SQL境界の実行基盤。interface + SQL file を direct proxy として解決する。

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
vendor/bin/phpunit --testsuite sql          # Ray.MediaQuery SQL suite（DATABASE_URL 要）
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
   `docs/phases/alps-audit-phase3.md`（ALPS 監査）/ `docs/skills/`（G-14 〜 G-25 の skill gap）。

---

## 3. 引き継いだ人が次にやること

残作業の punch-list は `docs/migration-status.md` の「Outstanding work」が正。
おおまかな優先度順:

1. **Compatibility fidelity residuals** — `goExportOrderPdf` は到達・download header・`%PDF-` 生成まで進んだが、帳票レイアウト、`dtb_order_pdf` 保存設定、複数配送テンプレート再現は残る。CSV/Mail/Template/MasterData も byte/副作用互換は別境界として扱う。
2. **Domain residuals** — `doCreateOrder` / `doCheckout` は PurchaseFlow + `dtb_order_item` snapshot writes まで実装済み。残るのは `order_item_register.sql` の MariaDB 10.11 target-engine 検証または `JSON_TABLE` なしの INSERT への置換。`doImportProductCsv` はこの移植では export-only として意図的に未移植。`doInstallPlugin` は plugin runtime out-of-scope。`doUpdateCsv` は column config の保存後、それを消費する export fidelity が残る。
3. **HTML enrichment backlog** — Mypage dashboard、Favorite、Address、Contact。各ページは Cart-style の re-derive（ALPS → Entity/SQL/Fake enrich → template wiring）で進める。
4. **Production DB bring-up / cutover** — seed script と prod `SqlModule` binding はある。実DBでの bring-up、運用データ投入、cutover 手順の検証は未完。
5. **Verification when touching presentation** — admin ページ移植のレシピは `var/templates/README.md`、画面マトリクスは `docs/html-screen-migration-matrix.md`、route/function 状態は `docs/eccube-feature-alps-status.html` を参照する。

各項目の詳細・unverified 注記は `docs/migration-status.md` を参照。

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
- non-void query には Fake fixture も追加し、`tests/Smoke/MediaQueryCoverageTest.php` の対応関係を崩さない。

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
│   ├── skills/               #   G-14 〜 G-25 の skill gap ドキュメント
│   ├── quality/              #   Phase 1 ALPS 監査ノート
│   └── archive/              #   旧トラッカー・初期計画（参考・現状とは乖離）
├── src/                      # BEAR.Sunday アプリ層
│   ├── Resource/Page/        #   ResourceObject（page://*、storefront + Admin/*）
│   ├── Module/               #   AppModule / SqlModule / HtmlModule
│   └── Form/                 #   Ray.WebFormModule のフォーム定義（Phase 3）
├── be/                       # Be Framework ドメイン層（my-vendor/be-mart-be）
│   └── src/{Input,Being,Final,Reason,Semantic,Exception}/
│       └── Reason/Query/*Interface.php   # Ray.MediaQuery interface境界
├── sql/                      # EC-CUBE スキーマダンプ・mtb_* seed・setup-db.sh（Phase 2）
├── var/sql/                  # Ray.MediaQuery SQL files（143 query）
├── var/templates/            # HTML テンプレート（EC-CUBE 移植、Phase 3）
├── tests/                    # BEAR 層のテスト（render-diff / hypermedia 含む）
└── .claude/                  # /run migrate ワークフロー（commands / workflows / prompts）
```

主要 entrypoint はそれぞれ context を固定する。`APP_CONTEXT` は一時的な上書き用の escape hatch。

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
vendor/bin/phpunit --testsuite fake,http,smoke  # DBなしで動く検証
vendor/bin/phpunit --testsuite sql          # Ray.MediaQuery SQL suite（DATABASE_URL 要）
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
