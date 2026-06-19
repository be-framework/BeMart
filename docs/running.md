# BeMart を動かす・管理する

ローカルでの起動手順と、管理画面・管理者メンテナンスの運用をまとめます。
プロジェクトの概要は [`../README.md`](../README.md) を参照してください。

## 前提

PHP 8.x / Composer。まず依存をインストールします。

```bash
composer install
```

## SQL-backed ローカルサイト

SQL context は [`malt`](https://github.com/koriym/homebrew-malt) と `DATABASE_URL` を使います。
DB 初期化の詳細は [`../sql/README.md`](../sql/README.md) を参照してください。

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
export DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb?charset=utf8mb4'
sql/setup-db.sh "$DATABASE_URL"  # schema + masters（drops + recreates the target DB）
sql/seed-dev.sh "$DATABASE_URL"  # dev/demo catalog（products）so the storefront is browsable
composer serve                   # http://127.0.0.1:8080 JSON/API
composer serve:page              # http://127.0.0.1:8081 HTML
```

開発用 DB 接続の基本は `127.0.0.1:3306` の `root` / パスワードなしです。

`setup-db.sh` は EC-CUBE スキーマと mtb_* マスタのみを入れます（商品など dtb_* 運用データは対象外）。
そのため `setup-db.sh` だけだと商品が 0 件で、トップページの商品リンクはすべて 404 になります。
`seed-dev.sh` が `be/var/fake/*.json` からデモ商品カタログ（商品・カテゴリ・タグ・会員）を生成・投入し、
ストアフロントを辿れる状態にします（Docker の `docker compose run --rm setup` は両者を自動で実行します）。
ブラウザ検証前に毎回まっさらな既知状態へ戻したいときは [`../sql/reset-dev.sh`](../sql/reset-dev.sh)
（`setup-db.sh` + デモカタログ + PoC 会員のログイン可能化）を使います。

## キャッシュ（コード更新後は serve の前に）

prod / SQL context はコンパイル済み DI コンテナを `var/tmp/` にキャッシュします。
**pull・ブランチ切替・依存更新などでコード（特にコンストラクタや DI 配線）を変えたら、
`composer serve` / `serve:page` の前に `composer clean`** を実行してください。
古いコンテナのままだと DI 初期化エラー（例: `must not be accessed before initialization`）で落ちます。

```bash
composer clean   # 全 context のコンパイル済み DI キャッシュを消す
```

## 環境変数

| 変数 | 用途 | 例 |
|---|---|---|
| `DATABASE_URL` | SQL context（`composer serve` / `serve:page` / SQL テスト）の接続先。**未設定だと SQL context の起動時に例外**になります | `mysql://root@127.0.0.1:3306/eccubedb?charset=utf8mb4` |
| `APP_CONTEXT` | context の明示切替（任意）。既定は `serve`=`sql-html-app`、`serve:page`=`html-eccube-sql-hal-app` | `html-eccube-sql-hal-app` |

## 管理画面にログインする

入口は **`/admin/login`** です（HTML サーバ例: http://127.0.0.1:8081/admin/login ）。
`sql/setup-db.sh` が流し込む seed 管理者でログインできます。

| login_id | password |
|---|---|
| `test-admin` | `local-dev-admin-password` |

### 2段階認証（TOTP）

`/admin/login` で login_id / password を通すと 2 段階認証に進みます。
ローカル開発は下の**開発用バイパス**が手軽です。本番同等の挙動を確認したいときは実 TOTP を使います。

#### 開発用バイパス（トークン `123456`）

`BEMART_DEV_LOGIN=1` のとき、2FA は認証アプリの代わりに固定トークン **`123456`** を受け付けます。
password を通すと `/admin/two-factor-auth` に遷移するので、そこで `123456` を入力すればログイン完了です。

- **Docker**: `page` サービスが既定で `BEMART_DEV_LOGIN=1` を持つので、そのまま `123456` でログインできます。
- **malt / ローカル**: 環境変数を付けて HTML サーバを起動します。

```bash
BEMART_DEV_LOGIN=1 composer serve:page   # http://127.0.0.1:8081 で 123456 が通る
```

> **SECURITY**: 開発専用です。`BEMART_DEV_LOGIN=1` に加えて **cli-server SAPI（`php -S`）かつ非 prod context** のときだけ有効になり、php-fpm や `prod-*` context では決してバインドされません。

#### 実 TOTP（本番と同じ）

EC-CUBE 互換の TOTP（RFC 6238）です。**SMS／メールのようなコード配信はありません**。
認証アプリ（Google Authenticator など）が 30 秒ごとに生成する 6 桁を入力します。

1. 初回ログイン（2FA 未登録）→ `/admin/two-factor-auth-set` で **QR コードが表示**されます。
   認証アプリで読み取り、表示された 6 桁を入力して登録します。
2. 2 回目以降 → `/admin/two-factor-auth`（QR なし・6 桁入力のみ）。
   認証アプリに出ている**現在の 6 桁**を入力します。

> 2 回目以降にコードは「届き」ません。登録済みの認証アプリを開けば常に最新の 6 桁が表示されています。

### 2FA でロックアウトした／管理者を消したいとき

管理者メンテナンス CLI（[`../bin/admin.php`](../bin/admin.php)）を使います。接続先は `DATABASE_URL` です。

```bash
composer admin -- list                  # 管理者一覧 (id / login_id / name / 2FA / 有効)
composer admin -- reset-2fa test-admin  # 2FA 解除（次回ログインで QR を再登録）
composer admin -- disable  test-admin   # 無効化 (work_id=0 ＝ 管理画面の削除と同じ)
composer admin -- delete   test-admin   # 行ごと削除
```

DB をまるごと初期状態へ戻すなら `sql/setup-db.sh "$DATABASE_URL"`（seed 状態に戻り 2FA も消えます）。

## その他のコマンド

```bash
composer run -l   # 全コマンド一覧
```

| コマンド | 内容 |
|---|---|
| `composer serve` / `serve:page` | API(8080) / HTML(8081) を起動 |
| `composer fake` | Fake context（DB 不要）の CLI 実行 |
| `composer test` | 既定の PHPUnit suite |
| `composer admin -- <action>` | 管理者メンテナンス（上記） |

SQL テストは `DATABASE_URL` と MariaDB 環境に依存します。詳細は
[`complete-replacement-residuals.md`](complete-replacement-residuals.md) と
[`migration-status.md`](migration-status.md) を参照してください。
