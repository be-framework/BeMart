# スキーマ／契約 品質負債監査（schema-quality-audit）

240 本のレスポンススキーマ全件分類に加え、書き込み SQL／ドメイン層の **sentinel-data** スキャンと、テストの **silent-skip** スキャンを統合した負債レポート。緑のまま素通りした契約・テストを洗い出し、優先度つきで是正順を示す。

## 0. 是正ステータス（2026-06-24 完了）

本監査が挙げた負債は全て是正済み。

- **バグクラス（object\|array＋string-items の業務データ袋）= 全7インスタンスを型付き契約化**: storefront `get-shopping-confirm` の `customer`、admin の `get-admin-order.customer` / `get-admin-order-edit.order` / `get-admin-category-edit.category` / `get-admin-delivery-delivery.delivery` / `get-admin-payment-payment.payment` / `get-admin-product-edit.product`。いずれも実 HTTP（guest/member/admin）で 200 を確認し、teeth-probe でランタイム強制（不一致→500）を実証。
- **render-only ノード 61本（`form`/`searchForm`）= ドキュメント化**: Ray\WebFormModule の AbstractForm オブジェクトで JSON は無視・業務データに serialize されない（型付けは 500）。意図的に緩い契約として description/$comment に明記。
- **回帰ガード**: `tests/Hypermedia/SchemaContractQualityTest.php` ＋ `docs/eccube-spec-coverage/schema-render-only-baseline.json`（双方向レジストリ・ゲート）。新たな opaque object\|array ノードは「型付き契約化」または「render-only として baseline 登録」を強制。現在データ袋 0・baseline 61。
- **silent-skip 3本**＝fail-loud 化、**sentinel** ＝ `order_register.sql` 1件に封じ込み済み（[セクション3・4](#3-sentinel-data-スキャンスコープは封じ込み済み)）。
- 動的 key→label マップ（`orderStatusOptions` / `productStatusOptions`）はバグクラスではない（対象外）。

以降は是正前のスナップショット（参考）。

## 1. 総計（TOTALS）

| 区分 | 件数 | 説明 |
|---|---|---|
| **opaque-bag**（不透明バッグ） | **67 / 240** | 業務オブジェクトを運ぶと称しながら、`properties` 空・`items` が裸の `string`・`type` が `object\|array\|null` 等で**業務フィールドを 1 つも固定していない**ノードを含む |
| **typed**（型付き） | 119 / 240 | 業務列が property として明示され型・制約が付いている |
| **trivial**（自明） | 54 / 240 | プロパティ 2 個以下、またはリンク／リダイレクト（`href`/`method`/`transitionId`/`csrfToken` 等）のみの薄いペイロード |

- opaque-bag 67 本のうち **HIGH severity（顧客向け = checkout / shopping / mypage）は 8 本**。残り 59 本は MED（ほぼ全て admin の `form` ノード）。
- HIGH の本質的な穴は 2 種：(a) `get-shopping-confirm.json` の `customer` が **裸の string 配列**で氏名・住所を 1 つも固定していない、(b) shopping/mypage 各フォームページの `form` ノードが **`["object","array","null"]` × `properties` 空 × `required` 未収載**。どちらも「会員の実情報を運ぶはずの口」が型なしで通っている。

## 2. プライオリティ・パンチリスト（HIGH severity opaque-bag）

顧客 checkout / order / customer を **最優先**。とくに `get-shopping-confirm.json` の `customer`。

| ファイル | 過少定義ノード | required かつ型付けすべき業務フィールド |
|---|---|---|
| **`var/json_schema/get-shopping-confirm.json`** | `customer`（`type: ["array","null","object"]`、`items.type:"string"`＝**裸の文字列配列**、しかも `required` 未収載） | `name01`/`name02`、`kana01`/`kana02`、`email`、`phoneNumber`、`postalCode`、`pref`、`addr01`/`addr02` を **object property として固定**し `customer` を `required` に追加。注文確認画面の宛名・送付先がここを通る最重要ノード。`$defs` に `email`/`postalCode`/`phoneNumber`/`pref` は既に存在するので参照するだけでよい |
| `var/json_schema/get-shopping.json` | `form`（`["object","array","null"]`、`properties` 空、`required` 未収載） | 配送・支払い選択フォームの業務フィールド（`deliveryId`/`paymentMethodId`/`shippingDeliveryTime` 等）を property 化し `form` を `required` に |
| `var/json_schema/get-shopping-login.json` | `form`（同上） | `loginEmail`/`loginPassword`（+ `csrfToken`）を固定 |
| `var/json_schema/get-shopping-non-member.json` | `form`（同上） | 非会員購入の氏名・カナ・住所・`email`・`phoneNumber` を固定（confirm の `customer` と同じ語彙） |
| `var/json_schema/get-shopping-shipping-edit.json` | `form`（同上） | お届け先の氏名・カナ・`postalCode`/`pref`/住所・`phoneNumber` を固定 |
| `var/json_schema/get-shopping-shipping-multiple-edit.json` | `form`（同上） | 複数配送先の明細（商品×数量×お届け先）を配列要素として固定 |
| `var/json_schema/get-mypage-address.json` | `form`（同上） | お届け先住所録の氏名・カナ・`postalCode`/`pref`/住所・`phoneNumber` |
| `var/json_schema/get-mypage-change.json` | `form`（同上） | 会員情報変更の氏名・カナ・`email`・`phoneNumber`・住所・パスワード |

### MED severity（admin、要約）

残り 59 本はほぼ全て admin 系で、過少定義の中身は**ほぼ単一パターン = `form` ノードが空の opaque-bag**（`get-admin-*` の大多数）。検索画面では `searchForm`（`get-admin-customer-list` / `-order-list` / `-product-list`）、一覧では `customer` / `order` / `product` / `delivery` / `payment` / `category` といった親オブジェクト（`get-admin-order` の `customer`、`get-admin-order-edit` の `order`、`get-admin-product-edit` の `product` 等）、PDF/CSV まわりで `orderNos`（`post-admin-order-bulk-delete` / `get-admin-order-export-order-pdf`）。HIGH と同じ「業務フィールドを property に昇格」修正だが、管理画面側のため緊急度は下。フォーム定義（`src/Form/`）が SSOT になり得るので、HIGH 確定後に form メタデータからまとめて生成するのが効率的。

## 3. Sentinel-data スキャン（スコープは封じ込み済み）

`var/sql/` 166 ファイル + `be/src/` 807 PHP ファイル全件を「実結合可能なソースがあるのに NOT-NULL 列へ `'-'`/`'N/A'`/`'不明'`/空文字/ゼロ日付/ハードコード値を捏造する」アンチパターンで走査。**現存する生きたインスタンスはゼロ**。

- **`var/sql/order_register.sql`（唯一の該当・修正済み）** — `name01`/`name02`（74・104 行目、実値 `'-'` を確認）は **COALESCE の最終アーム**で、JSON `customerSnapshot` と `dtb_customer` サブクエリ（54–73 / 84–103 行）が**両方 NULL のときだけ**到達する。これは `customerId` も snapshot 氏名も無い真のゲスト注文＝**実結合可能なソースが存在しない**ケースなので許容。修正コミットは `503a38ee`（"Show the member's real name on /shopping/confirm (was '- - 様')"）で `dtb_customer` フォールバックを追加済み。`kana01/kana02/company/email/phone/addr`（106–369 行）は同時に `'-'` 最終アームを撤廃し dtb_customer→NULL に落ちるため、`'-'` リテラルが残るのは `name01/name02` のみ。
- **境界 2 件（アンチパターンではない、参考）**：
  1. `var/sql/order_item_register.sql`（9–10 行）は `product_id`/`product_class_id` に NULL リテラルをハードコード。`dtb_product_class.product_code` 経由で結合可能だが、両列は**設計上 NULLABLE**（商品削除後も明細が名前＋コードのスナップショットとして残る）ため、捏造ではなくスナップショット判断。商品が現存する場合に FK を埋め戻したいなら `dtb_product_class` への LEFT JOIN に昇格、という選択肢のみ。
  2. `be/src/Final/AdminCustomerDeleted.php`（130/136 行）・`CustomerWithdrawn.php`（111/117 行）は退会時に email を `'withdrawn-{id}@example.test'` で上書き。実 email は `originalEmail` に退避し確認メールに使うため、これは**意図的な PII 消去**＝sentinel-data の逆。対応不要。

> 結論：sentinel スキャンのスコープは `order_register.sql` 1 件＝修正済みに封じ込まれている。新たな対応は不要。

## 4. Silent-skip スキャン ＋ phpunit `<env>` の事実

`tests/` を「DB 前提条件を**誤った superglobal** から読み、実アサーションが一切走らないまま緑になる」アンチパターンで走査。**ちょうど 3 本**が該当（いずれも `$_SERVER['DATABASE_URL']` を唯一のソースにしている）。

**phpunit `<env>` の事実（これが根本）**：`phpunit.xml`（45 行目）は `<php><env name="DATABASE_URL" .../></php>` で DATABASE_URL を設定する。PHPUnit 10.5.63 の `PhpHandler::handleEnvVariables()`（`vendor/.../TextUI/Configuration/PhpHandler.php` 105–122 行）はこれを **`putenv()` と `$_ENV['DATABASE_URL']` にのみ**反映し、**`$_SERVER` には一切書かない**。したがって値は `getenv('DATABASE_URL')` と `$_ENV` には載るが、**`$_SERVER['DATABASE_URL']` は常に未設定**。これを唯一のソースにするテストは null を読み、`markTestSkipped()` で静かにスキップする。

| ファイル | 行 | 走らなくなっている本来のアサーション | 1 行修正 |
|---|---|---|---|
| `tests/Http/HttpSqlResetPasswordFormTest.php` | 232 | パスワード不一致時に `reset_key` を消費しない／無効キーのインラインエラー／正常系のトークン消費＋`dtb_customer` のハッシュ書き換え | `$databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;` |
| `tests/Http/HttpSqlWithdrawConfirmFormTest.php` | 205 | 退会確定が実際に行を mutate したかの実スタック永続化検証 | 同上 |
| `tests/Http/HttpSqlCustomerRegistrationFormTest.php` | 226 | 会員登録／確認の実 SQL 永続化検証 | 同上 |

兄弟テストは既に正しい：`HttpSqlMemberPurchaseToOrderTest.php`（262 行 `$_ENV ?? $_SERVER`、実物を確認済み）と `HttpSqlAdminProductClassFormTest.php`（159 行 `$_SERVER ?? $_ENV`）、Module 系 2 本は `getenv()` を使用。`tests/Resource` の他の ~22 件の `markTestSkipped` は「SQL スイートで担保」の意図的スキップ、`AbstractResourceSqlTestCase.php` は `$GLOBALS` ブートフラグ判定で、**いずれも本バグではない**。これら 3 本は DB が上がっているときだけ走るスイートなので、`$_ENV` フォールバックではなく **`markTestSkipped` → `self::fail()` の fail-loud に変える**選択肢が望ましい（DB 未設定が緑スキップではなく失敗として顕在化する）。

## 5. なぜ緑をすり抜けたか（synthesis）

3 つの所見はすべて**同じ根**に収束する — **「検証しているように見えて、実は何も検証していない契約／テスト」**。

- **opaque-bag スキーマ**：`type: ["object","array","null"] + properties 空`／裸 string 配列は、`additionalProperties:false` の体裁を保ちつつ**どんな形のオブジェクトでも通す**。会員氏名や住所が欠落・型崩れしてもスキーマ検証は緑のまま。契約が「ここに何が入るか」を一切約束していない。
- **sentinel-data**：NOT-NULL 制約は満たすが**値は捏造**。DB 整合性チェックは緑、しかし画面には `'- - 様'` が出る。制約を満たすことと正しいことの乖離（封じ込み済みだが過去に実発生した）。
- **silent-skip**：`markTestSkipped` は**失敗ではない**。`$_SERVER` 誤読で実アサーションが 0 件実行のまま、テストランは緑。「テストがある」ことと「テストが走った」ことの乖離。

いずれも **PASS の体裁 ≠ 実検証**。スキーマは形を約束せず、SQL は値を約束せず、テストは実行を約束していなかった。

### 推奨是正順

- **(B) 高 severity の顧客／注文プロジェクションを堅牢化＋ sentinel 禁止** — 第 2 章の **HIGH 8 ファイル**（最優先 `get-shopping-confirm.json` の `customer`）で空 `form`／裸 string を業務 property に昇格し `required` に収載。あわせて「実結合ソースがあるのに NOT-NULL へリテラルを書く」ことを CI で禁ずる lint/grep ガードを 1 本追加（現状クリーンなので回帰防止が目的）。**変更規模：スキーマ 8 ファイル ＋ ガード 1 本**。MED の admin 59 本は後続バッチ（`src/Form/` から生成）。
- **(C) silent-skip テストを fail-loud 化** — 第 4 章の **3 ファイル**（`HttpSqlResetPasswordFormTest.php` 232 / `HttpSqlWithdrawConfirmFormTest.php` 205 / `HttpSqlCustomerRegistrationFormTest.php` 226）を `$_ENV` フォールバック、または DB 必須スイートなので `self::fail()` に置換。**変更規模：3 ファイル各 1 行**。

> 合計の着手規模：**(B) スキーマ 8＋ガード 1、(C) テスト 3** の計 12 ファイル前後で、顧客向けの主要な「緑のすり抜け」を塞げる。
