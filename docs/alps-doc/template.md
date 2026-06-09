---
layout: default
title: "Template"
---

# Template

Template は EC-CUBE のインストール済みデザインテンプレートを表す。ALPS の `Template` descriptor は、テンプレート本体ファイルではなく `dtb_template` の登録レジストリを BeMart の `TemplateEntity` として投影する。

## Meaning

`Template` は admin が参照するデザインテンプレートの登録情報である。テンプレート本体はファイルシステム上に存在し、`dtb_template` はそれを管理画面で列挙するためのレジストリとして扱う。

## EC-CUBE Schema Projection

BeMart の `TemplateEntity` は次の 3 項目を保持する。

- `templateId`
- `templateName`
- `deviceType`

ALPS の `Template` descriptor は EC-CUBE 語彙として `#templateCode` も参照する。これは `dtb_template.template_code` に対応する install-time の一意コードであり、テンプレート追加系の `InstallTemplateInput` / `TemplateInstalled` では `templateCode` として扱われる。

ただし、現在の `TemplateStorageInterface::list()` が返す `TemplateEntity` は `templateCode` をまだ保持しない。Template list 画面では `templateId` を保存先表示の代替値として使うため、`templateCode` は ALPS 語彙には存在するが list storage 投影では未充填の residual である。

EC-CUBE の `createDate` / `updateDate` / `discriminator_type` は投影外である。

`dtb_template` の列形状は `dtb_layout` と同一で、`id` / `device_type_id` / `*_code` / `*_name` / `create_date` / `update_date` / `discriminator_type` を持つ。

## Scope Boundary

`TemplateStorageInterface` は `list()` のみを提供する。この storage interface には `TemplateIdProvider`、getById、put、remove は存在しない。

一方で、ALPS と Resource 層には `TemplateList` からの `goTemplateList` に加えて、テンプレート追加画面への `goTemplateAdd` link、テンプレート追加・選択・削除・ダウンロードを表す `doInstallTemplate` / `doSelectTemplate` / `doDeleteTemplate` / `doDownloadTemplate` transition が存在する。ここで list-only と呼ぶのは、`TemplateStorageInterface` の永続化 query が list-only であるという意味であり、画面上の遷移リンクや操作 affordance が存在しないという意味ではない。

このスライスからテンプレートは変更されないため、FK cascade 問題も発生しない。

## Migration Decisions

`device_type_id` は `smallint(5) unsigned nullable` で、`mtb_device_type.id` を参照する。`TemplateEntity::deviceType` は non-null int として扱い、read 時に NULL を 0 へ coalesce する。EC-CUBE の代表値は 10=PC、2=モバイルである。

`template_name` は EC-CUBE 上 NOT NULL である。

## Implementation References

- Phase 2b
- `TemplateEntity`
- `TemplateStorageInterface`
- `InstallTemplateInput`
- `TemplateInstalled`
- `SqlTemplateStorage`
