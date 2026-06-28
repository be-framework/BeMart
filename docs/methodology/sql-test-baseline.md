---
layout: default
title: "SQL Test Baseline"
---

# SQL Test Baseline

作成日: 2026-05-26
対象: BeMart SQL suite / Ray.MediaQuery / EC-CUBE schema baseline

## 目的

BeMart の SQL suite がどの DB を前提にしているかを明確にし、MySQL と MariaDB の挙動差による誤判定を避ける。

## 結論

SQL suite の baseline は **MariaDB**。
`phpunit.xml` の `DATABASE_URL` も `serverVersion=mariadb-10.11.14` を前提としている。
ローカルに MySQL 8/9 しかない場合、SQL suite は実行せず skip する。

## 背景

ローカル環境で MySQL 8.0.45 / 9.6 を使って SQL suite を実行すると、主に次の差異で大量失敗する。

- `JSON_VALUE` の引数型判定が MariaDB baseline と合わない。
- JSON 文字列を受け取る SQL が MySQL では `Invalid data type for JSON data` になる。
- DB から返る int と Entity constructor の string semantic の hydration 差異が表面化する。

この失敗は HTML route / CSRF / fake suite の失敗ではなく、SQL baseline DB の不一致である。

## 現在の挙動

`~/git/BeMart/be/tests/Sql/bootstrap.php` は次のように動く。

- `DATABASE_URL` が未設定なら skip marker を設定する。
- `DATABASE_URL` に接続できなければ skip marker を設定する。
- 接続先が MariaDB でなければ skip marker を設定する。
- MariaDB に接続できた場合のみ schema を load し、SQL tests を実行する。
- MariaDB 到達後の schema load 失敗は smoke failure として失敗させる。

## 検証 baseline

2026-06-04 のローカル環境では Malt が MySQL 8.0.46 を起動するため、次の結果が正しい。

```bash
source <(malt env)
/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit --testsuite sql --colors=never
```

結果:

- Tests: 754
- Assertions: 0
- Skipped: 754
- 理由: current server is MySQL 8.0.46, SQL suite targets MariaDB

## MariaDB で実行する場合

MariaDB を用意し、`DATABASE_URL` を MariaDB に向ける。

```bash
export DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb_test?charset=utf8mb4&serverVersion=mariadb-10.11.14'
composer test:sql -- --colors=never
```

MariaDB reachable の場合は `be/tests/Sql/bootstrap.php` が `eccubedb_test` を drop/create し、`sql/schema/bemart-schema.sql` を読み込む。

## 今後の注意

- MySQL で SQL suite を直す方向に寄せない。対象 baseline は MariaDB。
- MySQL 対応を行う場合は別計画に分ける。
- SQL の新規境界は Ray.MediaQuery の `#[DbQuery]` interface + `var/sql/*.sql` を使う。
- fake / HTML / HTTP の green と SQL/MariaDB green は分けて評価する。
