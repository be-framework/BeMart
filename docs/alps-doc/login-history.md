---
layout: default
title: "LoginHistory"
---

# LoginHistory

LoginHistory は EC-CUBE の管理者ログイン監査ログを表す。ALPS の `LoginHistory` descriptor は、`dtb_login_history` を BeMart の `LoginHistoryEntity` として投影する。

## Meaning

`LoginHistory` は管理画面ログイン試行の履歴である。ログインID、成功/失敗、クライアントIP、日時を追記専用で記録する。

## EC-CUBE Schema Projection

BeMart の `LoginHistoryEntity` は次の 4 項目を保持する。

- `timestamp` → `dtb_login_history.create_date`
- `loginId` → `dtb_login_history.user_name`
- `success` → `dtb_login_history.login_history_status_id`
- `clientIp` → `dtb_login_history.client_ip`

## Append-Only Log

LoginHistory は append + list の追記専用監査ログである。getById、update、delete は存在しない。監査行はクライアント可視のハンドルを持たず、変更もされないため `LoginHistoryIdProvider` も存在しない。

管理者認証の 2 段（パスワードと 2 要素認証コード）は、成功も失敗も 1 行ずつ append する。同じ行はログイン試行回数の制限にも使われ、`LoginAttemptGateInterface` が「直近の成功より後」の失敗数を数える。

## Migration Decisions

EC-CUBE の `member_id` は `dtb_member` への FK だが、Phase 2 スコープ外で常に NULL を書く。structure-only ダンプでは `dtb_member` が空のため、NULL 以外を書き込むと FK 1452 が発生する。

`loginId` から `member_id` への解決は Phase 2 の後続スコープである。

`login_history_status_id` は `smallint unsigned NOT NULL` で、`mtb_login_history_status` への FK である。0 が失敗、1 が成功を表す。このマスタは `sql/seed/mtb-master.sql` が 2 行を投入する。

`success` bool と `login_history_status_id` は双方向に cast する。

## Date Handling

`create_date` は MySQL `datetime` で、タイムゾーンを持たない。append はこの列にデータベースの `NOW()` を書く（監査行の時刻は呼び出し側が主張するものではない）。read はその値をそのまま `timestamp` として返す。

`update_date` は append 時に `create_date` と同値にする。監査行は更新されない。

## Implementation References

- `LoginHistoryEntity`
- `LoginHistoryStorageInterface`
- `LoginAttemptGateInterface`
- `LoginHistoryFactory`
