# ALPS Analyze

対象: `{descriptor}`

`alps.json` からディスクリプタ `{descriptor}` を読み取り、Be Framework / BEAR.Sunday への移植マッピング案を作成せよ。

## 前提

- `alps.json` が**正（source of truth）**である。Symfony コードは見ない
- ALPS は既に 413 ディスクリプタ（semantic 276 + transition 137）で EC-CUBE 4.3 を記述済み
- このリポジトリの ALPS 構造とタグ体系は `tag.md` を参照

## 手順

### 1. ディスクリプタ本体の抽出

Read ツールで `alps.json` を読み、`{descriptor}` を id とするエントリを抽出する。

以下の情報を整理する:

- **id / title / def** — 識別子と意味
- **type** — `semantic` / `safe` / `unsafe` / `idempotent` のどれか
- **tag** — ドメインタグ（`catalog`, `cart`, `checkout`, `order`, `customer`, `admin` 等）とソースタグ（`src-entity`, `src-template`, `src-router`, `src-controller`）
- **rt (return type)** — 遷移先のリソース id
- **descriptor[]** — 子ディスクリプタ（ネストされたプロパティまたは関連操作）

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

**Be のマッピング原則**:

- `safe` (`go*`) → `Input → Final` の Direct 変換（読み取り）
- `unsafe` / `idempotent` (`do*`) → `Input → Final` の Direct 変換（書き込み、Final で副作用）
- 分岐が必要なら `Input → Being → Final A | Final B`（[`medical-triage`](https://github.com/be-framework/be-patterns/tree/1.x/demos/medical-triage) パターン）
- 独立した外部副作用が複数あるなら Moment を複数注入する Diamond パターン（[`order-processing`](https://github.com/be-framework/be-patterns/tree/1.x/demos/order-processing)）

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

結果を以下の構造で出力する:

```markdown
# ALPS Analyze: {descriptor}

## 概要
(1-2行で目的と型)

## セマンティックプロパティ
(テーブル: id / 型 / nullable / 情報源タグ)

## Be 層マッピング案
(テーブル: ALPS要素 → Be層)

## BEAR 層マッピング案
(テーブル: 項目 → 決定)

## Reason 候補
(箇条書き)

## 変換パターン判定
Direct / Multi-stage / Diamond / Branching のどれか、および根拠

## 次ステップへの引き渡し事項
domain ステップで使う情報を箇条書き
```

この出力は次の `domain` ステップへそのまま渡される。**曖昧さを残さない**こと。判断できない箇所はユーザーに質問せず、「暫定で X を採用。根拠: ...」と明記する。
