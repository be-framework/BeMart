---
layout: default
title: "SQL Test Baseline"
---

# SQL Test Baseline

作成日: 2026-05-26
対象: BeMart SQL suite / Ray.MediaQuery / EC-CUBE schema baseline

## 目的

BeMart の SQL suite がどの DB を前提にしているかを明確にし、誤判定を避ける。

## 結論

SQL suite の baseline は **MySQL 8.0**。
`phpunit.xml` の `DATABASE_URL` は `serverVersion=8.0.0` を前提としており、CI も `mysql:8.0` コンテナで動く。
`malt.json` が起動する MySQL 8.0 がそのまま利用できる。

## 背景

かつて `bootstrap.php` が「MariaDB でなければ skip」するゲートを持っていた時期があった。
これは初期の実装時に MariaDB を baseline としていた名残であり、SQL はすでに
`GROUP_CONCAT(JSON_OBJECT(... ) ORDER BY ...)` ベースに書き直されており、
MySQL 8.0 でも正常に動作する。ゲートは 2026-06-28 の `license-cleanup` ブランチで
MySQL 8.0+ / MariaDB 両対応のチェックへ緩和済み。

旧来の大量 skip の原因だった差異:

- `JSON_VALUE` の引数型判定の差
- JSON 文字列を受け取る SQL の Invalid data type
- int / string hydration の差

これらは DC2Type 時代の旧 SQL に由来する誤りであり、現在の `var/sql/*.sql` では修正済み。

## 現在の挙動

`~/git/BeMart/be/tests/Sql/bootstrap.php` は次のように動く。

- `DATABASE_URL` が未設定なら skip marker を設定する。
- `DATABASE_URL` に接続できなければ skip marker を設定する。
- MySQL 8.0+ または MariaDB に接続できた場合のみ schema を load し、SQL tests を実行する。
- schema load 失敗は smoke failure として失敗させる。

## ローカルでの実行

`malt.json` が起動する MySQL 8.0 をそのまま使う。

```bash
malt start
source <(malt env)
export DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb_test?charset=utf8mb4&serverVersion=8.0.0'
sql/setup-db.sh "$DATABASE_URL"
/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit --testsuite sql --colors=never
```

`be/tests/Sql/bootstrap.php` が `eccubedb_test` を drop/create し、`sql/schema/bemart-schema.sql` を読み込む。

## 注意

- SQL の新規境界は Ray.MediaQuery の `#[DbQuery]` interface + `var/sql/*.sql` を使う。
- fake / HTML / HTTP の green と SQL green は分けて評価する。
