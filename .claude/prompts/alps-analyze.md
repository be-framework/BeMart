# ALPS Analyze

対象: `{descriptor}`

`alps.json` からディスクリプタ `{descriptor}` を読み取り、Be Framework / BEAR.Sunday への移植マッピング案を作成せよ。

## 前提

- `alps.json` が**正（source of truth）**である。Symfony コードは見ない
- ALPS は 403 ディスクリプタ（container 266 + safe 58 + unsafe 35 + idempotent 44 = transition 計 137）で EC-CUBE 4.3 を記述済み
- 全 transition は doc 付き。container も主要ドメインは doc 付き
- このリポジトリの ALPS 構造とタグ体系は `docs/tag.md` を参照

## 手順

### 1. ディスクリプタ本体の抽出

Read ツールで `alps.json` を読み、`{descriptor}` を id とするエントリを抽出する。

**ID マッチング規則（決定的順序）**:

ALPS の id は lowerCamel（例: `quantity`, `productName`、container は `Cart` のような UpperCamel もあり）、Be Framework のクラス名は UpperCamel（例: `Quantity`, `ProductName`）で、この workflow の引数は Be クラス名に寄せている。**以下の順序で試行し、最初にヒットした 1 件を採用する**:

1. **完全一致**: 引数文字列をそのまま id として `"id": "<arg>"` を検索
2. **lowerCamel 化**: 引数の先頭 1 文字を小文字化して `"id": "<lowerCamel>"` を検索（例: `Quantity` → `quantity`）
3. **UpperCamel 化**: 引数の先頭 1 文字を大文字化して `"id": "<UpperCamel>"` を検索（例: `cart` → `Cart`）
4. **大小無視 LIKE**: `(?i)"id":\s*"<arg>"` 相当のケースインセンシティブ検索
5. **snake_case → camelCase 変換**: 引数が `_` を含む場合のみ camelCase 化して再検索（例: `cart_item` → `cartItem` → 1〜4 を再試行）

複数マッチした場合は **1 → 2 → 3 → 4 → 5 の順で先に出たもの**を優先。ヒット順序は決定的なのでユーザーに確認しない。

**ALPS に存在しない場合**: エラーで停止せず、`## 概要` セクションに `⚠️ ALPS 未登録` と明記した上で、Be 側の新規クラスとして扱う。`src-*` タグは付かず、制約は後続ステップで移植元コードや仕様書から補完する方針を明記する。出力 JSON では `"alps_found": false` をセットする。

以下の情報を整理する:

- **id / title / def** — 識別子と意味
- **type** — `safe` / `unsafe` / `idempotent` のどれか。container には type 属性が無い（取り扱いは「3. Be 層へのマッピング案」を参照）
- **tag** — ドメインタグ（`catalog`, `cart`, `checkout`, `order`, `customer`, `admin` 等）とソースタグ（`src-entity`, `src-template`, `src-router`, `src-controller`）
- **rt (return type)** — 遷移先のリソース id
- **descriptor[]** — 子ディスクリプタ（ネストされたプロパティまたは関連操作）
- **doc.value** — 操作の意味・副作用・主要パラメータの 1 文記述（後続ステップで Final 説明・リソース PHPDoc にそのまま使える）

### 2. 関連ディスクリプタの収集

`{descriptor}` から `href` で参照されている全てのセマンティックプロパティを再帰的に集め、それぞれの型・バリデーションヒント・情報源タグを記録する。

特に以下を見落とさないこと:

- 入力として必須のフィールド（`rt` 側で要求されるもの）
- nullable なフィールド（source では `null` 許容）
- 集約型プロパティ（価格計算・在庫計算などキャッシュされた派生値）
- 状態遷移のトリガー（`go*` / `do*` 操作）

### 3. Be Framework 層へのマッピング案

以下のテーブルを Markdown で出力する:

| ALPS 要素 | 分類 | Be 層での置き場所 | 根拠 |
|---|---|---|---|
| `{descriptor}` 本体 | 入力/変容/終点 | Input / Being / Final | type と rt から判断 |
| 各セマンティックプロパティ | 値 | Semantic クラス名（lowerCamel → UpperCamel） | バリデーションが必要なもの |
| 外部依存 | Reason | Reason/Media/Command · Query · Entity | DB / 決済 / 配送 / メール等 |
| 派生値 | Final プロパティ | Final コンストラクタ内で計算 | 集約・状態遷移先 |

**type → Be 分類の写像（決定的）**:

| ALPS の type | id 命名のヒント | Be 層での扱い | 例 |
|---|---|---|---|
| なし（container） | UpperCamel `Cart` / `Product` / `Order` | リソース集約。Final から `body` に詰める対象。単独 Input/Final にはしない | `Cart`, `Order`, `Product` |
| `safe` | `go*` (`goProductList`, `goCart`) | `Input → Final` の Direct 変換（読み取り）。Final で Query 実行 | `goProduct` → `GetProductInput → ProductFetched` |
| `unsafe` | `do*Add` / `do*Create` / `do*Submit` | `Input → Final` の Direct 変換（書き込み）。Final コンストラクタで副作用 | `doCreateProduct` → `CreateProductInput → ProductCreated` |
| `idempotent` | `do*Update` / `do*Delete` / `do*Replace` | `Input → Final`、副作用は冪等な書き込み | `doUpdateProduct` → `UpdateProductInput → ProductUpdated` |

`type` 属性が無い container は **純粋 Semantic スキップ判定**（後述）と区別すること。container は **複数 transition から rt として参照されるリソース集約**であり、リソース化はするが単独で Input/Final を持たない。

**Be のマッピング原則**:

- container（type 属性なし、UpperCamel id） → リソースの shape 定義として `descriptor[]` を読み、Final が body に詰める形を決める材料にする。`src/Semantic/` には出さない
- 純粋 Semantic（type 属性なし、lowerCamel id、単独で state transition を持たない、例: `quantity`, `productName`） → `src/Semantic/<UpperCamel>.php` の Semantic クラスとして扱う。`#[Be]` を持たず、`Input → Final` の起点・終点にはならない。他の Input クラスのコンストラクタ引数としてのみ参照される
- `safe` (`go*`) → `Input → Final` の Direct 変換（読み取り）
- `unsafe` / `idempotent` (`do*`) → `Input → Final` の Direct 変換（書き込み、Final で副作用）
- 分岐が必要なら `Input → Being → Final A | Final B`（[`medical-triage`](https://github.com/be-framework/be-patterns/tree/1.x/demos/medical-triage) パターン）
- 独立した外部副作用が複数あるなら Moment を複数注入する Diamond パターン（[`order-processing`](https://github.com/be-framework/be-patterns/tree/1.x/demos/order-processing)）

**Diamond サブパターン判定（3 軸）**:

複数 Reason を統合する場合、以下 3 軸でサブパターンを決定し、適切な範例デモを選ぶ:

| サブパターン | 特徴 | 範例（be-patterns） | 実装スタイル |
|---|---|---|---|
| **独立並列 Diamond** | Reason が互いに独立で並列実行可能。順序依存なし | [`blog-publishing`](https://github.com/be-framework/be-patterns/tree/1.x/demos/blog-publishing) | Final constructor で `#[Inject]` を並べる |
| **Cascade Diamond** | Reason 間に順序依存あり（例: 在庫検査 → 数量補正 → 価格確定） | [`loan-application`](https://github.com/be-framework/be-patterns/tree/1.x/demos/loan-application) | Being を 1〜2 段挟む。Input → Being → Final |
| **Branching Final** | Reason 結果に応じて Final クラスが分岐 | [`medical-triage`](https://github.com/be-framework/be-patterns/tree/1.x/demos/medical-triage) | `#[Be([FinalA::class, FinalB::class])]` で複数 Final を列挙 |

判定手順:

1. Reason を全部リストアップ（rt 先 container + 関連フィールド + 関連 transition から）
2. Reason 間に「先に確定しないと次の判断ができない」依存があるか? → Yes なら **Cascade**
3. Reason 結果で Final クラスが分岐するか?（成功/失敗、ケースA/ケースB など） → Yes なら **Branching**
4. どちらも No → **独立並列**

出力 JSON では `be_pattern` を `Direct | Multi-stage | Diamond-Independent | Diamond-Cascade | Diamond-Branching` のいずれかにする。

**制約の置き場所**:

- **静的制約**（値単体で判定できるもの。型・範囲・フォーマット・文字数）→ Semantic クラスの `#[Validate]` メソッドに書く
- **動的制約**（外部 lookup が必要。在庫・販売制限・一意性・権限）→ Semantic には書かず、その値を内包する **Final クラスの constructor 内**で `#[Inject]` した Reason を使って検証する
- 例: `Quantity` の `>= 1` は Semantic だが、`<= stock` / `<= saleLimit` は `CartItemAdded` Final の constructor で検証

### 4. BEAR.Sunday 層へのマッピング案

以下を Markdown テーブルで出力する:

| 項目 | 決定 | 根拠 |
|---|---|---|
| URI schema | `page://` or `app://` | ユーザー入口なら page、内部API/リソース合成なら app |
| HTTP メソッド | onGet / onPost / onPut / onPatch / onDelete | ALPS の type から写像 |
| ベース URI | 例: `/product/{id}` | `src-router` タグの情報源 |
| 呼び出す Be Input | `ProductInput::class` | domain 層で作成するクラス |
| Link 候補 | rel と href | ALPS の子 `go*` / `do*` 操作 |

**BEAR のマッピング原則**:

- `page://self/...` — ブラウザ入口（ルーター直下）
- `app://self/...` — 他リソースから呼ばれる内部 API
- Resource は `Becoming` を呼ぶだけ。ビジネスロジックを書かない
- `@link phpdoc` → `#[Link]` 属性に変換される（Be 側の Final に `@link` を書く）
- **純粋 Semantic（type=semantic かつ単独で state transition を持たない）の場合、BEAR リソースは生成しない**。テーブルは全項目 `N/A` と記入し、根拠に「純粋 Semantic のため単独 URI なし。上位 descriptor の Input 引数として参照される」と書く。後続の `application` ステップは skip 扱いとする

### 4.5. client-input vs server-fetched 判定

Phase 2（Fake 50 件観察）に渡す前に、対象 descriptor の各フィールドを **2 シートに分類** する。これにより Semantic クラスの作成範囲と Reason の責任範囲が決まる:

| 軸 | client-input | server-fetched |
|---|---|---|
| 由来 | ユーザーが入力（フォーム / URL param / JSON body） | DB / 外部 API / 別リソースから取得 |
| Semantic クラス作成 | **必要**（Smart Constructor で検証） | **不要**（Fake fixture が典型値の出処） |
| Exception | バリデーション例外を `#[Message]` 付きで作る | DomainException（NotFound 等）のみ |
| JSON Schema | `var/schema/request/*.json` に記述 | 不要 |
| 50 件 Fake 観察 | minLength / maxLength / null 率 / 値域を観察 | 典型値分布を Fake fixture に反映 |

**判定の質問**:

- 「このフィールドは HTTP リクエストの一部として送られるか?」 → Yes なら client-input
- 「このフィールドはサーバー側で取得・派生されるか?」 → Yes なら server-fetched / server-derived

出力 JSON では `semantic_classes[].input_kind` を `client | server` のいずれかでマークする。`server` のフィールドは Semantic を作らず、対応する Reason の Fake fixture（`server_fetched_fields`）として `notes` または別キーに記録する。

### 5. Reason 候補の洗い出し

EC-CUBE の副作用を Reason 層に押し込むため、以下を列挙する:

- DB アクセス → `Reason/Media/Command/*.php` + `Reason/Media/Query/*.php` + `Reason/Entity/*.php`
- 決済ゲートウェイ → `Reason/Payment*Interface.php`
- 配送計算 → `Reason/Delivery*Interface.php`
- 税計算 → `Reason/TaxCalculator*Interface.php`
- 在庫引き当て → `Reason/InventoryAllocator*Interface.php`
- メール送信 → `Reason/Mailer*Interface.php`

初期段階では **Phase 1 (FakeQuery)** 方針に従い、Command は no-op、Query は `var/fake/<id>.json` で済ませる。

### 6. 出力フォーマット

結果を以下の **2 ブロック構造**で出力する。前半は人間レビュー用の Markdown レポート、末尾の fenced JSON ブロックは後続ステップ（`domain`, `application`）が機械的に読む引き渡し情報。**両方を必ず出力すること**。

#### 6-1. Markdown レポート

```markdown
# ALPS Analyze: {descriptor}

## 概要
(1-2行で目的と型。ALPS 未登録の場合は冒頭に `⚠️ ALPS 未登録` を明記)

## セマンティックプロパティ
(テーブル: id / 型 / nullable / 情報源タグ)

## Be 層マッピング案
(テーブル: ALPS要素 → Be層)

## BEAR 層マッピング案
(テーブル: 項目 → 決定。純粋 Semantic は全項目 N/A)

## Reason 候補
(箇条書き。Phase 1 (FakeQuery) 方針で Command は no-op、Query は var/fake/<id>.json)

## 変換パターン判定
Direct / Multi-stage / Diamond / Branching のどれか、および根拠

## 次ステップへの引き渡し事項
domain ステップで使う情報を箇条書き
```

#### 6-2. 引き渡し JSON ブロック

レポートの末尾に **必ず** 以下の fenced ブロックを置く（`json handover` のラベル付き）。後続ステップはこのブロックを正規表現 ```` ```json handover\n([\s\S]+?)\n``` ```` で抽出して読む:

````markdown
```json handover
{
  "descriptor_id": "<引数として与えられた文字列>",
  "alps_id_resolved": "<実際にヒットした id（未ヒットなら null）>",
  "alps_found": true,
  "descriptor_type": "container | semantic | safe | unsafe | idempotent",
  "be_pattern": "Direct | Multi-stage | Diamond-Independent | Diamond-Cascade | Diamond-Branching",
  "be_reference_demo": "hello-world | contact-form | user-registration | order-processing | blog-publishing | medical-triage | loan-application | insurance-claim",
  "be_classes": {
    "input": "<例: GetProductInput / null（純粋 Semantic の場合）>",
    "being": "<分岐がある場合のみ。なければ null>",
    "final": ["<例: ProductFetched>"]
  },
  "semantic_classes": [
    {
      "name": "<UpperCamel、例: Quantity>",
      "alps_id": "<lowerCamel、例: quantity>",
      "input_kind": "client | server",
      "php_type": "int | string | float | bool | DateTimeImmutable",
      "nullable": false,
      "static_constraints": ["<例: >= 1, <= 9999>"],
      "dynamic_constraints": ["<Final 側で検証する制約。例: <= stock>"],
      "source_tag": "src-entity | src-template | src-router | src-controller | none"
    }
  ],
  "server_fetched_fields": [
    {
      "name": "<lowerCamel、例: stock>",
      "from": "<例: ProductClass テーブル>",
      "purpose": "<例: Stock 検査>",
      "fake_fixture_path": "<例: var/fake/product_classes.json>"
    }
  ],
  "reasons": [
    {
      "type": "DB-Query | DB-Command | Payment | Delivery | Tax | Inventory | Mailer | Other",
      "interface_name": "<例: ProductQueryInterface>",
      "phase": "Phase 1 (FakeQuery)",
      "fake_fixture": "<例: var/fake/product.json / null>"
    }
  ],
  "bear": {
    "skip": false,
    "uri_scheme": "page | app | null",
    "http_method": "onGet | onPost | onPut | onPatch | onDelete | null",
    "base_uri": "<例: /product/{id} / null>",
    "links": [
      {"rel": "<例: cart>", "href": "<例: /cart>"}
    ]
  },
  "notes": [
    "<曖昧さを残した暫定判断、別 PR への切り出し提案、ユーザー確認が望ましい論点など>"
  ]
}
```
````

**JSON ブロックの厳格ルール**:

- **必ずトップレベル 12 キーすべてを出力**（`descriptor_id`, `alps_id_resolved`, `alps_found`, `descriptor_type`, `be_pattern`, `be_reference_demo`, `be_classes`, `semantic_classes`, `server_fetched_fields`, `reasons`, `bear`, `notes`）。不明な値は `null` または空配列を使い、キー自体は省略しない
- `alps_found: false` の場合: `descriptor_type` は `null`、`semantic_classes` と `reasons` は空配列でも可、`be_pattern` は推測値、`notes` に「ALPS 未登録、移植元コードまたは仕様書から補完が必要」と明記
- `bear.skip: true` の場合（純粋 Semantic）: `bear.uri_scheme` 以下の他フィールドはすべて `null` または空配列。`application` ステップはこのフラグを見て早期 skip する
- `descriptor_type: "container"` の場合: `be_classes.input` / `be_classes.final` は空または `null`。container は単独で Input/Final を持たないため、`bear.skip: true` 相当として扱い、`notes` に「container 集約。Final の body shape 定義として参照される」と明記
- 各文字列値の `<例: ...>` プレースホルダーはあくまで参考。実際には決定値だけを書く

この出力は次の `domain` ステップへそのまま渡される。**曖昧さを残さない**こと。判断できない箇所はユーザーに質問せず、「暫定で X を採用。根拠: ...」と Markdown 側に明記し、JSON 側は決定値を入れて `notes` に補足を残す。
