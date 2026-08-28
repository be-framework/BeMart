<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/toggle-visible
EC-CUBE doToggleVisible — 表示・非表示を切り替える (Phase 3
ALPS-audit remediation).

PUT /admin/toggle-visible

The generic admin-list visibility transition. EC-CUBE has a
per-master *_visible / *_visibility route for each list screen
(Payment / Delivery / ClassCategory / News); BeMart folds them into
this one resource keyed by `masterType`. ALPS marks it `idempotent`
— the flag is set to an explicit `visible` value, so PUT is the verb.

Failure mapping:
  - Invalid CSRF                            → 403
  - SemanticVariableException               → 400 (masterType / visible)
  - UnauthorizedAdminAccessException        → 403 (no admin session)
  - MasterRowNotFoundException              → 404
  - MasterOperationNotSupportedException    → 400 (master lacks visible)




## PUT
ALPS `doToggleVisible` に対応する PUT 操作。

**ALPS**: `doToggleVisible`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string | マスタ種別（入力） - /admin/toggle-visible の処理文脈から派生したマスタ種別。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| rowId | string | 行ID（入力） - /admin/toggle-visible のレスポンスで対象を識別する行ID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"\u884cID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| visible | bool | 処理状態フラグ（入力） - 観察値 'true'。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | true |


### Response

[Object: PUT /admin/toggle-visible response](../schemas/put-admin-toggle-visible.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| masterType | string | 表示切替マスタ種別 - /admin/toggle-visible で表示状態を変更する管理マスタの種別。delivery や payment など、表示フラグを持つ管理一覧の対象を識別する。 | Required | {"minLength":1,"maxLength":64,"pattern":"^[A-Za-z][A-Za-z0-9_-]{0,63}$"} | delivery |
| rowId | string | 表示切替対象行ID - /admin/toggle-visible で表示状態を変更する管理マスタ行のID。masterType と組み合わせて対象行を一意に指す。 | Required | {"minLength":1,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]+$"} | del-yamato |
| visible | boolean | 表示状態 - 対象マスタ行を表示状態にするかどうかを表す真偽値。PUT はこの値へ冪等に設定する。 | Required |  | false |
