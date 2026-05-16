# Domain Implement — Be Framework

対象: `{descriptor}`

前ステップ `alps-analyze` の出力を入力として、Be Framework でドメイン層を実装せよ。

## 前提と参照

このプロジェクトの Be 実装は以下を**正解パターン**として踏襲する:

- `be-framework-skills:be` スキル — 実装ルールとプロジェクト構造
- `be-framework-skills:be-semantic` スキル — Story → ALPS → Schema → Be の流れ
- `be-framework-skills:semantic-ex` スキル — Fake データから制約発見
- `semantic-variable-creator` スキル — Semantic 変数の生成
- `alps-skills:alps-to-jsonschema` スキル — ALPS から JSON Schema 生成
- `/Users/akihito/git/be-patterns/demos/` — 8 種のパターン実装（hello-world, contact-form, user-registration, order-processing, blog-publishing, medical-triage, loan-application, insurance-claim）
- `/Users/akihito/git/be-skills/be/SKILL.md` — Be 実装ルール

**Be の核原則**:

- Objects don't DO things — they BECOME things
- Input → Final が基本（Direct 変換）
- Being は**分岐が必要な時のみ**導入する
- 独立した副作用が複数あるなら Moment を Final に注入する Diamond パターン
- Final コンストラクタでの副作用は正当（doing for being）
- `@link` phpdoc で次の becoming への潜在性を記述

## 入力契約

このステップへの入力は **2 種類**ある:

### 初回実行時

前ステップ `alps-analyze` の出力。**末尾の `json handover` fenced ブロックを最初に読む**:

```text
```json handover
{ ... }
```
```

このブロックから以下を抽出して実装に使う:

- `descriptor_id` / `alps_id_resolved` — 実装対象の確定
- `descriptor_type` — `container` または `bear.skip: true` ならドメイン層の実装も最小化（純粋 Semantic クラスのみ作成、Input/Final は作らない）
- `be_pattern` / `be_reference_demo` — 「1. alps-analyze の結果を再確認」で使う
- `be_classes.input` / `be_classes.final` — クラス名の確定
- `semantic_classes[]` — `src/Semantic/*.php` の生成材料。`static_constraints` は `#[Validate]` に、`dynamic_constraints` は Final コンストラクタへ
- `reasons[]` — `src/Reason/Media/{Command,Query}/` と `src/Reason/Entity/` の生成材料。`fake_fixture` パスに `var/fake/<id>.json` を置く

JSON ブロックが**存在しない / 破損している場合**は、Markdown レポート側を読んで人手相当の解釈を行う。ユーザーに確認しない。

### 差し戻し時（domain-review からの再実行）

直前の `domain-review` ステップが `{ "verdict": "fail", "blocking": [ ... ] }` を返した場合、このステップは再実行される。**`blocking[]` 配列の各要素を必ず潰す**こと:

- `blocking[].file` / `blocking[].line` — 修正対象の位置
- `blocking[].rule` — Be 原則の違反タイプ（例: `no-else`, `final-readonly`, `no-domain-exception-leak`, `becoming-not-doing`）
- `blocking[].message` — 修正の指示
- `blocking[].suggestion`（任意）— reviewer が提案する書き換え案

再実行時は**新規ファイル追加よりも既存ファイルの修正を優先**。`blocking[]` が空ならそもそも再実行されないので、空配列のチェックは不要。3 回失敗で workflow が停止する仕様は `/run` 側の責務。

## 手順

### 1. alps-analyze の結果を再確認

入力契約で取り込んだ `be_pattern` に対応する be-patterns のデモを特定する:

| パターン | 参照デモ |
|---|---|
| Direct | `hello-world` |
| Linear (Input→Being→Final) | `contact-form` |
| Sequential Chain | `user-registration` |
| Diamond | `order-processing` |
| Multi-Reason Being | `blog-publishing` |
| Branching | `medical-triage` |
| Cascade Diamond | `loan-application` |
| Complex Convergence | `insurance-claim` |

該当するデモのコードを Read ツールで読み、use 文・FQCN・クラス構造を**そのまま踏襲**する。推測で別の書き方をしない。

### 2. プロジェクト配置の確認

EC-CUBE 移植先の BEAR.Sunday + Be プロジェクトのルートディレクトリが決まっていなければ、ユーザーに確認する（初回のみ）。以下の構造に従う:

```text
<migration-project>/
└── src/
    ├── Input/      起点クラス
    ├── Semantic/   セマンティック変数
    ├── Exception/  ドメイン例外
    ├── Final/      終点クラス
    ├── Reason/
    │   ├── Entity/     Query の戻り値型
    │   └── Media/
    │       ├── Command/  書き込みインターフェース
    │       └── Query/    読み取りインターフェース
    └── Module/
        └── AppModule.php
```

既存プロジェクトなら、現在のディレクトリ構成を確認してから追加する。

### 3. Semantic 変数の実装

`alps-analyze` で抽出した各プロパティについて、`src/Semantic/*.php` を作成する:

- クラス名は UpperCamelCase（例: `ProductName`, `ProductCode`, `StockQuantity`）
- コンストラクタ引数名は lowerCamelCase（例: `$productName`）— ALPS の descriptor id と一致させる
- `#[Validate]` メソッドで制約を宣言
- nullable なら引数を `string|null` にして null 時早期 return
- 制約値の根拠は ALPS / 既存 DB スキーマ / fake data 観察から。`semantic-ex` の原則に従い、根拠を `$comment` または phpdoc に残す

例:

```php
final readonly class ProductName
{
    #[Validate]
    public function validate(string $productName): void
    {
        if ($productName === '') {
            throw new EmptyProductNameException();
        }
        if (mb_strlen($productName) > 255) {
            throw new ProductNameTooLongException();
        }
    }
}
```

### 4. Exception クラスの実装

Semantic 変数が投げる例外は専用クラスにする。汎用例外（`LogicException` 等）は禁止。

```php
#[Message([
    'en' => 'Product name cannot be empty.',
    'ja' => '商品名は空にできません。',
])]
final class EmptyProductNameException extends DomainException {}
```

### 5. Reason 層の実装（DB アクセスがある場合）

`alps-analyze` の Reason 候補リストに従い、以下を作成する:

#### Entity（Query の戻り値型）

```php
// Reason/Entity/ProductEntity.php
final readonly class ProductEntity
{
    public function __construct(
        public string $productId,
        public string $productName,
        public int $price02,
        // ...
    ) {}
}
```

SQL カラム名 (snake_case) → プロパティ名 (camelCase) は自動変換される。

#### Media/Query インターフェース

```php
// Reason/Media/Query/GetProductInterface.php
interface GetProductInterface
{
    #[DbQuery('get_product')]
    public function __invoke(string $productId): ProductEntity;
}
```

#### Media/Command インターフェース

```php
// Reason/Media/Command/InsertProductInterface.php
interface InsertProductInterface
{
    #[DbQuery('insert_product')]
    public function __invoke(string $productId, string $productName, int $price02): void;
}
```

#### FakeQuery フィクスチャ（Phase 1）

`var/fake/get_product.json` に実行時の戻り値を置く:

```json
{
  "productId": "01HXYZ...",
  "productName": "サンプル商品",
  "price02": 1000
}
```

コレクションの場合は `.jsonl`（1行1オブジェクト）。Command は void なのでファイル不要（no-op）。

### 6. Input クラスの実装

```php
#[Be([ProductPrepared::class])]
final readonly class CreateProductInput
{
    public function __construct(
        public string $productName,
        public int $price02,
        public ?string $descriptionDetail = null,
    ) {}
}
```

- `#[Be([...])]` で変換先を宣言
- コンストラクタ引数名は Semantic クラス名と自動マッチする
- 分岐が無ければ Final を直接指定、必要なら Being → Final

### 7. Final クラスの実装

```php
/**
 * A newly created product.
 *
 * @link GetProductInput(goProduct) View product
 * @link UpdateProductInput(doUpdateProduct) Edit product
 * @link DeleteProductInput(doDeleteProduct) Delete product
 */
final readonly class ProductCreated
{
    public function __construct(
        #[Input] public string $productName,
        #[Input] public int $price02,
        #[Input] public ?string $descriptionDetail,
        #[Inject] InsertProductInterface $insertProduct,
        #[Inject] UlidGeneratorInterface $ulidGenerator,
    ) {
        $this->productId = ($ulidGenerator)();
        ($insertProduct)($this->productId, $this->productName, $this->price02);
    }

    public string $productId;
}
```

- `final readonly class`
- `#[Input]` で前段データ、`#[Inject]` で DI
- コンストラクタ内で副作用（DB 書き込み等）を実行
- `@link InputClassName(alpsId) 説明` で Potential を記述（BEAR が `#[Link]` に変換）

### 8. AppModule の更新

```php
final class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new FakeQueryModule(__DIR__ . '/../../var/fake'));
        $this->bind(UlidGeneratorInterface::class)->to(UlidGenerator::class);
    }
}
```

Phase 1 では `FakeQueryModule`、Phase 2 で `MediaQueryModule` に切り替える。

### 9. テストの作成

```php
class ProductCreatedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(new AppModule());
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testCreate(): void
    {
        $final = ($this->becoming)(new CreateProductInput('サンプル商品', 1000));
        $this->assertInstanceOf(ProductCreated::class, $final);
        $this->assertSame('サンプル商品', $final->productName);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new CreateProductInput('', 1000));
    }
}
```

**存在の生成 = テスト完了**。DB を読み戻して確認する CRUD 的テストは書かない。

### 10. テスト実行

作成したテストを必ず実行する:

```bash
cd <migration-project> && composer test
```

全パスを確認してからステップ完了とする。失敗したら修正してから完了にする。

## 出力フォーマット

最後に、このステップで作成・変更したファイルの一覧を Markdown で出力する:

```markdown
## 作成ファイル

- src/Input/CreateProductInput.php
- src/Final/ProductCreated.php
- src/Semantic/ProductName.php
- src/Semantic/Price02.php
- src/Exception/EmptyProductNameException.php
- src/Reason/Entity/ProductEntity.php
- src/Reason/Media/Command/InsertProductInterface.php
- var/fake/get_product.json
- tests/ProductCreatedTest.php

## テスト結果
composer test: OK (N tests, N assertions)
```

この一覧は次の `domain-review` ステップへ渡される。
