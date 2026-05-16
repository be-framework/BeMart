# Application Implement — BEAR.Sunday

対象: `{descriptor}`

前ステップまでで、Be ドメイン層の Input / Final / Semantic / Reason が実装済みである。このステップでは BEAR.Sunday 側のアプリケーション層（リソース）を実装する。

## 早期スキップ判定

`alps-analyze` ステップ末尾の **`json handover` ブロック** を最初に読み、以下のいずれかに該当する場合はこのステップは実装対象を持たない:

1. `"bear": { "skip": true, ... }` が明示されている
2. `"descriptor_type": "container"` である（container 集約は単独 URI を持たず、Final の body shape 定義としてのみ参照される）
3. JSON が無い（古い alps-analyze 出力）または破損している場合のフォールバックとして、Markdown レポートの「BEAR 層マッピング案」テーブルが全項目 `N/A` となっている

抽出は決定的に行うこと:

- 正規表現 ```` ```json handover\n([\s\S]+?)\n``` ```` で fenced ブロックを取り、`JSON.parse` 相当で解釈
- 失敗したら上記フォールバック（テーブル全項目 N/A 判定）に進む。**ユーザーには質問しない**

該当する場合は以下だけを出力して終了する:

```markdown
## 作成ファイル
(なし — 純粋 Semantic / container のため BEAR リソースは生成しない)

## スキップ理由
<handover JSON の bear.skip / descriptor_type=container / フォールバックのいずれを根拠としたか 1 行>

## テスト結果
skipped: pure semantic or container aggregate, handled by upper descriptor
```

後続の `application-review` ステップも実質 no-op となり、上位 descriptor（例: `AddCartItemInput`, `Cart`）の `/run migrate` 実行時にこの Semantic が引数として参照される。

## 入力契約

このステップへの入力は **2 種類**ある:

### 初回実行時

前段 `alps-analyze` ステップ末尾の `json handover` fenced ブロックと、`domain` ステップの「作成ファイル」一覧。**handover JSON を最初に読み**、上記「早期スキップ判定」を通過した上で以下を使う:

- `bear.uri_scheme` — `page` なら `src/Resource/Page/`、`app` なら `src/Resource/App/` へ配置
- `bear.http_method` — `onGet` / `onPost` / `onPut` / `onPatch` / `onDelete` のいずれか 1 つを実装
- `bear.base_uri` — リソース URL のパスパターン。`{id}` 形式のパラメータは AuraRouter で受ける
- `bear.links[]` — `#[Link(rel, href)]` 属性として転記
- `be_classes.input` — リソースメソッド内で `($this->becoming)(new <Input>(...))` の形で呼び出す
- `be_classes.final` — Final のプロパティ名を `$this->body` への詰め替えに使う
- `notes[]` — 暫定判断や別 PR 切り出し提案。リソース側の実装にも影響する場合だけ参照

JSON ブロックが**存在しない / 破損している場合**は、Markdown レポートの「BEAR 層マッピング案」テーブルを読んで人手相当の解釈を行う。ユーザーに確認しない。

### 差し戻し時（application-review からの再実行）

直前の `application-review` ステップが `{ "verdict": "fail", "blocking": [ ... ] }` を返した場合、このステップは再実行される。**`blocking[]` 配列の各要素を必ず潰す**こと:

- `blocking[].file` / `blocking[].line` — 修正対象の位置
- `blocking[].rule` — 2 層境界違反のタイプ（例: `resource-has-business-logic`, `resource-touches-db`, `link-missing`, `static-return-violation`）
- `blocking[].message` — 修正の指示
- `blocking[].suggestion`（任意）— reviewer が提案する書き換え案

再実行時は**新規ファイル追加よりも既存ファイルの修正を優先**。`blocking[]` の指摘が Be ドメイン側に起因していると判明した場合のみ、Markdown 出力の「補足」にその旨を書いて完了とし、上位 `/run` 側で domain への手戻りを判断させる（このステップで勝手にドメインコードを編集しない）。

## 前提と参照

- `alps-skills:alps-to-bear` スキル — ALPS から BEAR.Sunday プロジェクト生成
- `bear-skills:bear-resource-generator` スキル — リソースセット生成
- `bear-skills:bear-hypermedia` スキル — `#[Link]` とハイパーメディアテスト
- `bear-skills:resource-documenter` スキル — リソース PHPDoc 生成
- `https://bearsunday.github.io/llms-full.txt` — BEAR.Sunday LLM リファレンス

## 設計原則

### BEAR と Be の責務分離

- **BEAR = 入口** — HTTP リクエストを受けて Be の `Becoming` を呼ぶだけ
- **Be = ドメイン** — ビジネスロジック、バリデーション、副作用はすべてここ
- リソースクラスに **if/else / DB アクセス / 計算ロジックを書かない**
- リソースクラスは Input を組み立てて `Becoming` に渡すだけのアダプター

### URI スキーマ

- `page://self/...` — ブラウザ入口（GET でページを返す、POST でフォーム受付）
- `app://self/...` — 内部 API。他のリソースから呼ばれるリソース合成用

ALPS の `src-router` タグが付いている descriptor は `page://`、それ以外で他リソースから呼ばれるものは `app://`。

### HTTP メソッドと ALPS type の対応

| ALPS type | HTTP / メソッド |
|---|---|
| safe (`go*`) | GET / `onGet` |
| idempotent (`do*Update`, `do*Replace`) | PUT / `onPut` または PATCH / `onPatch` |
| idempotent (`do*Delete`) | DELETE / `onDelete` |
| unsafe (`do*Add`, `do*Create`, `do*Confirm`) | POST / `onPost` |

## 手順

### 1. alps-analyze と domain の結果を再確認

前2ステップの出力を読み、以下を確定する:

- リソースクラス名（例: `Product`, `Cart`, `Shopping`）
- URI（例: `/product/{id}`, `/cart`）
- HTTP メソッド（onGet / onPost / etc.）
- 呼び出す Be Input クラス
- Link 候補（ALPS の子 `go*` / `do*` 操作）

### 2. Resource クラスの実装

**page:// リソースの例**:

```php
namespace MyCompany\EcCube\Resource\Page;

use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyCompany\EcCube\Input\GetProductInput;
use MyCompany\EcCube\Input\CreateProductInput;

final class Product extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {}

    #[Link(rel: 'goProductList', href: '/product/list')]
    #[Link(rel: 'doAddCartItem', href: '/cart/item', method: 'POST')]
    public function onGet(string $productId): static
    {
        $final = ($this->becoming)(new GetProductInput($productId));

        $this->body = [
            'productId' => $final->productId,
            'productName' => $final->productName,
            'price02' => $final->price02,
            'stock' => $final->stock,
        ];

        return $this;
    }

    public function onPost(string $productName, int $price02, ?string $descriptionDetail = null): static
    {
        $final = ($this->becoming)(new CreateProductInput($productName, $price02, $descriptionDetail));

        $this->code = 201;
        $this->headers['Location'] = "/product/{$final->productId}";
        $this->body = ['productId' => $final->productId];

        return $this;
    }
}
```

### 3. 重要なルール

#### リソースは Becoming を呼ぶだけ

**禁止事項**:

- リソースクラス内で DB アクセス
- リソースクラス内でビジネスロジック（`if` による条件分岐、計算など）
- リソースクラス内で例外をキャッチして変換（Be の例外はそのまま伝播させる）
- リソースクラス内で `new` で Final クラスを直接生成

**許容される処理**:

- HTTP パラメータを Input クラスのコンストラクタ引数に渡す
- Final クラスのプロパティを `$this->body` に詰める
- HTTP ステータス・ヘッダの設定（`$this->code`, `$this->headers`）
- `#[Link]` 属性による遷移先の宣言

#### 戻り値は `static`

BEAR.Sunday のモダン記法。`ResourceObject` ではなく `static` を返す。

#### `#[Link]` の生成元

Be の Final クラスに書いた `@link InputClassName(alpsId) 説明` phpdoc を参照して、対応する `#[Link]` 属性をリソースに付ける。

ALPS の alpsId（`goProductList` 等）と、対応する href（リソース URL）を自動で対応付ける。

### 4. リソースクラスへの PHPDoc 付与

`resource-documenter` スキルを参考に、リソースクラスとメソッドに PHPDoc を付ける:

```php
/**
 * Product resource.
 *
 * 商品の詳細表示・作成・更新・削除。
 */
final class Product extends ResourceObject
{
    /**
     * Get a product by id.
     *
     * @param string $productId ULID
     */
    public function onGet(string $productId): static
    {
        // ...
    }
}
```

### 5. ルーティングの更新

プロジェクトのルーター設定（`var/conf/aura.route.php` または AuraRouter 設定）に新しいリソースの URL パターンを追加する:

```php
$map->route('/product/{productId}', '/product');
```

### 6. スモークテストの作成

`bear-resource-test` スキルを参考に、全リソースの HTTP メソッドに対してスモークテストを作成する:

```php
final class ProductTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(new AppModule());
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->get('page://self/product', ['productId' => '01HXYZ...']);
        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('productName', $ro->body);
    }

    public function testOnPost(): void
    {
        $ro = $this->resource->post('page://self/product', [
            'productName' => 'サンプル商品',
            'price02' => 1000,
        ]);
        $this->assertSame(201, $ro->code);
    }
}
```

### 7. ハイパーメディアテスト（Link の遷移）

`bear-hypermedia` スキルを参考に、`#[Link]` で宣言した遷移が実際に動くかテストする:

```php
public function testLinkDoAddCartItem(): void
{
    $ro = $this->resource->get('page://self/product', ['productId' => '01HXYZ...']);
    $this->assertArrayHasKey('doAddCartItem', $ro->headers['Link'] ?? []);
}
```

### 8. テスト実行

```bash
cd <migration-project> && composer test
```

全パス確認。失敗したら修正してから完了にする。

## 出力フォーマット

```markdown
## 作成ファイル

- src/Resource/Page/Product.php
- tests/Resource/Page/ProductTest.php
- (ルーティング設定の更新)

## テスト結果
composer test: OK (N tests, N assertions)
```

この一覧は次の `application-review` ステップへ渡される。
