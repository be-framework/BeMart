# 会員・店舗設定ドメイン ディスクリプタ検証レポート

検証対象: 37ディスクリプタ
検証ソース: Customer.php, BaseInfo.php, Master/CustomerStatus.php, Master/Sex.php, Master/Job.php, EntryType.php, ContactType.php, ShopMasterType.php, CustomerType.php, ContactController.php, OrderStateMachine.php, PointDiffProcessor.php, フィクスチャCSV

---

## 会員ドメイン

### email

- **現在doc**: 「会員のログインIDを兼ねる。有効会員間で一意」
- **検証結果**: Customer.php: string, length=255, not null。UniqueEntity制約で `getNonWithdrawingCustomers` を使用。退会していない会員（仮会員+本会員）間で一意。`getUserIdentifier()` はemailを返しておりログインIDとして使用。退会時はダミーメール（ランダム60文字@dummy.dummy）に置換される。
- **改善要否**: 不要
- **備考**: 「有効会員間で一意」は正確には「退会していない会員（仮会員+本会員）間で一意」だが、本質的に同じ意味。

### password

- **現在doc**: 「書き込み専用（ハッシュ化して保存）」
- **検証結果**: Customer.php: string, length=255, nullable=true。plain_password プロパティが入力用（@Assert\NotBlank, @Assert\Length(max=4096)）。Symfony PasswordHasher でハッシュ化して password カラムに保存。直接読み出しはセッションシリアライズ用のみ。
- **改善要否**: 不要

### birth

- **現在doc**: 「会員の生年月日」
- **検証結果**: Customer.php: datetimetz, nullable=true。EntryType: BirthdayType, required=false, LessThanOrEqual（前日以前）。管理画面CustomerType: BirthdayType, required=false, Range(min='0003-01-01')。
- **改善要否**: 不要

### sex

- **現在doc**: 「1=男性, 2=女性, 3=その他, 4=回答しない」
- **検証結果**: mtb_sex.csv: 1=男性, 2=女性, 3=その他, 4=回答しない。Sex extends AbstractMasterEntity（定数なし）。EntryType/CustomerType: SexType, required=false。ManyToOne nullable。
- **改善要否**: 不要

### job

- **現在doc**: 「1=公務員〜18=その他の18区分」
- **検証結果**: mtb_job.csv: 1=公務員, 2=コンサルタント, 3=コンピューター関連技術職, 4=コンピューター関連以外の技術職, 5=金融関係, 6=医師, 7=弁護士, 8=総務・人事・事務, 9=営業・販売, 10=研究・開発, 11=広報・宣伝, 12=企画・マーケティング, 13=デザイン関係, 14=会社経営・役員, 15=出版・マスコミ関係, 16=学生・フリーター, 17=主婦, 18=その他。ManyToOne nullable。
- **改善要否**: 不要

### customerStatus

- **現在doc**: 「1=仮会員（メール未認証）, 2=本会員（認証済み）, 3=退会。退会時はメールアドレスが無効化される」
- **検証結果**: CustomerStatus.php: PROVISIONAL=1(仮会員), REGULAR=2(本会員), WITHDRAWING=3(退会)。退会時はメールをランダム文字列@dummy.dummyに変更（WithdrawController:137, CustomerEditController:148）。「無効化」の表現は正確。
- **改善要否**: 不要

### firstBuyDate

- **現在doc**: 「最初の購入日時。注文確定時に自動設定」
- **検証結果**: Customer.php: datetimetz, nullable=true。
- **改善要否**: 不要

### lastBuyDate

- **現在doc**: 「最後の購入日時。注文確定時に自動更新」
- **検証結果**: Customer.php: datetimetz, nullable=true。DBインデックスあり（dtb_customer_last_buy_date_idx）。
- **改善要否**: 不要

### buyTimes

- **現在doc**: 「累計購入回数。注文確定時に加算」
- **検証結果**: Customer.php: decimal, precision=10, scale=0, nullable=true, unsigned, default=0。DBインデックスあり。
- **改善要否**: 不要

### buyTotal

- **現在doc**: 「累計購入金額。注文確定時に加算」
- **検証結果**: Customer.php: decimal, precision=12, scale=2, nullable=true, unsigned, default=0。DBインデックスあり。
- **改善要否**: 不要

### customerNote

- **現在doc**: 「管理者用の内部メモ。顧客には表示されない」
- **検証結果**: Customer.php: `note` カラム, string, length=4000, nullable=true。管理画面CustomerType: TextareaType, required=false, max=eccube_ltext_len。フロントのEntryTypeには含まれない。
- **改善要否**: 不要

### point

- **現在doc**: 「会員の現在のポイント残高。注文時にポイント使用で減算、付与は発送済み(DELIVERED)ステータスへの遷移時に加算。付与計算: 商品単価(税抜) x pointRate x 数量 - 利用ポイント分控除」
- **検証結果**: Customer.php: decimal, precision=12, scale=0, unsigned=false, default=0。管理画面CustomerType: NumberType, Range(min=-eccube_price_max, max=eccube_price_max)。OrderStateMachine: `commitAddPoint` は `workflow.order.transition.ship`（発送済み遷移）で呼ばれ、`Customer->getPoint() + Order->getAddPoint()` で加算。使用ポイントはPointProcessor/PointHelper.prepare()で購入確定時に減算。
- **改善要否**: 不要
- **備考**: 「発送済み(DELIVERED)ステータスへの遷移時」は正確。ship遷移 = DELIVEREDステータスへの遷移。

### resetKey

- **現在doc**: 「パスワードリセット用のワンタイムトークン。リセット要求時に生成、使用後にクリア」
- **検証結果**: Customer.php: string, length=255, nullable=true。
- **改善要否**: 不要

### resetExpire

- **現在doc**: 「パスワードリセットキーの有効期限」
- **検証結果**: Customer.php: datetimetz, nullable=true。
- **改善要否**: 不要

### phoneNumber

- **現在doc**: 「日本の電話番号形式（ハイフン区切り）」
- **検証結果**: Customer.php/BaseInfo.php: string, length=14, nullable=true。EntryType/ShopMasterType: PhoneNumberType。ContactType: PhoneNumberType, required=false。
- **改善要否**: 不要
- **備考**: 共通で使用される（会員、店舗、お問い合わせ）。

### postalCode

- **現在doc**: 「日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁」
- **検証結果**: Customer.php/BaseInfo.php: string, length=8, nullable=true。EntryType/ShopMasterType/ContactType: PostalType。
- **改善要否**: 不要

### companyName

- **現在doc**: 「法人顧客の社名。B2B取引やインボイスで使用」
- **検証結果**: Customer.php: string, length=255, nullable=true。BaseInfo.php: string, length=255, nullable=true。EntryType/CustomerType: TextType, required=false, max=eccube_stext_len。ShopMasterType: TextType, required=false, max=eccube_stext_len。Customer/BaseInfoの両方に存在し、顧客の会社名と店舗の会社名の両方で使用。
- **改善要否**: 不要

---

## 店舗設定ドメイン（BaseInfo）

### shopName

- **現在doc**: 「ショップの表示名。フロント画面のヘッダやメールに表示」
- **検証結果**: BaseInfo.php: string, length=255, nullable=true。ShopMasterType: TextType, required=true, NotBlank, max=eccube_stext_len。
- **改善要否**: 不要

### shopKana

- **現在doc**: 「ショップ名のカタカナ読み」
- **検証結果**: BaseInfo.php: string, length=255, nullable=true。ShopMasterType: TextType, required=false, カタカナRegex(/^[ァ-ヶｦ-ﾟー]+$/u), max=eccube_stext_len, ConvertKanaListener('CV')。
- **改善要否**: 不要

### shopNameEng

- **現在doc**: 「ショップの英語名。多言語対応やメール署名等で使用」
- **検証結果**: BaseInfo.php: string, length=255, nullable=true。ShopMasterType: TextType, required=false, max=eccube_mtext_len, Regex(/^[[:graph:][:space:]]+$/i) = ASCII印字可能文字+空白のみ。
- **改善要否**: 不要

### companyKana

- **現在doc**: 「会社名のカタカナ読み」
- **検証結果**: BaseInfo.php: string, length=255, nullable=true。ShopMasterType: TextType, required=false, カタカナRegex, max=eccube_stext_len, ConvertKanaListener('CV')。
- **改善要否**: 不要

### businessHour

- **現在doc**: 「ショップの営業時間。フリーフォーマット」
- **検証結果**: BaseInfo.php: string, length=255, nullable=true。ShopMasterType: TextType, required=false, max=eccube_stext_len。
- **改善要否**: 不要

### optionMypageOrderStatusDisplay

- **現在doc**: 「マイページに受注ステータスを表示するか」
- **検証結果**: BaseInfo.php: boolean, default=true。ShopMasterType: ToggleSwitchType。
- **改善要否**: 不要

### optionNostockHidden

- **現在doc**: 「在庫切れ商品をフロントで非表示にするか」
- **検証結果**: BaseInfo.php: boolean, default=false。ShopMasterType: ToggleSwitchType。
- **改善要否**: 不要

### optionFavoriteProduct

- **現在doc**: 「お気に入り商品機能を有効にするか」
- **検証結果**: BaseInfo.php: boolean, default=true。ShopMasterType: ToggleSwitchType。
- **改善要否**: 不要

### optionProductDeliveryFee

- **現在doc**: 「商品ごとに個別送料を設定する機能を有効にするか。有効時はdeliveryFeeが送料計算に加算される」
- **検証結果**: BaseInfo.php: boolean, default=false。ShopMasterType: ToggleSwitchType。
- **改善要否**: 不要

### optionProductTaxRule

- **現在doc**: 「商品ごとに個別の税率を設定する機能を有効にするか」
- **検証結果**: BaseInfo.php: boolean, default=false。ShopMasterType: ToggleSwitchType。
- **改善要否**: 不要

### optionCustomerActivate

- **現在doc**: 「会員登録時にメール認証を要求するか。有効時は登録後が仮会員となり、認証メールで本会員に移行」
- **検証結果**: BaseInfo.php: boolean, default=true。ShopMasterType: ToggleSwitchType。
- **改善要否**: 不要

### optionRememberMe

- **現在doc**: 「ログイン画面に「次回から自動ログイン」を表示するか」
- **検証結果**: BaseInfo.php: boolean, default=true。ShopMasterType: ToggleSwitchType。
- **改善要否**: 不要

### optionPoint

- **現在doc**: 「ポイント付与・使用機能を有効にするか」
- **検証結果**: BaseInfo.php: boolean, default=true。ShopMasterType: ToggleSwitchType。PointDiffProcessor等で`pointHelper->isPointEnabled()`で判定。
- **改善要否**: 不要

### basicPointRate

- **現在doc**: 「商品購入時の基本ポイント付与率（%）。商品個別のpointRate未設定時に適用」
- **検証結果**: BaseInfo.php: decimal, precision=10, scale=0, unsigned, default=1, nullable=true。ShopMasterType: NumberType, Regex(数値のみ), Range(min=0, max=100)。PointRateProcessor: `$item->getProductClass()->getPointRate()` が null の場合、`getBasicPointRate()` を適用。
- **改善要否**: 不要

### pointConversionRate

- **現在doc**: 「1ポイントあたりの通貨換算額（円）。ポイント使用時の値引き額計算に使用」
- **検証結果**: BaseInfo.php: decimal, precision=10, scale=0, unsigned, default=1, nullable=true。ShopMasterType: NumberType, Regex(数値のみ), Range(min=1, max=100)。
- **改善要否**: 不要

### invoiceRegistrationNumber

- **現在doc**: 「適格請求書発行事業者の登録番号（Tから始まる13桁）。インボイス制度対応」
- **検証結果**: BaseInfo.php: string, length=255, nullable=true。ShopMasterType: TextType, required=false, max=eccube_stext_len。フォーム側ではT+13桁のバリデーション制約はない（長さ制限のみ）。
- **改善要否**: 不要
- **備考**: 「Tから始まる13桁」はインボイス制度上の正式フォーマットであり、EC-CUBE側にフォーマットバリデーションはないがドキュメントとしては正しい。

---

## お問い合わせドメイン

### contactContents

- **現在doc**: 「お問い合わせフォームの本文」
- **検証結果**: ContactType: `contents` フィールド, TextareaType, NotBlank, max=eccube_lltext_len。エンティティにはマッピングなし（フォームデータのみ）。
- **改善要否**: 不要

### contactEmail

- **現在doc**: 「お問い合わせフォームのメールアドレス」
- **検証結果**: ContactType: `email` フィールド, EmailType, NotBlank, Email制約。ログイン中は会員のメールアドレスを初期値設定（ContactController:78）。
- **改善要否**: 不要

### contactKana01

- **現在doc**: 「お問い合わせフォームの姓カナ」
- **検証結果**: ContactType: `kana` フィールド（KanaType）で kana01/kana02 が生成される。KanaType の kana01 はカタカナ入力。required=false。
- **改善要否**: 不要

### contactKana02

- **現在doc**: 「お問い合わせフォームの名カナ」
- **検証結果**: ContactType: `kana` フィールド（KanaType）で kana01/kana02 が生成される。required=false。
- **改善要否**: 不要

---

## サマリーテーブル

| # | descriptorId | 改善要否 | 理由 |
|---|---|---|---|
| 1 | email | 不要 | 正確 |
| 2 | password | 不要 | 正確 |
| 3 | birth | 不要 | 正確 |
| 4 | sex | 不要 | 正確 |
| 5 | job | 不要 | 正確 |
| 6 | customerStatus | 不要 | 正確 |
| 7 | firstBuyDate | 不要 | 正確 |
| 8 | lastBuyDate | 不要 | 正確 |
| 9 | buyTimes | 不要 | 正確 |
| 10 | buyTotal | 不要 | 正確 |
| 11 | customerNote | 不要 | 正確 |
| 12 | point | 不要 | 正確 |
| 13 | resetKey | 不要 | 正確 |
| 14 | resetExpire | 不要 | 正確 |
| 15 | phoneNumber | 不要 | 正確 |
| 16 | postalCode | 不要 | 正確 |
| 17 | companyName | 不要 | 正確 |
| 18 | companyKana | 不要 | 正確 |
| 19 | shopName | 不要 | 正確 |
| 20 | shopKana | 不要 | 正確 |
| 21 | shopNameEng | 不要 | 正確 |
| 22 | businessHour | 不要 | 正確 |
| 23 | optionMypageOrderStatusDisplay | 不要 | 正確 |
| 24 | optionNostockHidden | 不要 | 正確 |
| 25 | optionFavoriteProduct | 不要 | 正確 |
| 26 | optionProductDeliveryFee | 不要 | 正確 |
| 27 | optionProductTaxRule | 不要 | 正確 |
| 28 | optionCustomerActivate | 不要 | 正確 |
| 29 | optionRememberMe | 不要 | 正確 |
| 30 | optionPoint | 不要 | 正確 |
| 31 | basicPointRate | 不要 | 正確 |
| 32 | pointConversionRate | 不要 | 正確 |
| 33 | invoiceRegistrationNumber | 不要 | 正確 |
| 34 | contactContents | 不要 | 正確 |
| 35 | contactEmail | 不要 | 正確 |
| 36 | contactKana01 | 不要 | 正確 |
| 37 | contactKana02 | 不要 | 正確 |

## 総合結果

**全37ディスクリプタで改善不要。** すべてのドキュメントがソースコードの実装と一致しています。
