# Flow Ontology

Last updated: 2026-06-04

この文書は、BeMart / EC-CUBE semantic migration における **本来の flow** を自然言語で定義するためのオントロジーである。

旧 `flow-*` は、主に Feature matrix 用の機能領域タグとして使われてきた。これはカバー率を把握するには有効だったが、ハイパーメディアとして意味のある長い遷移、つまり「業務導線として通るか」を検証するには粒度が粗い。

以後、この文書では次を区別する。

| 種類 | 例 | 役割 |
|---|---|---|
| Domain tag | `catalog`, `order`, `checkout`, `account` | 業務語彙の領域。既存の prefix なしタグを維持する。 |
| Feature tag | `feature-admin-catalog`, `feature-purchase` | 機能領域・Feature matrix 用の分類。旧 `flow-*` の実態に近い。 |
| Flow tag | `flow-admin-product-publish`, `flow-customer-purchase` | 自然言語で定義された業務導線。複数 feature/domain を跨いでよい。 |

`flow-*` は「あるアクターが意味ある目的を達成するまでに辿るハイパーメディア遷移のまとまり」として扱う。単なる機能領域、単発操作、実装都合の画面グループには使わない。

Feature tag は coverage report のための補助軸である。正準の領域表現は、既存の domain tag と `actor-*` を優先する。

## Verification Scope

この文書は自然言語の flow ontology であり、実行可能な step list ではない。後続フェーズでは `tests/SemanticFlow/flows/*.json` のようなテスト fixture に、HTTP method/path、期待する `alpsId`、画面 affordance を具体化できる。

それらの fixture はこの ontology を検証するための実行形式であり、`alps.json` の意味語彙を置き換えるものではない。

---

## Flow List

| Flow ID | Actor | Natural language flow | Goal | Spans |
|---|---|---|---|---|
| `flow-admin-product-publish` | admin | admin が商品を作成・編集し、storefront で表示確認する。 | 作成・編集した商品が顧客向け商品一覧/詳細で確認できる。 | `feature-admin-catalog`, `feature-browse` |
| `flow-customer-purchase` | customer | 顧客が商品を見つけ、カートに入れ、注文を完了する。 | 購入完了画面に到達し、注文が記録される。 | `feature-browse`, `feature-purchase`, `feature-account` |
| `flow-admin-order-fulfillment` | admin | admin が受注を確認し、配送情報・ステータス・通知・帳票を扱う。 | 受注の処理状態が管理画面で更新され、必要な通知/帳票導線が成立する。 | `feature-admin-order`, `feature-admin-mail` |
| `flow-customer-registration` | customer | 顧客が会員登録し、認証・完了を経てログイン可能になる。 | 会員登録完了状態に到達し、登録済み顧客として扱われる。 | `feature-register`, `feature-account` |
| `flow-customer-account-maintenance` | customer | 顧客がログインし、会員情報・配送先・お気に入り・退会を管理する。 | マイページ配下の主要な情報変更・削除・一覧確認が成立する。 | `feature-account`, `feature-favorite` |
| `flow-customer-inquiry` | customer | 顧客が問い合わせを入力し、確認・送信・完了する。 | 問い合わせ完了状態に到達し、メール/記録の送信境界が成立する。 | `feature-inquiry` |
| `flow-admin-content-publish` | admin | admin がニュース/ページ/ブロック等のコンテンツを作成・編集し、表示面で確認する。 | 管理画面で更新したコンテンツが storefront または管理表示に反映される。 | `feature-admin-content`, `feature-admin-cms`, `feature-browse` |
| `flow-admin-shop-configuration` | admin | admin が店舗基本情報、配送、支払、税、営業日などを設定する。 | 店舗設定が保存され、購入/表示に使われる状態になる。 | `feature-admin-shop`, `feature-purchase` |
| `flow-admin-system-operation` | admin | admin がメンバー、権限、ログ、2FA、セキュリティ設定を運用する。 | 管理者運用に必要な認証・権限・監査導線が成立する。 | `feature-admin-auth`, `feature-admin-system` |
| `flow-admin-csv-exchange` | admin | admin が CSV の出力設定を確認し、業務データを export/import する。 | CSV 設定と入出力導線が対象データへ安全に反映される。 | `feature-admin-system`, `feature-admin-catalog`, `feature-admin-order`, `feature-admin-customer` |
| `flow-admin-master-data-update` | admin | admin がマスタデータを選択し、値を更新する。 | 更新したマスタ値が保存され、参照側の業務処理に使える状態になる。 | `feature-admin-system` |
| `flow-admin-template-lifecycle` | admin | admin がデザインテンプレートを追加・選択・取得・削除する。 | テンプレート登録と選択状態が安全に変更できる。 | `feature-admin-plugin`, `feature-admin-cms` |
| `flow-admin-mail-template-maintenance` | admin | admin がメールテンプレートを編集し、送信メールへの反映を確認する。 | テンプレート更新からメール送信/履歴確認までの導線が成立する。 | `feature-admin-mail`, `feature-admin-order` |

---

## Flow Definitions

### `flow-admin-product-publish`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | 商品を作成・編集し、顧客向け画面で表示されることを確認する。 |
| Start condition | admin が管理画面にログイン済み。 |
| Goal condition | 対象商品が storefront の商品一覧と商品詳細で確認できる。 |
| Success evidence | Hypermedia test、HTTP test、browser smoke。 |
| Out of scope | 商品 CSV import/export、画像アップロードの byte fidelity、購入 checkout。 |

### `flow-customer-purchase`

| Field | Value |
|---|---|
| Actor | customer |
| Intent | 商品を探し、カートに入れ、注文手続きを完了する。 |
| Start condition | storefront にアクセス可能。会員/非会員 checkout のどちらを対象にするかはテストケースで明示する。 |
| Goal condition | 購入完了画面に到達し、注文・明細 snapshot が記録される。 |
| Success evidence | Hypermedia test、HTTP test、SQL suite、browser smoke。 |
| Out of scope | 決済代行の本番 capture、メール本文の byte fidelity、PDF/CSV 出力。 |

### `flow-admin-order-fulfillment`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | 受注を確認し、配送情報、対応状況、通知、帳票を扱う。 |
| Start condition | admin がログイン済みで、対象受注が存在する。 |
| Goal condition | 受注編集・配送編集・通知/帳票導線が安全に辿れる。 |
| Success evidence | Hypermedia test、HTTP test、PDF/Mail compatibility tests、browser smoke。 |
| Out of scope | 帳票レイアウト完全一致、外部配送連携。 |

### `flow-customer-registration`

| Field | Value |
|---|---|
| Actor | customer |
| Intent | 会員情報を入力し、登録確認・完了・有効化へ進む。 |
| Start condition | 匿名 visitor。 |
| Goal condition | 登録済み顧客としてログインまたはマイページ導線に進める。 |
| Success evidence | Hypermedia test、HTTP test、browser smoke。 |
| Out of scope | 本番メール配送の到達性、外部本人確認。 |

### `flow-customer-account-maintenance`

| Field | Value |
|---|---|
| Actor | customer |
| Intent | マイページで会員情報、配送先、お気に入り、退会を管理する。 |
| Start condition | customer がログイン済み。 |
| Goal condition | 情報変更、配送先操作、お気に入り確認/削除、退会導線が成立する。 |
| Success evidence | Hypermedia test、HTTP test、render-diff、browser smoke。 |
| Out of scope | 退会後の長期履歴保持ポリシー、ポイント精算の完全互換。 |

### `flow-customer-inquiry`

| Field | Value |
|---|---|
| Actor | customer |
| Intent | 問い合わせを入力し、確認して送信する。 |
| Start condition | storefront visitor。 |
| Goal condition | 問い合わせ完了画面に到達し、送信境界が呼ばれる。 |
| Success evidence | Hypermedia test、HTTP test、mail body fixture、browser smoke。 |
| Out of scope | 本番 SMTP 到達性。 |

### `flow-admin-content-publish`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | ニュース、ページ、ブロック、レイアウトを編集し、表示面に反映する。 |
| Start condition | admin がログイン済み。 |
| Goal condition | 管理画面で保存したコンテンツが該当表示面で確認できる。 |
| Success evidence | Hypermedia test、HTTP test、render-diff、browser smoke。 |
| Out of scope | plugin runtime に依存する block / page 拡張。 |

### `flow-admin-shop-configuration`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | 店舗基本情報、配送、支払、税、営業日などを設定する。 |
| Start condition | admin がログイン済み。 |
| Goal condition | 保存済み設定が購入・表示・管理画面に反映される。 |
| Success evidence | Hypermedia test、HTTP test、SQL suite、browser smoke。 |
| Out of scope | 外部決済・配送事業者との本番連携。 |

### `flow-admin-system-operation`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | 管理者、権限、ログ、2FA、セキュリティ設定を運用する。 |
| Start condition | admin がログイン済み、または 2FA 設定では pre-auth challenge state が存在する。 |
| Goal condition | 管理運用の認証・権限・監査導線が成立する。 |
| Success evidence | Hypermedia test、HTTP test、security regression tests、browser smoke。 |
| Out of scope | 組織外 IdP / SSO 連携。 |

### `flow-admin-csv-exchange`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | CSV 出力設定を確認し、商品・カテゴリ・規格・受注・配送・会員データを export/import する。 |
| Start condition | admin がログイン済み。 |
| Goal condition | CSV 設定、export、import の各導線が対象データの境界に接続される。 |
| Success evidence | SQL suite、CSV fixture comparison、HTTP test、browser smoke。 |
| Out of scope | byte-exact な全CSV列互換、外部基幹システム連携。 |

### `flow-admin-master-data-update`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | マスタデータを選択し、値を更新する。 |
| Start condition | admin がログイン済み。 |
| Goal condition | マスタデータの選択・更新・保存が成立し、参照側がその値を利用できる。 |
| Success evidence | SQL suite、HTTP test、master fixture comparison、browser smoke。 |
| Out of scope | 全マスタ種別の業務影響完全検証、外部マスタ同期。 |

### `flow-admin-template-lifecycle`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | デザインテンプレートを追加、選択、取得、削除する。 |
| Start condition | admin がログイン済み。 |
| Goal condition | テンプレート登録と選択状態の変更が安全に確認できる。 |
| Success evidence | HTTP test、fixture comparison、render-diff、browser smoke。 |
| Out of scope | plugin marketplace からの取得、任意 ZIP の本番セキュリティ検査。 |

### `flow-admin-mail-template-maintenance`

| Field | Value |
|---|---|
| Actor | admin |
| Intent | メールテンプレートを編集し、その内容が注文メール等の送信境界に反映されることを確認する。 |
| Start condition | admin がログイン済みで、対象メールテンプレートと対象受注が存在する。 |
| Goal condition | テンプレート更新、メール送信、送信履歴確認の導線が成立する。 |
| Success evidence | HTTP test、mail body fixture、SQL suite、browser smoke。 |
| Out of scope | 本番 SMTP 到達性、全メール種別の byte-exact fidelity。 |
