# BaseInfo

BaseInfo は EC-CUBE の店舗基本設定を表す。ALPS の `BaseInfo` descriptor は、`dtb_base_info` の id=1 行を BeMart の admin 設定画面で扱うシングルトンとして定義する。

## Meaning

`BaseInfo` は店舗名、会社名、住所、連絡先、営業時間、店舗メッセージなど、storefront と admin の両方で参照されるショップ全体の基本情報である。所有者キーを持つ通常のマスタではなく、アプリケーション全体に 1 行だけ存在する設定として扱う。

## EC-CUBE Schema Projection

EC-CUBE 4.3 の `dtb_base_info` は、インストーラが必ず `id = 1` の単一行を seed する設計である。そのため `SqlBaseInfoStorage` は `WHERE id = 1` 固定でこの行を扱う。

BeMart の `BaseInfoEntity` は Wave 8 / Wave 9 が要求する次の列を保持する。

- `shopName`
- `shopKana`
- `shopNameEng`
- `companyName`
- `postalCode`
- `pref`
- `addr01`
- `addr02`
- `phoneNumber`
- `businessHour`
- `shopEmail01`
- `shopMessage`

## Scope Boundary

`dtb_base_info` には point / tax / option_* / delivery_free_* / invoice_registration_number / company_kana / shop_email02..04 / good_traded / ga_id などの列もある。これらは Phase 2 の `BaseInfoEntity` 投影には含めず、画面・リソース・業務要求が接続された段階で個別に扱う。

## Master Data

`pref_id` は `mtb_pref`、`country_id` は `mtb_country` を参照する。構造ダンプ上は両マスタとも空のため、pref を扱うテストでは `insertPref()` で先にマスタを seed する必要がある。

## Persistence Behavior

`dtb_base_info` に id=1 行が無い場合、`SqlBaseInfoStorage` は `BaseInfoEntity` の null 値で hydrate し、installer 前状態を表現する。

update は INSERT-or-UPDATE として実装し、`id = 1` の singleton を idempotent に維持する。

## Implementation References

- Phase 2
- Wave 8
- Wave 9
- `BaseInfoEntity`
- `SqlBaseInfoStorage`
