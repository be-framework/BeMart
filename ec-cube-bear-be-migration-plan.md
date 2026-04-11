# EC-CUBE to BEAR.Sunday + Be Migration Plan

## 目的

EC-CUBE 4.3 を、既存の業務意味と画面フローを保ちながら、BEAR.Sunday を resource / interface 層、Be Framework を domain transformation 層として再構成する。

この計画では、既存 EC-CUBE の全機能を一度に置き換えるのではなく、ALPS プロファイルを契約にした段階移行を前提にする。

## 前提

- 初期移行では既存 DB スキーマを維持する
- v1 の優先対象は storefront
- admin は後続フェーズで扱う
- 既存プラグイン互換は v1 の目標に含めない
- この repo の `alps.json` を、画面遷移と語彙の契約として使う
- 初期実装は `Be-first` で進め、`BEAR.Sunday` は後段で薄く載せる

## なぜこの構成か

### ALPS → BEAR.Sunday

- `safe` は主に `onGet`
- `unsafe` は主に `onPost`
- `idempotent` は `onPut` / `onDelete`
- state は `page://` または `app://` resource
- transition は resource method と hyperlink

ALPS ですでに整理されている state / transition を、そのまま resource 設計へ写せる。特に BEAR.Sunday は `page://` と `app://` を分けられるので、EC-CUBE の画面入口と内部業務 API を分離しやすい。

### ALPS descriptor → Be

- descriptor id は Semantic 変数の候補
- 入力フォームは `Input`
- 中間状態は `Being`
- 注文確定、配送確定、会員登録完了などは `Final`
- 決済、配送、税計算、メール、在庫、永続化は `Reason`

EC-CUBE の難所は「if だらけの業務手続き」と「外部依存の連鎖」なので、Be の `#[Be]`, `#[Input]`, `#[Validate]`, `#[Inject]` に寄せる価値が大きい。

## ターゲット構造

```text
src/
  Resource/
    Page/       # 外部 HTTP 入口
    App/        # 内部業務 API
  Input/        # フォーム・要求開始点
  Being/        # 中間変換状態
  Final/        # 完了状態
  Reason/       # 決済・配送・税・在庫・通知・永続化
  Semantic/     # email, postalCode, quantity, taxRate など
  Exception/    # semantic/domain 例外
  Module/       # DI
```

## 移行戦略

### 方針

Strangler pattern を採用する。最初は BEAR/Be アプリを新規に作り、既存 EC-CUBE と並走させる。ドメインごとに置換し、UI と業務ロジックの責務を同時に切り離す。

### 境界づけられたコンテキスト

この repo のタグ分類をそのまま大まかな移行単位にする。

- `catalog`
- `cart`
- `checkout`
- `order`
- `account`
- `favorite`
- `contact`
- `help`
- `shop`
- `content`
- `cms`
- `mail`
- `plugin`
- `admin-system`

## フェーズ計画

### Phase 0: 契約固定

目的: 移植対象を ALPS 契約として固定する。

- `alps.json` から storefront 対象の resource 一覧を切り出す
- 不足している descriptor / transition を storefront 優先で補完する
- admin 未カバー 30 ルートは backlog に切り離す
- 既存 DB と外部依存の一覧を作る

成果物:

- storefront resource inventory
- domain glossary
- migration backlog

完了条件:

- storefront の機能境界が固定される
- v1 対象外が明文化される

### Phase 1: 土台構築

目的: 最小の BEAR/Be 実行基盤を作る。

- app skeleton を作成
- 認証、セッション、CSRF、DI、DB 接続の方針を決める
- 共通 Semantic を実装する
  - `email`
  - `postalCode`
  - `pref`
  - `quantity`
  - `price`
  - `currencyCode`
- hypermedia test と resource test の雛形を作る

成果物:

- skeleton app
- semantic primitives
- test harness

完了条件:

- `page://` / `app://` の疎通が取れる
- Semantic validation の最小セットが動く

### Phase 2: Catalog 先行移植

目的: 読み取り中心の領域で設計を固める。

- 商品一覧、商品詳細、カテゴリ、検索、タグを移植
- Product / Category / ProductClass の read model を固定
- 既存 DB への read adapter を実装
- HTML 表現と JSON 表現の両方を試す

成果物:

- ProductList / Product / Category resources
- read adapters
- catalog hypermedia tests

完了条件:

- 商品閲覧フローが BEAR 側で完結する
- 既存データと表示差分を比較できる

### Phase 3: Cart / Checkout 移植

目的: EC-CUBE の中核フローを Be で再表現する。

- カート追加、数量変更、削除
- 配送先選択、支払方法選択、確認、注文確定
- `CartInput -> CartUpdated`
- `CheckoutInput -> CheckoutPrepared`
- `CheckoutPrepared -> OrderConfirmed`

Reason 候補:

- InventoryReason
- TaxReason
- PaymentReason
- DeliveryReason
- MailReason
- PersistenceReason

成果物:

- cart / checkout resources
- order confirmation flow
- transaction / idempotency policy

完了条件:

- 注文フローが end-to-end で動く
- 主要状態遷移が ALPS と一致する

### Phase 4: Account / Order History / Favorite

目的: 顧客系フローを storefront に揃える。

- ログイン、会員登録、パスワード再設定
- マイページ、注文履歴、お気に入り
- Customer / Order / Shipping の semantic を確定

完了条件:

- 顧客向け主要導線が BEAR 側へ移る

### Phase 5: Admin Core

目的: 管理機能を優先度順に移す。

- 商品
- 受注
- 顧客
- 基本設定

注意:

- この repo では admin ルートが未完備なので、先に契約補完が必要
- CMS / content / system settings は core admin の後

### Phase 6: CMS / Content / Mail / Remaining Admin

目的: 低優先だが運用上必要な機能を回収する。

- news
- page
- block / layout
- mail template
- admin-system 残件

### Phase 7: Plugin Strategy

目的: プラグインの扱いを決める。

選択肢:

- v2 で BEAR/Be 向け拡張機構を設計する
- 既存 EC-CUBE プラグイン互換は切る
- 一部のみ adapter 経由で延命する

## テスト戦略

- ALPS から resource contract test を作る
- BEAR.Sunday の resource test を基本にする
- workflow は hypermedia test で確認する
- 比較対象は既存 EC-CUBE のレスポンスと状態遷移
- checkout は golden path と failure path の両方を固定する

## Skills の利用方針

可能なら、以下の skill 群を標準工程に組み込む。

- Be Skills
  - `be`
  - `be-semantic`
  - `semantic-ex`
- BEAR.Skills
  - `bear-from-alps`
  - `bear-to-alps`
  - `bear-review`
  - `bear-hypermedia`
  - `bear-smoke-test`

使い分け:

- Story / glossary / semantic 制約の解像度を上げる段階では Be Skills
- ALPS から resource を起こす段階では `bear-from-alps`
- resource の規約確認と link 補完では `bear-review` と `bear-hypermedia`
- packet 完了時の最低限確認には `bear-smoke-test`

必要 skill の一覧は `skills-matrix.md` に切り出す。
Be-first の共有用要約は `be-first-migration-method.md` に切り出す。

## 長時間自律実行の原則

- 実装は必ず TDD で進める
- 1回の作業単位は `work packet` に分割する
- 1 packet には 1つの対象、1つの完了条件、1つの主要テスト群だけを持たせる
- packet 完了時には planning files と runbook を更新する
- セッション断絶や context 0% は前提条件として扱う

`work packet` の例:

- Catalog: ProductList の `onGet` と対応する hypermedia test
- Cart: `doAddCartItem` 相当 resource と quantity validation
- Checkout: 配送先確定の happy path と failure path

詳細な再開手順は `autonomous-execution-runbook.md` に切り出す。
初日の立ち上げ手順は `day0-workflow.md` に切り出す。
JSON-first の実行基盤メモは `orchestrator-v1.md` に切り出す。

## リスク

- plugin 互換を求めると計画が破綻しやすい
- EC-CUBE の stateful な購入フローを単純な CRUD に落とすと失敗する
- admin は ALPS 契約が不完全
- 決済、配送、税計算、メール送信の境界が曖昧だと Be の Reason が肥大化する
- DB リファクタとフレームワーク移植を同時に始めると切り戻し不能になる

## 最初の 90 日でやること

1. storefront 契約を `alps.json` から確定する
2. 新規 BEAR/Be skeleton を作る
3. catalog を read-only で移植する
4. cart / checkout の最小 happy path を通す
5. resource test と hypermedia test を定着させる

## 今の時点での推奨

最初の実装対象は `catalog` と `cart/checkout` に絞るべきです。理由は、front の ALPS カバレッジが高く、EC-CUBE の価値の中心がここにあり、BEAR.Sunday と Be の責務分離を最も検証しやすいからです。

admin を先にやると、契約不足の補完と画面数の多さで計画が膨らみます。まず storefront を動かし、その後に admin core を移す順序が妥当です。
