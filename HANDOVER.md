# HANDOVER

EC-CUBE 4.3 の ALPS プロファイル構築と、Be Framework + BEAR.Sunday への移植 Pilot の進行記録。次の AI セッション (および人間レビュアー) への引き継ぎメモ。

| メタ | 値 |
|---|---|
| Last updated | 2026-05-18 |
| Latest session | pilot-3-doConfirmOrder-branching-plus-diamond-finding-opus-4.7 |
| Scope | ALPS プロファイル + Be/BEAR 移植 Pilot (Pilot 1 goProduct / Pilot 2 doAddCartItem — Cascade / Pilot 3 doConfirmOrder — Branching + 4 段 Linear Cascade) + alps-to-be-bear skill dogfooding。Cascade Diamond は Pilot 3 で「apex が `#[Input]` 依存だと be-framework のメカニクス上不成立」と判明 (Linear Cascade に縮退) |

> このファイルは元 `handover.json` を Markdown 化したもの (2026-05-17)。スキーマ未定義のまま JSON で運用していたが、機械処理する場面がなく自然言語の `note` が多いため Markdown へ移行した。

---

## プロジェクト名と monorepo レイアウト (2026-05-17)

旧 `MyVendor.EcCube` (移植 pilot 用の作業 repo) を **BeMart** へ改称し、ALPS repo (`ec-cube-alps`) 内の monorepo に統合した。

- **製品コンセプト:** BeMart = AI 駆動の EC-CUBE → BEAR.Sunday + Be Framework 全置換プロダクト。「Be (変容による存在) で生まれ変わる Mart」
- **改称理由:** (a) 旧名は EC-CUBE 商標を連想させる、(b) Pilot 1+2 完了で「単発の参照実装」から「製品候補」へ昇格した

### レイアウト

```
ec-cube-alps/                            ← BEAR.Sunday アプリ (top)
├── composer.json                        ← my-vendor/be-mart (path repo で be-mart-be を ref)
├── phpunit.xml                          ← bemart + bemart-be 2 testsuite
├── src/
│   ├── Resource/Page/...                ← MyVendor\BeMart\Resource\Page
│   └── Module/                          ← MyVendor\BeMart\Module (AppModule + DevBecomingProvider)
├── tests/Resource/                      ← MyVendor\BeMart\Tests\Resource
├── bin/, public/                        ← BEAR 実行 entry
├── var/log/, var/tmp/                   ← BEAR runtime data
├── alps.json, docs/, ...                ← ALPS 公開成果物 (従来通り)
└── be/                                  ← Be ドメインライブラリ
    ├── composer.json                    ← my-vendor/be-mart-be (library)
    ├── src/{Input,Final,Semantic,Exception,Becoming,Reason}/   ← MyVendor\BeMart\Be\*
    ├── tests/Domain/                    ← MyVendor\BeMart\Be\Tests\Domain
    └── var/{fake,schema,analysis}/      ← Be domain fixture
```

namespace 関係: `MyVendor\BeMart\` (BEAR) ⊃ `MyVendor\BeMart\Be\` (Be domain)。BEAR は Be に片方向依存。Be は framework-agnostic を保つ (DI で path/Becoming は inject)。

### 開発と将来の packagist 切り出し

- 開発中は composer の `repositories: [{type: "path", url: "be", symlink: true}]` で `vendor/my-vendor/be-mart-be` に symlink。修正は即座に top から見える
- packagist 公開時は `git subtree split --prefix=be -b release/be` で別 repo に分離し、`my-vendor/be-mart-be` を通常依存に切り替え
- `be/` 単体テストは現状 top の AppModule に依存 (Domain test が `new AppModule(new Meta('MyVendor\\BeMart', 'test'))`)。packagist 切り出し時は Be ライブラリ独自の test bootstrap を作る必要あり

### Pilot 1/2 履歴の参照先

- **旧パス:** `~/git/MyVendor.EcCube/` (削除済み)
- **新パス:** `~/git/ec-cube-alps/` (BEAR top) + `be/` (Be domain)
- 以下 Pilot 1/2 セクションの「リポジトリ」欄も新パス前提で読む

---

## 完了済み作業

### Ontology (276 semantic descriptors)

182 src-entity + container 型。タグ: `src-entity` / `src-template` / `src-router` / `src-controller`。

### Taxonomy (73 states)

40 既存 + 17 新規 (フロントエンド) + 16 新規 (admin)。新規追加:

`Top`, `ShoppingLogin`, `ShoppingNonMember`, `Shopping`, `ShoppingShipping`, `ShoppingShippingEdit`, `ShoppingConfirm`, `ShoppingComplete`, `ShoppingError`, `CustomerRegistrationComplete`, `Mypage`, `MypageHistory`, `MypageChange`, `MypageWithdraw`, `HelpAbout`, `HelpGuide`, `HelpAgreement`, `HelpPrivacy`, `HelpTradeLaw`

### Choreography (137 transitions)

`safe: 58` / `unsafe: 35` / `idempotent: 44`。全 transition が `src-router` タグ付き + doc 付与。

**新規 transition:** `goTop`, `goLogin`, `doCopyProduct`, `doBulkUpdateProductStatus`, `goExportProduct`, `doImportProductCsv`, `doImportCategoryCsv`, `goExportCategory`, `goShopping`, `goShoppingLogin`, `goShoppingNonMember`, `doSubmitNonMember`, `goShoppingShipping`, `goShoppingShippingEdit`, `goShoppingShippingMultiple`, `doSelectShippingAddress`, `doUpdateShippingAddress`, `doConfirmOrder`, `goShoppingError`, `doBulkDeleteOrder`, `goExportOrder`, `goExportShipping`, `doImportShippingCsv`, `goExportOrderPdf`, `goMypage`, `goMypageHistory`, `goMypageChange`, `goMypageWithdraw`, `goExportCustomer`, `goHelpAbout`, `goHelpGuide`, `goHelpAgreement`, `goHelpPrivacy`, `goHelpTradeLaw`

**修正された rt 関係:**

- `doCheckout`: `#Order` → `#ShoppingComplete`
- `doLogin`: `#Customer` → `#Mypage`
- `doLogout`: `#Login` → `#Top`
- `doRegisterCustomer`: `#Customer` → `#CustomerRegistrationComplete`
- `doUpdateCustomer`: `#Customer` → `#MypageChange`
- `doWithdrawCustomer`: `#Login` → `#Top`
- `Cart` state: `doCheckout` → `goShopping`

### Quality refinement (Opus 4.7 session)

誤解を招く doc の改善、似た名前ペアの曖昧さ解消、仕様書 (doc4) 由来の拡充、ERD 由来の新規 descriptor 追加、123 件の S001 suggestion 解消。

**verify-* 由来:**

- **誤解を招く命名 doc 強化**: `classNameLabel`, `classCategoryName`, `preOrderId`, `confirmUrl`, `secretKey`, `linkMethod`, `cartKey`, `deviceType`, `pageEditType`, `authority`
- **似た名前ペアの区別 doc**: `charge/paymentCharge`, `taxRate/taxRuleRate`, `total/totalPrice/paymentTotal`, `deliveryFee/deliveryFeeAmount/deliveryFeeTotal`, `message/shopMessage`
- **誤記修正**: `message` (お問い合わせ欄), `shopMessage` (Help/about.twig)
- **バリデーション説明拡充**: `kana01`, `kana02`, `pref`, `freeArea`

**doc4 由来:** `orderStatus` (workflow rules), `orderItemType` (6 区分の課税/税表示), `orderItemPrice`, `orderItemTax`, `taxRate`, `taxAdjust`, `roundingType`, `subtotal`, `total`, `paymentTotal`, `paymentCharge`, `point`, `applyDate`

**ERD 由来の新規 descriptor:** `addPoint`, `usePoint`, `deliveryFeeAmount`, `saleTypeName`, `taxDisplayType`, `taxType`

**Transition doc 追加:** 123 件。ドメイン横断 (catalog / cart / checkout / order / customer / account / help / shop / payment / delivery / tax / content / admin-system / mail / plugin / cms / catalog-admin)。MyVendor.Cms 流の 1 文スタイル (目的 · スコープ · 主要な副作用/パラメータ)。

### Provenance tags

| タグ | 件数 |
|---|---|
| `src-entity` | 193 |
| `src-router` | 156 |
| `src-template` | 80 |
| `src-controller` | 10 |
| `src-unknown` | 0 |

### Validation

| 項目 | 値 |
|---|---|
| `asd --lint` errors | 0 |
| warnings | 0 |
| suggestions | 0 |
| HTML 生成 (`alps.json.html`, `docs/alps.json.html`) | ✅ 同期済み |
| SVG 生成 (`alps.svg`, `docs/alps.svg`) | ✅ 同期済み |
| Self review (Opus 4.7) | ✅ 合格 |

### Route coverage

| 項目 | 値 |
|---|---|
| 総 route 数 | 250 |
| transition でカバー | 135 |
| return type としてカバー | 7 |
| API / Ajax / utility | 78 |
| 未カバー (user-visible) | 30 |
| **実効カバレッジ** | **82.6%** |

---

## Pilot 1 — `goProduct` (Be + BEAR.Sunday 初参照実装)

**目的:** Be Framework + BEAR.Sunday の統合パターンを 1 件だけ確立。

| 項目 | 値 |
|---|---|
| リポジトリ | `~/git/ec-cube-alps` |
| スコープ | Product container を ProductClass 平坦化形まで縮小 (`#[Embed]` による子リソース合成は別 Phase)。URL param は `productCode` (schema.org/sku 由来、ユニーク性) |
| テスト | 8 passed (Domain 5 + Resource 3), 20 assertions |

### 指標 (Pilot 1)

| # | 指標 | Target | Actual | Note |
|---|---|---|---|---|
| 1 | Semantic クラス数 | 5 | 4 | ProductCode, ProductName, Price02, Stock。5 件目 (StockUnlimited 等) は Pilot スコープ外 |
| 2 | 自己証明で省略できた単体テスト | 5 | 4 | Semantic 型保証分を Semantic クラスごとに 1 件省略。Final 存在 = 検証済みを ProductResourceTest が間接確認 |
| 3 | 意味ログ自動カバレッジ | 100% | 100% | DevBecoming が Becoming chain 全体 (Input prop / Final inject / close prop) を `var/log/bemart.json` に自動記録 |
| 4 | LoC (Be+BEAR) | 実測のみ | 576 | BEAR-only 比較は推定値とせず実測のみ記録。BEAR-only 版は未実装 |
| 5 | i18n 例外メッセージ | 100% | 100% | 6/6 例外に `#[Message(['en'=>..., 'ja'=>...])]` 付与 |
| 6 | 自己証明 assert | ≥1 | 1 | `Resource/Page/Product.php:44` で `assert($final instanceof ProductFetched)` |

### Pilot 1 key decisions

- **`BecomingInterface → DevBecoming` を本番でも常時 bind** — 意味ログを常時取得
- **`AppModule` に `AppMetaModule` を `override` で install** — テストで `BEAR\Package\Module` factory を経由せず `new Injector()` で動作可能にするため
- **`ProductNotFoundException` は Resource 層で catch して `Code::NOT_FOUND` にマップ** — Be の Final 閉鎖原則と HTTP プロトコルの折衷点
- **Be 流 `FakeQueryModule` の代わりに** `ProductQueryInterface` を JSON fixture 読み込みの `FakeProductQuery` で実装

### Pilot 1 で見つかった workflow prompt の穴

- `alps-analyze.md`: `bear_url_template` 不足 (`/product/{productCode}` 解決方法)
- `domain-implement.md`: `BecomingInterface` の DI 経路と `AppModule` 例の不足
- `application-implement.md`: `BEAR\Resource\Code` 定数の参照、`#[Embed]` の扱い不足
- `integration-review.md`: Final 閉鎖原則 vs HTTP プロトコル例外マッピングの判断基準

---

## Pilot 2 — `doAddCartItem` (Cascade: Being chain + Final convergence)

**目的:** Cascade パターン (Being chain + Final での Reason 収束) の初参照実装 + `alps-to-be-bear` skill の dogfooding。

**改訂履歴 (2026-05-17 → 2026-05-18):**

1. 初版 → Phase 8 reflection で「Cascade Diamond」と分類
2. self review 1 で「1 つの Final 内に 5 ブロックを手続き的に並べただけで、Being 連鎖も `#[Reason]` 収束も無い」と判明し **Linear / Minimal** へ再分類 (commit 8f75c66)
3. self review 2: 真の Cascade として refactor — `QuantityAdjusted` Being を導入し、Stage 1 (quantity 確定 + cartKey 解決) → Stage 2 (cart 文脈 + マージ + 配送 + 保存) の **2 段 cascade** に。Final で 3 つの独立 Reason (`CartQuery` + `CartCommand` + `ProductClassQuery`) が `#[Inject]` で収束 (commit 35a0201)
4. self review 3 (2026-05-18): 「Final が永続化以外に in-memory merge + totalPrice + deliveryFeeTotal 計算を抱えていて厚い」という違和感から、`CartMerged` Being を抽出。**3 段 cascade** に再構成 — Stage 1 (`QuantityAdjusted`: ProductClass lookup / Stock cap / SaleLimit cap / cartKey) → Stage 2 (`CartMerged`: 既存 cart 取得 / item merge / totalPrice / deliveryFeeTotal) → Stage 3 (`CartItemAdded` Final: 永続化のみ)。Final の `#[Inject]` は `CartCommand` 1 つに収束。Linear 版の snapshot は `be/docs/variations/linear-doAddCartItem/` に保存 (commit 8f75c66 時点のコード)。本評価は `be/docs/be-adoption-evaluation.md` に詳述。Cascade Diamond reference (be-patterns `order-processing` のような複数 Moment 並列収束) は doAddCartItem の domain には不適 (quantity 確定 → cart 合成 → 永続化 は本質的に直列) で、将来の Pilot (例: `doCreateOrder` で Cart + Customer + Payment 並列収束) に譲る

| 項目 | 値 |
|---|---|
| リポジトリ | `~/git/ec-cube-alps` |
| パターン | Cascade (3 段の Being chain + Final convergence)。`AddCartItemInput → QuantityAdjusted (Being) → CartMerged (Being) → CartItemAdded (Final)`。Stage 1 = ProductClass lookup + Stock cap + SaleLimit cap + SaleType 解決, Stage 2 = 既存 cart 検索 + item merge + delivery fee 集計, Stage 3 = 永続化のみ |
| テスト | 21 passed (Pilot 1 既存 8 + Pilot 2 新規 13), 51 assertions, **0 notices** (Cascade refactor で導入された全 Semantic 変数を登録: `SessionPrefix` / `RequestedQuantity` / `AdjustedQuantity` / `UnitPrice` / `SaleTypeId` / `SaleTypeName` / `DeliveryFee` / `StockUnlimited` / `SaleLimit` / `CartKey` / `TotalPrice` / `DeliveryFeeTotal` / `MergedCart` の 13 件) |
| Skill 配置 | `~/.claude/skills/alps-to-be-bear/` (local dogfooding。Pilot 3 + 本番移植 10 件後に `be-framework-skills` plugin marketplace へ promote 候補) |

### Pilot 2 完了の判定基準 — 12 指標

| # | 指標 | Target | Actual | Note |
|---|---|---|---|---|
| 1 | composer test 全 pass | 100% | 100% | 21/21 pass (Pilot 1 既存 8 + Pilot 2 Domain 9 + Resource 4) |
| 2 | 意味ログ被覆 | 100% | 100% | `var/log/bemart.json` に Becoming chain 全体が JSON で記録 |
| 3 | i18n 例外メッセージ | 100% | 100% | 全 DomainException (`QuantityFormatException`, `ProductClassNotFoundException`, `OutOfStockException`) に `#[Message(['en'=>..., 'ja'=>...])]` |
| 4 | 自己証明 assert | ≥1 | ≥1 | Final 内: `assert($adjustedQuantity >= 1 && $adjustedQuantity <= $requestedQuantity)`。Resource 内: `assert($final instanceof CartItemAdded)` |
| 5 | Semantic クラス数 | 1 | 1 | `Quantity` 1 件新規。`ProductCode` は Pilot 1 既存を再利用 |
| 6 | client-input / server-fetched 分離 | 2 シート | 2 シート | client-input (`productCode`, `quantity`) と server-fetched (`stock`, `stockUnlimited`, `saleLimit`, `price01/02`, `deliveryFee`, `saleTypeName`) を Phase 2 で分離観察 |
| 7 | LoC (Be+BEAR) | 実測のみ | src 約 620 + tests 約 250 | BEAR-only 比較は推定値とせず実測のみ記録 |
| 8 | Final の `#[Inject]` 数 | (当初は Cascade 5 phase = 5 を想定) | **1** | 3 段 Cascade refactor 後の Final (`CartItemAdded`) は `CartCommand` 1 件のみ `#[Inject]` で受け取り、永続化に専念。Read 系の `CartQuery` / `ProductClassQuery` は Stage 2 (`CartMerged`) と Stage 1 (`QuantityAdjusted`) に分散。本質的指針: **「Final の `#[Inject]` 数 = Final で収束する独立 Reason 数」。Final が薄ければ Inject も少なくなる** |
| 9 | 数量自動調整テスト | pass | pass | `testStockShortageAutoAdjusts`: sample-003 stock=3 で qty=5 → 自動補正 3, totalPrice=13500 (4500×3) |
| 10 | CartItem merge テスト | pass | pass | `testSameSkuAddedTwiceMergesQuantity`: 同 SKU を 2+3 で追加 → totalPrice=5000 (1000×5) |
| 11 | cartKey 分離テスト | pass | pass | `testDifferentSaleTypeIsolatesCart`: 通常販売 (`cartKey=session-prefix-1_1`) と予約販売 (`cartKey=session-prefix-1_2`) で cart が分離 |
| 12 | ALPS 整合性 (Rule 7) | 手動チェック合格 | 合格 (refine 後) | Final = transition outcome envelope と判明。container 状態属性 (`cartKey`, `totalPrice`, `deliveryFeeTotal`, `saleTypeName`) は ALPS Cart container に存在。transition outcome (`requestedQuantity`, `adjustedQuantity`, `unitPrice`) は ALPS container に不要 (操作結果)。Rule 7 を「container 状態属性 ⊆ ALPS descriptor」へ refine し、transition outcome 分離を `decision-matrix.md §5` に明示 |

### Pilot 2 で発生した skill gap (G-1〜G-7) — すべて昇格済み

| ID | 内容 | 昇格先 |
|---|---|---|
| G-1 | Final の `#[Inject]` 数 = Final で収束する独立 Reason 数 (Cascade refactor 後の指針。当初は「Cascade 段数 ≠ Inject 数」として記録 → Linear/Minimal に一旦訂正 → Cascade refactor で「Final で収束する Reason のみカウント」に確定) | `SKILL.md #6` / `decision-matrix.md §4.E` |
| G-2 | Reason 層の Query/Command 共有ストア (Singleton 必須) | `SKILL.md #7` / `decision-matrix.md §4.E` |
| G-3 | Final shape は平坦が原則 | `SKILL.md #8` / `decision-matrix.md §4.E` |
| G-4 | Pilot Input の sentinel default は本番移植で外す | `SKILL.md #12` / `decision-matrix.md §6.C` |
| G-5 | Pilot 段階に framework-level Semantic→400 マッパー無し (Resource で明示 catch 必要) | `SKILL.md #9` / `decision-matrix.md §6.A` |
| G-6 | Phase 6 統合 smoke を Phase 7 (PHPUnit) より前に | `SKILL.md #11` / `decision-matrix.md §6.B` |
| G-7 | `BEAR\Resource\Code` に CONFLICT/GONE/UNPROCESSABLE_ENTITY 無し | `SKILL.md #10` / `decision-matrix.md §6.A` (整数リテラル + コメントで対処) |

### Pilot 2 で更新した prompt

**`.claude/prompts/domain-implement.md`:**

- §9: Cascade 段数 ≠ `#[Inject]` 数 セクション追加
- §9: Query/Command 共有ストア (`FakeXxxStorage` Singleton) パターン追加
- §9: Diamond test snippet を平坦 Final 形 (`adjustedQuantity`, `totalPrice`) に修正
- §8: Phase 6 統合 smoke パターン (`bin/smoke_<descriptor>.php`) 追加

**`.claude/prompts/application-implement.md`:**

- `SemanticVariableException` を Resource で明示 catch (Pilot 段階で framework マッパー無し)
- catch group の後に self-proof assert (`$final instanceof Final`) パターン
- `DomainException` → HTTP Code マッピング表で `CONFLICT` 行を整数リテラル + コメント規約に変更

---

## Orphaned states

**解消済み:** `ShoppingError` (`goShoppingError` transition を追加)

**残存 (意図的なデータ合成パターン):**

- `LoginHistory` — `LoginHistoryList` の子要素 (href で参照、詳細ページなし)
- `Template` — `TemplateList` の子要素 (href で参照、詳細ページなし)
- `TradeLaw` — `TradeLawList` / `HelpTradeLaw` の子要素 (href で参照、編集は List 内で完結)

これらは「真の孤立状態」ではなく、親 list state から href で参照されるデータ合成要素。

---

## 次の AI / 人間レビュアーへの助言

### ALPS 品質

- **S001 は 0**。全 137 transition が MyVendor.Cms 流の 1 文スタイル (目的 · スコープ · 主要な副作用/パラメータ) で doc を持ち、全 transition doc が全角句点 `。` で終わる (ファイル全体で一貫)
- **トップレベル descriptor 数は 403** (container 266 + safe 58 + unsafe 35 + idempotent 44)。過去の handover snapshot に 413 とあったのは誤り — 403 が検証済みの値
- **Self review (Opus 4.7 follow-up)** で `doDeleteProduct`, `doCopyProduct`, `doUpdateCustomer`, `doCreateCustomerAddress` の doc を強化した (元は <20 文字または入力契約のギャップあり)。他の全 transition は監査済みで受け入れ
- **alps.json に未反映の memo (意図的にスコープ外):** SEO meta on Page (`pageDescription`, `pageKeyword`, `pageMetaRobots`, `pageMetaTags`, `pageAuthor`); 顧客向け status (`customerOrderStatus`, `customerOrderStatusName`, `orderStatusColorName`); 国マスタ (`countryName`); delivery extras (`deliveryRegionalFee`, `deliveryTimeVisible`); payment extras (`paymentFixed`, `displayOrderCount`)。理由: BEAR.Sunday/Be 移植ターゲットでは優先度低
- **30 admin routes が未カバー**。優先ギャップ: System Settings (8), Content Management (6), Product Class Management (5), Shop Settings (5)。追加候補 state: `AdminDashboard`, `SystemInfo`, `SecuritySettings`, `LogViewer`, `MasterData`, `CssEditor`, `JsEditor`, `FileManager`, `CacheManager`, `MaintenanceMode`, `TwoFactorAuth`, `CalendarSettings`, `OrderStatusSettings`
- **`deliveryFeeAmount` 曖昧さ解消:** 既存 ALPS id を再利用するが doc は「都道府県別の送料金額 (`DeliveryFee.fee`)」と明示し、基本情報側の「送料無料閾値」と区別。将来別 id が必要なら `deliveryFeeFreeThreshold` を推奨
- **`LoginHistory`, `Template`, `TradeLaw`** は意図的なデータ合成要素 (EC-CUBE 4.3 に詳細ページが存在しない)
- **フロントエンドの顧客向け route は 100% カバー済み**
- **Memo cleanup 完了:** verify-misleading / verify-similar-names / verify-suffix-abbrev / verify-agent1-product / verify-agent2-order / verify-agent3-payment-delivery / verify-agent4-customer-shop / verify-agent5-admin-cms / improvements-doc4 / improvements-erd / improvements-graphql (11 ファイル、~3500 行) をリポジトリ root から `docs/quality/` へ移動。`improvements-graphql.md` 以外は `alps.json` に完全反映済み (GraphQL-API alignment は BEAR.Sunday/Be 移植ターゲットではスコープ外)。agent-1..5 は audit trail として保持

### Workflow

- **Phase E 完了:** `alps-analyze.md` は決定論的な `json handover` fenced ブロックを末尾に emit する (`descriptor_id`, `alps_id_resolved`, `alps_found`, `descriptor_type`, `be_pattern`, `be_reference_demo`, `be_classes`, `semantic_classes`, `reasons`, `bear`, `notes`)。ID マッチングは 5 step の決定論的順序 (exact → lowerCamel → UpperCamel → case-insensitive LIKE → snake_case→camelCase)。type → Be 分類表を追加。`domain-implement.md` と `application-implement.md` は handover JSON を初回読み込みし、rework 時には `reviewer.blocking[]` を消費する「入力契約」セクションを各々持つ。Pure-Semantic / container スキップ判定は脆い "all N/A" 文字列マッチではなく `bear.skip` / `descriptor_type` で行う

### Pilot

- **Pilot 1 完了** (`BeMart` (旧 MyVendor.EcCube), `goProduct` 1 件): Be + BEAR.Sunday 統合の初参照実装が動作確認済み。意味ログ自動記録 100%、i18n 例外 100%、composer test 8/8 pass。次は (1) workflow prompt 改修 PR で Pilot で発見した穴を埋める、(2) Pilot 2 として `doAddCartItem` (Diamond パターン) を別タスクで実装、(3) 残り 135 transition を `/run migrate <id>` で自走移植。詳細指標は「Pilot 1」セクション参照
- **Pilot 2 完了** (`BeMart`, `doAddCartItem` 1 件): **Cascade** パターンの参照実装。`AddCartItemInput → QuantityAdjusted (Being) → CartMerged (Being) → CartItemAdded (Final)` の **3 段 cascade**。Final は `CartCommand` 1 件のみ `#[Inject]` で受け取り永続化に専念、Read 系 Reason は上流 Being に分散。改訂履歴: 初版「Cascade Diamond」分類 → Linear/Minimal に訂正 → 2 段 Cascade refactor → 3 段 Cascade refactor (`CartMerged` 抽出で Final から in-memory merge / totalPrice / deliveryFeeTotal 計算を引き剥がした、2026-05-18)。composer test 21/21 pass, 51 assertions, **0 notices** (Cascade refactor で導入された 13 件の Semantic 変数を全登録: `SessionPrefix` / `RequestedQuantity` / `AdjustedQuantity` / `UnitPrice` / `SaleTypeId` / `SaleTypeName` / `DeliveryFee` / `StockUnlimited` / `SaleLimit` / `CartKey` / `TotalPrice` / `DeliveryFeeTotal` / `MergedCart`)。Linear 版 snapshot は `be/docs/variations/linear-doAddCartItem/` に保存 (educational comparison 用)。Pilot 1+2 を通じた **Be 採用評価** は `be/docs/be-adoption-evaluation.md` に詳述 (基層効力 4 軸 = Pilot 1 から効く / 上層効力 4 軸 = Pilot 2 で爆発)。Query/Command 共有時は Singleton ストア必須、`SemanticVariableException` は Resource で明示 catch (Pilot 段階)、`BEAR\Resource\Code` は CONFLICT 未定義で整数リテラル運用、いずれも Cascade 実装でも有効。skill `~/.claude/skills/alps-to-be-bear/` に昇格済み (SKILL.md / decision-matrix.md は 3 段 Cascade refactor の読み替え注記が必要)。**Pilot 2 末尾の「次は Cascade Diamond を別 Pilot で」という想定は Pilot 3 で部分的に覆った — `#[Input]` 依存の Being を `#[Inject]` で apex に持つ Cascade Diamond は be-framework のメカニクス上不成立 (詳細は Pilot 3 セクション / `be/docs/be-adoption-evaluation.md §6`)。EC-CUBE の典型的な `#[Input]` 依存遷移は Linear Cascade に縮退する**
- **Pilot 3 完了** (`BeMart`, `doConfirmOrder` 1 件): **Branching** パターンの参照実装 + **Cascade Diamond 不成立の構造的発見**。`ConfirmOrderInput → PreOrderResolved (Being) → PurchaseFlowApplied (Being) → PaymentVerified (Being) → OrderConfirming (Being, 分岐点) → OrderConfirmed | OrderConfirmFailed (Branching Final)` の **4 段 Linear Cascade + 1 段 Branching**。`OrderConfirming` の `public PaymentSuccessCase|PaymentFailureCase $being` 型 discriminator により `BecomingType::match()` が Final を選択 (be-patterns `medical-triage` デモ準拠)。composer test 27/27 pass (Pilot 1 既存 8 + Pilot 2 既存 13 + Pilot 3 新規 6), 79 assertions, **0 notices** (Pilot 3 で 14 件の Semantic 変数を新規登録: `PreOrderId` / `PaymentMethodId` / `Subtotal` / `Tax` / `Total` / `Discount` / `Charge` / `DeliveryFee`(既存) / `AddPoint` / `UsePoint` / `PaymentTotal` / `Order` / `Totals` / `Result` / `Being`)。改訂履歴: 初版 Cascade Diamond 想定で `OrderConfirming` を apex (`#[Inject] PreOrderResolved $preOrder` 等) → Ray.Di `NoHint($preOrderId)` で全テスト失敗 → `#[Input]` 依存 Being を `#[Inject]` 経由でインスタンス化できないと判明 (Ray.Di は `#[Input]` 属性を理解しない) → 4 段 Linear Cascade に再構成。**構造的発見**: Cascade Diamond は「apex Moment が `#[Input]` を一切持たない」場合のみ成立する。EC-CUBE の典型的フロー (Input scalar から DB 引き当て → 並列に Service 呼び出し) は全て Linear Cascade に縮退する。Pilot 3 の Branching パターンは clean に動作し、be-framework の Branching 機構 (`BecomingType::match()` による型ベース選択) は実用検証済み。Skill 配置: `~/.claude/skills/alps-to-be-bear/` の `SKILL.md` / `decision-matrix.md` には「Cascade Diamond の成立条件 (apex が `#[Input]` 不要) と Linear Cascade への縮退ルール」追記が必要。**次は (1) Complex Convergence (`insurance-claim` のような多分岐 + 多経路収束)、(2) admin 系の本番移植 (10 件規模) で skill を bake、(3) `~/.claude/skills/alps-to-be-bear/` の plugin marketplace 昇格判断**

---

## Pilot 3 — `doConfirmOrder` (Branching + Cascade Diamond 不成立の発見)

**目的:** Branching パターン (Final が分岐) と Cascade Diamond (apex Moment 並列収束) の 2 つを同時に検証する設計だったが、Diamond 側は be-framework のメカニクス上不成立と判明し、Linear Cascade + Branching に縮退した。

**改訂履歴 (2026-05-18):**

1. 初版: `ConfirmOrderInput → OrderConfirming (Cascade Diamond apex; `#[Inject]` で PreOrderResolved + PurchaseFlowApplied + PaymentVerified を並列収束) → OrderConfirmed | OrderConfirmFailed` を想定
2. Phase 7 で全 6 テスト失敗 — `Ray\Di\Exception\NoHint: $preOrderId at PreOrderResolved.php:28`。Ray.Di は `#[Inject] PreOrderResolved $preOrder` を解決する際に `$injector->getInstance(PreOrderResolved::class)` を呼ぶが、`PreOrderResolved::__construct(#[Input] string $preOrderId, ...)` の `#[Input]` 属性を解釈できず、コンストラクタ引数 `$preOrderId` を hint なしと判定して落ちる
3. `vendor/be-framework/be-framework/src/BecomingArguments.php` の挙動を確認 — `#[Input]` は be-framework の cascade (`BecomingArguments::be(object $current, string $becoming)` で `get_object_vars($current)` から拾う) でのみ解決される。Ray.Di の `getInstance()` 経路は `#[Inject]` のみ理解する
4. **構造的結論**: 「`#[Input]` を必要とする Being」は `#[Inject]` の対象になれない。Cascade Diamond の apex (`#[Inject]` で複数 Moment を並列収束) は、各 Moment が Input 依存ゼロの場合のみ成立する。EC-CUBE の `doConfirmOrder` のように Input scalar から派生して Service 呼び出しを並列する用途は不成立 → 4 段 Linear Cascade に再構成 (各 Being が `#[Input] public` プロパティで下流に scalar を forward)
5. 4 段 Linear Cascade + Branching に書き換え後、6/6 pass, 0 notices

| 項目 | 値 |
|---|---|
| リポジトリ | `~/git/ec-cube-alps` |
| パターン | **Linear Cascade (4 段) + Branching (1 段)**。`ConfirmOrderInput → PreOrderResolved (Being: 注文引き当て) → PurchaseFlowApplied (Being: 金額計算) → PaymentVerified (Being: 決済検証) → OrderConfirming (Being: discriminator 計算) → OrderConfirmed \| OrderConfirmFailed (Branching Final)`。`OrderConfirming::$being` の union 型 (`PaymentSuccessCase\|PaymentFailureCase`) を `BecomingType::match()` が読み、`#[Be([OrderConfirmed::class, OrderConfirmFailed::class])]` から型一致する Final を選択 |
| テスト | 27 passed (Pilot 1 既存 8 + Pilot 2 既存 13 + Pilot 3 新規 6), 79 assertions, **0 notices** |
| Skill 配置 | `~/.claude/skills/alps-to-be-bear/` (Cascade Diamond の成立条件と Linear 縮退ルール追記が必要) |

### Pilot 3 完了の判定基準 — 12 指標

| # | 指標 | Target | Actual | Note |
|---|---|---|---|---|
| 1 | composer test 全 pass | 100% | 100% | 27/27 pass (Pilot 1 既存 8 + Pilot 2 既存 13 + Pilot 3 Domain 6) |
| 2 | 意味ログ被覆 | 100% | 100% | DevBecoming が 5 段 cascade 全体を `var/log/bemart.json` に自動記録 |
| 3 | i18n 例外メッセージ | 100% | 100% | 全 DomainException (`PreOrderNotFoundException`, `PreOrderIdFormatException`, `PaymentMethodIdFormatException`, Semantic format 系 12 件) に `#[Message(['en'=>..., 'ja'=>...])]` |
| 4 | 自己証明 assert | ≥1 | ≥1 | `OrderConfirming::__construct()` 内: `$this->being = $result->success ? new PaymentSuccessCase(...) : new PaymentFailureCase(...)` で型 discriminator を自己証明 |
| 5 | Semantic クラス数 | 14 | 14 | `PreOrderId` / `PaymentMethodId` / `Subtotal` / `Tax` / `Total` / `Discount` / `Charge` / `AddPoint` / `UsePoint` / `PaymentTotal` を scalar 系として新規、composite 系 4 件 (`Order` for `OrderEntity`, `Totals` for `PurchaseFlowResult`, `Result` for `PaymentVerifyResult`, `Being` for union `PaymentSuccessCase\|PaymentFailureCase`) を MergedCart パターン (空 `#[Validate]` body) で登録 |
| 6 | Reason 層共有ストア | Singleton 必須 | 該当 | `FakeOrderQuery` を `Scope::SINGLETON` で bind (`AppModule:50`)。他の `FakePurchaseFlow` / `FakePaymentMethodFactory` は state を持たないので普通 bind |
| 7 | client-input / server-fetched 分離 | 2 シート | 2 シート | client-input (`preOrderId`, `paymentMethodId`) と server-fetched (`OrderEntity`, `PurchaseFlowResult`, `PaymentVerifyResult`, `PaymentSuccessCase\|PaymentFailureCase` discriminator) を Cascade 内で分離 |
| 8 | LoC (Pilot 3 新規分) | 実測のみ | src 約 469 + tests 約 132 | Being 4 件 (164 LoC) + Final 2 件 (88 LoC) + Input 1 件 (42 LoC) + Reason Case 2 件 (43 LoC) + Test 132 LoC。Semantic / Exception / Reason Entity / Reason Query / Reason Service は別カウント |
| 9 | Branching 分岐テスト | pass | pass | `testCashOnDeliverySucceeds` / `testCreditCardSucceeds` (success path → `OrderConfirmed`) と `testVerifyFailureBranchesToOrderConfirmFailed` (failure path → `OrderConfirmFailed`, errors `['Card validation failed']`) で双方向検証 |
| 10 | Cascade chain 整合性 | pass | pass | 4 段の `#[Input]` forward (preOrderId / paymentMethodId / order / totals / result) が `BecomingArguments::be()` で正しく chain される。`testMissingPreOrderThrows` で chain 中断 (Stage 1 で例外) も検証 |
| 11 | Branching 型 discriminator | pass | pass | `OrderConfirming::$being: PaymentSuccessCase\|PaymentFailureCase` が `BecomingType::match()` で `#[Be([OrderConfirmed::class, OrderConfirmFailed::class])]` から正しい Final を選択。`#[Input] PaymentSuccessCase $being` / `#[Input] PaymentFailureCase $being` で各 Final が型ベースに hit |
| 12 | ALPS 整合性 (Rule 7) | 手動チェック合格 | 合格 | Final の public プロパティ (`OrderConfirmed::$subtotal` / `$tax` / `$total` / `$addPoint` 等、`OrderConfirmFailed::$errors`) は ALPS `OrderConfirmed` / `OrderConfirmFailed` container の descriptor と一致 |

### Pilot 3 で発見された skill gap

| ID | 内容 | 昇格先 |
|---|---|---|
| G-8 | **Cascade Diamond は apex が `#[Input]` 不要なときのみ成立** — Ray.Di `getInstance()` は `#[Input]` を解釈しないため、`#[Input]` 依存 Being を `#[Inject]` で apex に並列収束させると `NoHint` で fail。EC-CUBE の典型的フロー (Input scalar から派生して並列計算) は全て Linear Cascade に縮退する | `SKILL.md` / `decision-matrix.md §5` に「Diamond 成立条件と Linear 縮退ルール」セクション追加 (TODO) |
| G-9 | **Branching pattern** は型 discriminator (`public A\|B $being`) + `#[Be([FinalA, FinalB])]` + 各 Final が `#[Input] A $being` / `#[Input] B $being` でクリーンに動作。be-patterns `medical-triage` 準拠 | `SKILL.md` / `decision-matrix.md §4.F` に Branching 実装テンプレ追加 (TODO) |
| G-10 | **Composite 型 Semantic 登録** — `PaymentSuccessCase\|PaymentFailureCase` の union 型 Semantic は `validate(A\|B $being): void {}` で登録可。scalar に貼る Semantic と区別して「composite Semantic の登録は型断定のみ、payload contract は型自体に委ねる」と明示すべき | `SKILL.md` の「Semantic クラスの種類」セクションに composite 型項目追加 (TODO) |

### Pilot 3 で更新したファイル

**`be/src/Module/AppModule.php`** (実体は `src/Module/AppModule.php`):

- Pilot 3 用 3 件の bind 追加 (`OrderQueryInterface` Singleton, `PurchaseFlowInterface`, `PaymentMethodFactoryInterface`)

**Pilot 3 で新規追加した Reason / Semantic 群:**

- `Reason/Entity/`: `OrderEntity`, `PurchaseFlowResult`, `PaymentVerifyResult`
- `Reason/Query/`: `OrderQueryInterface`, `FakeOrderQuery`
- `Reason/Service/`: `PaymentMethodFactoryInterface`, `FakePaymentMethodFactory`, `PaymentMethodInterface`, `FakeCashOnDelivery`, `FakeCreditCard`, `FakeVerifyFailing`, `PurchaseFlowInterface`, `FakePurchaseFlow`
- `Reason/`: `PaymentSuccessCase`, `PaymentFailureCase` (branching discriminator)
- `Semantic/`: 14 件 (上記指標 #5)
- `Exception/`: 14 件 (Semantic format 系 12 + `PreOrderNotFoundException`)

### Pilot 3 の参照ドキュメント

- **`be/docs/be-adoption-evaluation.md` §6** — Cascade Diamond 不成立の機構詳細 (Ray.Di の `#[Input]` 非対応、`BecomingArguments::be()` の `get_object_vars()` 経路、Linear Cascade への縮退ルール)
- **`be/docs/be-adoption-evaluation.md §5`** — パターン採用判定表 (Branching ◎採用 / Cascade Diamond ✗構造的に不成立)
- **`~/git/be-patterns/demos/medical-triage/`** — Branching 参照実装 (Pilot 3 の typed discriminator 設計はここから流用)

### Pilot 1+2+3 を通じた集計

- composer test: 27/27 pass, 79 assertions, **0 notices**
- Be domain LoC (Pilot 1+2+3 累計): 約 1670 src + 約 514 tests (Pilot 1 = 576 + 20、Pilot 2 = 620 + 250、Pilot 3 = 469 + 132、新規 Semantic / Reason / Exception 共有部分は重複カウントなし)
- 採用パターン: Linear/Minimal (Pilot 1), Cascade (Pilot 2), Linear Cascade + Branching (Pilot 3)
- 未検証パターン: Multi-Reason Being, Complex Convergence (`insurance-claim` 系), 真の Cascade Diamond (apex が Input 不要なケース; EC-CUBE の通常 transition では出現しない可能性大)
