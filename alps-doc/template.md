# Template

Template は EC-CUBE のインストール済みデザインテンプレートを表す。ALPS の `Template` descriptor は、テンプレート本体ファイルではなく `dtb_template` の登録レジストリを BeMart の `TemplateEntity` として投影する。

## Meaning

`Template` は admin が参照するデザインテンプレートの登録情報である。テンプレート本体はファイルシステム上に存在し、`dtb_template` はそれを管理画面で列挙するためのレジストリとして扱う。

## EC-CUBE Schema Projection

BeMart の `TemplateEntity` は次の 3 項目を保持する。

- `templateId`
- `templateName`
- `deviceType`

EC-CUBE の `templateCode` / `createDate` / `updateDate` / `discriminator_type` は投影外である。

`dtb_template` の列形状は `dtb_layout` と同一で、`id` / `device_type_id` / `*_code` / `*_name` / `create_date` / `update_date` / `discriminator_type` を持つ。

## Scope Boundary

`TemplateStorageInterface` は `list()` のみを提供する。ALPS 上も `goTemplateList` のみで、作成・更新・削除のアフォーダンスはない。そのため `TemplateIdProvider`、getById、put、remove は存在しない。

このスライスからテンプレートは変更されないため、FK cascade 問題も発生しない。

## Migration Decisions

`device_type_id` は `smallint(5) unsigned nullable` で、`mtb_device_type.id` を参照する。`TemplateEntity::deviceType` は non-null int として扱い、read 時に NULL を 0 へ coalesce する。EC-CUBE の代表値は 10=PC、2=モバイルである。

`template_name` は EC-CUBE 上 NOT NULL である。

## Implementation References

- Phase 2b
- `TemplateEntity`
- `TemplateStorageInterface`
- `SqlTemplateStorage`
