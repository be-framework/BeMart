# HOW_TO_CONTINUE.md

別マシン / 別セッションでこの **BeMart** プロジェクト（EC-CUBE 4.3 → BEAR.Sunday +
Be Framework 移植）の作業を再開するための引き継ぎガイド。
最終更新: 2026-05-21（Phase 3 admin Tier-1 完了直後）

---

## 0. 現状サマリ

- **ブランチ**: `be-first-migration-bootstrap`
- **リモート**: `https://github.com/koriym/ec-cube-alps.git`
- **PR**: #2（draft、`be-first-migration-bootstrap` → `1.x`）
- **テスト**: `vendor/bin/phpunit` → 約 1734 tests / 5633 assertions, OK（deprecation 3 件のみ、failure なし）

移植は ALPS を契約として 3 フェーズ進行している:

| フェーズ | 内容 | 状態 |
|---|---|---|
| **Phase A** | ALPS 状態遷移 → Be ドメイン層 + BEAR JSON リソース | 完了（139 transition、stub 7 件あり） |
| **Phase 2** | 全 34 ストレージ Fake → SQL（MariaDB/MySQL）、本番カットオーバー | 完了 |
| **Phase 3** | HTML プレゼンテーション層（EC-CUBE テンプレート忠実移植） | ストアフロント完了 / Admin **Tier-1 完了**（77 ページ中 34）/ Admin Tier-2（約 43）+ enrichment backlog 残 |

> **現在の移植ステータス（レイヤ別マトリクス・残作業 punch-list）の正は
> [`docs/migration-status.md`](migration-status.md)**。本ファイルは「引き継いだ人が
> 次に何をするか」を示す。数値はステータスマトリクス側を必ず参照すること。

---

## 1. 新マシンでのセットアップ

### 1.1 取得

```bash
git clone https://github.com/koriym/ec-cube-alps.git
cd ec-cube-alps
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
vendor/bin/phpunit                          # 全テスト（約 1734、OK なら緑）
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
   `docs/phases/alps-audit-phase3.md`（ALPS 監査）/ `docs/skills/`（G-14 〜 G-23 の skill gap）。

---

## 3. 引き継いだ人が次にやること

残作業の punch-list は `docs/migration-status.md` の「Outstanding work」が正。
おおまかな優先度順:

1. **Admin HTML Tier-2（約 43 テンプレート、最大の残作業）** — admin Tier-1（77 ページ中
   34、list/data + 単純 CRUD）は 8 section-wave で完了。残る Tier-2 は性質が異なる:
   - **重量エディタ** — `Order/edit`（約 1057 行）/ `Product/product`（約 932 行）/
     `Product/product_class`（約 448 行）/ `Order/shipping`（約 709 行）
   - **新規リソースが必要なページ** — action-only リソース（POST/CSV/PDF）に `onGet` が
     無いページ群。`be/src` ドメイン層（Input/Final/body-shape）の追加を伴う
   テンプレ移植 wave では片付かない別種の作業。section 別の defer リストは
   `docs/phases/admin-fanout-plan.md` と `var/templates/README.md`「Fan-out status」。
   admin ページ移植のレシピ（`admin-base.html.twig`・per-section ja-message・
   render-diff テスト）は `var/templates/README.md`「Admin pages」節が正。
2. **HTML enrichment backlog** — リソース本体が薄すぎて EC-CUBE テンプレートを
   忠実 port できないデータページ。Cart / Mypage History はパイロット完了。
   残: Shopping confirm/complete・Mypage ダッシュボード・Favorite・Address・Contact。
   Cart スタイルの再導出（ALPS → Entity/SQL/Fake enrich → テンプレート配線）。
3. **`Block/*` ウィジェット** — ヘッダ/フッタ等のブロック領域のレンダリングサブステップ。
4. **5 ALPS-only 遷移のドメイン実装** — Phase 3 の ALPS 是正で追加された
   `doSortNoMove` / `doToggleVisible` / `doUpdateTrackingNumber` /
   `doSendShippingNotifyMail` / `doResendActivationMail` は `be/src` に実装が無い。
5. **Phase A の stub 7 件** — `doImportProductCsv` / `doImportCategoryCsv` /
   `doImportShippingCsv` / `doInstallPlugin` / `goExportOrderPdf` / `doCreateOrder` /
   `doUpdateCsv` の本物実装。

各項目の詳細・コミット・unverified 注記は `docs/migration-status.md` を参照。

**Admin section-wave を並列で回す場合** — per-section ja-message split（`1e91e92`）に
より、admin の section 単位 wave は共有ファイル衝突なしで並列実行できる（各 wave が
触るのは自 section の templates / tests / `src/Form/` + 自前の
`tests/Resource/Admin/<Section>JaMessages.php` のみ）。並列 agent には**ページ単位の
逐次 commit**を指示すること — 長時間 agent が session limit でカットオフされても
commit 済み分は失われない（バッチ 1 で 2 agent がカットオフされた実例あり。
未 commit の WIP は手動 salvage で回収した。HANDOVER「Admin HTML — section-wave
並列移植」参照）。

---

## 4. リポジトリ構造の要点

```text
ec-cube-alps/
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
