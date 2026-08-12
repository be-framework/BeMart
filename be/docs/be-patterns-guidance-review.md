# be-patterns 案内評価 — BeMart 移植を証拠としたパターンカタログのレビューと改善指針

| メタ | 値 |
|---|---|
| 評価対象 | [be-patterns](https://github.com/be-framework/be-patterns) のパターン案内(8 デモ + CLAUDE.md + patterns.json)が、BeMart 移植(154 Input / 155 Final / 14 Being)をどうルートしたか |
| 評価時点 | 2026-07-18 |
| 評価種別 | 外部監査。be-patterns 側の 4 軸監査([PR #22](https://github.com/be-framework/be-patterns/pull/22) として修正済み)と、BeMart 側の 3 軸精査(形状全数調査 / イディオム伝播 / 決定痕跡の再構成)の統合 |
| 想定読者 | be-patterns / be-framework のメンテナ、および BE でドメインを書く AI エージェントの案内を設計する人 |
| 関連 | [be-adoption-evaluation.md](./be-adoption-evaluation.md)(Be 採用そのものの評価。本書はその対: 採用を導いた**案内**の評価)、[HANDOVER.md](../../docs/HANDOVER.md) |

---

## TL;DR

BeMart は「案内がどう成果物に写像されるか」の一級の実証標本である。観察の要点は 3 つ:

1. **生きた手本と機械検証可能な規則は完全に伝播する。** 5 つの hard invariant は 323/323 ファイルで 100% 準拠(教師の be-patterns 自身に 2 件あった順序違反が BeMart にはゼロ)。Semantic 命名整合も 155/155 で、be-patterns の `ClaimAmount` 型バグは非再現。medical-triage(E2E テスト付きで動く唯一の Branching デモ)の typed `$being` discriminator + Case クラスは、[OrderConfirming](../src/Being/OrderConfirming.php) に docblock の機構説明まで含めて正確に再現された。テストも本物の `Becoming` E2E。**伝播率は案内の強度(機械検証可能性 × 生きた実例の有無)に比例する。**
2. **Diamond(Moment)の不採用は案内の見落としではない。** BeMart は案内どおり Diamond を探し、3 軸判定器で全遷移を形式判定し、Pilot 3 で実際に試して `Ray\Di\Exception\NoHint` で失敗し、「apex が `#[Input]` を要する Diamond は現行メカニクス上不成立」という成立条件まで自力で導出して棄却した([be-adoption-evaluation.md §6](./be-adoption-evaluation.md))。ドメイン適合性(EC-CUBE の遷移は本質的に順序依存)の判断も正しい。
3. **それでも案内の欠落は実害を残した。** パターンの**機構**(Moment / `be()` / 並列収束)は正しく棄却されたが、パターンの**本質**(補償可能な予約→一斉確定の二相コミット)まで一緒に捨てられた。[CheckoutSettled](../src/Being/CheckoutSettled.php) は在庫引当+課金+採番を一発実行の Reason 直列で行い、「カード課金済み・注文行なし」の補償不能ウィンドウを docblock で自己申告している。order-processing の Reason 契約(`lock→confirm` / `authorize→capture`)は Input 依存でも使えたのに、伝播しなかった。

**結論**: 案内の改善は「フロー形状の選択ガイド」だけでは足りない。**形状と独立に適用される横断指針(副作用の二相化・用語の構造的定義・成立条件)**をカタログに載せることが、次の移植の品質を決める。

---

## 1. 調査方法

- **be-patterns 側**: docs-vs-code 整合 / CLAUDE.md invariant 準拠 / テスト・ツーリング / コード品質の 4 軸監査。発見(フレッシュ install 破損、advanced 3 デモの未配線、CardExpiry 日付バグ等)は [PR #22](https://github.com/be-framework/be-patterns/pull/22) で修正済み。
- **BeMart 側**: (a) `#[Be]` 全 168 宣言の形状全数調査、(b) イディオム伝播の突合、(c) `docs/HANDOVER.md`・`be/var/analysis/*.json`・[be-adoption-evaluation.md](./be-adoption-evaluation.md)・`.claude/prompts/*` による決定過程の再構成。

## 2. 観察 — 形状分布

`#[Be]` グラフは純フォレスト(全ノード in-degree ≤ 1)。収束系の形は構造的にゼロ。

| 形状(カタログ名) | 件数 | 比率 | 例 |
|---|---:|---:|---|
| Minimal(Input → Final 直行) | 145 | 94.2% | `GetProductListInput → ProductListFetched` |
| Multi-Reason Being(1 Being に 2〜5 Reason) | 6 | 3.9% | `RegisterCustomerInput → CustomerRegistering → CustomerRegistered` |
| Sequential Chain(Being 2 段) | 2 | 1.3% | `AddCartItemInput → QuantityAdjusted → CartMerged → CartItemAdded` |
| Sequential + 末尾 Branching(**カタログ未収載の合成形**) | 1 | 0.6% | `ConfirmOrderInput → …4 Being… → {OrderConfirmed \| OrderConfirmFailed}` |
| Linear(1-Reason Being)/ Diamond / Cascade Diamond / Complex Convergence | 0 | 0% | — |

付随観察:

- **読み取り系(`Get* → *Fetched` + CSV/PDF export)が約 50/154 ≈ 32%**。カタログに query/projection の項が無いため、全て Minimal に「たまたま」流れた(結果は妥当だが無案内)。
- `*Fetched` のうち [ShoppingFetched](../src/Final/ShoppingFetched.php) のみ constructor で DB write(EC-CUBE の `goShopping` 互換の意図的設計)。「Fetched = 副作用なし」は 40/41 で成立する**ほぼ**不変則。
- 重量級(4+ Reason 注入)は 21/169 クラス(12.4%)。最大は ShoppingFetched の 6。

## 3. 観察 — 何が伝播し、何が伝播しなかったか

| 案内の要素 | 伝播 | 証拠 |
|---|---|---|
| 機械検証可能な invariant(`final readonly` / strict_types / `#[Be]` 配置 / Input→Inject 順序) | ◎ **323/323 = 100%** | 教師である be-patterns 自身に順序違反が 2 件あった(PR #22 で修正)のに対し、BeMart はゼロ |
| Semantic 命名整合(パラメータ名 → クラス解決) | ◎ **155/155 = 100%** | be-patterns の `ClaimAmount`-vs-`$estimatedAmount` 型バグは非再現。`UpdateTradeLawInput` は解決規則を docblock で自己文書化 |
| Branching の typed discriminator(medical-triage) | ◎ 完全 | `OrderConfirming` の `PaymentSuccessCase\|PaymentFailureCase $being`。機構説明の docblock 付き |
| 本物の `Becoming` E2E テスト | ◎ | `tests/Domain/*` は TestModule + Injector で実チェーンを駆動(be-patterns の advanced デモの手組みテストより忠実) |
| Exception 規約(`DomainException` 継承 + `#[Message]` i18n) | ◎ 100% / 99.2% | 唯一の欠落は `CalendarHolidayNotFoundException` の `#[Message]` |
| 境界 Reason の Interface 化 | ◯ | `InventoryAllocatorInterface` / `PaymentGatewayInterface` 等。`AdminSession` 等 3 件は abstract class(public プロパティを持つため — PHP 制約による合理的逸脱) |
| `@link schema.org` docblock | ✗ 4.9%(8/163) | `Email` / `PostalCode` / `PhoneNumber` すら無い。**運用の弱い案内(「when a standard term exists」)は伝播しない** — be-patterns 側でも insurance-claim が 0% だった |
| Moment / Potential 機構 | ✗(正当な棄却) | §4 |
| **Reason の二相契約(lock→confirm / authorize→capture)** | **✗(欠落・実害あり)** | `InventoryAllocatorInterface::allocate()` は一発実行。§5.1 |
| state クラスでの clock/乱数直呼び回避 | ✗(悪例が伝播) | 7 クラス(`new DateTimeImmutable()` 4 + `date`/`random_bytes`/`uniqid` 4、重複 1)。be-patterns の canonical Final 自身の `date()` 直呼びが伝播源。しかも BeMart は `Reason/Provider/` に正しい seam を 16 個持ちながら、これらの箇所でバイパスしている(内部不整合) |
| パターン用語の正確な意味 | ✗(drift) | §5.2 |

**要約: 伝播率は案内の強度に比例する。** 機械検証可能な規則(クラス宣言・属性配置)は 100%、生きた実例に体現された規則(分岐・E2E)はほぼ 100%、運用判断を要する規則(schema.org リンク・clock の Reason 化)は数 % に落ちる。

## 4. 因果の再構成 — Diamond 不採用の三層

1. **ドメイン形状(一次)**: EC-CUBE の遷移は各段の出力が次段の入力になる順序依存の連鎖で、「相互独立な関心事の並列収束」という Diamond の前提を満たさない。`be/var/analysis/doAddCartItem.json` の 3 軸判定(`independent_parallel: false`)が形式的に記録。
2. **フレームワーク制約(二次)**: `#[Inject]` 解決は Ray.Di の純 DI で行われ `BecomingArguments` を経由しないため、`#[Input]` を持つクラスは注入不能(`NoHint`)。**「Diamond は収束対象が全て Input 非依存という稀な条件でのみ成立」**([be-adoption-evaluation.md §6](./be-adoption-evaluation.md))。be-patterns の order-processing が Moment のスカラーを qualifier 属性 + `toInstance()` **固定値**で束縛している(= 入力が流れない)のは、この制約の症状である。
3. **案内の欠落(三次)**: 上記の成立条件は be-patterns のどこにも書かれておらず、BeMart はフレームワークのソースを読んで自力で導出した。また BeMart のルーティングスキーマ(`alps-analyze` の `be_pattern` enum: 5 値)はカタログの 8 パターンと対応しておらず、`Diamond-Independent` は一度も割り当てられなかった。

**評価**: 層 1・2 に基づく棄却は正しく、その過程の文書化品質は高い。問題は層 3 — 正しい判断に到達するコストが「ソース読解と実試行」だった点、および次節の取りこぼし。

## 5. 実害と drift

### 5.1 補償不能ウィンドウ(最重要)

[CheckoutSettled](../src/Being/CheckoutSettled.php) は在庫引当 → 課金 → 採番を constructor で一発実行し、docblock で自己申告している:

> "Failures in the Final after this point leave the system in a state where the customer's card has been charged but the order row is missing."

Diamond の**機構**が使えないことと、**二相コミット(予約→確定)**が不要なことは別問題である。order-processing の Reason 契約 — `InventoryReserver::lock()` が Potential を返し、Final の self-completion で一斉 `confirm()` — は Input 依存とは無関係に適用できた。カタログがこの本質を「Diamond というフロー形状の一部」としてしか提示していないため、形状の棄却と共に捨てられた。

### 5.2 用語 drift

パターン名が「多段逐次」の便利なラベルとして流用され、名前が指す機構は伴っていない:

- `HANDOVER.md` は「Diamond-Cascade: 3 件(Pilot 2, 5, 12)」と集計するが、3 件とも `MomentInterface` ゼロの逐次 Reason チェーン。
- [ReorderResolving](../src/Being/ReorderResolving.php) は「Cascade Diamond Stage 1 (loan-application demo)」を自称。`docs/skills/G-15-multi-side-effect-final.md` は loan-application を「Complex Convergence」と誤記(カタログ上その名は insurance-claim のもの)。
- `CheckoutSettled` は自らを「Three Reasons **converge** on a single Being」と説明 — Diamond と Multi-Reason Being の境界判断基準がカタログに無いことの直接の現れ。

### 5.3 その他

- clock/乱数直呼び 7 クラス(§3)。canonical の悪例が伝播源で、自前の `Reason/Provider/` seam をバイパスする内部不整合。
- 単要素配列 `#[Be([X])]` が 4 箇所(分岐しないのに配列構文。無害だが読者に分岐を誤示唆)。
- `Semantic/MemberName.php` — `Name.php` に置換された後、削除されなかった唯一の dead orphan(be-patterns の `ArticleTitle` と同型の残骸)。

### 5.4 カタログに語彙のない発明(実アプリで必要になった構造)

be-patterns のデモは永続レコードを round-trip しないため、以下は BeMart が自力で発明した:

- **`Reason/Entity/`(41 ファイル)+ `MediaQueryJsonEntityTrait`**: readonly な永続 DTO を `ToScalarInterface` 経由で `#[DbQuery]` のスカラーパラメータ境界を越えさせる橋渡し機構。
- **`#[DbQuery]` を be/src の Reason interface に直接注釈**し、外側アプリの Ray.Di コンパイルで AOP プロキシを生成する ray/media-query 統合の型。
- `Reason/Query/`(interface 59)/ `Reason/Query/Factory/`(hydrator)/ `Reason/Query/Result/`(read model)/ `Reason/Provider/`(ID/token 生成)というサブ名前空間の分類学。

これらは「次に BE で実アプリを書く AI」が再発明を強いられる構造であり、カタログ側に還元する価値が高い(§6.1 指針 8)。

## 6. 改善指針

### 6.1 be-patterns(パターンカタログ)へ

1. **Diamond 成立条件の明文化(最優先)**: 「収束 apex は `#[Inject]`-only でなければならない。`#[Input]` 依存データが各段に流れるなら、それは Linear Cascade の合図」— BeMart が自力導出したこの規約を CLAUDE.md / 選択ガイドに正式収載する。
2. **形状と独立の横断指針**: 「補償不能な副作用(課金・在庫・外部確定)が複数あるときは、フロー形状に関わらず Reason を二相契約(予約→確定)にする」。§5.1 の実害がこの指針の不在の帰結。
3. **パターン名の構造的定義**: 各パターンに機構チェックリスト(Diamond ⊃ `MomentInterface` + Final での `be()` 一斉コミット + 並列収束)を付け、「呼称だけの流用」を検出可能にする。
4. **Query/projection パターンの追加**: 実アプリの ~1/3 は読み取り。Minimal の特殊化として「safe な取得(Fetched)」を正式な項にし、「Fetched は副作用なし」を規約として明文化(ShoppingFetched のような意図的例外は docblock で宣言)。
5. **合成の追認**: BeMart 実証済みの「Sequential Chain + 末尾 Branching」をカタログの合成例として収載。
6. **ドメイン→パターンの選択ガイド(CHOOSING)**: 3〜5 問のデシジョンツリー + 境界事例の tie-breaker(Linear vs Sequential vs Multi-Reason Being の分割基準 =「中間状態に業務上の意味があり後続・監査が参照するか」)。BeMart の 3 軸判定器(`independent_parallel` / `cascade` / `branching_final`)は良い出発点。
7. **canonical の clock 直呼び解消**: OrderConfirmed 等の `date()` 直呼びを Reason 経由に改め、悪例の伝播源を断つ。
8. **永続 DTO / クエリ層の語彙追加**: BeMart の `Reason/Entity` + `Reason/Query`(interface / Factory / Result / Param)+ ray/media-query 統合(§5.4)を、DB を持つ実アプリ向けの参考パターンとしてカタログに収載する。現状この語彙が無いため、次の移植者は再発明を強いられる。

### 6.2 be-framework(本体)へ

9. **Input 依存 Moment の解決機構、または制約の公式化**: `#[Inject]` 解決時にも `BecomingArguments` の `#[Input]` 充足を通す道を設けるか、設けないなら「Diamond の成立条件」を framework 公式ドキュメントに明記する。現状この知識は BeMart の評価文書にしか存在しない。

### 6.3 BeMart(このリポジトリ)へ

10. **CheckoutSettled の二相化**: `InventoryAllocatorInterface` / `PaymentGatewayInterface` を予約→確定契約に改め、確定を `CheckoutCompleted`(Final)へ移す。「Production Phase 2 で DB トランザクション」よりもフレームワークの思想に沿う解であり、DB トランザクションで包めない外部決済にも一般化する。
11. **用語の是正**: `HANDOVER.md` の「Diamond-Cascade: 3 件」等のラベルを「Multi-Reason Being / Sequential Chain」に読み替える注記を入れ、docblock の「Cascade Diamond」自称を実態(逐次)に合わせる。
12. **小粒の掃除**: dead orphan の `Semantic/MemberName.php` 削除、`CalendarHolidayNotFoundException` への `#[Message]` 追加、clock/乱数直呼び 7 箇所の `Reason/Provider/` 経由化、単要素 `#[Be([X])]` の平叙化(`#[Be(X::class)]`)、`ShoppingFetched` の write の規約上の位置づけ明文化。

## 7. 出典

- be-patterns 監査と修正: [PR #22](https://github.com/be-framework/be-patterns/pull/22)(2026-07-18、install 復旧・未配線の開示・CardExpiry 等のバグ修正・invariant 精緻化)
- 決定痕跡: [be-adoption-evaluation.md](./be-adoption-evaluation.md) §6–7、`../../docs/HANDOVER.md`、`../var/analysis/doAddCartItem.json`、`../../.claude/prompts/{alps-analyze,domain-implement,be-review}.md`
- 実装の一次証拠: [OrderConfirming](../src/Being/OrderConfirming.php)、[CheckoutSettled](../src/Being/CheckoutSettled.php)、[ShoppingFetched](../src/Final/ShoppingFetched.php)、`../tests/Domain/OrderConfirmedTest.php`
