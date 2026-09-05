# 公開デモの運用

<https://131.186.41.241.sslip.io/> は Oracle Cloud Always Free 上の 1 インスタンスで
`docker compose up` 構成のまま動いている。ここでは「誰でも書き込める公開デモ」を
どう保つかだけを扱う。

## 資格情報

リポジトリに動く資格情報を置かない。デモのパスワードは環境変数で与える。

| 変数 | 対象 |
| --- | --- |
| `BEMART_DEMO_MEMBER_PASSWORD` | 会員 `login-test@example.com` |
| `BEMART_DEMO_ADMIN_PASSWORD` | 管理者 `test-admin` |

会員が未設定ならリポジトリのローカル開発値になる。管理者が未設定なら
`docker/seed.sh` がシードごとにランダムなパスワードを生成してログに 1 度だけ出す
（`sql/seed/dtb-system-master.sql` の committed hash は必ず上書きされる）。
**公開デプロイでは両方設定する。** `docker-compose.override.yml` などに置き、
リポジトリには commit しない。

```yaml
# docker-compose.override.yml（デモホスト側のみ。コミットしない）
services:
  app:
    environment:
      BEMART_DEMO_MEMBER_PASSWORD: "…"
      BEMART_DEMO_ADMIN_PASSWORD: "…"
```

設定後は一度リセット（下記）を回すと、その値がデータベースに反映される。
README にログイン情報は書かない。

## コンテキストと 2 要素認証

`APP_CONTEXT` は `prod-html-eccube-sql-hal-app`。`prod` トークンが BEAR の `ProdModule` を
入れるので、① 未マップ例外は `message` と `logref` だけの本番エラーページになり
スタックトレースや絶対パスを返さない ② `OPTIONS` イントロスペクションが 405 になる
③ DI がコンパイルされる。`docker/php.ini` が `display_errors` を切り、
`PHP_CLI_SERVER_WORKERS` が `php -S` の単一プロセス化を緩和する。

`prod` コンテキストでは開発用 2FA バイパス（固定コード `123456`）が効かない
（`src/Dev/DevLogin::active()` が `prod` を含む文脈を拒否する）。管理画面の初回ログイン後に
表示される鍵を認証アプリに登録して 6 桁コードを入力する。リセットで登録は消えるので、
リセット後の最初の管理操作では再登録が必要。

## 定期リセット

デモは読み取り専用にしない。チェックアウト・会員登録・管理画面の更新が動かない
デモは、移植の成果を何も示さないからである。代わりに、書き込みを許したまま
一定間隔で seed 状態へ戻す。

```bash
docker compose exec -T app docker/demo-reset.sh
```

`docker/demo-reset.sh` は `docker/seed.sh`（entrypoint と共有）を使って
schema → master → dev fixture → カタログ → デモ資格情報の順に作り直す。
データベースは DROP されるので、残したいデータがある用途には使わない。

デモホスト（Oracle Cloud VM）には以下を導入済み。1 日 1 回 18:00 UTC（03:00 JST）に走る。

この間隔で足りるのは、訪問者が触れる書き込みが DB 行に限られるからである。管理画面から
書けるのは商品・受注・マスタ等の行だけで、Twig の自動エスケープを通るので残るのは
見た目の汚れだけ。サイトを壊す経路は移植されていない — メンテナンスモードは
`var/tmp` のフラグを書くだけで storefront に強制されず（`EccubeMaintenanceMode`）、
CSS/JS 編集は `var/tmp` の JSON に入るだけで公開ページに出力されず、テンプレート追加は
zip を展開せず名前とサイズだけを記録し、`move_uploaded_file` はリポジトリに存在しない。
この前提が変わったら間隔を短くする。

```ini
# /etc/systemd/system/bemart-demo-reset.service
[Unit]
Description=Reset the BeMart public demo to its seeded state
After=docker.service
Requires=docker.service

[Service]
Type=oneshot
User=ubuntu
WorkingDirectory=/home/ubuntu/BeMart
ExecStart=/usr/bin/docker compose exec -T app docker/demo-reset.sh
TimeoutStartSec=900
```

```ini
# /etc/systemd/system/bemart-demo-reset.timer
[Unit]
Description=Periodic BeMart demo reset

[Timer]
OnCalendar=*-*-* 18:00:00
Persistent=true

[Install]
WantedBy=timers.target
```

```bash
sudo systemctl enable --now bemart-demo-reset.timer
systemctl list-timers bemart-demo-reset.timer
```

リセットは `setup-db.sh` がデータベースを DROP するところから始まる。
所要はデモ VM で 14〜34 秒（3,000 商品の投入が大半）、ローカルの Docker で約 4 秒。
リセット中の `/products` を 0.8 秒間隔で 40 回叩いた計測では、全て 200 で
商品カードも 20 枚のままだった。訪問者に見える停止は観測できていない。
