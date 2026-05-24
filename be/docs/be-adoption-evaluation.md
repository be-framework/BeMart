# Be 採用評価 — BEAR.Sunday + Service Object でなく Be である理由 (中間)

| メタ | 値 |
|---|---|
| 評価対象 | BeMart Pilot 1 (`goProduct`) + Pilot 2 (`doAddCartItem`) + Pilot 3 (`doConfirmOrder`) |
| 評価時点 | 2026-05-18 |
| 評価種別 | 中間評価 (Branching 検証済み / Cascade Diamond は構造的に不成立と判明) |
| 想定読者 | BEAR.Sunday + Service Object で書いている人。「なぜ Be を追加で入れるのか？」を疑問に思う立場 |
| 関連 | [HANDOVER.md](../../docs/HANDOVER.md) (工程ログ・指標達成記録) |

---

## TL;DR

Be の効力は **2 層構造**:

- **基層 (Pilot 1 から効く)** — Semantic 型保証によるテスト省略 / 意味ログ自動カバレッジ / i18n 例外標準化 / 自己証明 assert。**単純取得 transition でも採用に値する**。
- **上層 (Pilot 2 で爆発)** — Cascade で「状態 = 型」 / `#[Input]` の by-name 連結 / Reason 層 IO 局所化 (mock 0 件) / 足し算 refactor / フレームワーク由来の anti-pattern 語彙。**状態変容 transition で本領発揮**。

**中間結論**: 全 transition で Be を採用に値する。ただし上層効力は状態変容を伴うものでのみ発生し、単純取得では基層効力のみ。Pilot 3 で **Branching** (`#[Be([A, B])]` + 型付き discriminator) を検証済み、**Cascade Diamond** は be-framework の現行メカニクス上 apex が `#[Input]` を必要とする場合は不成立と判明 (Linear Cascade に縮退する。詳細 §6)。

---

## 1. データ点

| 項目 | Pilot 1 (`goProduct`) | Pilot 2 (`doAddCartItem`) | Pilot 3 (`doConfirmOrder`) |
|---|---|---|---|
| Transition 種別 | safe (取得) | unsafe (状態変容 + 永続化) | unsafe (PaymentMethod::verify + Branching) |
| Be chain 段数 | 1 段 (Input → Final) | 3 段 (Input → Being → Being → Final) | 5 段 (Input → 4 Beings → Branching Final) |
| 関与 Reason 数 | 1 (`ProductQuery`) | 3 (`CartQuery` + `CartCommand` + `ProductClassQuery`) | 3 (`OrderQuery` + `PurchaseFlow` + `PaymentMethodFactory`) |
| Semantic クラス数 | 4 (`ProductCode` / `ProductName` / `Price02` / `Stock`) | +1 (`Quantity`) を含めて 5 | +14 (`PreOrderId`, `PaymentMethodId`, `Subtotal`, `Tax`, `Charge`, `Discount`, `Total`, `PaymentTotal`, `AddPoint`, `UsePoint`, `Order`, `Totals`, `Result`, `Being`) |
| 型保証で省略できた単体テスト | 4 件 | (新規分のみ追加観察) | 2 件 (`PreOrderId` / `PaymentMethodId` 形式) |
| 意味ログ自動カバレッジ | **100%** | **100%** | **100%** |
| i18n 例外メッセージ被覆 | 6/6 = **100%** | 全 DomainException で **100%** | 11/11 = **100%** (10 FormatException + `PreOrderNotFoundException`) |
| 自己証明 assert | 1 件 (`$final instanceof ProductFetched`) | 2 件 (Final 内数量範囲 + Resource 層 `instanceof CartItemAdded`) | テストで `instanceof OrderConfirmed` / `instanceof OrderConfirmFailed` の Branching 判定 |
| テスト | 8 pass / 20 assertions | 14 pass / 51 assertions、**mock 0 件** | 6 pass / 28 assertions (Pilot 3 単体)、**mock 0 件**、累計 27/27 |
| Refactor 履歴 | なし | Linear (1 段) → Cascade (3 段) | Diamond 設計試行 → Be framework メカニクス上不成立判明 → Linear Cascade (4 段) に変更 (§6 詳細) |

Pilot 2 の Cascade chain:

```text
AddCartItemInput
  → QuantityAdjusted (Being)   — ProductClass lookup / Stock cap / SaleLimit cap / cartKey 確定
  → CartMerged (Being)         — 既存 cart 取得 / item merge / totalPrice / deliveryFeeTotal
  → CartItemAdded (Final)      — 永続化のみ
```

---

## 2. もし BEAR.Sunday + Service Object で同じ transition を書いたら

### 現在の Be 版 (Pilot 2 要約)

```php
// Stage 1
#[Be([CartMerged::class])]
final readonly class QuantityAdjusted {
    public int $adjustedQuantity;
    public string $cartKey;
    // ...

    public function __construct(
        #[Input] string $productCode,
        #[Input] int $quantity,
        #[Input] string $sessionPrefix,
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
        // ProductClass lookup → Stock cap → SaleLimit cap → cartKey 確定
    }
}

// Stage 2
#[Be([CartItemAdded::class])]
final readonly class CartMerged {
    public CartEntity $mergedCart;
    // ...

    public function __construct(
        #[Input] string $productCode,
        #[Input] int $adjustedQuantity,
        #[Input] string $cartKey,
        // ...
        #[Inject] CartQueryInterface $cartQuery,
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
        // 既存 cart 取得 → item merge → totalPrice / deliveryFeeTotal
    }
}

// Final
final readonly class CartItemAdded {
    public int $totalPrice;
    public int $deliveryFeeTotal;
    // ...

    public function __construct(
        #[Input] CartEntity $mergedCart,
        // ...
        #[Inject] CartCommandInterface $cartCommand,
    ) {
        $this->totalPrice = $mergedCart->totalPrice;
        $this->deliveryFeeTotal = $mergedCart->deliveryFeeTotal;
        $cartCommand->save($mergedCart);
        assert($this->adjustedQuantity >= 1 && $this->adjustedQuantity <= $this->requestedQuantity);
    }
}
```

### Service Object 版 (擬似コード)

```php
final class CartResource extends ResourceObject {
    public function __construct(
        private readonly ProductClassService $productClassService,
        private readonly QuantityAdjuster $quantityAdjuster,
        private readonly CartMerger $cartMerger,
        private readonly CartRepository $cartRepository,
    ) {}

    public function onPost(string $productCode, int $quantity, string $sessionPrefix): static {
        // Step 1: ProductClass lookup + Stock/SaleLimit cap
        $productClass = $this->productClassService->findOrFail($productCode);
        $adjusted = $this->quantityAdjuster->adjust($quantity, $productClass);
        $cartKey = sprintf('%s_%d', $sessionPrefix, $productClass->saleTypeId);

        // Step 2: cart 取得 + merge
        $existingCart = $this->cartRepository->byCartKey($cartKey)
            ?? CartFactory::empty($cartKey, $productClass);
        $merged = $this->cartMerger->merge($existingCart, $productCode, $adjusted, $productClass);

        // Step 3: 保存
        $this->cartRepository->save($merged);

        $this->body = [
            'cartKey' => $cartKey,
            'adjustedQuantity' => $adjusted,
            'totalPrice' => $merged->totalPrice,
            // ...
        ];
        return $this;
    }
}
```

### 何が違うか

| 観点 | Service Object 版 | Be 版 |
|---|---|---|
| 中間状態の表現 | 変数 (`$adjusted`, `$merged`) — 型はプリミティブ or Entity | **型** (`QuantityAdjusted` / `CartMerged`) — 状態そのものが型 |
| 「数量確定完了」の証明 | 不可 (`$adjusted` は int) | `$x instanceof QuantityAdjusted` で型で証明 |
| 「永続化完了」の証明 | コメント / ログ | `$final instanceof CartItemAdded` で型で証明 |
| Stage 間の引数渡し | 自由 (Service の都合) | `#[Input]` の by-name 連結 — 下流は上流の public プロパティ名にしか繋げない |
| 段階境界の修正 | Controller の引数並びを書き換える | 新しい Being を 1 ファイル足すだけ |
| テストでの DB 分離 | Service 単位で mock / partial mock | Reason interface に Fake を `#[Inject]`。**mock 0 件で 14 pass** |
| 抽象の名前 | `CartMerger` (動詞 / 役割) | `CartMerged` (状態) |
| ALPS との対応 | 1 Resource ↔ N Service — 多対多 | ALPS 表現 ↔ Be 型 — 1:1 写像 |

---

## 3. 上層の効力 — 状態変容 Pilot (Pilot 2) で爆発した 4 局面

これらは **Be chain が複数段ある場合に発生する** 性質。Pilot 1 (1 段) では効力が限定的だが、Pilot 2 (3 段) で爆発した。

### (a) 「Final が厚い」という違和感が、Service 増設ではなく Being 発見に向かった

Cascade refactor の発端は「`CartItemAdded` の中身に永続化以外のロジックが詰まっている」という違和感。

**Service Object パターンでの自然な解** は `CartMergerService` を分離し Controller から呼ぶ。だがこの解は **「Cart が merge されている状態」を型として作らない**。Controller の中で `$merged = $this->cartMerger->merge(...)` という名前の変数になるだけ。

**Be での解** は `CartMerged` という新しい Being の発見だった。型階層自体が「数量確定 → カート合成 → 永続化完了」というドメイン状態遷移と一致する。「永続化完了の証拠」を `assert($final instanceof CartItemAdded)` で記述できる。

「Final の厚さは Being の不在の証拠」という診断軸は Service Object パターンには存在しない。Service Object では「Service が太い」と「ある状態への到達が型として表現されていない」が区別できないため、状態を発見する圧力が生まれにくい。

### (b) `#[Input]` の by-name 連結が層境界を物理的に強制する

Service Object の `merge($cart, $productCode, $adjusted, $productClass)` は、型さえ合えば呼び手が何を渡しても通る。「`$adjusted` は実は調整前の `$quantity` だった」というバグが容易に混入する。

Be では `CartMerged` の `#[Input] int $adjustedQuantity` は、**上流 `QuantityAdjusted` の `public int $adjustedQuantity` という名前のプロパティにしか繋がらない**。境界を緩める誘惑が技術的に塞がれている。今回の refactor でも、`CartMerged` の `#[Input]` 一覧を埋める作業がそのまま「`QuantityAdjusted` が公開すべき property の決定」になった。

### (c) Reason 層の IO 局所化 — mock 0 件で 14 pass

`CartQueryInterface` / `CartCommandInterface` / `ProductClassQueryInterface` が `#[Inject]` で入る。テストでは `FakeCartQuery` / `FakeCartCommand` / `FakeProductClassQuery` を bind するだけ。

Service Object 版で同じことをやろうとすると、`CartRepository` / `ProductClassService` / `CartMerger` / `QuantityAdjuster` の 4 つを mock するか partial mock を組むか、あるいは全部を「実 DB を初期化して使う」結合テストにするかの選択になりがち。**「mock を使わずに済む」と「Reason 層が物理的に IO 接点として独立している」は別の話で、Be は後者を構造的に保証する**。

### (d) リファクタリングが「足し算」で進んだ

Linear → Cascade の refactor で触ったもの:

- `CartMerged.php` 新規作成 (1 ファイル追加)
- `QuantityAdjusted.php` の `#[Be]` 1 行変更 (`CartItemAdded` → `CartMerged`)
- `CartItemAdded.php` の引数を簡略化 (個別フィールドの再計算を `mergedCart: CartEntity` 1 つの受け取りに置換)

**触らなかったもの**: Reason 層 (Query/Command interface とその Fake)、Resource 層、テストコード本体 (アサーション値は変更なし)。

Service Object 版で同じ「途中の状態を取り出す」refactor をやると、通常は Service の分割 + 呼び出し側の修正 + 関連テストの書き直しが必要になる。Be では `#[Input]` 連結が by-name で **新しい段を挿入する点しか変更を要求しない**。

---

## 4. 基層の効力 — Pilot 1 から効いている 4 局面 (単純取得でも採用に値する理由)

これらは **chain の段数に依らず Be 採用そのものから発生する** 性質。Pilot 1 (1 段) で既に 100% カバレッジを達成しており、Service Object パターンで同等の保証を得るには別途の設計と運用負担がかかる。

### (a) Semantic 変数による型保証 → テスト省略

Pilot 1 で 4 件の Semantic クラス (`ProductCode`, `ProductName`, `Price02`, `Stock`) を登録し、**各クラスにつき 1 件の単体テストを省略できた** (4/5 達成)。

`ProductCode` を例にすると:

```php
final class ProductCode {
    #[Validate]
    public function validate(string $productCode): void {
        if (trim($productCode) === '' || mb_strlen($productCode) > 50) {
            throw new ProductCodeFormatException();
        }
        if (! preg_match('/^[A-Za-z0-9._-]+$/', $productCode)) {
            throw new ProductCodeFormatException();
        }
    }
}
```

Input の `#[Input] string $productCode` は Be ランタイムが `ProductCode::validate()` を自動適用する。**「productCode が不正だった場合」を Final 内で再検査する単体テストは不要** — 不正なら Input 構築時に例外が飛んで Final に到達しないことが型で保証される。

Service Object 版で同等の保証を作るには、(1) 値オブジェクトクラスを設計、(2) Controller で値オブジェクトに変換、(3) 不正系のテストを書く、の手作業が必要。**Be では「by-name で Semantic クラスが自動適用される」ので、Validate を書いた瞬間にすべての Input に効く**。

### (b) 意味ログ (`DevBecoming`) — chain 全体の自動 JSON 記録 = 透明性 / 追跡性

`BecomingInterface → DevBecoming` を本番でも常時 bind することで、**Becoming chain 全体 (Input prop / Final inject / close prop) が `var/log/bemart.json` に自動 JSON 記録される**。Pilot 1 / Pilot 2 とも 100% カバレッジ。

これは Service Object パターンには無い性質:

| 観点 | Service Object | Be (`DevBecoming`) |
|---|---|---|
| chain 全体の記録 | 自前で AOP / 各 Service にロギング差し込み | **自動。コード追加 0 行** |
| 記録内容 | ロガー設計次第 | Input prop / Final inject / close prop が機械可読 JSON |
| デバッグ時 | `var_dump` / breakpoint | **意味ログを読むだけで chain 復元可能** |
| 観察可能性のオプトイン | 必要な箇所で個別に手当て | **オプトインなし。常時 ON** |

`var_dump` を使わず構造ログから後追いするデバッグ運用を採る現場では、**Be 採用自体がデバッグ運用の前提条件** になる。Pilot 1 段階で既にこの効力が出ている。

### (c) i18n 例外メッセージの `#[Message]` 必須化

Be Framework は DomainException 継承 + `#[Message(['en'=>..., 'ja'=>...])]` を必須化する。Pilot 1 で 6/6 = 100%、Pilot 2 でも全 DomainException が達成。

```php
#[Message([
    'en' => 'Invalid quantity. Must be an integer between 1 and 999.',
    'ja' => '数量が不正です。1〜999 の整数で指定してください。',
])]
final class QuantityFormatException extends DomainException {}
```

Service Object 版で同等の標準化を作るには:

- 例外クラスごとに翻訳キーを管理する仕組み
- gettext / Symfony Translator / Laravel Lang など別途導入
- 翻訳ファイル (`.po` / `.yml` / `.json`) と例外クラスを別管理 (同期失敗のリスク)

**Be では「例外クラスに `#[Message]` が並ぶ」ことが ja/en メッセージの単一情報源**。同期失敗が構造的に起きない。

### (d) 自己証明 assert — Resource 層から Be の閉鎖原則を裏取り

Pilot 1 で `Resource/Page/Product.php` に:

```php
$final = $this->becoming(new GetProductInput($productCode));
assert($final instanceof ProductFetched);
```

Pilot 2 でも Final 内に `assert($adjustedQuantity >= 1 && $adjustedQuantity <= $requestedQuantity)` + Resource 層に `assert($final instanceof CartItemAdded)`。

これらは Service Object パターンでは **そもそも書けない**。なぜなら:

- Service の戻り値は Entity / array で、「最終状態」を表現する型がない
- 「Service が成功した = 何が証明されたか」が型に出ない
- 後続コードが Service の事後条件を信頼するには、コメント / ドキュメント / 単体テストに頼るしかない

**Be では Final 型の到達自体が事後条件の証拠**。Resource 層が `instanceof Final` を assert すれば、開発時にドメイン契約違反を catch できる。

### Pilot 1 でも Be が効いている要約

「単純取得でも Be を採用に値する」のは、上記 4 つが **chain の段数に依らず Be 採用そのものから発生する** から。Cascade による状態 = 型のような華やかな効力は出ないが、**開発速度 (Semantic) / 観察可能性 (意味ログ) / 多言語 (i18n) / ドメイン整合性 (自己証明)** の 4 面で恒常的に効く。

---

## 5. 採用判定 (中間)

| Transition 種別 | Be 採用 | 効く層 |
|---|---|---|
| safe / 単純取得 | **◎ 採用** | 基層のみ (Semantic / 意味ログ / i18n / 自己証明) |
| unsafe / idempotent で状態変容あり | **◎ 採用** | 基層 + 上層 (Cascade / by-name 連結 / Reason 局所化 / 足し算 refactor) |
| Branching (分岐先で別 Final) | **◎ 採用** (Pilot 3 検証済み) | 基層 + 上層 + `#[Be([A, B])]` + 型付き discriminator (`PaymentSuccessCase\|PaymentFailureCase $being`) |
| Cascade Diamond (並列 Reason 収束) | ✗ 構造的に不成立 | Linear Cascade に縮退 (§6 詳細) — apex が `#[Input]` を必要とする場合は Be framework の現行メカニクスで表現不能 |

実運用での目安: **EC-CUBE 137 transition 全てで Be 採用候補**。ただし上層効力は unsafe 35 + idempotent 44 = 79 件で爆発し、safe 58 件では基層効力のみ。「採用するか」ではなく「どのレイヤーの効力を当てにするか」が transition ごとに変わる。

---

## 6. Pilot 3 の知見 — Branching 検証済み・Cascade Diamond 構造的に不成立

### Branching は問題なく動く

`#[Be([OrderConfirmed::class, OrderConfirmFailed::class])]` + `public PaymentSuccessCase|PaymentFailureCase $being` の典型形が想定どおりに機能した。Be framework の `BecomingType::match()` が `$being` の実型を見て、各 Final の `#[Input] PaymentSuccessCase $being` / `#[Input] PaymentFailureCase $being` のいずれにマッチするかで Final を自動選択する。

Pilot 3 のテスト 6/6 で:

- 支払い成功系 (CashOnDelivery / CreditCard) → `OrderConfirmed` に分岐
- 支払い失敗系 (fake payment failure handler) → `OrderConfirmFailed` に分岐 (`errors: ['Card validation failed']` を Final が保持)
- `instanceof` での Final 型判定で分岐が型レベルで証明される

medical-triage demo の Case クラス委譲パターン (Final が `$being->totals` を読む) もそのまま流用でき、ALPS の `ShoppingConfirm` ↔ `ShoppingError` 状態分岐と 1:1 写像できた。

### Cascade Diamond は be-framework の現行メカニクス上、apex が Input 依存だと作れない

当初設計: `OrderConfirming` が `#[Inject] PreOrderResolved` / `#[Inject] PurchaseFlowApplied` / `#[Inject] PaymentVerified` で 3 つの Being を Moment として注入し、`PreOrderResolved` (apex) を 2 arm で共有する Diamond。

実際: `composer test` で 6/6 失敗。エラーは `Ray\Di\Exception\NoHint: $preOrderId` at `PreOrderResolved.php:28`。

#### 何が起きていたか

Be framework の `BecomingArguments::be(object $current, string $becoming)` は:

1. 上流 Being の `public` プロパティを `get_object_vars($current)` で取得。
2. 次の Being のコンストラクタ引数を走査:
   - `#[Input] $x` → 上流プロパティから `$x` を埋める
   - `#[Inject] $service` → `$this->injector->getInstance($service::class)` を呼ぶ

`#[Inject] PreOrderResolved $preOrder` を解決するため、Ray.Di が `Injector::getInstance(PreOrderResolved::class)` を実行する。**ここで Ray.Di は `BecomingArguments` を経由せず、純粋な DI 解決を試みる。** `PreOrderResolved::__construct(#[Input] string $preOrderId, ...)` の `$preOrderId` には DI bind が存在しないので `NoHint` で落ちる。

すなわち: **`#[Input]` を持つ Being class は `#[Inject]` できない。** `#[Input]` は Be framework の cascade 文脈でのみ意味を持つ属性で、Ray.Di にとっては未知。

#### 結論 — Diamond が成立する条件

| Apex (= 並列 arm から `#[Inject]` される側) のコンストラクタ | Diamond 成立可否 |
|---|---|
| `#[Inject]` のみ (= 純粋 DI で構築可能) | ◯ — loan-application の `Moment/CreditApproved` 等の形 |
| `#[Input]` を 1 つでも含む | ✗ — Ray.Di が Input を解決できず `NoHint` |

これは「Be framework が Cascade Diamond をサポートしていない」のではなく、**Moment を `#[Input]` 非依存の Reason 系オブジェクト (Service / Strategy 相当) として設計すれば成立する** ことを意味する。loan-application demo の Moment はすべて `#[Inject]` 専用で、Input scalar (`applicantId` 等) は Final レベルで初めて `#[Input]` として組み合わさる構造になっており、この制約と一致している。

#### EC-CUBE の `doConfirmOrder` で Diamond を作れない理由

`PreOrderResolved` は `preOrderId` (Input) で `OrderQuery` を引いて初めて成立する。`preOrderId` 非依存の "純粋 DI で作れる pre-order resolver" を作ろうとすると、結局 ScopedContext / RequestScope のような暗黙の Input 受け渡しが必要になり、Be framework の「依存は明示」哲学に反する。

したがって `doConfirmOrder` の cascade は Diamond ではなく Linear (4 段) として写像する以外にない。これは設計上の妥協ではなく、**Input 依存のデータが各段に流れる以上は本質的に Linear** という観察。

```text
ConfirmOrderInput (preOrderId, paymentMethodId)
  → PreOrderResolved   (Stage 1 — order を解決, public: preOrderId / paymentMethodId / order)
  → PurchaseFlowApplied (Stage 2 — totals を計算, public: …+ totals)
  → PaymentVerified    (Stage 3 — verify() 結果取得, public: …+ result)
  → OrderConfirming    (Stage 4 — Branching, $being = PaymentSuccessCase|PaymentFailureCase)
    #[Be([OrderConfirmed, OrderConfirmFailed])]
  → OrderConfirmed | OrderConfirmFailed
```

各段は `#[Input]` で上流 public プロパティを by-name 連結する Pilot 2 と同じ要領で繋がる。

### 採用判定への反映

Diamond は **「並列収束したい独立 Reason がすべて Input 非依存である」** という稀な条件下でしか成立しない。EC-CUBE の典型的な業務 transition では、ほぼ全段が Input (識別子) に依存するため Linear Cascade に収束する。**「Diamond で書きたい」という設計欲求が出たら、それは Linear Cascade の合図** と読み替えるのが移植上の規約として実用的。

§5 の採用表で「Cascade Diamond ◎ 期待大」を「✗ 構造的に不成立 (Linear に縮退)」に修正済み。

## 7. まだ残る留保

| 観察したい性質 | 候補 transition | 備考 |
|---|---|---|
| Be chain が長くなった時 (6 段以上) の認知負荷 | TBD | Pilot 3 で 5 段は許容範囲。10 段超で揺らぐかは未検証 |
| Semantic 変数登録の運用負担 | 全 Pilot 共通 | Pilot 3 で `Order` / `Totals` / `Result` / `Being` の 4 件を composite-type 空 validator として追加。chain 長段化で増える傾向 |
| Diamond と多段 chain 以外の高度パターン (Sequential Chain / Convex Convergence 等) | TBD | be-patterns の残り demo は移植不要な題材 |
| 意味ログサイズの本番運用 | 全 Pilot 共通 | `var/log/bemart.json` の rotation / 抽出ツール |

これらを観測してから「Be 全面採用」の最終結論を出す。**現時点では「全 transition で採用 (効力レイヤーは transition による、Diamond は Linear に縮退して扱う)」を中間結論とする**。

---

## 結び

Be は「BEAR.Sunday + Service Object」の上位互換ではない。両者は **設計判断の語彙と保証のレイヤーが違う**。

Be の効力は 2 層:

- **基層** — Semantic 型保証 / 意味ログ自動カバレッジ / i18n 例外標準化 / 自己証明 assert。**Pilot 1 単純取得から効く**。Service Object パターンで同等を作るには別途の設計と運用負担。
- **上層** — Cascade で状態 = 型 / `#[Input]` by-name 連結 / Reason 局所化で mock 0 件 / 足し算 refactor。**Pilot 2 状態変容で爆発**。Service Object には構造的に出せない。

「BEAR.Sunday + Service Object で書けば動く」のはその通り。だが Pilot 1 を Service Object 版で書けば基層 4 軸を自前で整備する負担が出て、Pilot 2 を Service Object 版で書けば上層 4 軸が構造的に出てこない。**この差を欲しいかどうか** が Be 採用の判断軸。

EC-CUBE 移植の文脈では、(a) 全 transition で基層効力が効く、(b) 状態変容を伴う transition (unsafe + idempotent) が大半を占めて上層効力も効く、(c) ALPS 表現と Be 型を 1:1 写像できる、という 3 点から、**Be 採用は全 transition で妥当** と中間判断する。Branching / Cascade Diamond の Pilot で覆る可能性を残しつつ、本評価を Pilot 3+ への入口とする。
