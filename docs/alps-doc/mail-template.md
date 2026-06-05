---
layout: default
title: "MailTemplate"
---

# MailTemplate

MailTemplate は EC-CUBE のメールテンプレート設定を表す。ALPS の `MailTemplate` descriptor は、本文ファイルではなく `dtb_mail_template` の登録情報を BeMart の `MailTemplateEntity` として投影する。

## Meaning

`MailTemplate` は admin がメールテンプレート名と件名を保守するための設定である。本文は DB ではなく Twig ファイルとして保持される。

## EC-CUBE Schema Projection

BeMart の `MailTemplateEntity` は厳密移植の整合のため、次の 4 項目のみを保持する。

- `mailTemplateId`
- `mailTemplateName`
- `fileName`
- `mailSubject`

これは `dtb_mail_template` の `id` / `name` / `file_name` / `mail_subject` と 1:1 に対応する。

## Scope Boundary

`dtb_mail_template` には本文列が存在しない。EC-CUBE 4.3 はメール本文をディスク上の Twig ファイルとして保持し、`file_name` がそのパスを指す。

BeMart 由来で schema から乖離していた `body` / `htmlBody` は削除済みである。

## Migration Decisions

`name` / `file_name` / `mail_subject` は EC-CUBE 上 nullable だが、Entity 上は non-null である。read 時に NULL を空文字列へ coalesce して投影形を維持する。

`creator_id` は `dtb_member` への FK だが、structure-only ダンプでは参照先が空のため固定 NULL とする。

## Persistence Behavior

update は `mail_subject` と `update_date` のみを書き込む。`file_name` は作成時固定で、更新不可である。

更新専用契約のため INSERT 経路はなく、ID generator も存在しない。未知 id は `MailTemplateNotFoundException` になる。

## Implementation References

- Phase 2b
- `MailTemplateEntity`
- `SqlMailTemplateStorage`
