# タグ分類体系

EC-CUBE 4.3 ALPSプロファイルのタグ命名規則。

| プレフィックス | カテゴリ | 例 |
|---------------|---------|-----|
| `flow-` | ワークフロー | flow-purchase, flow-manage-order |
| `src-` | 情報源 | src-entity, src-router |
| `actor-` | アクター | actor-admin, actor-customer |
| `page` / `page-` | HTML画面状態 | page-admin, page-list, page-edit |
| `route-` | 外部/EC-CUBEルート対応 | route-ec-cube |
| （なし） | ドメイン | catalog, order, checkout |

## Workflow（`flow-*`）

| タグ | 説明 |
|------|------|
| flow-browse | 商品閲覧（トップ→一覧→詳細→カテゴリ） |
| flow-purchase | 購入（カート→注文手続き→確認→完了） |
| flow-register | 会員登録（フォーム→メール認証→完了） |
| flow-account | マイページ（履歴・情報変更・退会・パスワードリセット） |
| flow-favorite | お気に入り（追加・削除・一覧） |
| flow-inquiry | お問い合わせ（フォーム→送信→完了） |
| flow-manage-product | 管理：商品（CRUD・CSV入出力・カテゴリ・タグ・規格） |
| flow-manage-order | 管理：受注（CRUD・ステータス変更・CSV・PDF・メール送信） |
| flow-manage-customer | 管理：会員（CRUD・CSV出力） |
| flow-manage-shop | 管理：店舗設定（基本情報・支払方法・配送方法・税率） |
| flow-manage-content | 管理：コンテンツ（ニュース・ページ） |
| flow-manage-cms | 管理：CMS（レイアウト・ブロック・特定商取引法） |
| flow-manage-system | 管理：システム（メンバー・権限・ログイン履歴・CSV） |
| flow-manage-mail | 管理：メール（テンプレート編集） |
| flow-manage-plugin | 管理：プラグイン（インストール・有効化・無効化） |

## Domain

### ドメイン領域（プレフィックスなし）

| タグ | 説明 |
|------|------|
| catalog | 商品・カテゴリ・規格・タグなど商品情報全般 |
| cart | カート操作（追加・数量変更・削除） |
| checkout | 注文手続き（配送先・支払方法・確認・確定） |
| order | 受注データの管理（一覧・編集・ステータス・CSV・PDF） |
| account | 会員のマイページ機能（履歴・情報変更・退会） |
| favorite | お気に入り機能 |
| contact | お問い合わせ機能 |
| help | ヘルプ・ガイドページ |
| shop | 店舗設定（基本情報・支払方法・配送方法・税率） |
| content | コンテンツ管理（ニュース・自由ページ） |
| cms | CMS機能（レイアウト・ブロック・特定商取引法） |
| mail | メールテンプレート管理 |
| plugin | プラグイン管理 |
| admin-system | システム管理（メンバー・権限・ログイン履歴・CSV設定） |
| front | フロント画面共通（トップ・一覧・共通テンプレート） |

### アクター（`actor-`）

| タグ | 説明 |
|------|------|
| actor-admin | 管理者。管理画面から操作を行うアクター |
| actor-customer | 顧客。フロント画面から操作を行うアクター |

### 情報源（`src-`）

| タグ | 説明 |
|------|------|
| src-entity | Doctrineエンティティ由来のデータ項目 |
| src-router | Symfonyルーター由来の遷移アクション |
| src-controller | コントローラ由来の処理アクション |
| src-template | Twigテンプレート由来の画面構造 |

### HTML画面状態（`page`, `page-*`）

`src-template` は「Twigテンプレート由来」という出自を示すタグです。
一方、`page` / `page-*` は **ブラウザで到達する画面状態** としての役割を明示します。
エンティティ（例: `Order`, `Customer`）と、ルート・テンプレートに対応する画面（例: `AdminOrderEditPage`）を混同しないために使います。

| タグ | 説明 |
|------|------|
| page | routable な HTML 画面状態。共有部品単体や純粋なデータ項目には付けない |
| page-storefront | フロント画面 |
| page-admin | 管理画面 |
| page-mypage | 会員マイページ配下の画面 |
| page-list | 一覧画面 |
| page-detail | 詳細画面 |
| page-edit | 編集画面 |
| page-new | 新規作成画面 |
| page-confirm | 確認画面 |
| page-complete | 完了画面 |
| page-login | ログイン画面 |
| page-dashboard | トップ/ダッシュボード画面 |
| page-error | 404/405/501 などのエラー画面 |

### ルート対応（`route-*`）と移植管理

| タグ | 説明 |
|------|------|
| route-ec-cube | EC-CUBE の具体的な route 名・テンプレートに対応する画面状態 |
| migration-target | 移植対象として追跡するノード。未実装の場合も 404/Fatal ではなく 501 やドキュメント化された残差として扱う |
