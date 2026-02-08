# タグ分類体系

EC-CUBE 4.3 ALPSプロファイルのタグ命名規則。

| プレフィックス | カテゴリ | 例 |
|---------------|---------|-----|
| `flow-` | ワークフロー | flow-purchase, flow-manage-order |
| `src-` | 情報源 | src-entity, src-router |
| `actor-` | アクター | actor-admin, actor-customer |
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
