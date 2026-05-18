# HANDOVER

EC-CUBE 4.3 の ALPS プロファイル構築と、Be Framework + BEAR.Sunday への移植 Pilot の進行記録。次の AI セッション (および人間レビュアー) への引き継ぎメモ。

| メタ | 値 |
|---|---|
| Last updated | 2026-05-18 |
| Latest session | phase-b-slice-5-env-gated-entry-point-opus-4.7 |
| Scope | ALPS プロファイル + Be/BEAR 移植 Pilot (Pilot 1 goProduct / Pilot 2 doAddCartItem — Cascade / Pilot 3 doConfirmOrder — Branching + 4 段 Linear Cascade / Pilot 4 doRegisterCustomer — Multi-Reason Being / Pilot 5 doCheckout — Diamond-Cascade + Multi-side-effect Final) + alps-to-be-bear skill dogfooding + **Phase B Slice 1-5** (Psalm setup / ProdModule / Mass-assignment fix / env-gated entry point) |

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

- **旧パス:** `/Users/akihito/git/MyVendor.EcCube/` (削除済み)
- **新パス:** `/Users/akihito/git/ec-cube-alps/` (BEAR top) + `be/` (Be domain)
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
| リポジトリ | `/Users/akihito/git/ec-cube-alps` |
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
| リポジトリ | `/Users/akihito/git/ec-cube-alps` |
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
- **Pilot 3 完了** (`BeMart`, `doConfirmOrder` 1 件): **Branching** パターンの参照実装 + **Cascade Diamond 不成立の構造的発見**。`ConfirmOrderInput → PreOrderResolved (Being) → PurchaseFlowApplied (Being) → PaymentVerified (Being) → OrderConfirming (Being, 分岐点) → OrderConfirmed | OrderConfirmFailed (Branching Final)` の **4 段 Linear Cascade + 1 段 Branching**。`OrderConfirming` の `public PaymentSuccessCase|PaymentFailureCase $being` 型 discriminator により `BecomingType::match()` が Final を選択 (be-patterns `medical-triage` デモ準拠)。composer test 27/27 pass (Pilot 1 既存 8 + Pilot 2 既存 13 + Pilot 3 新規 6), 79 assertions, **0 notices** (Pilot 3 で 14 件の Semantic 変数を新規登録: `PreOrderId` / `PaymentMethodId` / `Subtotal` / `Tax` / `Total` / `Discount` / `Charge` / `DeliveryFee`(既存) / `AddPoint` / `UsePoint` / `PaymentTotal` / `Order` / `Totals` / `Result` / `Being`)。改訂履歴: 初版 Cascade Diamond 想定で `OrderConfirming` を apex (`#[Inject] PreOrderResolved $preOrder` 等) → Ray.Di `NoHint($preOrderId)` で全テスト失敗 → `#[Input]` 依存 Being を `#[Inject]` 経由でインスタンス化できないと判明 (Ray.Di は `#[Input]` 属性を理解しない) → 4 段 Linear Cascade に再構成。**構造的発見**: Cascade Diamond は「apex Moment が `#[Input]` を一切持たない」場合のみ成立する。EC-CUBE の典型的フロー (Input scalar から DB 引き当て → 並列に Service 呼び出し) は全て Linear Cascade に縮退する。Pilot 3 の Branching パターンは clean に動作し、be-framework の Branching 機構 (`BecomingType::match()` による型ベース選択) は実用検証済み。Skill 配置: `~/.claude/skills/alps-to-be-bear/` の `SKILL.md` / `decision-matrix.md` には「Cascade Diamond の成立条件 (apex が `#[Input]` 不要) と Linear Cascade への縮退ルール」追記が必要
- **Pilot 4 完了** (`BeMart`, `doRegisterCustomer` 1 件): **Multi-Reason Being** パターンの参照実装。`RegisterCustomerInput → CustomerRegistering (Being: 4 つの独立 Reason `EmailUniquenessChecker` / `CustomerIdGenerator` / `PasswordHasher` / `CustomerInitialPoint` を並列 `#[Inject]`) → CustomerRegistered (Final: 永続化のみ)` の **1 段 Multi-Reason Being + Final**。Diamond と区別される構造的特徴は「各 Reason の結果が他の Reason の入力にならない (互いに独立)」こと。Pilot 4 では fail-fast query (uniqueness check) + 3 つの pure derivation (id / hash / point) が同じ Being に同居しても Diamond にはならず、blog-publishing デモのバリエーションとして成立した。composer test 39/39 pass, 111 assertions, **0 notices** (Pilot 4 で 19 件の Semantic 変数を新規登録: client-input 15 件 `Email` / `Password` / `Name01` / `Name02` / `Kana01` / `Kana02` / `CompanyName` / `PhoneNumber` / `PostalCode` / `Pref` / `Addr01` / `Addr02` / `Birth` / `Sex` / `Job` + server-derived 4 件 `CustomerId` / `PasswordHash` / `InitialPoint` / `CustomerStatus`)。スコープ決定: email 検証 OFF 経路のみ (`customerStatus = 2` 固定)。検証 ON は将来の Branching pilot に譲る (Branching 機構自体は Pilot 3 で検証済み)。security レビュー反映: plaintext password を Being の non-public parameter + `#[SensitiveParameter]` で受ける (stack trace redact + 下流 public surface 不在) / `CustomerId` は `bin2hex(random_bytes(16))` (128-bit CSPRNG)。**次は (1) Complex Convergence (`insurance-claim` のような多分岐 + 多経路収束)、(2) admin 系の本番移植 (10 件規模) で skill を bake、(3) `~/.claude/skills/alps-to-be-bear/` の plugin marketplace 昇格判断**
- **Pilot 5 完了** (`BeMart`, `doCheckout` 1 件): **Complex Convergence / Multi-side-effect Final** パターンの参照実装。`CheckoutInput → CheckoutPrepared (Being: 値集約のみ) → CheckoutSettled (Being: payment 副作用 + 番号採番) → CheckoutCompleted (Final: 永続化 + メール送信 + cart-clear の 3 副作用を strict order で並走)` の **2 段 Being + Multi-side-effect Final**。loan-application デモ準拠。composer test 52/52 pass, 149 assertions, **0 notices** (Pilot 5 で server-derived Semantic 3 件 `OrderNo` / `OrderDate` / `PaymentDate` を MergedCart パターン (空 `#[Validate]` body) で追加。client-input 側は Pilot 3 の `PreOrderId` / `PaymentMethodId` を再利用)。skill gap: **G-14 (Ray.Di binding gotcha)** — `bind(Iface)->to(Impl)` は `bind(Impl)->in(SINGLETON)` を consult しない。state を hold する Fake (`FakeMailer` / `FakePaymentGateway` 等) は `$obj = new Fake(); bind(Iface)->toInstance($obj); bind(Impl)->toInstance($obj);` で両 binding を同一インスタンスに束ねる必要がある。AppModule.php に commented warning として記録。**G-15 (Multi-side-effect Final 判定基準)** / **G-16 (server-derived Semantic 登録漏れ)** も発見。スコープ決定: happy-path + 失敗時 422/404 のみ。Branching Final と補償処理 (Refund / InventoryRelease) は Phase B へ deferred。**次は Phase B (CSRF / rate-limit / bear-security / Psalm taint / env-gated ProdModule)、または admin 系の本番移植 10 件で skill を bake**

### 振り返り方

- **決定的要素と非決定的要素を分けて記録する。** Pilot 振り返りで「ミスがどちらの種類か」を意識する。
  - **決定的要素** (規則を覚えれば機械的に処理できる) = 命名 / Semantic 登録 / `#[Input]` ↔ Semantic 対応 / DomainException → HTTP code mapping / Ray.Di binding 規則 (G-14 等)。ミスは過失。**skill / レビュー subagent / HANDOVER の checklist で潰す層**
  - **非決定的要素** (ドメイン判断が要る) = Being を入れるか / 段数 / Reason 粒度 / Final に集約する副作用の数と順序 / DomainException の粒度 / Resource body の露出範囲。**人間の業務知識が責任を持つ層**。AI が勝手に決めると「思ったものと違うアプリ」になる (be-semantic Rule 5/6 と同じ警告)
- Pilot 5 の NOTICE 3 件取りこぼし (`OrderNo` / `OrderDate` / `PaymentDate` 未登録) は決定的要素の失敗。Ray.Di `toInstance` 発見 (G-14) も決定的要素 (一度知ればルール化できる)。2 段 Being にした判断は非決定的要素 (「payment 前に総額を確定したい」業務観点)

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
| リポジトリ | `/Users/akihito/git/ec-cube-alps` |
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

---

## Pilot 4 — `doRegisterCustomer` (Multi-Reason Being)

**目的:** Multi-Reason Being パターン (be-patterns `blog-publishing` 系) の参照実装。1 つの Being が複数の独立な `#[Inject]` Reason を持ち、互いに依存しない server-derived scalar を並列生成する構造の動作確認。

| 項目 | 値 |
|---|---|
| リポジトリ | `/Users/akihito/git/ec-cube-alps` |
| パターン | **Multi-Reason Being (1 段) + Final (永続化)**。`RegisterCustomerInput → CustomerRegistering (Being: 4 つの独立 Reason 並列起動) → CustomerRegistered (Final: 永続化のみ)`。Being は (1) `EmailUniquenessCheckerInterface` (uniqueness fail-fast), (2) `CustomerIdGeneratorInterface` (32-char opaque hex), (3) `PasswordHasherInterface` (bcrypt), (4) `CustomerInitialPointInterface` (welcome bonus) を `#[Inject]` で並列に呼ぶ。各 Reason の結果 (customerId / passwordHash / initialPoint / customerStatus=2 固定) は Being 自身の readonly プロパティに格納され、`#[Input]` 経由で Final に forward |
| テスト | 39 passed (Pilot 1 既存 8 + Pilot 2 既存 13 + Pilot 3 既存 6 + Pilot 4 新規 12), 111 assertions, **0 notices** (Pilot 4 で 19 件の Semantic 変数を新規登録: client-input 15 件 `Email` / `Password` / `Name01` / `Name02` / `Kana01` / `Kana02` / `CompanyName` / `PhoneNumber` / `PostalCode` / `Pref` / `Addr01` / `Addr02` / `Birth` / `Sex` / `Job` + server-derived 4 件 `CustomerId` / `PasswordHash` / `InitialPoint` / `CustomerStatus` を MergedCart パターン (空 `#[Validate]` body) で登録) |
| Skill 配置 | `~/.claude/skills/alps-to-be-bear/` (Multi-Reason Being テンプレ追加が必要) |
| スコープ決定 | **email 検証 OFF 経路のみ実装** (`customerStatus = 2` 固定)。検証 ON (provisional → email confirm → activate) は将来の Branching pilot で実装。理由: Branching 機構自体は Pilot 3 で検証済みのため、ここで再検証しても新たな知見は得られない |

**改訂履歴 (2026-05-18):**

1. ALPS 分析 → `doRegisterCustomer` (4 必須フィールド) + `CustomerRegistration` container (11 オプショナル) を Input にマップ。`CustomerRegistrationComplete.descriptor` = `[#goTop]` のみ → `#[Link(rel: 'goTop', ...)]` 1 件
2. パターン判定: 4 つの Reason が互いに独立で並列実行可能 → **Multi-Reason Being** (blog-publishing 準拠)。Diamond ではない (各 Reason の結果が他の Reason に流れない)
3. domain-review subagent → pass (findings 7 / blocking 0)。指摘で実装に反映: (a) `InitialPoint` / `CustomerStatus` の docblock 主張をバリデータと整合させる, (b) `FakeCustomerStorage::getByEmail()` を追加してテストの Reflection を除去
4. application-review subagent → pass (findings 0 / blocking 0)
5. security-review subagent → pass (findings 7 / blocking 0)。指摘で実装に反映: `CustomerRegistering::__construct` の `$password` を `public` から外し `#[SensitiveParameter]` 付与。plaintext が Being の public surface に露出せず、stack trace にも redact される

### Pilot 4 完了の判定基準 — 12 指標

| # | 指標 | Target | Actual | Note |
|---|---|---|---|---|
| 1 | composer test 全 pass | 100% | 100% | 39/39 pass (Pilot 1+2+3 既存 27 + Pilot 4 Domain 7 + Resource 5) |
| 2 | 意味ログ被覆 | 100% | 100% | DevBecoming が Input → Being → Final 全体を `var/log/bemart.json` に自動記録 |
| 3 | i18n 例外メッセージ | 100% | 100% | 全 DomainException (`EmailAlreadyRegisteredException` + Semantic format 系 15 件) に `#[Message(['en'=>..., 'ja'=>...])]` |
| 4 | 自己証明 assert | ≥1 | ≥1 | `Resource/Page/Entry.php:78` で `assert($final instanceof CustomerRegistered)`。Being 内では `EmailUniquenessCheckerInterface::ensureUnique()` が「重複なし」を自己証明 (例外で否定証明) |
| 5 | Semantic クラス数 | 19 | 19 | client-input 15 (`Email` / `Password` / `Name01` / `Name02` / `Kana01` / `Kana02` / `CompanyName` / `PhoneNumber` / `PostalCode` / `Pref` / `Addr01` / `Addr02` / `Birth` / `Sex` / `Job`) + server-derived 4 (`CustomerId` / `PasswordHash` / `InitialPoint` / `CustomerStatus`)。server-derived は空 `#[Validate]` body (composite Semantic と同じ「型断定のみ。値の契約は Service」パターン) |
| 6 | Reason 層共有ストア | Singleton 必須 | 該当 | `FakeCustomerStorage` を `Scope::SINGLETON` で bind (`AppModule:80`)。`FakeCustomerCommand` (write) と `FakeEmailUniquenessChecker` (read) が同一 storage を参照することで、Command の書き込みが同一 request 内で uniqueness check に見える |
| 7 | client-input / server-fetched 分離 | 2 シート | 2 シート | client-input (15 フィールド: email / password / name01 / name02 / 11 オプショナル) と server-derived (customerId / passwordHash / initialPoint / customerStatus) を Being 内で分離。Being の public surface に両方並ぶが、Final に流れる際は `passwordHash` のみ (plaintext password は `#[SensitiveParameter]` で promoted から外し、Being の public プロパティから除外) |
| 8 | LoC (Pilot 4 新規分) | 実測のみ | src 約 540 + tests 約 200 | Input 1 件 (54 LoC) + Being 1 件 (87 LoC) + Final 1 件 (97 LoC) + Resource 1 件 (112 LoC) + Semantic 19 件 (約 250 LoC 合計) + Exception 16 件 (約 200 LoC 合計) + Reason Entity 1 件 (40 LoC) + Reason Query/Service 12 件 (約 250 LoC 合計) + Test 200 LoC |
| 9 | Multi-Reason Being テスト | pass | pass | `testHappyPathPersistsAndReturnsServerScalars` で Being の 4 つの Reason 並列実行 (uniqueness OK + id生成 + hash + point) を統合検証 |
| 10 | 重複 email rejection | pass | pass | `testDuplicateEmailIsRejected` (Domain) / `testOnPostDuplicateEmailReturns409` (Resource) で `EmailAlreadyRegisteredException` → HTTP 409 マッピングを検証。alice@example.com は seed fixture に存在 |
| 11 | password hash 非露出 + 永続側 round-trip | pass | pass | `testPasswordIsHashedAndNotExposed`: `property_exists(CustomerRegistered::class, 'passwordHash') === false` で Final の surface に hash 不在を確認。`password_verify($plain, $persisted->passwordHash)` で永続側の round-trip も検証 |
| 12 | ALPS 整合性 (Rule 7) | 手動チェック合格 | 合格 | Final (`CustomerRegistered`) の public プロパティ (customerId / email / name01 / name02 / initialPoint / customerStatus) は `CustomerRegistrationComplete` container の状態と整合。Resource の `#[Link(rel: 'goTop')]` は `CustomerRegistrationComplete.descriptor = [#goTop]` に完全一致 |

### Pilot 4 で発見された skill gap

| ID | 内容 | 昇格先 |
|---|---|---|
| G-11 | **Multi-Reason Being の構造的特徴** — Diamond と区別するための判定基準: 「各 Reason の結果が他の Reason の入力にならず、互いに独立に並列実行できる」場合は Multi-Reason Being (be-patterns blog-publishing)、結果が他の Reason に流れる場合は Diamond/Cascade。Pilot 4 では (uniqueness check / id 生成 / password hash / initial point) が完全独立 | `SKILL.md` の「パターン判定フロー」に Multi-Reason Being の判定基準セクション追加 (TODO) |
| G-12 | **Multi-Reason Being では Reason の種類が混在しても良い** — Pilot 4 では「fail-fast query」(EmailUniquenessChecker) + 「pure derivation」(Hash / IdGen / PointService) が同じ Being に同居。blog-publishing の元パターンは pure derivation のみだが、guard を 1 つ混ぜても Diamond にはならない | `SKILL.md` の Multi-Reason Being テンプレに「guard + pure derivation の混在を許容」明記 (TODO) |
| G-13 | **plaintext password の `#[SensitiveParameter]` + 非 public 化** — 暗号化されるべき入力は Being の `__construct` パラメータで受け取り内部で hash 化、`public` promoted property にしない (`#[Input] #[SensitiveParameter] string $password` の形)。be-framework の cascade は public プロパティだけを下流に流すので、非 public にすれば自動的に Final に到達しない | `SKILL.md` の「機密データ取り扱い」セクション新規追加 (TODO) |

### Pilot 4 で更新したファイル

**`src/Module/AppModule.php`:**

- Pilot 4 用 6 件の bind 追加 (`FakeCustomerStorage` Singleton, `EmailUniquenessCheckerInterface`, `CustomerCommandInterface`, `PasswordHasherInterface`, `CustomerIdGeneratorInterface`, `CustomerInitialPointInterface`)

**Pilot 4 で新規追加した Be 層 (`be/src/`):**

- `Input/`: `RegisterCustomerInput.php`
- `Being/`: `CustomerRegistering.php`
- `Final/`: `CustomerRegistered.php`
- `Reason/Entity/`: `CustomerEntity.php`
- `Reason/Query/`: `CustomerCommandInterface.php`, `EmailUniquenessCheckerInterface.php`, `FakeCustomerStorage.php` (Singleton), `FakeCustomerCommand.php`, `FakeEmailUniquenessChecker.php`
- `Reason/Service/`: `PasswordHasherInterface.php` + `FakePasswordHasher.php`, `CustomerIdGeneratorInterface.php` + `FakeCustomerIdGenerator.php`, `CustomerInitialPointInterface.php` + `FakeCustomerInitialPoint.php`
- `Semantic/`: 19 件 (指標 #5)
- `Exception/`: 16 件 (15 FormatException + `EmailAlreadyRegisteredException`)
- `var/fake/customers.json` — 3 件の seed (alice / bob / carol。passwordHash は文法上 valid だが `password_verify` を通らないダミー文字列。`$comment` でダミーである旨を明記)

**Pilot 4 で新規追加した BEAR 層:**

- `src/Resource/Page/Entry.php` — `page://self/entry` の `onPost` (15 引数: 4 required + 11 nullable)
- `tests/Resource/EntryResourceTest.php` — 5 tests (happy / optional fields / 409 / 400 invalid email / 400 empty password)
- `be/tests/Domain/CustomerRegisteredTest.php` — 7 tests (happy / optional fields / hash 非露出 + round-trip / duplicate / invalid email / empty password / empty name01)

### Pilot 4 の security 観点 (security-review findings から記録)

- **Pilot スコープでの既知トレードオフ:**
  - email 検証 OFF 即 Active (`customerStatus = 2`) → アドレス squat 可能 (将来の Branching pilot で検証 ON 経路実装で解消)
  - 重複 email 409 + body `"The email is already registered."` は user enumeration oracle。検証 ON 経路では「silent 成功 + メール通知で重複を伝える」設計に切替予定
  - `AppModule` が `FakeCustomerStorage` を無条件 bind。本番デプロイ前に env-gated `ProdModule` 切替が必要 (Pilot 1-3 と同根の Phase B 案件)
- **Pilot 4 で実装した security guard:**
  - plaintext password は Being の non-public parameter + `#[SensitiveParameter]` → stack trace redact + 下流 public surface 不在
  - `CustomerId` は `bin2hex(random_bytes(16))` (128-bit CSPRNG opaque id) で EC-CUBE の sequential bigint と違って予測不能
  - Final (`CustomerRegistered`) の public surface は self-registration projection のみ (customerId / email / name01 / name02 / initialPoint / customerStatus)。`passwordHash` および 11 のオプショナル PII (kana / 住所 / 電話 / 生年月日 / 性別 / 職業 / 会社名) は永続側にのみ保持し、レスポンス body には含めない
- **Phase B (Pilot 終了後) に着手すべき項目:** CSRF / rate-limit / `bear/security` + Psalm taint / env-gated `ProdModule` / 検証 ON 経路 Branching

### Pilot 4 の参照ドキュメント

- **`~/git/be-patterns/demos/blog-publishing/`** — Multi-Reason Being の正解パターン (Pilot 4 設計の参照元)
- **`be/docs/be-adoption-evaluation.md`** — Pilot 1+2+3 の採用評価 (Pilot 4 で Multi-Reason Being を採用パターンに追加予定; TODO)

### Pilot 1+2+3+4 を通じた集計

- composer test: 39/39 pass, 111 assertions, **0 notices**
- Be domain LoC (累計): 約 2210 src + 約 714 tests
- 採用パターン: Linear/Minimal (Pilot 1), Cascade (Pilot 2), Linear Cascade + Branching (Pilot 3), **Multi-Reason Being (Pilot 4)**
- 未検証パターン: Complex Convergence (`insurance-claim` 系), 真の Cascade Diamond (apex が Input 不要なケース; EC-CUBE の通常 transition では出現しない可能性大)

## Pilot 5 — `doCheckout` (Complex Convergence / Multi-side-effect Final)

**目的:** 「Final で複数の独立 side-effect Reason を 1 constructor 内で収束させる」初回ピロット。Pilot 4 までは Final が単一 `CustomerCommand` のみ呼ぶ単純な形だったが、`doCheckout` は **OrderCommand.register + Mailer.sendOrderConfirmation + CartCommand.clearByPreOrderId** の 3 副作用を Final で並走させる。これが be-patterns `loan-application` で言う Complex Convergence の本質。

| 項目 | 値 |
|---|---|
| リポジトリ | `/Users/akihito/git/ec-cube-alps` |
| パターン | **Diamond-Cascade (2 段 Being + Multi-side-effect Final)**。`CheckoutInput → CheckoutPrepared (Being: pre-order 取得 + 金額確定) → CheckoutSettled (Being: 在庫引当 → 決済 → 注文番号発番、3 Reason の strict sequence) → CheckoutCompleted (Final: 注文永続化 + メール送信 + カートクリアの 3 副作用収束)`。失敗時は Branching Final を使わず Reason 内 DomainException → Resource 層 HTTP code マッピング (Pilot 3 で Branching は検証済みのため重複を避けた) |
| テスト | 52 passed (Pilot 1-4 既存 39 + Pilot 5 新規 13), 149 assertions, **0 notices** (Pilot 5 で server-derived Semantic 3 件 `OrderNo` / `OrderDate` / `PaymentDate` を MergedCart パターン (空 `#[Validate]` body) で追加。client-input 側は Pilot 3 の `PreOrderId` / `PaymentMethodId` を再利用) |
| Skill 配置 | `~/.claude/skills/alps-to-be-bear/` (Multi-side-effect Final テンプレ + Ray.Di `toInstance` 注意書きの追加が必要) |
| スコープ決定 | **happy-path + 失敗時 422/404 のみ実装**。Branching Final (`CheckoutFailed`) と補償処理 (Refund / InventoryRelease) は Phase B の決済セキュリティピロットへ deferred。理由: Branching 機構自体は Pilot 3 で検証済み。Pilot 5 の新規性は「Multi-side-effect Final の収束」に絞る |

**改訂履歴 (2026-05-18):**

1. ALPS 分析 → `doCheckout` (`paymentMethod` client-input) + `preOrder` 既存 → `CheckoutInput(preOrderId, paymentMethodId)` にマップ。`ShoppingComplete.descriptor` から `#[Link(rel: 'goTop')]` + `#[Link(rel: 'goCart')]` を導出
2. パターン判定: 3 Reason が strict sequence (inventory before payment before number-gen) → **Diamond-Cascade** (loan-application 準拠)。Pilot 4 の Multi-Reason Being (並列 Reason) と区別: Pilot 5 は順序依存 + Final で複数副作用
3. **Ray.Di binding gotcha 発見 (G-14)**: `bind(Iface)->to(Impl)` は `bind(Impl)->in(SINGLETON)` を consult しない。Iface の linked binding は singleton scope と独立に新 Impl を生成する。test 5 件中 2 件 (mailer/gateway captures) が初期実装で fail。`toInstance($obj)` パターンで両 binding を同一 object reference に固定 → 解決
4. domain-review subagent → pass (findings 5 / blocking 0)。指摘は全て non-blocking style nit (Final public surface が ALPS ShoppingComplete より広い / DateTimeImmutable 直生成 / FinalizedOrderEntity inline 生成 / FakeInventoryAllocator atomicity コメント / AppModule コメント評価)
5. application-review subagent → pass (findings 6 / blocking 0)。指摘は全て non-blocking (Code::UNPROCESSABLE_ENTITY 欠如の literal 422 / Location ヘッダ test 強化 / locale 'ja' ハードコード / paymentMethodId 不正系 400 test / 4xx で orderNo 漏れていない assert / `@var` PHPDoc 追加)
6. security-review subagent → pass (findings 13 / blocking 0)。**重要な Phase B 課題を多数発見** (詳細は下記)

### Pilot 5 完了の判定基準 — 13 指標

| # | 指標 | Target | Actual | Note |
|---|---|---|---|---|
| 1 | composer test 全 pass | 100% | 100% | 52/52 pass (Pilot 1-4 既存 39 + Pilot 5 Domain 8 + Resource 5) |
| 2 | 意味ログ被覆 | 100% | 100% | DevBecoming が CheckoutInput → CheckoutPrepared → CheckoutSettled → CheckoutCompleted 全体を `var/log/bemart.json` に自動記録 |
| 3 | i18n 例外メッセージ | 100% | 100% | 全 DomainException (`InsufficientStockException` / `PaymentDeclinedException` 新規 + `PreOrderNotFoundException` Pilot 3 既存) に `#[Message(['en'=>..., 'ja'=>...])]` |
| 4 | 自己証明 assert | ≥1 | ≥1 | `Resource/Page/Shopping/Checkout.php` で `assert($final instanceof CheckoutCompleted)`。CheckoutSettled の existence proof は「stock allocated AND payment captured AND order number issued」、CheckoutCompleted の existence proof は「persisted AND mail sent AND cart cleared」 |
| 5 | Semantic クラス数 | 0 (新規) | 0 | Pilot 3 の `PreOrderId` / `PaymentMethodId` を共有。重複作成を避けた |
| 6 | Reason 層共有ストア | toInstance 必須 | 該当 | `FakeInventoryAllocator` / `FakePaymentGateway` / `FakeMailer` を `toInstance($obj)` で Iface + Impl の両 binding に固定 (`AppModule:121-129`)。`FakeFinalizedOrderStorage` は Becoming chain から直接見られないため通常の `in(SINGLETON)` で十分 (Pilot 4 の FakeCustomerStorage と同パターン) |
| 7 | client-input / server-fetched / side-effect 分離 | 3 シート | 3 シート | client-input (preOrderId / paymentMethodId), server-fetched (OrderEntity from OrderQuery, PurchaseFlowResult totals from PurchaseFlow), side-effect (InventoryAllocator / PaymentGateway / OrderNumberGenerator / OrderCommand / Mailer / CartCommand) を 3 段階に明確分離 |
| 8 | LoC (Pilot 5 新規分) | 実測のみ | src 約 670 + tests 約 240 | Input 1 件 (29 LoC) + Being 2 件 (71 + 71 LoC) + Final 1 件 (98 LoC) + Resource 1 件 (95 LoC) + Exception 2 件 (約 40 LoC) + Reason Entity 1 件 (61 LoC) + Reason Service Iface 4 + Fake 4 (約 260 LoC) + Reason Query Iface 1 + Fake 2 (約 110 LoC) + Test Domain 154 LoC + Resource 87 LoC |
| 9 | Multi-side-effect Final テスト | pass | pass | `testPersistsFinalizedOrder` + `testSendsExactlyOneConfirmationMail` + `testCapturesPaymentExactlyOnceWithCorrectAmount` + `testClearsSourceCart` で 4 副作用 (persist / mail / payment capture / cart clear) を独立検証。Final の 3 Reason すべてが期待回数だけ呼ばれたことを保証 |
| 10 | DomainException → HTTP 422/404 マッピング | pass | pass | `testUnknownPreOrderRejected` (Domain `PreOrderNotFoundException`) + `testOnPostUnknownPreOrderReturns404` (Resource 404), `testInsufficientStockRejected` (Domain) + `testOnPostInsufficientStockReturns422` (Resource 422), `testPaymentDeclinedRejected` (Domain) + `testOnPostPaymentDeclinedReturns422` (Resource 422) で 3 失敗経路を Domain + Resource 両層で検証 |
| 11 | side-effect strict sequence | pass | pass | CheckoutSettled で inventory.allocate → gateway.checkout → numbers.generate の順 (在庫を確保してから決済、無駄な番号発番をしない)。CheckoutCompleted で orderCommand.register → mailer.send → cartCommand.clear の順 (record of truth が先、cart cleanup は最後)。`testInsufficientStockRejected` は stock 不足時に gateway が呼ばれないことを captures count で検証可能 |
| 12 | ALPS 整合性 (Rule 7) | 手動チェック合格 | 概ね合格 (1 件 non-blocking finding) | Final (`CheckoutCompleted`) の public プロパティ (orderNo / completeMessage / customerId / total / paymentTotal / addPoint / orderStatus / orderDate / paymentDate) は `ShoppingComplete` 表示状態 + audit info として整合。domain-review で「ShoppingComplete descriptor は orderNo / completeMessage のみ。残り 7 件は EC-CUBE Order 表示に必要な audit info — Resource 層で projection 縮小を検討」の finding を non-blocking 扱い |
| 13 | Ray.Di binding correctness | pass | pass | 上記指標 #6。Pilot 5 で発見した toInstance パターンを AppModule のコメント (lines 102-119) に詳述。今後の Pilot は Iface + Impl 両参照を要する Fake で同パターンを踏襲 |

### Pilot 5 で発見された skill gap

| ID | 内容 | 昇格先 |
|---|---|---|
| G-14 | **Ray.Di `bind(Iface)->to(Impl)` は `bind(Impl)->in(SINGLETON)` を consult しない** — Iface 経由の resolution と Impl 経由の resolution が独立した instance を作る。Pilot 5 の `FakeMailer` / `FakePaymentGateway` (state を hold する Fake) は Becoming chain が `MailerInterface` で resolve、test introspection が `FakeMailer` で resolve するため、両 binding が同一 instance を返す必要がある。Solution: `$obj = new Fake(); $this->bind(Iface)->toInstance($obj); $this->bind(Impl)->toInstance($obj);` (Storage 経由で state を分離するパターンも可だが、refactor 量が大きい) | `SKILL.md` の「Ray.Di binding patterns for Fakes」セクション新規追加 (TODO)。Pilot 1-4 で使ってきた `bind(Iface)->to(Impl); bind(Impl)->in(SINGLETON)` パターンは「Impl が state-less で test が直接参照しない」場合に限る旨を明記 |
| G-15 | **Multi-side-effect Final (Complex Convergence) の判定基準** — Pilot 4 までの Final は単一 Command のみ呼んだが、Pilot 5 の Final は OrderCommand + Mailer + CartCommand の 3 副作用を 1 constructor で並走させる。判定基準: 「副作用同士に順序依存があり (record of truth が先 / cleanup が後)、互いの結果に依存しない」場合は Multi-side-effect Final として 1 つの Final で収束させて良い。3 副作用以上で各副作用が他の副作用の結果を要する場合は Cascade Final (中間 Final を挟む) を検討 | `SKILL.md` の「パターン判定フロー」に Multi-side-effect Final の項目追加 (TODO) |
| G-16 | **Failure mode: side-effect ordering と partial-commit window** — Pilot 5 で `gateway.checkout()` が成功した後に `numbers.generate()` が throw (現状の Fake では起きないが Phase 2 で起きうる) すると、顧客は課金されたが orderNo 未発番 → FinalizedOrder 未永続化 → カートも残る状態に陥る。同様に Final で `orderCommand.register()` が成功して `mailer.send()` が throw すると永続化 + 課金完了 + メール無し + カート残存。Solution (Phase B): (a) Final の Mailer は契約上 non-throwing (失敗時は internal log + swallow)、(b) CartCommand 失敗も swallow (注文は durable なので stale cart は許容)、(c) CheckoutSettled は Phase 2 で DB transaction + register_shutdown_function gateway hook に書き換え | `SKILL.md` の「side-effect ordering と例外契約」セクション (TODO) |

### Pilot 5 で更新したファイル

**`src/Module/AppModule.php`:**

- Pilot 5 用 9 件の bind 追加: `FakeFinalizedOrderStorage` Singleton, `FakeInventoryAllocator` / `FakePaymentGateway` / `FakeMailer` の **toInstance による Iface + Impl 両 binding**, `OrderNumberGeneratorInterface` / `OrderCommandInterface` の通常 link binding
- 11-19 行のコメントブロックで Ray.Di toInstance パターンを文書化 (将来 pilot 用の breadcrumb)

**Pilot 5 で新規追加した Be 層 (`be/src/`):**

- `Input/`: `CheckoutInput.php`
- `Being/`: `CheckoutPrepared.php`, `CheckoutSettled.php`
- `Final/`: `CheckoutCompleted.php`
- `Exception/`: `InsufficientStockException.php`, `PaymentDeclinedException.php` (`PreOrderNotFoundException` は Pilot 3 既存)
- `Reason/Entity/`: `FinalizedOrderEntity.php` (16 fields + STATUS_NEW=1 constant)
- `Reason/Service/`: `InventoryAllocatorInterface.php` + `FakeInventoryAllocator.php` (inventory.json 読み + atomic 引当), `PaymentGatewayInterface.php` + `FakePaymentGateway.php` (paymentMethodId===9 で決済失敗シミュレーション), `OrderNumberGeneratorInterface.php` + `FakeOrderNumberGenerator.php` (`bin2hex(random_bytes(16))` で 32-hex), `MailerInterface.php` + `FakeMailer.php` (sent 配列で送信記録 / non-throwing 契約)
- `Reason/Query/`: `OrderCommandInterface.php` + `FakeOrderCommand.php` (FakeFinalizedOrderStorage delegate), `FakeFinalizedOrderStorage.php` (Singleton, orderNo + preOrderId 両ルックアップ)
- `Reason/Query/CartCommandInterface.php` — **既存に `clearByPreOrderId(string $preOrderId): void` 追加**
- `Reason/Query/FakeCartCommand.php` — 同上 implementation
- `Reason/Query/FakeCartStorage.php` — `removeByPreOrderId()` / `getByPreOrderId()` 追加
- `var/fake/orders.json` — Pilot 5 用 3 件 pre-order 追加: `aaaa…aaaa` (happy, paymentMethodId=2, sample-001×1@1500, delivery=600 → total 2250), `bbbb…bbbb` (OOS, oos-pilot5-tightstock×3 だが inventory=1), `cccc…cccc` (declined, paymentMethodId=9)
- `var/fake/carts.json` — Pilot 5 happy-path 用 `session-checkout-pilot5` cart 追加 (clearByPreOrderId テストのため)
- `var/fake/inventory.json` — 新規。`{sample-001: 10, preorder-2026-spring-bag: 5, oos-pilot5-tightstock: 1}`
- `docs/pilot5/alps-analyze.md` — 新規 (Pilot 5 設計の handover ドキュメント)

**Pilot 5 で新規追加した BEAR 層:**

- `src/Resource/Page/Shopping/Checkout.php` — `page://self/shopping/checkout` の `onPost(string $preOrderId, int $paymentMethodId)` (4 例外を 400/404/422/422 にマップ + success 9 fields projection)
- `tests/Resource/CheckoutResourceTest.php` — 5 tests (happy 201 / 404 unknown / 422 OOS / 422 declined / 400 malformed)
- `be/tests/Domain/CheckoutCompletedTest.php` — 8 tests (happy / persist / mail送信 1 回 / payment capture 1 回 + amount=2250 / cart clear / 404 unknown / 422 OOS / 422 declined)

### Pilot 5 の security 観点 (security-review findings から記録)

**Phase B / Phase 2 に着手すべき項目 (security-review 13 finding の要約):**

- **AUTHZ 欠如**: Resource は preOrderId 所有者 (customerId) を session と照合していない。誰でも他人の pre-order を確定できる。Phase B: `CheckoutPrepared` に `SessionGuard` Reason 追加 → `OrderEntity.customerId !== session.customerId` で `UnauthorizedPreOrderAccessException` throw
- **MASS-ASSIGNMENT**: client supplied `paymentMethodId` を `OrderEntity.paymentMethodId` と照合せず gateway に forward。決済方法のすり替えが可能。Phase B fix: `CheckoutInput` から `paymentMethodId` を削除し、`CheckoutPrepared` が `$order->paymentMethodId` を採用する形に変更
- **IDEMPOTENCY**: pre-order row を「消費済み」状態に遷移させていない。同じ preOrderId の double-submit で **二重課金 + 二重 FinalizedOrder + 在庫二重引当** が発生しうる。現状唯一の緩和策は最後の `clearByPreOrderId` だが redirect を follow しない並列 / replay 攻撃には無力。Phase B fix: 実 DB では `OrderCommand.register()` を `SELECT FOR UPDATE` または `dtb_order.pre_order_id` UNIQUE 制約 + `INSERT ON CONFLICT` で atomic にする
- **PARTIAL-COMMIT WINDOW**: gateway.checkout 成功後の throw / orderCommand.register 後の mailer throw / cartCommand throw で各種の partial state が発生しうる。Phase B fix: Mailer / CartCommand を契約上 non-throwing にして Final 側で try/catch + log + swallow。CheckoutSettled は Phase 2 で DB transaction + `register_shutdown_function` 化
- **PAYMENT-FIRST ORDERING 正しい**: inventory.allocate → gateway.checkout の順を守っているため OOS 時に課金しない設計は OK (no action required)
- **PRE-ORDER ID ENTROPY (cross-pilot)**: Pilot 5 では mint しないが、将来の `doShopping` pilot で実装する `PreOrderIdGenerator` は **必ず `bin2hex(random_bytes(20))` CSPRNG** にすること (上記 AUTHZ 欠如と組み合わさると enumeration 攻撃が成立する)
- **EXCEPTION MESSAGE 漏洩**: `InsufficientStockException` (productCode + counts), `PaymentDeclinedException` (preOrderId + paymentMethodId + amount) が SemanticLogger 経由でログに残る。Resource は固定文字列を返すので HTTP body には漏れない。Phase B fix: preOrderId はログでは前 8 桁に redact、または sensitive channel に分離
- **RESPONSE BODY 漏洩**: success body に `customerId` (32-hex opaque) が含まれる。session で既知のため client 側で必要ない。Phase B fix: response body から `customerId` を drop
- **INPUT VALIDATION 完全**: `onPost(string $preOrderId, int $paymentMethodId)` 以外の body フィールドは BEAR が signature reject。over-posting 不可
- **CSRF / REPLAY**: Pilot 5 は CSRF guard 無し。Phase B で標準 CSRF middleware を投入予定。Pilot 5 は CSRF retrofit を阻害しない設計 (no GET fallback / no JSONP)
- **AppModule toInstance パターン (Phase 2)**: 本番 PaymentGateway / Mailer は stateless (delegate to external) のため `bind(Iface)->to(Impl)->in(SINGLETON)` で十分。ただし state を hold する production wrapper (例: idempotency cache, connection pool, metrics counter) を追加する場合は Pilot 5 の toInstance パターンに準じる必要あり
- **SemanticLogger 機密漏洩 (Phase B)**: DevSemanticLogger が CheckoutInput / OrderEntity (customerId + items + prices) / PurchaseFlowResult / FinalizedOrderEntity を平文で `var/log/bemart.json` に書く。Phase B fix: ProdModule で per-class allowlist + redacted logger に切替。**Card PAN / CVV は Be 層に到達しない設計** (PaymentGatewayInterface は paymentMethodId のみ受け取る) なので PCI 対象は order metadata に限定される
- **FakePaymentGateway 規約**: paymentMethodId===9 を「決済失敗」マジックナンバーに使用。fixture コメントに記録済み。Phase 2 ProdModule は同マジックナンバーを継承しないこと

### Pilot 5 の参照ドキュメント

- **`~/git/be-patterns/demos/loan-application/`** — Diamond-Cascade パターンの正解 (Pilot 5 設計の参照元)
- **`be/docs/pilot5/alps-analyze.md`** — Pilot 5 の設計 handover (descriptor → pattern → reasons → BEAR mapping)
- **`HANDOVER.md` Pilot 4 の skill gap G-11/G-12/G-13** — Multi-Reason Being との混同を避けるための参考

### Pilot 1+2+3+4+5 を通じた集計

- composer test: 52/52 pass, 149 assertions, **0 notices** (Pilot 5 follow-up commit `3ad9821` で server-derived Semantic 3 件 `OrderNo` / `OrderDate` / `PaymentDate` を MergedCart パターンで追加)
- Be domain LoC (累計): 約 2880 src + 約 954 tests
- 採用パターン: Linear/Minimal (Pilot 1), Cascade (Pilot 2), Linear Cascade + Branching (Pilot 3), Multi-Reason Being (Pilot 4), **Diamond-Cascade + Multi-side-effect Final (Pilot 5)**
- 未検証パターン: 真の Cascade Diamond (apex が Input 不要なケース; EC-CUBE の通常 transition では出現しない可能性大), Complex Convergence の更に深いネスト (insurance-claim の Multi-stage Multi-Reason)
- **Phase A 完了** (Pilot 1-5)。Phase B は Psalm taint 設定から着手 (下記参照)

---

## Phase B — Security / Hardening Slice 1: Psalm Setup

**目的:** 静的解析を Phase A コードに後付けで導入し、Phase B 以降の判定基盤を整える。**決定的要素側に置ける security チェック** を 1 つ確立する。

### 完了した作業

- **Psalm 6.16 を `composer require --dev` で導入** (`vimeo/psalm`)
- **`psalm.xml` を errorLevel=3 + 主要な MixedX / Unused 系を suppress で起動** (Pilot 1-5 コードに後付けで適用するため)
- **baseline 生成**: `psalm-baseline.xml` に既存 11 件をベースライン化 (`MissingOverrideAttribute` 9 件 + `InvalidOperand` 2 件 in `FakePurchaseFlow` の tax/point 計算 int/float 混合)
- **composer scripts 追加**:
  - `composer psalm` — 通常解析 (baseline 適用、exit 0)
  - `composer psalm-taint` — taint 解析 (`--ignore-baseline` でモード切替時の `UnusedBaselineEntry` を抑制、exit 0)

### Slice 1 の現状認識 (重要)

**`composer psalm-taint` は exit 0 だが、これは「Phase A コードベースで Psalm の標準 taint mechanism が検出する flow が存在しない」ことを意味する。「PII flow が無い」ことではない。**

Phase A の構造的事実:

- **DB クエリ**: 全部 Fake (in-memory) → SQL sink が存在しない
- **shell exec**: なし
- **明示的な `log()` 呼び出し**: なし (SemanticLogger は framework 内で自動)
- **HTML/string concat**: JSON response のみ
- **Psalm が標準で知る source**: `$_GET` / `$_POST` / `$_REQUEST` 等の superglobal。BEAR の `#[Input]` や Be の Input クラスは未知

→ Psalm 標準では BEAR/Be の **HTTP user input → SemanticLogger 平文出力** の flow を捕捉できない。security-review が指摘した PII 漏洩 (customerId / items / prices が `var/log/bemart.json` に平文出力) は taint analysis では検出されない。

### Slice 2 候補 (Psalm taint を BEAR/Be に対応させる)

未実施。実施候補:

- **オプション A**: BEAR Resource の `onGet` / `onPost` parameters に `@psalm-taint-source` annotation を手動追加 (workflow prompt で自動化)
- **オプション B**: `SemanticLogger::log()` 系の write API に `@psalm-taint-sink` annotation
- **オプション C**: Psalm plugin で `#[Input]` attribute を taint source として自動認識 (be-framework 側に PR を出す筋)

判断: Slice 1 では Phase B の **足場を作る** ことに集中。アノテーション戦略は Phase B Slice 2 で別途決定。

### Slice 2 以降の Phase B 項目 (security-review の累積 finding から)

未着手:

- **CSRF token** — 全 onPost 系 Resource に適用 (Pilot 3-5 の 3 endpoint が対象)
- **rate-limit** — `/shopping/checkout` 等の sensitive endpoint
- **bear-security-setup skill** の適用 — 認証 / 認可基盤
- **env-gated ProdModule** — `SemanticLogger` を prod で per-class allowlist + redacted 出力に切替
- **AUTHZ check** — `CheckoutPrepared` の `SessionGuard` Reason (Pilot 5 security-review F-1)
- **mass-assignment fix** — `CheckoutInput::paymentMethodId` を削除し `OrderEntity::paymentMethodId` を採用 (Pilot 5 F-2)
- **idempotency** — `dtb_order.pre_order_id` UNIQUE 制約 + 実 DB で `SELECT FOR UPDATE` (Pilot 5 F-3)

### Slice 1 の振り返り (決定的/非決定的)

- **決定的側で確立できたもの**: Psalm の存在 / baseline / composer 起動口。これらは「一度作れば後続に効く」道具立て
- **非決定的側で残ったもの**: BEAR/Be の taint source/sink 戦略 (3 案あり、業務的に何を「機密」と扱うかの判断と絡む)。Phase B Slice 2 でユーザー判断を仰ぐ

---

## Phase B — Slice 3: env-gated ProdModule (DevBecoming PII leak fix)

**目的:** Pilot 5 security-review F-12 (DevSemanticLogger が `var/log/bemart.json` に customerId / 明細 / 価格 / paymentTotal を平文出力) を、prod context で **構造的に** 塞ぐ。AppModule(dev default) は触らず、ProdModule で override する形を取る。

### 完了した作業

- **`src/Module/ProdLoggingOverrideModule.php` 新規** — `BecomingInterface` を **DevBecoming wrapper なし** で素の `Be\Framework\Becoming` に rebind。`SemanticLoggerInterface` も `DevSemanticLogger` ではなく素の `Koriym\SemanticLogger\SemanticLogger` に rebind。In-memory の semantic trace は引き続き framework が収集するが、**ファイルには永続化されない**
- **`src/Module/ProdModule.php` 新規** — `AbstractAppModule` を継承し、`AppModule` を install した上で `ProdLoggingOverrideModule` で override。エントリポイント (将来の `bin/app.php` / `public/index.php`) は `APP_CONTEXT=prod` で ProdModule を選ぶ想定だが、現状の Pilot は CLI smoke (`bin/smoke_phase6.php`) のみのため env switching の bin 改修は **deferred**
- **`tests/Module/ProdModuleTest.php` 新規** — 4 ケース:
  1. ProdModule context で `BecomingInterface` を resolve → `Becoming::class` (not `DevBecoming`)
  2. ProdModule context で `SemanticLoggerInterface` を resolve → `SemanticLogger::class` (not `DevSemanticLogger`)
  3. ProdModule context で実際に `/shopping/checkout` を叩いて 201 を返した後、`var/log/bemart.json` が **存在しない** ことを assert
  4. **負の対照**: AppModule (dev) で同じリクエストを叩くと `var/log/bemart.json` が **存在する** ことを assert (この対照が壊れたら #3 が vacuous になるので必須)

### 確認結果

- **`composer test`**: 56/56 pass, 154 assertions, **0 notices** (Phase A 既存 52 + Phase B Slice 3 新規 4)
- **`composer psalm`**: exit 0 (baseline 11 件のまま)
- **`composer psalm-taint`**: exit 0

### Slice 3 の振り返り (決定的/非決定的)

- **決定的側**: 「prod context は `BecomingInterface` を `Becoming` に直結し `DevBecoming` を挟まない」は機械的判断。`ProdLoggingOverrideModule` の 2 行で完結
- **半決定的**: 「prod logger は何を出力すべきか」は本来非決定 (allowlist? redaction? per-endpoint? off?)。Slice 3 では **最も保守的** な「完全 off」を選択。Slice 4 以降で需要が出たら redacted logger を追加検討
- **未着手 (Slice 4 以降)**: env-driven entry point (`APP_CONTEXT=prod` の bin 改修)、構造化 prod logger (audit trail 用途で何かしらは要る)、Fake → 実 DB の差し替え用 `ProdReasonModule`

### 今後の Phase B Slice (推奨順)

| 候補 | 性質 | 一行コメント |
|---|---|---|
| ~~**Slice 4**: Mass-assignment fix (Pilot 5 F-2)~~ | 決定的 | ✅ 完了 (下記 Slice 4 セクション参照) |
| ~~**Slice 5**: env-gated entry point~~ | 決定的 | ✅ 完了 (下記 Slice 5 セクション参照) |
| Slice 6: AUTHZ check (Pilot 5 F-1) | 非決定的 | `CheckoutPrepared` に `SessionGuard` Reason 追加。session 設計と絡む |
| Slice 7: bear-security-setup | 半決定的 | 認証 / 認可基盤の skill 適用 |
| Slice 8: CSRF token | 非決定的 | 全 onPost endpoint に手を入れる |
| Slice 9: Taint annotation (Slice 1 の続き) | 非決定的 | Psalm を BEAR/Be 用に annotate

---

## Phase B — Slice 4: Mass-assignment fix (Pilot 5 F-2)

**目的:** Pilot 5 security-review F-2 (MASS-ASSIGNMENT) の構造的修正。

### Problem (F-2 抜粋)

> client supplied `paymentMethodId` を `OrderEntity.paymentMethodId` と照合せず gateway に forward。決済方法のすり替えが可能。

`doCheckout` の前段 (`doProceedToConfirm`) で確定した決済方法を、confirm 段階で client が別の `paymentMethodId` (例: 安価 / 未認証の方式) に差し替えても通っていた。

### Fix

**「client から `paymentMethodId` を受け取らない」** に変更。サーバ側で永続化済みの `OrderEntity.paymentMethodId` を採用する。

| 変更ファイル | 変更内容 |
|---|---|
| `be/src/Input/CheckoutInput.php` | コンストラクタから `public int $paymentMethodId` を削除。docblock に「client から受け取らない理由 (mass-assignment 防止)」を明記 |
| `be/src/Being/CheckoutPrepared.php` | `#[Input] public int $paymentMethodId` 削除 |
| `be/src/Being/CheckoutSettled.php` | `#[Input] public int $paymentMethodId` 削除。`$gateway->checkout($preOrderId, $paymentMethodId, ...)` を `$gateway->checkout($preOrderId, $order->paymentMethodId, ...)` に変更 |
| `src/Resource/Page/Shopping/Checkout.php` | `onPost(string $preOrderId, int $paymentMethodId)` → `onPost(string $preOrderId)`。docblock に F-2 言及追加 |
| `tests/Resource/CheckoutResourceTest.php` | 全 POST body から `'paymentMethodId' => N` を削除。**新規テスト**: `testClientSuppliedPaymentMethodIdIsIgnored` — paymentMethodId=9 (本来 decline を起こす値) を client から injection しても 201 を返すこと (ResourceObject が `paymentMethodId` を Input にバインドしないため、key は黙って捨てられる) を assert |
| `be/tests/Domain/CheckoutCompletedTest.php` | 全 `new CheckoutInput(..., paymentMethodId: N)` から `paymentMethodId` を削除 |
| `tests/Module/ProdModuleTest.php` | POST body から `paymentMethodId` を削除 |

`be/src/Final/CheckoutCompleted.php` は元から `$order->paymentMethodId` を使っていたため変更不要。

### Test Suite の偶然の幸運

`be/var/fake/orders.json` の fixture が **元々 `preOrderId → paymentMethodId` を 1 対 1 に定めていた**:

- `aaaa...` → `paymentMethodId: 2` (success)
- `bbbb...` → `paymentMethodId: 1` (insufficient stock test 用)
- `cccc...` → `paymentMethodId: 9` (gateway decline test 用)

つまり以前から **「テストで指定していた `paymentMethodId` は OrderEntity と一致していた」**。Slice 4 は invariant を「契約として明文化」しただけ。テストの assertion 値 (total 2250, captures[paymentMethodId]=2 等) は一切変更不要。

### 動作確認

- **`composer test`**: 57/57 pass, 155 assertions, **0 notices** (Phase A 既存 52 + Slice 3 新規 4 + Slice 4 新規 1)
- **`composer psalm`**: errors なし (`MissingOverrideAttribute` 等の info 24 件のみ; baseline 内)
- **`composer psalm-taint`**: errors なし

### Slice 4 の振り返り (決定的/非決定的)

- **決定的だった**: 「client supplied PII を信頼しない」は OWASP / mass-assignment の textbook ルール。修正方針は一意 (= client field を削除して server-side source に置換)
- **設計上の含意**: Be Framework の `#[Input]` は **「client から受け取る値の宣言」** であり、これを削るだけで攻撃面が物理的に消える。BEAR ResourceObject の `onPost` シグネチャからも引数が消えるため、ResourceObject のバインダーが余計な key を黙って捨ててくれる (= `Mass-assignment` 攻撃が **型シグネチャ違反として表現される** ことが構造的に保証される)
- **対称的な気づき**: Slice 3 (logger 切替) が「**書く側の構造的封じ込め**」だったのに対し、Slice 4 は「**読む側の構造的封じ込め**」。両方ともコードを書き換えずに OFF にできる位置に security 境界がある (= AAA Module / Input 型)。これが Be Framework + BEAR.Sunday を「決定的要素として置ける」と言える根拠の 1 つ

### 残課題 (Slice 4 由来)

- **`PaymentMethodFactoryInterface` 経由の表示用 ID**: `doProceedToConfirm` 段階で client が支払い方法を **選択する** UI は別途必要。Pilot 5 fixture は scope 外なので未確認だが、その段階での paymentMethodId 取得経路 (session? form post? AJAX?) は Slice 6 (AUTHZ) と合わせて再設計する
- **double-submit / replay 攻撃**: Slice 4 は payment method の固定化のみ。同じ preOrderId を 2 回送ると依然 2 回 gateway を呼べる (F-3 IDEMPOTENCY)。これは Slice 6+ で `dtb_order.pre_order_id` UNIQUE + `INSERT ON CONFLICT` に再設計する

---

## Phase B — Slice 5: env-gated entry point

**目的:** Slice 3 で作った `ProdModule` を **実際に起動経路から呼ばせる**。それまでは `ProdModuleTest` が in-process で binding を検証しているだけで、CLI / HTTP の entry point は存在しなかった (Pilot 1-5 は全部 PHPUnit 経由)。

### Why now (Slice 順序の判断)

Slice 3 で「ProdModule は AppModule の安全な置き換え」を **証明済み**。Slice 4 で「Input 側の構造防御」も導入した。あとは **どこからどうやって `APP_CONTEXT=prod` をフリップさせるか** を実装するだけ。これが無いと「prod の binding はあるが、それを呼ぶ場所が無い」状態。Slice 6+ (AUTHZ / CSRF) も entry point 越しに振る舞いを観測したくなる場面が出るので、ここで足場を作る。

### 追加ファイル (3)

| ファイル | 役割 |
|---|---|
| [bin/app.php](bin/app.php) | CLI entry。`APP_CONTEXT` → Module class (`{name}Module`, ucfirst) を resolve し、`Injector` で `ResourceInterface` を解決。`page://self/...` URI と JSON body 引数を受け取って resource を実行、結果を JSON で stdout に出力。失敗時は exit 2 |
| [public/index.php](public/index.php) | HTTP entry。同じ context resolution を行い、`REQUEST_METHOD` + `REQUEST_URI` + JSON body から resource 呼び出しに変換、JSON で response 返す。Slice 5 では minimum viable (router / AOP / cache 全部なし)。本番運用には不足だが「prod context が起動経路で発動する」ことの実証としては十分 |
| [tests/EntryPoint/AppEntryPointTest.php](tests/EntryPoint/AppEntryPointTest.php) | `bin/app.php` を subprocess (`exec()`) で起動して APP_CONTEXT が **本当に効いている** ことを検証。`prod` で log 未書き出し、`app` で書き出し、未定義 context で exit 2、URI 欠落で exit 2 の 4 ケース |

### Context resolution 規約

```text
APP_CONTEXT=prod  → MyVendor\BeMart\Module\ProdModule
APP_CONTEXT=app   → MyVendor\BeMart\Module\AppModule
APP_CONTEXT 未設定 → AppModule (dev default)
```

実装は `ucfirst($context) . 'Module'` で class 名を生成 → `class_exists` + `is_subclass_of(AbstractModule)` で安全性チェック → `new $class($meta)` で instantiate。BEAR\Package\Injector の `Module` クラス (vendor/bear/package/src/Module.php) と同じ convention を採用したが、`Injector::getInstance` 自体は使わなかった (理由: `AppInterface` binding を要求するが、Pilot 5 までの module は AppInterface を bind していない。Slice 5 の scope を逸脱するので深追いせず、`Ray\Di\Injector` を直接使う簡易版で済ませた)。

### 検証

```bash
$ rm -f var/log/bemart.json
$ APP_CONTEXT=app php bin/app.php 'page://self/shopping/checkout' '{"preOrderId":"aaaa00000000000000000000000000000000aaaa"}'
{ "context": "app", "uri": "...", "code": 201, "body": { ... } }
$ ls var/log/bemart.json
-rw-r--r--  ...  bemart.json    # ← 書かれた

$ rm -f var/log/bemart.json
$ APP_CONTEXT=prod php bin/app.php 'page://self/shopping/checkout' '...'
{ "context": "prod", ... }
$ ls var/log/bemart.json
ls: ... No such file or directory   # ← 書かれていない
```

- **`composer test`**: 61/61 pass, 166 assertions, **0 notices** (Slice 4 まで 57 + entry-point 新規 4)
- **`composer psalm`**: errors なし
- **`composer psalm-taint`**: errors なし

### 設計上の判断と注釈

- **HTTP server を一切設定していない**: `public/index.php` は WebRouter / Compiler / cache 一切なし。本番デプロイ前に Slice 7 (bear-security-setup) と合わせて router + 認証 middleware を埋める前提
- **Psalm scope 外**: `bin/` / `public/` は `psalm.xml` の `projectFiles` に含めていない。理由は (a) 元々 procedural script は `$_SERVER` / `$_POST` で `mixed` が多発し noise になりがち、(b) entry point の logic 自体は 50 行程度で目視確認可能。Slice 9 (taint annotation) で `$_POST` を taint source として正しく扱うときに合わせて scope に入れる
- **テストは subprocess fork**: `exec()` で別 PHP プロセスを起動。理由は (a) `getenv` の親プロセスとの汚染を避ける、(b) DI cache が共有されない、(c) 「本当に起動経路から呼べる」ことの証明。代償として 4 件のテストで約 1 秒余計にかかる
- **`bin/smoke_phase6.php`**: 残置。これは Pilot 2 (`AddCartItemInput`) 用の手元 smoke runner で、`bin/app.php` の汎用版とは別物。削除はしない (Pilot 2 を再現する手っ取り早い手段として有用)

### Slice 5 の振り返り (決定的/非決定的)

- **決定的だった**: 「env 変数で Module を switch する」 のメカニズム自体は **完全に固定パターン**。BEAR\Package\Module の class-name convention をコピーするだけ。Slice 1 (Psalm setup) より更に意思決定が少ない
- **判断が要ったポイント** (= 半決定的):
  - **どこまで作るか**: 完全 router 付きの HTTP front controller か、それとも env switch + 最低限の dispatch か。Slice 5 の目的「prod がアクセス経路から発動する」 を満たすだけなら後者で十分 → 後者を選択
  - **Injector の API 選択**: `BEAR\Package\Injector::getInstance` (Compiler 付き) か `Ray\Di\Injector` (直接) か。前者は `AppInterface` binding を要求するため、Slice 5 でやるには Pilot 全部に AppInterface を入れる必要があった → 後者で済ませた
- **積み残しの自覚**: Slice 5 を経ても **「production-grade な entry point」 ではない**。Router (URI → Resource binding) / Compiler (eager AOP weaving) / Logger sink / Error formatter のどれも欠けている。Slice 7 (bear-security-setup) / 将来の Slice (Phase C 相当の deployment 系) で順次埋める

### 次の Slice (Slice 6 以降)

| 候補 | 性質 | 一行コメント |
|---|---|---|
| **Slice 6**: AUTHZ check (Pilot 5 F-1) | 非決定的 | `CheckoutPrepared` に `SessionGuard` Reason を追加。session の保持方法 (cookie / JWT / server-side store) は未決定なのでユーザー判断が必要 |
| Slice 7: bear-security-setup | 半決定的 | 認証 / 認可基盤の skill 適用。Slice 6 と一部統合検討 |
| Slice 8: CSRF token | 非決定的 | 全 `onPost` endpoint に token middleware。token 配布手段 (session vs sync) は要判断 |
| Slice 9: Taint annotation | 非決定的 | Psalm `@psalm-taint-*` の BEAR `#[Input]` 対応。`$_POST` を taint source として正しく扱う |

---

## Phase B — Slice 6: AUTHZ check (Pilot 5 F-1)

**目的:** Pilot 5 security-review が指摘した **F-1 (AUTHZ 欠如)** を閉じる。Resource は preOrderId を受け取るだけで「その pre-order が requester のものか」を一切確認していなかった。`?preOrderId=...` を URL から拾えば誰でも他人の確定を実行できた状態。

### Why now (Slice 順序の判断)

Slice 5 で entry point が動いたので、Slice 6 は「session という非決定的な要素を **どこに** 持つか」だけ決めれば実装に入れる。CSRF (Slice 8) / Taint (Slice 9) より前にやる理由は: AUTHZ は **business invariant** (誰がオーナーか) で、CSRF は **transport invariant** (この request が本物か)。前者が無いと後者があっても他人の pre-order は確定できてしまう。順序として AUTHZ → CSRF が正しい。

### 追加 / 変更ファイル

| ファイル | 役割 | 種別 |
|---|---|---|
| [be/src/Reason/Service/SessionInterface.php](be/src/Reason/Service/SessionInterface.php) | `customerId(): string\|null` のみを持つ最小 contract。Be Reason として注入される | 新規 |
| [be/src/Reason/Service/FakeSession.php](be/src/Reason/Service/FakeSession.php) | constructor で渡された customerId をそのまま返す。`null` を渡せば anonymous | 新規 |
| [be/src/Exception/UnauthorizedPreOrderAccessException.php](be/src/Exception/UnauthorizedPreOrderAccessException.php) | `DomainException` 派生 + `#[Message]` で en/ja を持つ。Resource は HTTP 403 にマップ | 新規 |
| [be/src/Being/CheckoutPrepared.php](be/src/Being/CheckoutPrepared.php) | `SessionInterface` を `#[Inject]` し、`$session->customerId() !== $order->customerId` で reject。**順序: 存在 → AUTHZ → PurchaseFlow**。理由は本文参照 | 変更 |
| [src/Resource/Page/Shopping/Checkout.php](src/Resource/Page/Shopping/Checkout.php) | `UnauthorizedPreOrderAccessException` を `Code::FORBIDDEN` (403) にマップ。docblock 更新 | 変更 |
| [src/Module/AppModule.php](src/Module/AppModule.php) | `bind(SessionInterface)->toInstance(new FakeSession('customer-001'))`。**default を logged-in customer-001 に固定** することで既存 Pilot テストを破壊しない | 変更 |
| [be/tests/Domain/CheckoutCompletedTest.php](be/tests/Domain/CheckoutCompletedTest.php) | `rebindSession()` helper を追加。`cccc…` (customer-002) を扱う既存テスト + 2 件の AUTHZ 失敗テストを追加 | 変更 |
| [tests/Resource/CheckoutResourceTest.php](tests/Resource/CheckoutResourceTest.php) | 同じパターン。403 を返す 2 件追加 | 変更 |

### 設計上の判断

#### Session の保持方法 — 「Reason として注入」 を選択

候補は 3 つあった:

1. **`$_SESSION` global を直接読む procedural helper** — Be / BEAR どちらの DI 哲学にも合わない。テストで session を切替えるのも難しい。却下
2. **`SessionInterface` を `#[Inject]` する Reason** — Be Reason として扱う。Fake は memory、本番は `$_SESSION` 読みアダプタを後付け差し替え。**採用**
3. **Resource 層で読んで Input に乗せる** — `CheckoutInput` に `customerId` を足す。クライアントが指定できる field を増やすことになり Slice 4 で潰した mass-assignment と矛盾。却下

#### 順序: 存在 → AUTHZ → PurchaseFlow

```text
1. OrderQuery::byPreOrderId(...)
   → null なら PreOrderNotFoundException (404)
2. $session->customerId() !== $order->customerId
   → throw UnauthorizedPreOrderAccessException (403)
3. PurchaseFlow::apply($order)
   → totals 計算 (失敗時 InsufficientStock 422)
```

3 つの理由:

- **(存在) が先**: もし AUTHZ を先にしてしまうと、anonymous user が `?preOrderId=<random>` で総当たりした場合に「403 = 存在する」「404 = 存在しない」の **存在オラクル** ができてしまう。存在チェックを先にすれば、anonymous は常に「404 か 403 か」のどちらか (= 同じ 4xx の塊) しか観測できない。**ただし完璧ではない**: customer-001 がログイン中に他人の preOrderId を試した場合は「404 (存在しない)」 vs 「403 (存在するが他人のもの)」 で区別できる。これは F-1 の scope 外 (timing attack 系) として残置。Phase B でログイン中の存在チェックも 404 に統一する判断は要相談
- **(AUTHZ) が PurchaseFlow より先**: PurchaseFlow は (将来) 在庫引当の前計算 / 配送料計算を含む。AUTHZ を後にすると認可されない request でも compute が走る。DoS amplification を避ける
- **PurchaseFlow が最後**: ここまで来れば requester は本物のオーナー。失敗は純粋に business reason (在庫無し等)

#### Default を customer-001 に固定した理由

`AppModule` の default `SessionInterface` を `new FakeSession('customer-001')` に固定。これにより **既存 Pilot 1-5 のテスト (`aaaa…` を使う happy-path 系) は 1 行も変更不要**。AUTHZ をテストしたいテストだけが override する。

代替案として「default を anonymous (`null`) にする」 もあった。これは「security by default」 として正しい設計だが、Pilot 1-5 の 50+ テストすべてに rebindSession を入れる必要があった。Slice 6 の scope を肥大化させるので却下。本番では Slice 7 (bear-security-setup) で `$_SESSION` アダプタに差し替えるので、AppModule の default 値はあくまで test fixture。

#### `Code::FORBIDDEN` (403) と `Code::NOT_FOUND` (404) の差

OWASP の AUTHZ guide に従い、**「リソースが存在することは知っているが、あなたには見せない」** を 403、**「リソースは存在しない (またはあなたに見せない、を兼ねる)」** を 404 とした。Pilot 5 では:

- 404: `OrderQuery` が null → 物理的に存在しない
- 403: 存在するが `customerId` が一致しない → AUTHZ 違反

これは設計上の選択であり、上記の **timing attack** 注釈の通り完璧な存在隠蔽ではない。Slice 6 では「明確な区別」 を取り、Slice 7+ で「存在隠蔽の強化」 を別途検討する。

#### Test 戦略: `rebindSession()` helper

PHPUnit の `setUp()` で default customer-001 を bind、各テストで必要なら `rebindSession(...)` で injector を作り直す。理由:

- **Pilot 5 既存テスト** (`aaaa…` / `bbbb…` = customer-001) は default で動くまま → 既存テスト 0 行変更
- **payment-decline (`cccc…` = customer-002)** は 1 行 `rebindSession('customer-002')` を足すだけ
- **AUTHZ 失敗テスト** は `rebindSession('customer-999')` / `rebindSession(null)` を足す

新規 AUTHZ テスト 4 件:

| テスト名 | session | preOrderId | 期待結果 |
|---|---|---|---|
| `testForeignCustomerRejectedWithAuthz` | `customer-999` | `aaaa…` (owner: customer-001) | `UnauthorizedPreOrderAccessException` + **no side-effect** (gateway captures / mailer sent が 0 件のまま) |
| `testAnonymousSessionRejectedWithAuthz` | `null` | `aaaa…` | 同上 |
| `testOnPostForeignCustomerReturns403` | `customer-999` | `aaaa…` | HTTP 403 |
| `testOnPostAnonymousReturns403` | `null` | `aaaa…` | HTTP 403 |

**no side-effect の検証** が重要。AUTHZ は `CheckoutPrepared` (Stage 1) で reject されるので、`CheckoutSettled` (Stage 2: payment capture + order number 採番) と `CheckoutCompleted` (Final: persist + mail + cart-clear) には到達しない。`$gateway->captures()` と `$mailer->sent()` を直接 introspect して確認。

### 検証

```bash
$ composer test
OK (65 tests, 172 assertions)   # Slice 5 から +4 (新規 AUTHZ 4 件)

$ composer psalm
No errors found!

$ composer psalm-taint
No errors found!
```

`cccc…` を使う既存テスト 2 件 (`testPaymentDeclinedRejected` / `testOnPostPaymentDeclinedReturns422`) は AUTHZ が先に走るようになったため一度 fail したが、`rebindSession('customer-002')` の追加で復旧。これは **新規 AUTHZ が想定通り効いている** 証跡でもある (orders.json で `cccc…` が customer-002 にひもづいているのは Pilot 3 時の元データ)。

### Slice 6 の振り返り (決定的/非決定的)

- **非決定的だった部分**: 「session の保持方法」 と 「default value」。前者は **Reason として注入** で決着、後者は **test 互換性を優先して customer-001 固定**。どちらも Slice 7 (bear-security-setup) で **本番アダプタ + 真の default は anonymous** に置き換える前提
- **決定的だった部分**: 順序 (存在 → AUTHZ → PurchaseFlow) は OWASP / DoS 観点から論理的に **強制された**。Resource → 403 のマッピングも HTTP semantics から自明
- **積み残し**:
  - **本番 SessionInterface アダプタ** (`$_SESSION` 読み or JWT decode or cookie-based) は Slice 7 で実装。`SessionInterface` という contract だけ確定させて、実装は差し替え可能にしてある
  - **存在オラクル**: ログイン中 user が他人の preOrderId を試したときの 404 vs 403 区別は残置。Slice 8+ で「ログイン中も 404 に統一」 を検討
  - **logged-out user の HTTP 401 vs 403**: anonymous (`customerId()===null`) は技術的には「authentication が無い」ので 401 が正解。Slice 6 では「ownership 不一致」 として 403 で統一した。Slice 7 で AuthN/AuthZ を分離する時に 401 / 403 を分ける可能性あり

### 次の Slice (Slice 7 以降)

| 候補 | 性質 | 一行コメント |
|---|---|---|
| **Slice 7**: bear-security-setup + 本番 Session アダプタ | 半決定的 | `bear-security-setup` skill 適用 + `$_SESSION` (または JWT) → `SessionInterface` の本番実装。`ProdModule` 側で差し替え |
| Slice 8: CSRF token | 非決定的 | 全 `onPost` endpoint に CSRF guard。token 配布手段 (session-bound vs sync token) は要判断 |
| Slice 9: Taint annotation | 非決定的 | Psalm `@psalm-taint-*` を Pilot 1-5 + Slice 6 にも適用。`$_POST` / `$_SESSION` を source、`gateway::charge` 等を sink としてグラフを引く |
| (追加検討) Slice 10: 存在オラクル軽減 | 非決定的 | 既存 user に対する 404 / 403 統一。**user-facing UX を悪化させる** ので、threat model と合わせてユーザー判断が必要 |

---

## Phase B — Slice 7: 本番 Session アダプタ (EC-CUBE bridge)

**目的:** Slice 6 で導入した `SessionInterface` の **本番実装** を `ProdModule` 配下に与える。Slice 6 までは `FakeSession('customer-001')` が全 context で動いており、本番でも全員 customer-001 扱い = AUTHZ 素通り状態だった。Slice 7 で `SymfonySessionAdapter` を `ProdModule` の override として bind し、本番経路では **実 session が空なら anonymous** になる。

### Why now (Slice 順序の判断)

Slice 6 (AUTHZ) は test 上だけで完全に機能していた。本番では `ProdModule` が `AppModule` を install し、`AppModule` 内の `bind(SessionInterface)->toInstance(new FakeSession('customer-001'))` が活きてしまうため AUTHZ は無効化される。**Slice 7 が無いと Slice 6 は飾り**。それより前に CSRF (Slice 8) を入れても、AUTHZ が無効なら他人の pre-order を確定できる事実は変わらない。順序は AUTHZ binding 本物化 → CSRF → 残り、で確定。

### ユーザー判断 (Slice 7 着手前に必要だった)

「Session の保持方法」 3 択を提示:

| option | dep | EC-CUBE 側変更 |
|---|---|---|
| A. Cookie (BEAR 独自) | symfony/http-foundation 必要 | 不要 (BEAR 側で完結) |
| B. JWT | firebase/php-jwt 等 | 不要 (BEAR 側で完結) |
| **C. 既存 EC-CUBE の Symfony Session 継承** ← 採用 | BEAR 側 dep ゼロ | 5 行の EventListener 追加 |

C を選択した理由 (ユーザー判断): 移行期は EC-CUBE と BEAR が並存する。「BEAR 側に独自 session を建てる」 = 二重ログイン or 二重 session sync が必要 = 移行コスト大。EC-CUBE が既に管理している session を共有し、BEAR は**読むだけ**にする方が現実的。

C の内部にもう 1 サブ判断: 「customerId をどこから読むか」:

- **A1. Flat key `$_SESSION['customer_id']` を読む** ← 採用
- A2. Symfony Security の serialized Token (`$_SESSION['_security_main']`) を unserialize → symfony/security-core 依存追加
- A3. Sidecar HTTP `/api/me` → 1 RTT 追加

A1 (Recommended) を選択: **BEAR 側に Symfony Security 依存をゼロにする** ことを優先。EC-CUBE 側に 5 行の EventListener (login 時に flat key へ mirror、logout 時に unset) を入れれば完結。`Symfony Security` の internal class signature 変更にも引きずられない (これは Symfony 5 → 6 → 7 で実際に起きてる) 。

### 追加 / 変更ファイル

| ファイル | 役割 | 種別 |
|---|---|---|
| [src/Auth/SymfonySessionAdapter.php](src/Auth/SymfonySessionAdapter.php) | `SessionInterface` の本番実装。$_SESSION → flat key 読み + CLI fallback (env var) + headers-sent 防御 | 新規 |
| [src/Module/ProdSessionOverrideModule.php](src/Module/ProdSessionOverrideModule.php) | `bind(SessionInterface)->to(SymfonySessionAdapter)`。ProdModule から override として install | 新規 |
| [src/Module/ProdModule.php](src/Module/ProdModule.php) | `$this->override(new ProdSessionOverrideModule())` を 1 行追加 | 変更 |
| [tests/Auth/SymfonySessionAdapterTest.php](tests/Auth/SymfonySessionAdapterTest.php) | adapter 単体: 7 ケース (session 読み / anonymous / CLI env fallback / session 優先 / 空文字 / 非 string / custom key) | 新規 |
| [tests/Module/ProdModuleTest.php](tests/Module/ProdModuleTest.php) | 既存 happy-path テストに `$_SESSION['customer_id']='customer-001'` 注入 + tearDown でクリア | 変更 |
| [tests/EntryPoint/AppEntryPointTest.php](tests/EntryPoint/AppEntryPointTest.php) | prod CLI に `BEMART_CLI_CUSTOMER_ID` env 注入 + anonymous CLI が 403 になることの negative test 追加 | 変更 |

### EC-CUBE 側 contract (Slice 7 では実装しない)

BEAR 側は **読むだけ**。EC-CUBE 側に以下のミラーを入れることでブリッジが完成する:

```php
// EC-CUBE 側 EventListener (例: app/Customize/EventListener/SessionMirrorListener.php)
public function onLoginSuccess(InteractiveLoginEvent $event): void
{
    $customer = $event->getAuthenticationToken()->getUser();
    if ($customer instanceof \Eccube\Entity\Customer) {
        // BEAR 側 SymfonySessionAdapter::CUSTOMER_ID_KEY と一致させる
        $event->getRequest()->getSession()->set('customer_id', (string) $customer->getId());
    }
}

public function onLogout(LogoutEvent $event): void
{
    $event->getRequest()->getSession()->remove('customer_id');
}
```

- Cookie 名: EC-CUBE 4.x のデフォルト `ECCUBE` (= `SymfonySessionAdapter::COOKIE_NAME`)
- Flat key: `customer_id` (= `SymfonySessionAdapter::CUSTOMER_ID_KEY`)
- BEAR が同じドメイン / path で動作することが前提 (= cookie 共有可能なデプロイ構成)

この EC-CUBE 側ハーネスは EC-CUBE 移植 (Phase 2 以降) で実装される。Slice 7 は contract だけ確定させる。

### CLI fallback (運用スクリプト用)

`bin/app.php` から ProdModule を呼ぶ場合、SAPI=cli で HTTP session が無い。`BEMART_CLI_CUSTOMER_ID` env var を設定すれば、adapter はそれを authenticated customerId として返す。env が無ければ anonymous = AUTHZ で 403。subprocess test の `testProdContextRejectsAnonymousCli` がこの contract を pin している。

⚠️ **この env var は authentication をバイパスする**。HTTP context で絶対に設定してはいけない (adapter 側で `PHP_SAPI === 'cli'` をガードしているが、`php-fpm` 内で誤って setenv される可能性を残さないこと)。

### 検証

```bash
$ composer test
OK (73 tests, 181 assertions)   # Slice 6 から +8 (adapter unit 7 + entry-point anon 1)

$ composer psalm
No errors found!

$ composer psalm-taint
No errors found!
```

### 設計上の判断

#### 「Symfony Session 継承」 の正確な意味

このリポジトリには EC-CUBE 本体も `symfony/http-foundation` も入っていない (= 入っているのは `symfony/cache`, `symfony/console` の transitive dep のみ)。 そのため Slice 7 で言う 「継承」 は **「実 EC-CUBE のセッション形式を物理的に共有する契約」** であって 「Symfony コンポーネントを依存に入れる」 ことではない。Adapter は **PHP 標準 `$_SESSION`** だけを使う。

これにより:

- BEAR 側 dep ゼロ (Slice 7 は composer.json を 1 行も触らない)
- EC-CUBE の Symfony version を BEAR が知らなくていい
- 単体テストが Symfony 依存を持たない (`$_SESSION` を poke するだけ)

#### `Override` を 2 段重ねる構造

```text
ProdModule (configure)
  ├── install(AppModule)                          ← 既存 dev binding 一式
  ├── override(ProdLoggingOverrideModule)         ← Slice 3: PII log fix
  └── override(ProdSessionOverrideModule)         ← Slice 7: Session binding 切替
```

`ProdLoggingOverrideModule` (Slice 3) のパターンをそのまま踏襲。**1 つの override module = 1 つの責務** にすることで、Slice 4-9 で増えていく差し替え bindings (CSRF / rate-limit / 本番 DB / 本番 Mailer / …) を独立にレビューできる。`ProdModule.configure()` は将来 5-6 行の `override` 呼び出しが並ぶことを許容する設計。

#### Adapter の resolution 優先順位 (session 優先 → CLI env fallback)

```php
1. $_SESSION['customer_id'] が string で空でない → return
2. PHP_SAPI === 'cli' && BEMART_CLI_CUSTOMER_ID env が空でない → return
3. else null (anonymous)
```

`$_SESSION` を **CLI でも優先** する。理由は PHPUnit 内 `ProdModuleTest` が `$_SESSION` を poke する pattern を取るため。CLI 環境でも session 値を honor することで、test harness と運用スクリプトで同じ adapter を使い続けられる。本番 HTTP context では env var は無視されるので「session が無いのに env で詐称できる」 攻撃ベクタは生まれない (= prerequisite: `PHP_SAPI === 'cli'` で `$_SESSION` が空、という二重条件)。

#### `bin/app.php` の exit code

`bin/app.php` は `4xx → exit 1` で動く (Slice 5 時に決定)。Slice 7 で追加した `testProdContextRejectsAnonymousCli` も exit=1 を期待する。これで「subprocess が走った (exit 2 = script error ではない)」 かつ 「resource が 403 で reject した」 を別々に assert できる。

### Slice 7 の振り返り (決定的/非決定的)

- **決定的だった**: 
  - 「Slice 6 binding の本番化が必要」 という議論の余地ゼロ
  - `ProdLoggingOverrideModule` のパターンを踏襲する (1 module = 1 責務)
  - PHP `$_SESSION` を使う (Symfony dep を入れない)
- **非決定的だった (= ユーザー判断要)**:
  - 「Symfony Session 継承」 の正確な意味 (A. cookie / B. JWT / C. EC-CUBE 共有) — C 採用
  - 「customerId をどこから読むか」 (Flat key / Symfony Token / Sidecar API) — Flat key 採用
- **積み残し**:
  - EC-CUBE 側の EventListener 実装 (Phase 2 で EC-CUBE 移植時に対応)
  - **本番 HTTP server** — Slice 5 の `public/index.php` はまだ router 無しの最小実装。real-world deploy 前に WebRouter + Compiler + error formatter が必要
  - **Logout 動線** — adapter は read-only。BEAR 側に明示 logout endpoint は無い。Phase 2 で EC-CUBE の /mypage/logout を経由するルートを確認
  - **Session security 強化** — adapter は `use_strict_mode` / `cookie_httponly` / `cookie_samesite=Lax` を session_start に渡しているが、`cookie_secure` (HTTPS only) は php.ini 任せ。本番デプロイ時に `cookie_secure=1` を必ず設定すること

### 次の Slice (Slice 8 以降)

| 候補 | 性質 | 一行コメント |
|---|---|---|
| **Slice 8**: CSRF token | 非決定的 | 全 `onPost` に CSRF guard。Slice 7 の session 上に token を持たせる方式 (session-bound) か、stateless synchronizer token か |
| Slice 9: Taint annotation | 半決定的 | Psalm `@psalm-taint-*` を Pilot 1-5 全体に適用。`$_POST` / `$_SESSION` source → `gateway::charge` 等 sink の graph |
| (追加検討) Slice 10: 存在オラクル軽減 | 非決定的 | 既存 user に対する 404 / 403 統一 |
| (追加検討) bear-security-setup skill 適用 | skill bake | Slice 6-7 で手動構築した AUTHZ 基盤を skill 化するレビュー。`bear-skills` 側に knowledge を回す |
