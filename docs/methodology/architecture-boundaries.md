# アーキテクチャの境界線

各境界線は、意味論を実装へ写像するときの依存方向、責務、表現変換の接続点です。

| 境界 | 役割 |
|---|---|
| ALPS | アプリケーション意味論・情報構造 |
| Be Framework | ドメイン境界（`Input` schema / `Being` / `Final`） |
| Ray.MediaQuery | ドメイン ↔ インフラ境界。PHP interface ↔ SQL file、SQL row/result ↔ domain object |
| BEAR.Sunday | HTTP request ↔ リソース境界（URI / HTTP method / `on*` method parameter / `ResourceObject`） |
| OpenAPI / API schema | PHP の `on*` method から生成される公開 HTTP 契約（parameter / status / representation shape） |
| Workflow / Hypermedia evidence | 同じ状態遷移契約を PHP Resource / HTTP / HTML affordance へ投影する境界（`#[Link]` / `href` / `form action`） |
| Cache / freshness | Resource 表現 ↔ browser / proxy / CDN の鮮度境界（`CacheableResponse` / `Cache-Control` / `ETag` / `Vary` / invalidation） |
| Context / DI | 実装選択境界（Fake ↔ SQL、HTML ↔ JSON、test ↔ prod） |
| SQL schema | 永続化境界（table / column / FK / nullable / id shape） |

## 各境界の連携

まず EC-CUBE から集めた意味論・情報構造を ALPS に固定します。そこから Be Framework が
ドメイン状態遷移を表し、BEAR.Sunday Resource が HTTP / PHP 共通の Resource 境界を作ります。
Ray.MediaQuery は SQL を interface 境界に閉じ込め、Context / DI が Fake / SQL、HTML / JSON、
test / prod の実装選択を担います。Fake は最初の契約実装であり、SQL 実装は同じ Resource 契約を
満たすものとして検証されます。Twig HTML は EC-CUBE の affordance をできるだけ保持し、
workflow test は controller 内部ではなく、link / form を辿って状態遷移を証明します。
HTTP workflow は同じシナリオを実 HTTP / cookie 境界へ持ち出し、HTML render と Web E2E は
その遷移が実際の画面 affordance として残っていることを確認します。

## 境界制約として扱う原則

Taint tracking、DIP / ADP も境界制約として扱います。関連ノートは
[methodology index](index.md) と [skills index](../skills/index.md) を参照してください。
