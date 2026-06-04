{% raw %}
# 番号サフィックス系 + 略語系の検証結果

EC-CUBE 4.3 ソースコードから、ALPS プロファイルの曖昧なセマンティック ID を検証した結果。

---

## name01 / name02

### Entity

`Customer`, `Order`, `Shipping`, `CustomerAddress` の4エンティティで共通使用。

```php
// src/Eccube/Entity/Customer.php:54-63
/** @ORM\Column(name="name01", type="string", length=255) */
private $name01;
/** @ORM\Column(name="name02", type="string", length=255) */
private $name02;
```

PHPDoc にはセマンティクスの記述なし（`@var string` のみ）。

### FormType

`NameType` (`src/Eccube/Form/Type/NameType.php`) が複合フィールドとして構成:

- `name01` = `lastname_name`（姓）、placeholder: `common.last_name` = **姓**
- `name02` = `firstname_name`（名）、placeholder: `common.first_name` = **名**

コード上の変数名 `lastname_options` / `firstname_options` が意味を明示。

### Template

```twig
{# admin/Order/mail.twig:71 #}
{{ Order.name01 }} {{ Order.name02 }}（{{ Order.kana01 }} {{ Order.kana02 }}）
```

常に「姓 名」の順序で表示。

### 現在の ALPS doc

- `name01`: "顧客・受注・配送先・お問い合わせで共通使用される姓"
- `name02`: "顧客・受注・配送先・お問い合わせで共通使用される名"

### 推奨 doc 改善

現状の doc で十分。改善不要。

---

## kana01 / kana02

### Entity

`Customer`, `Order`, `Shipping`, `CustomerAddress` の4エンティティで共通使用。

```php
// src/Eccube/Entity/Customer.php:65-77
/** @ORM\Column(name="kana01", type="string", length=255, nullable=true) */
private $kana01;
/** @ORM\Column(name="kana02", type="string", length=255, nullable=true) */
private $kana02;
```

### FormType

`KanaType` (`src/Eccube/Form/Type/KanaType.php`) が `NameType` を継承し、カタカナバリデーション (`/^[ァ-ヶｦ-ﾟー]+$/u`) と `ConvertKanaListener`（ひらがな→カタカナ自動変換）を追加:

- `kana01` = `lastname_options`（セイ）、placeholder: `common.last_name_kana` = **セイ**
- `kana02` = `firstname_options`（メイ）、placeholder: `common.first_name_kana` = **メイ**

### Template

```twig
{# admin/Order/mail_confirm.twig:62 #}
{{ Order.name01 }} {{ Order.name02 }}（{{ Order.kana01 }} {{ Order.kana02 }}）
```

氏名の後ろに括弧付きで「セイ メイ」表示。

### 現在の ALPS doc

- `kana01`: "姓のカタカナ読み。日本の氏名入力に特有の読み仮名"
- `kana02`: "名のカタカナ読み。日本の氏名入力に特有の読み仮名"

### 推奨 doc 改善

`kana01`: "姓のカタカナ読み（セイ）。全角カタカナのみ許可。ひらがな入力は自動変換される"
`kana02`: "名のカタカナ読み（メイ）。全角カタカナのみ許可。ひらがな入力は自動変換される"

バリデーションルールとひらがな自動変換の情報は API 利用者にとって有用。

---

## addr01 / addr02

### Entity

`Customer`, `Order`, `Shipping`, `CustomerAddress`, `BaseInfo` の5エンティティで共通使用。

```php
// src/Eccube/Entity/Customer.php:93-105
/** @ORM\Column(name="addr01", type="string", length=255, nullable=true) */
private $addr01;
/** @ORM\Column(name="addr02", type="string", length=255, nullable=true) */
private $addr02;
```

### FormType

`AddressType` (`src/Eccube/Form/Type/AddressType.php`) が `pref` + `addr01` + `addr02` の複合フィールド:

- `addr01`: class=`p-locality p-street-address`、placeholder: `common.address_sample_01` = **市区町村名(例：大阪市北区)**
- `addr02`: class=`p-extended-address`、placeholder: `common.address_sample_02` = **番地・ビル名(例：西梅田1丁目6-8)**

CSS class は [yubinbango](https://github.com/yubinbango/yubinbango) の郵便番号自動入力に対応。

### Template

```twig
{# admin/Order/edit.twig:518 #}
{% set shipping_addr = '〒' ~ shipping.postal_code ~ ' ' ~ shipping.pref ~ shipping.addr01 ~ shipping.addr02 %}
```

住所表示順: 〒郵便番号 + 都道府県 + 市区町村(addr01) + 番地・建物名(addr02)

### 現在の ALPS doc

- `addr01`: "都道府県より下位の市区町村名"
- `addr02`: "番地・ビル名・部屋番号等の詳細住所"

### 推奨 doc 改善

現状の doc で十分。改善不要。

---

## price01 / price02

### Entity

`ProductClass` エンティティのみで使用。

```php
// src/Eccube/Entity/ProductClass.php:211-223
/** @ORM\Column(name="price01", type="decimal", precision=12, scale=2, nullable=true) */
private $price01;  // nullable = 任意入力
/** @ORM\Column(name="price02", type="decimal", precision=12, scale=2) */
private $price02;  // NOT NULL = 必須入力
```

`Product` エンティティには集約プロパティ `$price01[]`, `$price02[]`（規格ごとの価格配列）が存在。

翻訳ファイルのコメントに直接対応関係が記載:

```yaml
# src/Eccube/Resource/locale/messages.ja.yaml:649-650
admin.product.sale_price: 販売価格 # price02
admin.product.normal_price: 通常価格 # price01
```

### FormType

`ProductClassType` / `ProductClassEditType` (`src/Eccube/Form/Type/Admin/ProductClassType.php`):

- `price01`: `PriceType` (required=false) → 通常価格
- `price02`: `PriceType` (required=true) → 販売価格

### Template

```twig
{# admin/Product/product.twig:392 #}
{{ 'admin.product.sale_price'|trans }}   → price02 のラベル（販売価格）
{{ 'admin.product.normal_price'|trans }} → price01 のラベル（通常価格）

{# default/Product/detail.twig:315 #}
{{ 'front.product.normal_price'|trans }}：{{ Product.getPrice01IncTaxMin|price }}  → 通常価格
{# price02 はメイン価格として表示（ラベルなし） #}
```

### 現在の ALPS doc

- `price01`: "メーカー希望小売価格や参考価格。表示専用で計算には使用されない"
- `price02`: "実際の販売価格（税抜）。税計算・小計計算のベース"

### 推奨 doc 改善

現状の doc で十分。price01 が nullable（任意）、price02 が必須という点も正確に反映されている。改善不要。

---

## shopEmail01 / shopEmail02 / shopEmail03 / shopEmail04

### Entity

`BaseInfo` エンティティの `email01`〜`email04` に対応。

```php
// src/Eccube/Entity/BaseInfo.php:96-122
/** @ORM\Column(name="email01", type="string", length=255, nullable=true) */
private $email01;
/** @ORM\Column(name="email02", type="string", length=255, nullable=true) */
private $email02;
/** @ORM\Column(name="email03", type="string", length=255, nullable=true) */
private $email03;
/** @ORM\Column(name="email04", type="string", length=255, nullable=true) */
private $email04;
```

### FormType

`ShopMasterType` (`src/Eccube/Form/Type/Admin/ShopMasterType.php`) で4つとも `EmailType` として定義。

### Template (shop_master.twig)

テンプレート上のラベルと翻訳キーの対応:

| フィールド | 翻訳キー | 日本語ラベル |
|:--|:--|:--|
| `email01` | `admin.setting.shop.shop.email_from` | 送信元メールアドレス(From) |
| `email02` | `admin.setting.shop.shop.email_for_inquiries` | 問い合わせ専用メールアドレス(From, ReplyTo) |
| `email03` | `admin.setting.shop.shop.email_reply_to` | 返信先メールアドレス(ReplyTo) |
| `email04` | `admin.setting.shop.shop.email_return_path` | 送信エラー通知メールアドレス(ReturnPath) |

メールテンプレートでの実際の使用:
- `email02` はメールテンプレート内で「お心当たりが無い場合は、その旨 {{ BaseInfo.email02 }} まで」のように顧客向け問い合わせ先として表示

### 現在の ALPS doc

- `shopEmail01`: "ほぼ全メール種別の送信元（From）兼ショップ控え（BCC）アドレス"
- `shopEmail02`: "MailService.phpではお問い合わせメールの送信元（From）/BCC/Reply-To として使用。他のメール種別での使用は未確認"
- `shopEmail03`: "Reply-To ヘッダに設定されるアドレス。顧客がメールに返信した際の宛先"
- `shopEmail04`: "Return-Path ヘッダに設定されるアドレス。メール配信エラー（バウンスメール）の通知先"

### 推奨 doc 改善

`shopEmail02` の doc を改善:

"問い合わせ専用メールアドレス。お問い合わせメールの From/BCC/Reply-To として使用。また、メールテンプレート内で顧客向けの問い合わせ先として表示される"

現在の「他のメール種別での使用は未確認」という表現は不正確。テンプレート内で顧客がお問い合わせする際の連絡先として、注文確認メール・会員登録メール・退会メール等で広く表示されている。

---

## contactName01 / contactName02

### Entity

専用の Entity は存在しない。お問い合わせフォームは `ContactType` で定義され、データは `Contact` エンティティではなくメール送信のみに使用される。

### FormType

`ContactType` (`src/Eccube/Form/Type/Front/ContactType.php`):

```php
$builder->add('name', NameType::class, ['required' => true]);
```

`NameType` の `buildForm` で `name01` / `name02` が自動生成される（`$builder->getName().'01'` / `'02'`）。
つまり `contact[name][name01]` / `contact[name][name02]` としてフォーム内に展開される。

### Template

お問い合わせフォームテンプレートでは `form.name.name01` / `form.name.name02` としてアクセス。

### 現在の ALPS doc

- `contactName01`: "お問い合わせフォームの姓"
- `contactName02`: "お問い合わせフォームの名"

### 推奨 doc 改善

`contactName01`: "お問い合わせフォームの姓。NameType による name01 と同じバリデーション（空白禁止、最大文字数制限）が適用される"
`contactName02`: "お問い合わせフォームの名。NameType による name02 と同じバリデーション（空白禁止、最大文字数制限）が適用される"

内部的には `name01`/`name02` と同じ仕組みであることの言及があると API 利用者の理解が深まる。

---

## pref

### Entity

`Customer`, `Order`, `Shipping`, `CustomerAddress`, `BaseInfo`, `DeliveryFee`, `TaxRule` の7エンティティで使用。
`Master\Pref` エンティティ (`AbstractMasterEntity` を継承) への ManyToOne リレーション。

```php
// src/Eccube/Entity/Customer.php
/** @ORM\JoinColumn(name="pref_id", referencedColumnName="id") */
private $Pref;
```

### FormType

`AddressType` 内で `PrefType`（`src/Eccube/Form/Type/Master/PrefType.php`）として使用。
`SearchCustomerType` では `label: 'admin.common.pref'` = **都道府県**。

### Template

```twig
{# admin/Order/edit.twig:518 #}
{{ shipping.pref }}{{ shipping.addr01 }}{{ shipping.addr02 }}
```

住所の先頭要素として都道府県名を表示。`DeliveryFee` では配送料の地域区分として使用。

### 現在の ALPS doc

"1=北海道〜47=沖縄県。送料計算の地域区分に使用"

### 推奨 doc 改善

"日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用される"

送料計算だけでなく、住所の一部としての主要用途と税率設定での用途も記載すべき。

---

## work

### Entity

`Member` エンティティで `Master\Work` への ManyToOne リレーション。

```php
// src/Eccube/Entity/Member.php:179-184
/** @ORM\JoinColumn(name="work_id", referencedColumnName="id") */
private $Work;
```

マスターデータ (`mtb_work.csv`):
- `0` = 非稼働
- `1` = 稼働

### FormType

`MemberType` で使用（ラジオボタン形式）。

### Template

```twig
{# admin/Setting/System/member.twig:91 #}
{{ Member.Work.name }}
```

翻訳キー: `admin.setting.system.member.work` = **稼働**

### 現在の ALPS doc

"Work Masterの定数で定義: 0=NON_ACTIVE（非稼働、ログイン不可）, 1=ACTIVE（稼働、ログイン可能）。管理者メンバーの有効/無効を制御"

### 推奨 doc 改善

現状の doc で十分。改善不要。

---

## freeArea

### Entity

`Product` エンティティで使用。

```php
// src/Eccube/Entity/Product.php:498-501
/** @ORM\Column(name="free_area", type="text", nullable=true) */
private $free_area;
```

### FormType

`ProductType` (`src/Eccube/Form/Type/Admin/ProductType.php`):

```php
->add('free_area', TextareaType::class, [
    'purify_html' => true,  // HTML浄化あり
    'required' => false,
    'constraints' => [new TwigLint(), ...],
])
```

`TwigLint` バリデーションが適用 → Twig テンプレート構文として正しい必要がある。

翻訳キー: `admin.product.free_area` = **フリーエリア**

### Template

管理画面では WYSIWYG エディタ（ACE Editor）で編集:

```twig
{# admin/Product/product.twig:674 #}
{{ form_widget(form.free_area, {id: 'wysiwyg-area', attr : { rows : "8"} }) }}
```

フロント画面では Twig テンプレートとして評価・サンドボックス実行:

```twig
{# default/Product/detail.twig:440-442 #}
{% if Product.freearea %}
    {{ include(template_from_string(Product.freearea), sandboxed = true) }}
{% endif %}
```

### 現在の ALPS doc

"商品詳細ページ下部に表示される自由入力エリア。HTML入力可能"

### 推奨 doc 改善

"商品詳細ページ下部に表示される自由入力エリア。HTML/Twig テンプレート構文が使用可能（TwigLint でバリデーション、サンドボックス内で実行）。HTMLPurifier による浄化あり"

Twig 構文が使えることと、セキュリティ機構（サンドボックス、HTMLPurifier）の情報は API 利用者にとって重要。

---

## goodTraded

### Entity

`BaseInfo` エンティティで使用。

```php
// src/Eccube/Entity/BaseInfo.php:152-157
/** @ORM\Column(name="good_traded", type="string", length=4000, nullable=true) */
private $good_traded;
```

### FormType

`ShopMasterType` で `TextareaType` として定義:

```php
->add('good_traded', TextareaType::class, ['required' => false])
```

### Template

管理画面: `admin.setting.shop.shop.good_traded` = **取り扱い商品説明文**

```twig
{# admin/Setting/Shop/shop_master.twig:157 #}
{{ 'admin.setting.shop.shop.good_traded'|trans }}
```

フロント（当サイトについてページ）: `front.about.good_traded` = **取り扱い商品**

```twig
{# default/Help/about.twig:71 #}
{{ 'front.about.good_traded'|trans }}
{{ BaseInfo.good_traded|nl2br }}
```

### 現在の ALPS doc

"取扱商品の説明文。特定商取引法の表記で使用"

### 推奨 doc 改善

"店舗の取り扱い商品の説明文。「当サイトについて」ページに表示される。特定商取引法に基づく表記での使用を想定"

「当サイトについて」ページでの表示という具体的な表示箇所を追加。

---

## message（Order.message / BaseInfo.message）

### Entity

2つの Entity で異なる意味で使用:

1. `Order.message` — 注文時のお問い合わせ

```php
// src/Eccube/Entity/Order.php:396-401
/** @ORM\Column(name="message", type="string", length=4000, nullable=true) */
private $message;
```

2. `BaseInfo.message` — 店舗からのメッセージ

```php
// src/Eccube/Entity/BaseInfo.php:159-164
/** @ORM\Column(name="message", type="string", length=4000, nullable=true) */
private $message;
```

### FormType

- `OrderType` (`Shopping/OrderType.php`): `TextareaType`, max 3000文字
- `ShopMasterType`: `TextareaType`, max `eccube_ltext_len`

### Template

Order の message:

```twig
{# 管理画面 admin/Order/edit.twig:484 #}
{{ 'admin.order.message'|trans }}  → お問い合わせ

{# フロント default/Shopping/index.twig:384 #}
{{ 'front.shopping.message_info'|trans }}  → お問い合わせ
placeholder: お問い合わせ事項がございましたら、こちらにご入力ください。
```

BaseInfo の message:

```twig
{# default/Help/about.twig:80 #}
{{ 'front.about.message'|trans }}  → メッセージ
{{ BaseInfo.message|nl2br }}
```

### ALPS での区別

ALPS プロファイルではこの2つを正しく分離:
- `message` (Order のコンテキスト) → "注文メッセージ"
- `shopMessage` (BaseInfo のコンテキスト) → "ショップメッセージ"

### 現在の ALPS doc

- `message`: "顧客が注文時に入力する備考・配送希望メモ"
- `shopMessage`: "トップページ等に表示するショップからのメッセージ"

### 推奨 doc 改善

`message`:
"顧客が注文時に入力するお問い合わせ欄。ラベルは「お問い合わせ」で、備考欄ではない。最大3000文字"

現在の doc「備考・配送希望メモ」は不正確。EC-CUBE のラベル・placeholder ともに「お問い合わせ」であり、備考欄ではない。

`shopMessage`:
"「当サイトについて」ページに表示される店舗からのメッセージ"

「トップページ等」は不正確。実際の表示箇所は `Help/about.twig`（当サイトについてページ）。

---

## doc 改善サマリー

| ID | 現在の doc (要約) | 推奨 doc | 改善理由 |
|:--|:--|:--|:--|
| `name01` | 姓 | 改善不要 | - |
| `name02` | 名 | 改善不要 | - |
| `kana01` | 姓のカタカナ読み | 全角カタカナのみ許可、ひらがな自動変換の記述追加 | バリデーションルールが API 利用者に有用 |
| `kana02` | 名のカタカナ読み | 同上 | 同上 |
| `addr01` | 市区町村名 | 改善不要 | - |
| `addr02` | 番地・ビル名 | 改善不要 | - |
| `price01` | 通常価格、表示専用 | 改善不要 | - |
| `price02` | 販売価格（税抜） | 改善不要 | - |
| `shopEmail01` | 送信元(From)兼BCC | 改善不要 | - |
| `shopEmail02` | 問い合わせメールの送信元 | メールテンプレート内で顧客向け問い合わせ先としても表示される点を追加 | 「他のメール種別での使用は未確認」が不正確 |
| `shopEmail03` | Reply-To アドレス | 改善不要 | - |
| `shopEmail04` | Return-Path アドレス | 改善不要 | - |
| `contactName01` | お問い合わせの姓 | NameType の name01 と同じ仕組みである旨を追加 | 内部構造の理解に有用 |
| `contactName02` | お問い合わせの名 | 同上 | 同上 |
| `pref` | 都道府県、送料計算用 | 住所の最上位区分としての主要用途と税率設定での用途を追加 | 送料計算だけが用途ではない |
| `work` | 稼働状態 | 改善不要 | - |
| `freeArea` | 自由入力エリア、HTML可 | Twig 構文使用可、サンドボックス実行、HTMLPurifier 浄化の記述追加 | セキュリティ機構の情報が重要 |
| `goodTraded` | 取扱商品の説明文 | 「当サイトについて」ページでの表示箇所を追加 | 表示箇所の明示 |
| `message` | 備考・配送希望メモ | **「お問い合わせ」に修正**。備考欄ではない。最大3000文字 | ラベル・placeholder と不一致 |
| `shopMessage` | トップページ等に表示 | **「当サイトについてページ」に修正** | 表示箇所が不正確 |
{% endraw %}
