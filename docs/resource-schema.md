# Resource schema

Resource の request / response shape は `src/Support/Resource/ResourceSchemas.php` に集約する。
新しい schema は lowerCamelCase で `{resource}{HttpMethod}{Input|Status}` と命名する。

例:

- `productGetInput()` — `Product::onGet()` の入力
- `productGetOk()` — `Product::onGet()` の 200 body
- `adminProductGetOk()` — `Admin\Product::onGet()` の 200 body
- `error()` — 共通エラー body

エラー body は `message: string` を必須にする。`productCode` など Resource 固有の補助フィールドは
既存テンプレートと JSON client 互換のため top-level に残してよい。

ResourceObject でエラー応答を組み立てるときは `ErrorResponseBody` / `ResourceErrorResponder` を使う。
`SemanticVariableException` は `semanticBadRequest()` で `400 Bad Request` に変換する。

Schema validation は Resource test で行う。主要 Resource の happy path body と代表的なエラー body は
`ResourceSchemas::*()->assertMatches($body)` で検証する。
