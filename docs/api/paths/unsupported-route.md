<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /unsupported-route
Safe placeholder for template routes that are not backed by a resource yet.






## GET
ALPS `goUnsupportedRoute` に対応する GET 操作。

**ALPS**: `goUnsupportedRoute`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| routeName | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /unsupported-route response](../schemas/get-unsupported-route.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 未対応ルートメッセージ - /unsupported-route のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| routeName | string|null | ページURL - ページのURLパス（Symfonyルート名。例: homepage, product_list） | Required | {"minLength":0,"maxLength":255} |  |

## POST
ALPS `doUnsupportedRoute` に対応する POST 操作。

**ALPS**: `doUnsupportedRoute`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| routeName | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| returnTo | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | /products |


### Response

[Object: POST /unsupported-route response](../schemas/post-unsupported-route.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 未対応ルートメッセージ - /unsupported-route のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| routeName | string|null | ページURL - ページのURLパス（Symfonyルート名。例: homepage, product_list） | Required | {"minLength":0,"maxLength":255} |  |
