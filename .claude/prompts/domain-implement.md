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
- `~/git/be-patterns/demos/` — 8 種のパターン実装（hello-world, contact-form, user-registration, order-processing, blog-publishing, medical-triage, loan-application, insurance-claim）
- `~/git/be-skills/be/SKILL.md` — Be 実装ルール

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

````markdown
```json handover
{ ... }
```
````

このブロックから以下を抽出して実装に使う:

- `descriptor_id` / `alps_id_resolved` — 実装対象の確定
- `descriptor_type` — `container` または `bear.skip: true` ならドメイン層の実装も最小化（純粋 Semantic クラスのみ作成、Input/Final は作らない）
- `be_pattern` / `be_reference_demo` — 「1. alps-analyze の結果を再確認」で使う
- `be_classes.input` / `be_classes.final` — クラス名の確定
- `semantic_classes[]` — `src/Semantic/*.php` の生成材料。**`input_kind: "client"` のフィールドだけが Semantic クラスを必要とする**。`input_kind: "server"` のフィールドは Semantic を作らず Reason の Fake fixture (`server_fetched_fields[]`) として表現する。`static_constraints` は `#[Validate]` に、`dynamic_constraints` は Final コンストラクタへ
- `server_fetched_fields[]` — Reason が取得するフィールド。これらは `var/fake/<noun>.json` の典型値分布として記述。**Semantic クラスを作らない**
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

`alps-analyze` で抽出した各プロパティのうち **`input_kind: "client"` のものだけ** について、`src/Semantic/*.php` を作成する:

- クラス名は UpperCamelCase（例: `ProductName`, `ProductCode`, `StockQuantity`）
- コンストラクタ引数名は lowerCamelCase（例: `$productName`）— ALPS の descriptor id と一致させる
- `#[Validate]` メソッドで制約を宣言
- nullable なら引数を `string|null` にして null 時早期 return
- 制約値の根拠は **`var/fake/<descriptor-id>/client-input.json` の観察値**から。最大長や正規表現は 50 件の最大値・パターンに基づく。`semantic-ex` の原則に従い、根拠を `$comment` または phpdoc に残す

**`input_kind: "server"` のフィールドは Semantic を作らない**。それらは Reason 層の Fake fixture で表現される (`server_fetched_fields[]` 参照)。

**既存 Semantic の再利用**: 過去の Pilot や別 descriptor で既に作成済みのクラス（`src/Semantic/` 配下にあるもの）は再作成しない。1 変数名 1 クラスのルールに従い、同名であれば再利用する。観察値で制約を緩和する必要があれば既存クラスの制約を更新する（コミット履歴で意図が読めるように）。

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

**Pilot 1 (goProduct) で確立した不変条件をすべて満たすこと**:

```php
final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new PackageModule());

        // PackageModule does not bind @AppName by itself; BEAR\Package\Module factory
        // normally overrides it. Install explicitly so tests can use
        // `new Injector(new AppModule(...))` without the factory.
        $this->override(new AppMetaModule($this->appMeta));

        // Be Framework: BecomingInterface, SemanticLogger, semantic validator, Been provider.
        $this->install(new BeModule('<Vendor>\\<Package>\\Semantic'));

        // Always-on semantic logging: wrap Be's Becoming with DevBecoming.
        $this->bind(Becoming::class);
        $this->bind(BecomingInterface::class)->to(DevBecoming::class);
        $this->bind(SemanticLoggerInterface::class)
            ->toProvider(DevSemanticLoggerProvider::class)
            ->in(Scope::SINGLETON);

        // Reason: bind Query/Command interfaces to Fake implementations (Phase 1).
        $this->bind(ProductQueryInterface::class)->to(FakeProductQuery::class)->in(Scope::SINGLETON);
        // (Pilot 2 の追加分はここに append する。既存 bind を削らない)
    }
}
```

**チェックリスト**:

- [ ] `extends AbstractAppModule` (AbstractModule ではなく `$this->appMeta` が必要)
- [ ] `PackageModule()` install の **後** に `override(new AppMetaModule($this->appMeta))` — これがないと `new Injector(AppModule(...))` 直叩きで `'\Resource\Page\<X>-'` の Unbound エラー
- [ ] `BecomingInterface` → `DevBecoming` bind (本番でも意味ログ取得)
- [ ] `SemanticLoggerInterface` → `DevSemanticLoggerProvider` (Singleton)
- [ ] `BeModule` の引数 `<Vendor>\\<Package>\\Semantic` に新規 Semantic の名前空間が含まれているか (新規 Semantic が同名前空間に置かれていれば既存 `BeModule` 行はそのまま再利用できる)
- [ ] 新規 Reason の `QueryInterface` → `FakeQuery` bind が追加されているか
- [ ] **Append-only**: 既存 Pilot の bind 行は削らない。新しい descriptor の bind 群をファイル末尾に追記する形で増やす

**Phase 6 統合 smoke**: PHPUnit を書き始める前に `bin/smoke_<descriptor>.php` を作って `new Injector(new AppModule(new Meta(...)), 'var/tmp/test')` → `$injector->getInstance(BecomingInterface::class)` → `($becoming)(new XxxInput(...))` の 3 行を走らせる。これが通れば DI 配線・AppMeta override・BeModule namespace は OK で、あとは PHPUnit でロジック検証に専念できる。失敗するなら `'\Resource\…\-'` のような Unbound エラー (AppMeta 漏れ) か `class not found` (autoload 未生成) のどちらかが大半。

### 9. テストの作成

**setUp の正しい雛形** (Pilot 1 で確立):

```php
protected function setUp(): void
{
    $injector = new Injector(
        new AppModule(new Meta('<Vendor>\\<Package>', 'test')),
        dirname(__DIR__, 2) . '/var/tmp/test',
    );
    $this->becoming = $injector->getInstance(BecomingInterface::class);
}
```

- `new AppModule(new Meta(...))` — `AbstractAppModule` は `Meta` 引数を取る
- 第 2 引数の cache dir (`var/tmp/test`) が無いと Ray.Di が cache 書き込みでエラー

**テスト例**:

```php
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

public function testI18nException(): void
{
    try {
        ($this->becoming)(new CreateProductInput('', 1000));
    } catch (SemanticVariableException $e) {
        $messages = $e->getErrors()->getMessages('ja');
        $this->assertSame('商品名は空にできません。', $messages[0]);
    }
}

public function testSemanticLogIsWritten(): void
{
    ($this->becoming)(new CreateProductInput('サンプル商品', 1000));
    $logFile = dirname(__DIR__, 2) . '/var/log/ec-cube.json';
    $this->assertTrue(file_exists($logFile), 'DevBecoming should write a semantic log');
}
```

**Diamond Cascade パターン専用テスト** (Pilot 2 doAddCartItem 流):

```php
public function testStockShortageAutoAdjusts(): void
{
    // stock=3 で qty=5 をリクエスト → adjustedQuantity=3 になる
    $final = ($this->becoming)(new AddCartItemInput('sample-003', 5));
    $this->assertSame(3, $final->adjustedQuantity);
}

public function testSameSkuAddedTwiceMergesQuantity(): void
{
    // 同じ productCode を 2 回追加 → totalPrice が加算される
    ($this->becoming)(new AddCartItemInput('test-cart-merge-001', 2));
    $final = ($this->becoming)(new AddCartItemInput('test-cart-merge-001', 3));
    $this->assertSame(5000, $final->totalPrice); // 1000 × (2+3)
}

public function testDifferentSaleTypeIsolatesCart(): void
{
    // 異なる saleTypeId は別 cartKey に分離
    $normal = ($this->becoming)(new AddCartItemInput('sample-001', 1));
    $preorder = ($this->becoming)(new AddCartItemInput('preorder-2026-spring-bag', 1));
    $this->assertNotSame($normal->cartKey, $preorder->cartKey);
}
```

**Cascade 段数 ≠ `#[Inject]` 数** (Pilot 2 で確認した不変条件):

5 段の cascade phase (Stock → SaleLimit → SaleType → Delivery → Merge) でも、データ源が共有されるなら `#[Inject]` は 3 つで足りる (例: `ProductClassQueryInterface` が Stock/SaleLimit/Delivery/SaleType/Price の 5 phase 全部に効く)。  
**"5 Reason" は cascade phase の数えであって、Inject ディレクティブの数ではない**。Final のコンストラクタは「データソースの数」、Final 内部のロジックは「phase の数」で別カウント。

**共有状態の Reason** (Query + Command が同じ store を見る場合):

```php
// Reason/Query/FakeCartStorage.php — 単一の in-memory ストア
final class FakeCartStorage { /* get/put */ }

// Reason/Query/FakeCartQuery.php / FakeCartCommand.php — それぞれが Storage を inject
// AppModule で必ず Storage を Singleton で bind:
$this->bind(FakeCartStorage::class)->in(Scope::SINGLETON);
$this->bind(CartQueryInterface::class)->to(FakeCartQuery::class);
$this->bind(CartCommandInterface::class)->to(FakeCartCommand::class);
```

Singleton スコープを忘れると Command が書いたものを Query が読めない (Ray.Di がそれぞれ新インスタンスを作るため)。

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
