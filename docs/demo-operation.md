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

未設定なら会員はリポジトリのローカル開発値、管理者は seed の
`sql/seed/dtb-system-master.sql` の値のままになる。**公開デプロイでは両方必ず設定する。**
`docker-compose.override.yml` などに置き、リポジトリには commit しない。

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

デモホスト（Oracle Cloud VM）には以下を導入済み。毎時 0 分に走る。
管理画面も公開しているので、メンテナンスモードや店名・テンプレートの書き換えが
次のリセットまで残る。その滞留時間が間隔の上限を決める。

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
OnCalendar=hourly
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
