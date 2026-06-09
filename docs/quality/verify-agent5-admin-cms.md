---
layout: default
title: "Agent 5: 管理・CMS・メール・プラグインドメイン ディスクリプタ検証"
---

# Agent 5: 管理・CMS・メール・プラグインドメイン ディスクリプタ検証

## 検証対象

管理者(Member)、ページ(Page)、レイアウト(Layout)、ブロック(Block)、ニュース(News)、メール(MailTemplate/MailHistory)、ログイン履歴(LoginHistory)、CSV(Csv)、プラグイン(Plugin)、テンプレート(Template)、特定商取引法(TradeLaw)、共通(createDate/updateDate)

---

### memberName

- **現在doc**: 管理者メンバーの表示名
- **検証結果**: `Member.php` — `$name`: string|null, ORM Column `name`, varchar(255), nullable=true。FormType(MemberType)では NotBlank + Length(max=eccube_stext_len)。管理者の名前。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### department

- **現在doc**: 管理者の所属部署名
- **検証結果**: `Member.php` — `$department`: string|null, ORM Column `department`, varchar(255), nullable=true。FormType(MemberType)では required=false だが constraints に NotBlank + Length(max=eccube_stext_len) がある。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### loginId

- **現在doc**: 管理画面ログイン用のID。一意
- **検証結果**: `Member.php` — `$login_id`: string, ORM Column `login_id`, varchar(255), not null。UniqueEntity制約あり（message='form_error.member_already_exists'）。FormType(MemberType)では新規時のみ入力可能で Length(min=eccube_id_min_len, max=eccube_id_max_len) + graphのみ許可。編集時はdisabled。getUserIdentifier()でも使用。
- **改善要否**: 不要
- **理由**: 正確。一意制約も正しく記述されている

---

### twoFactorAuthEnabled

- **現在doc**: 二要素認証（TOTP）を有効にするか。有効時はログイン時にワンタイムパスワードを要求
- **検証結果**: `Member.php` — `$two_factor_auth_enabled`: boolean, ORM Column `two_factor_auth_enabled`, boolean, nullable=false, default=false。FormTypeではToggleSwitchType。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### loginDate

- **現在doc**: 管理者の最終ログイン日時
- **検証結果**: `Member.php` — `$login_date`: DateTime|null, ORM Column `login_date`, datetimetz, nullable=true。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### denyUrl

- **現在doc**: アクセスを拒否する管理画面URLパターン。authority=1（店舗オーナー）に対して適用
- **検証結果**: `AuthorityRole.php` — `$deny_url`: string, ORM Column `deny_url`, varchar(4000), not null。AuthorityRole エンティティは Authority との ManyToOne 関連を持つ。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### pageUrl

- **現在doc**: ページのURLパス
- **検証結果**: `Page.php` — `$url`: string, ORM Column `url`, varchar(255), not null。Index付き(dtb_page_url_idx)。これはSymfonyルート名に対応するURL文字列。
- **改善要否**: 要
- **推奨doc**: ページのURLパス（Symfonyルート名）

---

### pageFileName

- **現在doc**: ページのテンプレートファイル名
- **検証結果**: `Page.php` — `$file_name`: string|null, ORM Column `file_name`, varchar(255), nullable=true。Twig テンプレートのパス（拡張子なし）を格納。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### pageName

- **現在doc**: 管理画面でのページ表示名
- **検証結果**: `Page.php` — `$name`: string|null, ORM Column `page_name`, varchar(255), nullable=true。管理画面およびtitleタグに使用。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### layoutName

- **現在doc**: レイアウトの表示名
- **検証結果**: `Layout.php` — `$name`: string, ORM Column `layout_name`, varchar(255), nullable=true。__toString()メソッドで返される。FormType(LayoutType)では NotBlank 制約あり。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### blockName

- **現在doc**: ブロックの表示名
- **検証結果**: `Block.php` — `$name`: string|null, ORM Column `block_name`, varchar(255), nullable=true。FormType(BlockType)では NotBlank + Length(max=eccube_stext_len)。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### blockFileName

- **現在doc**: ブロックのテンプレートファイル名
- **検証結果**: `Block.php` — `$file_name`: string, ORM Column `file_name`, varchar(255), not null。UniqueConstraint(device_type_id + file_name)。FormType(BlockType)では NotBlank + Length(max=eccube_stext_len) + Regex(英数字とスラッシュ/アンダースコアのみ) + 連続スラッシュ禁止。重複チェックもPOST_SUBMITで実施。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### blockDeletable

- **現在doc**: このブロックを管理画面から削除できるか。システム標準ブロックは削除不可
- **検証結果**: `Block.php` — `$deletable`: bool, ORM Column `deletable`, boolean, default=true。isDeletable()メソッドで取得。
- **改善要否**: 不要
- **理由**: 正確。デフォルトはtrue（削除可能）であり、システム標準ブロックのみfalseに設定される

---

### newsTitle

- **現在doc**: ニュース記事の見出し
- **検証結果**: `News.php` — `$title`: string, ORM Column `title`, varchar(255), not null。FormType(NewsType)では NotBlank + Length(max=eccube_mtext_len)。__toString()で返される。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### newsDescription

- **現在doc**: ニュース記事の本文
- **検証結果**: `News.php` — `$description`: string|null, ORM Column `description`, text, nullable=true。FormType(NewsType)では TextareaType, required=false, purify_html=true, Length(max=eccube_ltext_len)。HTMLPurifierで浄化される。
- **改善要否**: 要
- **推奨doc**: ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり

---

### newsUrl

- **現在doc**: 外部リンクURL。設定時はニュース本文の代わりにこのURLへ遷移
- **検証結果**: `News.php` — `$url`: string|null, ORM Column `url`, varchar(4000), nullable=true。FormType(NewsType)では Url制約 + Length(max=eccube_mtext_len), required=false。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### publishDate

- **現在doc**: ニュースの公開日時。フロントの表示順を制御
- **検証結果**: `News.php` — `$publish_date`: DateTime|null, ORM Column `publish_date`, datetimetz, nullable=true。FormType(NewsType)では NotBlank + Range(min='0003-01-01'), with_seconds=true。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### newsVisible

- **現在doc**: ニュースをフロントに表示するか
- **検証結果**: `News.php` — `$visible`: bool, ORM Column `visible`, boolean, default=true。FormType(NewsType)では ChoiceType(表示/非表示)。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### mailTemplateName

- **現在doc**: メールテンプレートの表示名
- **検証結果**: `MailTemplate.php` — `$name`: string|null, ORM Column `name`, varchar(255), nullable=true。FormType(MailType)では NotBlank + Length(max=eccube_stext_len)。__toString()で返される。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### mailTemplateFileName

- **現在doc**: メールテンプレートのファイル名
- **検証結果**: `MailTemplate.php` — `$file_name`: string|null, ORM Column `file_name`, varchar(255), nullable=true。FormType(MailType)では新規作成時のみ入力可能。保存時に 'Mail/' + 入力値 + '.twig' の形式に変換される。Regex制約(英小文字、数字、アンダースコア、ハイフンのみ)。
- **改善要否**: 要
- **推奨doc**: メールテンプレートのTwigファイルパス。'Mail/{入力値}.twig'形式で保存。新規作成時のみ設定可能

---

### mailSubject

- **現在doc**: メールの件名。テンプレート変数を含む場合あり
- **検証結果**: `MailTemplate.php` — `$mail_subject`: string|null, ORM Column `mail_subject`, varchar(255), nullable=true。FormType(MailType)では NotBlank + Length(max=eccube_stext_len)。`MailHistory.php` にも同名プロパティあり（string|null, varchar(255), nullable=true）。
- **改善要否**: 不要
- **理由**: 正確。MailTemplate（テンプレート）とMailHistory（送信済み）の両方で使用される

---

### sendDate

- **現在doc**: メールの送信日時
- **検証結果**: `MailHistory.php` — `$send_date`: DateTime|null, ORM Column `send_date`, datetimetz, nullable=true。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### mailBody

- **現在doc**: 送信済みメールのプレーンテキスト本文
- **検証結果**: `MailHistory.php` — `$mail_body`: string|null, ORM Column `mail_body`, text, nullable=true。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### mailHtmlBody

- **現在doc**: 送信済みメールのHTML本文
- **検証結果**: `MailHistory.php` — `$mail_html_body`: string|null, ORM Column `mail_html_body`, text, nullable=true。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### userName

- **現在doc**: ログイン試行時のユーザー名（メールアドレスまたはログインID）
- **検証結果**: `LoginHistory.php` — `$user_name`: string, ORM Column `user_name`, text, nullable=true。ログイン試行時に入力されたユーザー名を記録。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### clientIp

- **現在doc**: ログイン試行元のIPアドレス。セキュリティ監査用
- **検証結果**: `LoginHistory.php` — `$client_ip`: string, ORM Column `client_ip`, text, nullable=true。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### loginHistoryStatus

- **現在doc**: 0=失敗, 1=成功
- **検証結果**: `LoginHistory.php` — `$Status`: LoginHistoryStatus (ManyToOne)。`Master/LoginHistoryStatus.php` では定数: FAILURE=0, SUCCESS=1。マスタテーブル mtb_login_history_status を参照。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### csvEntityName

- **現在doc**: CSV出力対象のデータ種別（例: 商品、受注、会員）
- **検証結果**: `Csv.php` — `$entity_name`: string, ORM Column `entity_name`, varchar(255), not null。Doctrineエンティティの完全修飾クラス名（例: Eccube\Entity\Product）を格納。
- **改善要否**: 要
- **推奨doc**: CSV出力対象のDoctrineエンティティクラス名（完全修飾名。例: Eccube\Entity\Product）

---

### csvFieldName

- **現在doc**: CSV出力対象のフィールド名
- **検証結果**: `Csv.php` — `$field_name`: string, ORM Column `field_name`, varchar(255), not null。エンティティのプロパティ名を格納。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### csvDispName

- **現在doc**: CSVヘッダ行に表示する列名
- **検証結果**: `Csv.php` — `$disp_name`: string, ORM Column `disp_name`, varchar(255), not null。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### csvEnabled

- **現在doc**: このCSV列を出力に含めるか
- **検証結果**: `Csv.php` — `$enabled`: bool, ORM Column `enabled`, boolean, default=true。isEnabled()メソッドで取得。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### pluginName

- **現在doc**: プラグインの表示名
- **検証結果**: `Plugin.php` — `$name`: string, ORM Column `name`, varchar(255), not null。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### pluginCode

- **現在doc**: プラグインの一意識別子
- **検証結果**: `Plugin.php` — `$code`: string, ORM Column `code`, varchar(255), not null。ディレクトリ名やnamespaceにも使用される。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### pluginVersion

- **現在doc**: プラグインのバージョン文字列
- **検証結果**: `Plugin.php` — `$version`: string, ORM Column `version`, varchar(255), not null。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### pluginEnabled

- **現在doc**: プラグインが有効か。インストール直後は無効
- **検証結果**: `Plugin.php` — `$enabled`: bool, ORM Column `enabled`, boolean, default=false。isEnabled()メソッドで取得。`$initialized` プロパティ（default=false）もあり、初期化済みかどうかを別途管理。
- **改善要否**: 不要
- **理由**: 正確。default=false でインストール直後は無効であることと一致

---

### templateCode

- **現在doc**: テンプレートの一意識別コード。標準テンプレートは'default'
- **検証結果**: `Template.php` — `$code`: string, ORM Column `template_code`, varchar(255), not null。定数 DEFAULT_TEMPLATE_CODE = 'default'。isDefaultTemplate()メソッドで判定。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### templateName

- **現在doc**: テンプレートの表示名
- **検証結果**: `Template.php` — `$name`: string, ORM Column `template_name`, varchar(255), not null。__toString()で返される。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### tradeLawName

- **現在doc**: 特定商取引法に基づく表記の項目名
- **検証結果**: `TradeLaw.php` — `$name`: ?string, ORM Column `name`, varchar(255), nullable=true。FormType(TradeLawType)では TextType, required=false, Length(max=eccube_stext_len)。setName()で空文字列にフォールバック。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### tradeLawDescription

- **現在doc**: 特定商取引法に基づく表記の項目内容
- **検証結果**: `TradeLaw.php` — `$description`: ?string, ORM Column `description`, varchar(4000), nullable=true。FormType(TradeLawType)では TextareaType, required=false, purify_html=true, Length(max=4000)。setDescription()で空文字列にフォールバック。
- **改善要否**: 要
- **推奨doc**: 特定商取引法に基づく表記の項目内容。HTML入力可能でHTMLPurifierによる浄化あり

---

### displayOrderScreen

- **現在doc**: 注文確認画面にこの項目を表示するか
- **検証結果**: `TradeLaw.php` — `$displayOrderScreen`: bool, ORM Column `display_order_screen`, boolean, default=false。FormType(TradeLawType)では ToggleSwitchType。
- **改善要否**: 不要
- **理由**: 正確に記述されている

---

### createDate

- **現在doc**: 作成日時
- **検証結果**: 全エンティティ共通 — `$create_date`: DateTime, ORM Column `create_date`, datetimetz, not null。Member, Page, Layout, Block, News, MailTemplate, MailHistory, LoginHistory, Csv, Plugin, Template 等で使用。
- **改善要否**: 不要
- **理由**: 正確。共通プロパティとして簡潔な記述で適切

---

### updateDate

- **現在doc**: 最終更新日時
- **検証結果**: 全エンティティ共通 — `$update_date`: DateTime, ORM Column `update_date`, datetimetz, not null。
- **改善要否**: 不要
- **理由**: 正確。共通プロパティとして簡潔な記述で適切

---

## サマリーテーブル

| # | descriptorId | 改善要否 | 理由 |
|---|---|---|---|
| 1 | memberName | 不要 | 正確 |
| 2 | department | 不要 | 正確 |
| 3 | loginId | 不要 | 正確 |
| 4 | twoFactorAuthEnabled | 不要 | 正確 |
| 5 | loginDate | 不要 | 正確 |
| 6 | denyUrl | 不要 | 正確 |
| 7 | pageUrl | 要 | Symfonyルート名であることを明記 |
| 8 | pageFileName | 不要 | 正確 |
| 9 | pageName | 不要 | 正確 |
| 10 | layoutName | 不要 | 正確 |
| 11 | blockName | 不要 | 正確 |
| 12 | blockFileName | 不要 | 正確 |
| 13 | blockDeletable | 不要 | 正確 |
| 14 | newsTitle | 不要 | 正確 |
| 15 | newsDescription | 要 | HTML入力可能+HTMLPurifier浄化の情報が欠落 |
| 16 | newsUrl | 不要 | 正確 |
| 17 | publishDate | 不要 | 正確 |
| 18 | newsVisible | 不要 | 正確 |
| 19 | mailTemplateName | 不要 | 正確 |
| 20 | mailTemplateFileName | 要 | 保存形式(Mail/{name}.twig)と新規時のみ設定可能な点が欠落 |
| 21 | mailSubject | 不要 | 正確 |
| 22 | sendDate | 不要 | 正確 |
| 23 | mailBody | 不要 | 正確 |
| 24 | mailHtmlBody | 不要 | 正確 |
| 25 | userName | 不要 | 正確 |
| 26 | clientIp | 不要 | 正確 |
| 27 | loginHistoryStatus | 不要 | 正確 |
| 28 | csvEntityName | 要 | Doctrineエンティティの完全修飾クラス名であることが欠落 |
| 29 | csvFieldName | 不要 | 正確 |
| 30 | csvDispName | 不要 | 正確 |
| 31 | csvEnabled | 不要 | 正確 |
| 32 | pluginName | 不要 | 正確 |
| 33 | pluginCode | 不要 | 正確 |
| 34 | pluginVersion | 不要 | 正確 |
| 35 | pluginEnabled | 不要 | 正確 |
| 36 | templateCode | 不要 | 正確 |
| 37 | templateName | 不要 | 正確 |
| 38 | tradeLawName | 不要 | 正確 |
| 39 | tradeLawDescription | 要 | HTML入力可能+HTMLPurifier浄化の情報が欠落 |
| 40 | displayOrderScreen | 不要 | 正確 |
| 41 | createDate | 不要 | 正確 |
| 42 | updateDate | 不要 | 正確 |

## 統計

- 検証対象: 42件
- 改善要: 5件 (pageUrl, newsDescription, mailTemplateFileName, csvEntityName, tradeLawDescription)
- 改善不要: 37件

## 改善要の詳細まとめ

### pageUrl
- 現在: `ページのURLパス`
- 推奨: `ページのURLパス（Symfonyルート名）`
- 理由: Page.phpの$urlはSymfonyルート名（例: homepage, product_list）を格納しており、通常のURLパス文字列(/products等)ではない

### newsDescription
- 現在: `ニュース記事の本文`
- 推奨: `ニュース記事の本文。HTML入力可能でHTMLPurifierによる浄化あり`
- 理由: FormTypeでpurify_html=trueが設定されており、freeAreaと同様にHTMLが許可されている点が重要

### mailTemplateFileName
- 現在: `メールテンプレートのファイル名`
- 推奨: `メールテンプレートのTwigファイルパス。'Mail/{入力値}.twig'形式で保存。新規作成時のみ設定可能`
- 理由: 実際にはファイルパス(Mail/xxx.twig形式)で保存され、新規作成後は変更不可

### csvEntityName
- 現在: `CSV出力対象のデータ種別（例: 商品、受注、会員）`
- 推奨: `CSV出力対象のDoctrineエンティティクラス名（完全修飾名。例: Eccube\Entity\Product）`
- 理由: 実際の値は日本語名称ではなくDoctrineエンティティの完全修飾クラス名

### tradeLawDescription
- 現在: `特定商取引法に基づく表記の項目内容`
- 推奨: `特定商取引法に基づく表記の項目内容。HTML入力可能でHTMLPurifierによる浄化あり`
- 理由: FormTypeでpurify_html=trueが設定されている
