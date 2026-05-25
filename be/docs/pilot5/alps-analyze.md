# ALPS Analyze: doCheckout

## 概要

`doCheckout` は EC-CUBE 注文フロー最終ステージ。Pilot 3 (`doConfirmOrder`) が
verify() まで進めた pre-order を、(1) 在庫引当 (2) 決済確定 (3) 注文確定 +
メール送信 + カートクリア の 3 段階副作用で実注文化する unsafe transition。
ShoppingConfirm 画面の「注文する」ボタンに対応し、成功時 ShoppingComplete、
失敗時 ShoppingError へ遷移する。

ALPS doc:
> PurchaseFlow(shoppingフロー)による税計算・送料計算・在庫引当・ポイント減算を実行。
> PaymentMethod::checkout()で決済確定後、注文確認メールを送信しカートをクリア。
> 処理中はorderStatus=PROCESSING(8)->PENDING(7)->NEW(1)と遷移。

## セマンティックプロパティ

### client-input

| id | 型 | nullable | 情報源タグ |
|---|---|---|---|
| paymentMethod | int | false | src-entity |

`paymentMethod` は ShoppingConfirm で表示されるが、`doCheckout` 自体の入力としては
Pilot 3 で確立済みの pre-order と紐付いたものを再確認するのみ。実用上 Resource は
`preOrderId` をフォーム hidden に持って遷移する想定 (Pilot 3 と同じ)。

### server-fetched (Reason 経由で取得)

| id | from | 用途 |
|---|---|---|
| preOrder 全フィールド | PreOrderQuery (Pilot 3 fixture 拡張) | 注文確定の元データ |
| subtotal / deliveryFeeTotal / charge / discount / tax / total / paymentTotal | PurchaseFlowApplied (Pilot 3) | 金額計算済みの値 |
| stock 残高 (per item) | InventoryQuery | 在庫引当時の最終チェック |
| orderNo | OrderNumberGenerator | 注文番号発番 |
| addPoint / usePoint | ポイント計算済み | Pilot 3 totals に含む |
| completeMessage | 決済プラグインからの追記 (Pilot 5 では空文字) | ShoppingComplete 表示用 |

### server-derived (Being / Final 内で計算)

| id | 計算式 | 配置 |
|---|---|---|
| orderStatus | PROCESSING(8) → PENDING(7) → NEW(1) の状態機械 | Being 内で transition、Final 公開 |
| orderDate | 注文確定の瞬間 | Final |
| paymentDate | 決済確定の瞬間 | Final |

## Be 層マッピング案

| ALPS 要素 | 分類 | Be 層 | 根拠 |
|---|---|---|---|
| doCheckout | unsafe transition | `CheckoutInput → CheckoutSettling → [CheckoutCompleted \| CheckoutFailed]` | Multi-Reason Being + Branching Final |
| paymentMethod (再確認) | client-input | `CheckoutInput` のコンストラクタ | 既存 `PaymentMethodId` を再利用 |
| preOrderId | client-input | `CheckoutInput` | Pilot 3 から引き継がれる識別子 |
| 在庫引当 | side-effect | `InventoryAllocatorInterface` (Reason) | PurchaseFlow の StockReducePostProcessor 相当 |
| 決済確定 | side-effect | `PaymentGatewayInterface` (Reason) | PaymentMethod::checkout() 相当 |
| 注文番号発番 | server-derived | `OrderNumberGeneratorInterface` (Reason) | Pilot 4 IdGenerator の踏襲 |
| 注文永続化 | side-effect | `OrderCommandInterface` (Reason) | Pilot 4 CustomerCommand の踏襲 |
| メール送信 | side-effect | `MailerInterface` (Reason) | 新規 |
| カートクリア | side-effect | `CartCommandInterface::clear()` | Pilot 2 Cart の拡張 |

### 変換チェーン (Pilot 5)

```
CheckoutInput (preOrderId, paymentMethodId)
  ↓  #[Be(CheckoutSettling::class)]
CheckoutSettling  ← Multi-Reason Being (cascading 副作用)
  - InventoryAllocator (在庫引当 → InsufficientStockException で停止)
  - PaymentGateway (決済確定 → PaymentDeclinedException で停止)
  - OrderNumberGenerator (注文番号発番)
  - PreOrderQuery (確定済み totals の読み取り)
  ↓  #[Be(CheckoutCompleted::class)]
CheckoutCompleted  ← 3 副作用の収束 Final
  - OrderCommand.persist (注文永続化)
  - Mailer.sendOrderConfirmation (注文確認メール)
  - CartCommand.clear (preOrderId からカート紐づけクリア)
```

**例外フロー (CheckoutFailed への分岐は別ピロットへ deferred)**:

Pilot 5 では Branching Final ではなく **Reason 内で DomainException を throw → Resource 層で
ShoppingError 422 にマップ** する Pilot 3 と同じ exception-based 制御で進める。Branching の
変容自体は Pilot 3 で既に検証済みのため、ここで再演しない。

| 例外 | 由来 | HTTP コード |
|---|---|---|
| `InsufficientStockException` | InventoryAllocator | 422 |
| `PaymentDeclinedException` | PaymentGateway | 422 |
| `PreOrderNotFoundException` | PreOrderQuery | 404 |

## BEAR 層マッピング案

| 項目 | 決定 | 根拠 |
|---|---|---|
| URI schema | `page://` | ブラウザ入口 (`/shopping/checkout`) |
| HTTP メソッド | onPost | unsafe transition |
| ベース URI | `page://self/shopping/checkout` | EC-CUBE の `/shopping/checkout` パス |
| 呼び出す Be Input | `CheckoutInput::class` | `domain` ステップで作成 |
| Link 候補 | `goTop` → `page://self/`、`goCart` → `page://self/cart` | ShoppingComplete の遷移 |

## Reason 候補

Phase 1 (FakeQuery) 方針で実装する:

- **`InventoryAllocatorInterface`** (新規) — `allocate(preOrderId)`。fixture: pre-order item ごとに固定 stock を返す `FakeInventoryAllocator`。3 件目以降は `InsufficientStockException` を throw するシナリオを混ぜる
- **`PaymentGatewayInterface`** (新規) — `checkout(preOrderId, paymentMethodId, amount)`。fixture: `paymentMethodId === 99` を「決済失敗」シナリオ、それ以外を成功
- **`OrderNumberGeneratorInterface`** (新規) — `generate()`。bcc Pilot 4 の CustomerIdGenerator と同様の 32-hex 形式
- **`OrderCommandInterface`** (新規) — `persist(OrderEntity)`。fixture: `FakeOrderStorage` (Pilot 4 の FakeCustomerStorage パターン)
- **`MailerInterface`** (新規) — `sendOrderConfirmation(OrderEntity)`。fixture: `FakeMailer` が送信回数を記録
- **`CartCommandInterface::clear`** (拡張) — Pilot 2 の既存 Command にメソッド追加。fixture: `FakeCartStorage` に `clear(preOrderId)` を追加
- **`PreOrderQueryInterface`** (Pilot 3 既存) — Pilot 3 の fixture をそのまま使う

## 変換パターン判定

**Multi-Reason Cascade** (`blog-publishing` + `loan-application` の混合)

- 複数の独立 Reason を 1 つの Being にぶら下げる構造 → Multi-Reason Being (`blog-publishing`)
- Reason 間に順序依存あり (在庫引当が成功してから決済) → Cascade (`loan-application`)
- 失敗時の分岐は Branching ではなく例外 → Pilot 3 と同方針

`be_pattern` = `Diamond-Cascade` を採用。`be_reference_demo` = `loan-application`。

新規性: Pilot 5 は **Final で複数の副作用 Reason を持つ** 初回ピロット。Pilot 4 の
CustomerRegistered が `CustomerCommand` 1 つしか持たなかったのに対し、CheckoutCompleted は
`OrderCommand + Mailer + CartCommand` の 3 副作用を 1 つの constructor で収束させる。これが
"Complex Convergence" の本質。

## 次ステップへの引き渡し事項

- `CheckoutInput` は Pilot 3 既存の `preOrderId` と `paymentMethodId` を受け取る。新規 Semantic は不要 (Pilot 3 と共有)
- `CheckoutSettling` は **Multi-Reason Being** として 4 つの `#[Inject]` Reason を持つ。実行順序は (1) PreOrderQuery → (2) InventoryAllocator → (3) PaymentGateway → (4) OrderNumberGenerator
- `CheckoutCompleted` は **3 副作用収束 Final**。constructor 内で 3 つの Reason を順に呼ぶ
- Fake fixture: `var/fake/orders.json` (空配列で start)、`var/fake/inventory.json` (3 アイテム分の stock 値)
- 例外: `InsufficientStockException`, `PaymentDeclinedException`, `PreOrderNotFoundException` を新規作成
- Pilot 3 の `PreOrderQueryInterface` fixture (`preorders.json`) を拡張して checkout 用 pre-order を 2 件追加 (成功用・在庫不足用)

```json handover
{
  "descriptor_id": "doCheckout",
  "alps_id_resolved": "doCheckout",
  "alps_found": true,
  "descriptor_type": "unsafe",
  "be_pattern": "Diamond-Cascade",
  "be_reference_demo": "loan-application",
  "be_classes": {
    "input": "CheckoutInput",
    "being": "CheckoutSettling",
    "final": ["CheckoutCompleted"]
  },
  "semantic_classes": [],
  "server_fetched_fields": [
    {
      "name": "preOrder",
      "from": "PreOrderQueryInterface (Pilot 3)",
      "purpose": "確定済み pre-order の totals/items 取得",
      "fake_fixture_path": "be/var/fake/preorders.json"
    },
    {
      "name": "stock",
      "from": "InventoryAllocatorInterface",
      "purpose": "在庫引当時の数量確保",
      "fake_fixture_path": "be/var/fake/inventory.json"
    },
    {
      "name": "orderNo",
      "from": "OrderNumberGeneratorInterface",
      "purpose": "注文番号発番",
      "fake_fixture_path": null
    }
  ],
  "reasons": [
    {"type": "DB-Query", "interface_name": "PreOrderQueryInterface", "phase": "Pilot 3 既存", "fake_fixture": "be/var/fake/preorders.json"},
    {"type": "Inventory", "interface_name": "InventoryAllocatorInterface", "phase": "Phase 1 (FakeQuery)", "fake_fixture": "be/var/fake/inventory.json"},
    {"type": "Payment", "interface_name": "PaymentGatewayInterface", "phase": "Phase 1 (FakeQuery)", "fake_fixture": null},
    {"type": "Other", "interface_name": "OrderNumberGeneratorInterface", "phase": "Phase 1 (FakeQuery)", "fake_fixture": null},
    {"type": "DB-Command", "interface_name": "OrderCommandInterface", "phase": "Phase 1 (FakeQuery)", "fake_fixture": "be/var/fake/orders.json"},
    {"type": "Mailer", "interface_name": "MailerInterface", "phase": "Phase 1 (FakeQuery)", "fake_fixture": null},
    {"type": "DB-Command", "interface_name": "CartCommandInterface", "phase": "Pilot 2 既存 + clear() 追加", "fake_fixture": "be/var/fake/carts.json"}
  ],
  "bear": {
    "skip": false,
    "uri_scheme": "page",
    "http_method": "onPost",
    "base_uri": "/shopping/checkout",
    "links": [
      {"rel": "goTop", "href": "page://self/"},
      {"rel": "goCart", "href": "page://self/cart"}
    ]
  },
  "notes": [
    "Pilot 5 では Branching Final を意図的に避け、Reason 内 DomainException + Resource 層 422 マッピングで失敗を表現する。Branching 自体は Pilot 3 で検証済みのため重複を避ける。",
    "CheckoutFailed Final + 補償処理 (Refund/InventoryRelease) は Phase B 以降のセキュリティピロットへ deferred。今回は happy-path + 失敗時 422 で打ち切る。",
    "PaymentGateway は外部依存だが Pilot 5 では FakeGateway を使う。実 PG 連携は Phase B の決済セキュリティピロットへ deferred。",
    "Pilot 3 既存の preorders.json fixture と FakePreOrderStorage は Pilot 5 で拡張する。preOrderId='completed-pilot5'/'oos-pilot5' の 2 件追加。"
  ]
}
```
