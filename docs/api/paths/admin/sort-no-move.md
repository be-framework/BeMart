<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/sort-no-move
EC-CUBE doSortNoMove — 並び順を変更する (Phase 3 ALPS-audit
remediation).

PUT /admin/sort-no-move

The generic admin-list reorder transition. EC-CUBE has a per-master
*_sort_no_move route for each list screen (Payment / Delivery / Tag /
ClassName / ClassCategory); BeMart folds them into this one resource
keyed by `masterType`. ALPS marks it `idempotent` — PUT is the verb.

Failure mapping:
  - Invalid CSRF                            → 403
  - SemanticVariableException               → 400 (masterType / sortNo)
  - UnauthorizedAdminAccessException        → 403 (no admin session)
  - MasterRowNotFoundException              → 404
  - MasterOperationNotSupportedException    → 400 (master lacks sort_no)




## PUT
ALPS `doSortNoMove` に対応する PUT 操作。

**ALPS**: `doSortNoMove`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string | マスタ種別（入力） - /admin/sort-no-move の処理文脈から派生したマスタ種別。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| rowId | string | 行ID（入力） - /admin/sort-no-move のレスポンスで対象を識別する行ID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"\u884cID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| sortNo | int | 表示順（入力） - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |


### Response

[Object: PUT /admin/sort-no-move response](../schemas/put-admin-sort-no-move.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/sort-no-move のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| masterType | string | マスタ種別 - /admin/sort-no-move の処理文脈から派生したマスタ種別。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minLength":1,"maxLength":255} |  |
| rowId | string|int|null | 行ID - /admin/sort-no-move のレスポンスで対象を識別する行ID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} |  |
| sortNo | int|null | 表示順 - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
