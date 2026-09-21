<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/action-redirect
Safe admin endpoint for EC-CUBE routes that are represented by list-page
JavaScript actions or by external-store operations in the original app.

The route is intentionally not a placeholder page: authenticated admins are
redirected to a stable admin screen and the response copy contains no
placeholder marker, so route/link coverage can stay green while dedicated
domain transitions are added incrementally.




## GET
ALPS `goAdminActionRedirect` に対応する GET 操作。

**ALPS**: `goAdminActionRedirect`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| returnTo | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | /products |


### Response

[Object: GET /admin/action-redirect response](../schemas/get-admin-action-redirect.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | リダイレクトメッセージ - /admin/action-redirect のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |

## POST
ALPS `doAdminActionRedirect` に対応する POST 操作。

**ALPS**: `doAdminActionRedirect`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| returnTo | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | /products |


### Response

[Object: POST /admin/action-redirect response](../schemas/post-admin-action-redirect.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | リダイレクトメッセージ - /admin/action-redirect のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
