# HANDOVER

EC-CUBE 4.3 の ALPS プロファイル構築と、Be Framework + BEAR.Sunday への移植 Pilot の進行記録。次の AI セッション (および人間レビュアー) への引き継ぎメモ。

| メタ | 値 |
|---|---|
| Last updated | 2026-05-17 |
| Latest session | pilot-2-doaddcartitem-dogfooding-opus-4.7 |
| Scope | ALPS プロファイル + Be/BEAR 移植 Pilot (Pilot 1 goProduct / Pilot 2 doAddCartItem) + alps-to-be-bear skill dogfooding |

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

## Pilot 2 — `doAddCartItem` (Cascade Diamond)

**目的:** Cascade Diamond パターンの初参照実装 + `alps-to-be-bear` skill の dogfooding。Phase ごとに 2 commit (実装 + skill 反映) のリズムで知見を即時定着。

| 項目 | 値 |
|---|---|
| リポジトリ | `~/git/ec-cube-alps` |
| スコープ | `doAddCartItem` を Cascade Diamond として実装。Stock 検査 → SaleLimit 検査 → SaleType 判定 → Delivery 計算 → CartItem merge-Price の 5 cascade phase を 1 つの Final (`CartItemAdded`) に集約 |
| テスト | 21 passed (Pilot 1 既存 8 + Pilot 2 新規 13), 51 assertions |
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
| 8 | Diamond `#[Inject]` 数 | (原計画 5) | **3** | 原計画は 5 だったが、Cascade 5 phase でも独立データソースは 3 (`ProductClassQuery` + `CartQuery` + `CartCommand`) で十分。Pilot 2 で「cascade 段数 ≠ `#[Inject]` 数」が判明 (SKILL.md Pilot 2 不変条件 #6 に昇格)。**新基準は「`#[Inject]` 数 = 独立データソース数」** |
| 9 | 数量自動調整テスト | pass | pass | `testStockShortageAutoAdjusts`: sample-003 stock=3 で qty=5 → 自動補正 3, totalPrice=13500 (4500×3) |
| 10 | CartItem merge テスト | pass | pass | `testSameSkuAddedTwiceMergesQuantity`: 同 SKU を 2+3 で追加 → totalPrice=5000 (1000×5) |
| 11 | cartKey 分離テスト | pass | pass | `testDifferentSaleTypeIsolatesCart`: 通常販売 (`cartKey=session-prefix-1_1`) と予約販売 (`cartKey=session-prefix-1_2`) で cart が分離 |
| 12 | ALPS 整合性 (Rule 7) | 手動チェック合格 | 合格 (refine 後) | Final = transition outcome envelope と判明。container 状態属性 (`cartKey`, `totalPrice`, `deliveryFeeTotal`, `saleTypeName`) は ALPS Cart container に存在。transition outcome (`requestedQuantity`, `adjustedQuantity`, `unitPrice`) は ALPS container に不要 (操作結果)。Rule 7 を「container 状態属性 ⊆ ALPS descriptor」へ refine し、transition outcome 分離を `decision-matrix.md §5` に明示 |

### Pilot 2 で発生した skill gap (G-1〜G-7) — すべて昇格済み

| ID | 内容 | 昇格先 |
|---|---|---|
| G-1 | Cascade 段数 ≠ `#[Inject]` 数 | `SKILL.md #6` / `decision-matrix.md §4.E` |
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
- **Pilot 2 完了** (`BeMart` (旧 MyVendor.EcCube), `doAddCartItem` 1 件): Cascade Diamond パターンの初参照実装。composer test 21/21 pass、12 指標すべて達成 (詳細: 「Pilot 2 完了の判定基準 — 12 指標」)。重要な不変条件の発見: cascade 段数 ≠ `#[Inject]` 数 (5 phase でも 3 Inject で十分)、Query/Command 共有時は Singleton ストア必須、Final = transition outcome envelope (container 状態属性 ⊆ ALPS / transition outcome は ALPS 不要)、`SemanticVariableException` は Resource で明示 catch (Pilot 段階)、`BEAR\Resource\Code` は CONFLICT 未定義で整数リテラル運用。skill `~/.claude/skills/alps-to-be-bear/` に SKILL.md (Pilot 2 不変条件 #6-12 追記) + decision-matrix.md (§5 Rule 7 refine + §6 Pilot 2 専用チェック) として昇格済み。`.claude/prompts/domain-implement.md` と `application-implement.md` も Pilot 2 反映済み。**次は Pilot 3 として Branching パターン (例: `doCreateCustomer`) を別タスクで検証し、skill 完成度 70% を確認する想定**
