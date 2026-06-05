---
layout: default
title: "/admin/base-info"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/base-info
EC-CUBE doUpdateBaseInfo + goBaseInfo — 基本情報 (Wave 8 + Wave 9).

- GET  → goBaseInfo (safe read, admin AUTHZ, Wave 9ι)
  - POST → doUpdateBaseInfo (idempotent, admin AUTHZ + CSRF, Wave 8ε)

dtb_base_info is a single-row table; POST replaces the row wholesale
(no per-field PATCH semantic in EC-CUBE). Only the shopName is
required — null in other fields means "clear it".

Failure mapping:
  - Invalid CSRF                          → 403 (POST only)
  - SemanticVariableException             → 400 (shopName / address /
                                               phoneNumber / … format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Idempotency (ALPS `type=idempotent`): replaying the same body is a
no-op-equivalent — the Final reports `changed=false` and the row
is not rewritten.

Mass-assignment safety: only the shop-info columns are accepted.




## GET
Wave 9ι: goBaseInfo — admin views the shop base info form data.

Setting/Shop Tier-2 also renders `shop_master.twig` from this body;
the `form` key carries an {@see \AdminShopMasterForm} pre-filled
with the dtb_base_info row for the HTML editor.



### Request

_No parameters required_

### Response

_Not available_
## POST
Wave 8: every shop-info field is admin-form input.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| shopName | string | ショップ名 |  | Required |  |  |
| shopKana | string | ショップ名フリガナ |  | Optional |  |  |
| shopNameEng | string | ショップ名英語 |  | Optional |  |  |
| companyName | string | 会社名 |  | Optional |  |  |
| postalCode | string | 郵便番号 |  | Optional |  |  |
| pref | int | 都道府県 |  | Optional |  |  |
| addr01 | string | 市区町村 |  | Optional |  |  |
| addr02 | string | 番地・建物名 |  | Optional |  |  |
| phoneNumber | string | 電話番号 |  | Optional |  |  |
| businessHour | string | 営業時間 |  | Optional |  |  |
| shopEmail01 | string | 送信元/BCC メールアドレス |  | Optional |  |  |
| shopMessage | string | ショップメッセージ |  | Optional |  |  |


### Response

_Not available_