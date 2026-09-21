<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /action-redirect
Safe HTML endpoint for legacy storefront links whose state transition is
performed by JavaScript or by a POST-only route in EC-CUBE.

It never renders a placeholder page. The browser is redirected to a stable
page so link crawls do not surface "not implemented" copy while templates
are migrated to explicit POST forms.




## GET
ALPS `goActionRedirect` に対応する GET 操作。

**ALPS**: `goActionRedirect`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| returnTo | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | /products |


### Response

[Object: GET /action-redirect response](../schemas/get-action-redirect.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | リダイレクトメッセージ - /action-redirect のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |

## POST
ALPS `doActionRedirect` に対応する POST 操作。

**ALPS**: `doActionRedirect`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| returnTo | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | /products |


### Response

[Object: POST /action-redirect response](../schemas/post-action-redirect.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | リダイレクトメッセージ - /action-redirect のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
