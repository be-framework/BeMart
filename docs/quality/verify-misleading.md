---
layout: default
title: "誤解を招く名前 + 値体系の検証結果"
---

{% raw %}
# 誤解を招く名前 + 値体系の検証結果

EC-CUBE 4.3 のソースコード (`src/Eccube/`) を調査し、ALPSプロファイルの誤解を招くセマンティックIDを検証した。

---

## classNameLabel (Product.className1 / className2)

### Entity
- **File**: `src/Eccube/Entity/Product.php` (lines 46-47)
- **Definition**: `private $className1;` / `private $className2;` -- 非永続化フィールド（`@ORM\Column`なし）。`_calc()` メソッドで動的に計算
- **関連Entity**: `src/Eccube/Entity/ClassName.php` (table `dtb_class_name`) に規格名を保持

### 実際の意味
「規格名」。EC-CUBEの "class" はOOPのクラスではなく、**商品バリエーション軸**（例: 「カラー」「サイズ」）を意味する。`Product._calc()` が `$ProductClass->getClassCategory1()->getClassName()->getName()` で取得。

### 使われ方
フロントエンドで商品バリエーション選択UIのラベルとして使用（「カラー」「サイズ」等）。

### 推奨doc
「商品バリエーション軸の名前（例: カラー、サイズ）。EC-CUBEの"class"はOOPのクラスではなく商品規格を意味する。className1は第1規格軸のラベル」

---

## classCategoryName (OrderItem.class_category_name1 / class_category_name2)

### Entity
- **File**: `src/Eccube/Entity/OrderItem.php` (lines 176-185)
- **Table**: `dtb_order_item` の `class_category_name1`, `class_category_name2` カラム
- **関連Entity**: `src/Eccube/Entity/ClassCategory.php` (table `dtb_class_category`)

### 実際の意味
**受注時のバリエーション値のスナップショット**。ClassCategoryは規格軸の具体的な値（例: 「赤」「L」）を保持。マスタ変更後も受注当時の値を保持するため受注明細に非正規化コピーされる。

### 推奨doc
「受注時のバリエーション値のスナップショット（例: 赤、L）。ClassCategory.nameから非正規化コピー。マスタ変更後も受注当時の値を保持する。"class"はOOPのクラスではなく商品規格を意味する」

---

## preOrderId (Order.pre_order_id / Cart.pre_order_id)

### Entity
- **File**: `src/Eccube/Entity/Order.php` (line 385) -- `dtb_order` にUNIQUE制約
- **File**: `src/Eccube/Entity/Cart.php` (line 93) -- `dtb_cart` にUNIQUE制約

### 実際の意味
「仮注文ID」。購入フロー中にCartとOrderを紐づける**一時的なランダムトークン**（`sha1(random(32))`）。「pre-order（予約注文）」ではない。

### 使われ方
```php
// OrderHelper.php: チェックアウト開始時に生成
$preOrderId = sha1(StringUtil::random(32));
// ShoppingController.php: pre_order_idで既存Orderを検索、なければ新規作成
// PreOrderIdValidator: カートのpre_order_idとセッションの整合性を検証
```

### 推奨doc
「購入フローの一時セッショントークン（SHA1ハッシュ）。CartとOrderを紐づける。予約注文IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去される」

---

## confirmUrl (Delivery.confirm_url)

### Entity
- **File**: `src/Eccube/Entity/Delivery.php` (lines 77-79)
- **Table**: `dtb_delivery`

### 実際の意味
**配送業者のお問い合わせ（追跡）URL**。管理画面のラベルは「お問い合わせ番号URL」/ "Tracking URL"。注文確認画面のURLではない。

### 使われ方
出荷通知メール (`shipping_notify.twig`):
```twig
{% if Shipping.tracking_number %}
お問い合わせ番号：{{ Shipping.tracking_number }}
{% if Shipping.Delivery.confirm_url %}
お問い合わせURL：{{ Shipping.Delivery.confirm_url }}
{% endif %}
```

### 推奨doc
「配送業者の荷物追跡ページURL。フィールド名は"confirm_url"だが注文確認URLではない。管理画面では"お問い合わせ番号URL"と表示される」

---

## secretKey (Customer.secret_key)

### Entity
- **File**: `src/Eccube/Entity/Customer.php` (lines 152-154)
- **Table**: `dtb_customer` (UNIQUE制約)

### 実際の意味
**会員アカウントの有効化/認証トークン**。暗号鍵やAPIシークレットではない。メール認証URLに使用されるランダム文字列。

### 使われ方
```php
// EntryController.php: メール認証URLを生成
$activateUrl = $this->generateUrl('entry_activate', ['secret_key' => $Customer->getSecretKey()]);
// /entry/activate/{secret_key} でアカウント有効化
```

### 推奨doc
「会員アカウントのメール認証トークン。/entry/activate/{secret_key} 形式のURLに使用。暗号鍵やAPIシークレットではない。会員登録時にランダム生成される」

---

## linkMethod (News.link_method)

### Entity
- **File**: `src/Eccube/Entity/News.php` (lines 86-88)
- **Type**: boolean, default false
- **Table**: `dtb_news`

### 実際の意味
**ブーリアンフラグ**: ニュースURLを新しいウィンドウで開くかどうか。`true`(1) = `target="_blank"`, `false`(0) = 同一ウィンドウ。

### 使われ方
管理画面のラベルは「別ウインドウを開く」。

### 推奨doc
「ニュースURLを新しいウィンドウで開くかどうかのフラグ。true=別ウィンドウ(target="_blank")、false=同一ウィンドウ。管理画面では"別ウインドウを開く"と表示」

---

## cartKey (Cart.cart_key)

### Entity
- **File**: `src/Eccube/Entity/Cart.php` (lines 60-62)
- **Table**: `dtb_cart`

### 実際の意味
**販売種別によるカート分離キー**。EC-CUBEは異なるSaleType（販売種別）の商品を同一カートに混在させない。形式: `{ランダムプレフィックス}_{saleTypeId}`。

### 使われ方
`SaleTypeCartAllocator` がSaleType IDを返し、`CartService.createCartKey()` がランダムプレフィックスと結合してカートキーを生成。

### 推奨doc
「カート分離キー。形式: {セッションプレフィックス}_{販売種別ID}。EC-CUBEは販売種別ごとにカートを分離するため、異なる販売種別の商品は別カートになる」

---

## deviceType (Master: DeviceType)

### 値

| id | name | sort_no |
|---|---|---|
| 2 | モバイル | 0 |
| 10 | PC | 1 |

### 非直感的な理由
EC-CUBE 2.x時代（ガラケー対応）の名残。タブレット(3)はソース上コメントアウト。ページレイアウトのデバイス切り替えに使用。

### 推奨doc
「デバイス種別マスタ（EC-CUBE 2.x からの名残）。値: 2=モバイル, 10=PC。非連番のIDは旧バージョンのデバイスサポート(ガラケー等)に由来。ページレイアウトのデバイス別表示に使用」

---

## pageEditType (Page.edit_type)

### 値

| 値 | 定数 | 意味 |
|---|---|---|
| 0 | EDIT_TYPE_USER | ユーザー作成ページ（編集・削除可） |
| 1 | EDIT_TYPE_PREVIEW | プレビューページ |
| 2 | EDIT_TYPE_DEFAULT | システムデフォルトページ（構造ロック、削除不可） |
| 3 | EDIT_TYPE_DEFAULT_CONFIRM | 編集可能なシステムページ（利用規約等） |

### 推奨doc
「ページ編集レベル。0=ユーザー作成（完全編集/削除可）、1=プレビュー、2=システムページ（構造ロック・削除不可）、3=内容編集可能なシステムページ（利用規約等）。editType >= 2は削除不可」

---

## authority (Master: Authority)

### 値

| id | name | sort_no |
|---|---|---|
| 0 | システム管理者 | 0 |
| 1 | 店舗オーナー | 1 |

### 非直感的な理由
0が最高権限。通常の権限システムとは逆。`AuthorityVoter` がメンバーのAuthorityに基づいてURL拒否パターンを適用する。

### 推奨doc
「管理者権限レベル。0=システム管理者（最高権限）、1=店舗オーナー（制限あり）。数値が小さいほど権限が高い。AuthorityRoleのURL拒否パターンでアクセス制御される」

---

## doc改善サマリー

| ID | 推奨doc（日本語簡潔版） | 理由 |
|---|---|---|
| `classNameLabel` | 商品バリエーション軸名（例: カラー、サイズ）。EC-CUBEの"class"=商品規格、OOPのクラスではない | OOPクラスと混同 |
| `classCategoryName` | 受注時のバリエーション値スナップショット（例: 赤、L）。ClassCategoryから非正規化コピー | OOPカテゴリと混同 |
| `preOrderId` | 購入フロー一時トークン（SHA1）。CartとOrderを紐づけ。予約注文IDではない | 英語"pre-order"=予約注文と誤解 |
| `confirmUrl` | 配送業者の荷物追跡URL。注文確認URLではない | "confirm"=確認画面を連想 |
| `secretKey` | メール認証トークン。/entry/activate/{secret_key}で使用。暗号鍵ではない | 暗号鍵と混同 |
| `linkMethod` | ニュースURLを別ウィンドウで開くかのフラグ。true=_blank | "method"が抽象的 |
| `cartKey` | カート分離キー: {prefix}_{saleTypeId}。販売種別ごとにカートを分離 | 単純な識別子に見える |
| `deviceType` | デバイス種別マスタ。2=モバイル、10=PC。EC-CUBE 2.x由来の非連番ID | 非連番で意味不明 |
| `pageEditType` | ページ編集レベル。0=ユーザー作成、1=プレビュー、2=システム(ロック)、3=システム(編集可) | 0-3の意味が不明 |
| `authority` | 管理者権限。0=システム管理者(最高)、1=店舗オーナー(制限)。小さいほど高権限 | 0=最高権限が直感に反する |
{% endraw %}
