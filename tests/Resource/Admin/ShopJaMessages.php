<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **Setting/Shop** section
 * (`admin/Setting/Shop/`).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. Covers the ported Setting/Shop pages:
 *
 *  - `payment.twig`       — 支払方法設定 list
 *  - `delivery.twig`      — 配送方法設定 list
 *  - `order_status.twig`  — 受注対応状況設定 form
 *  - `tradelaw.twig`      — 特定商取引法設定 form
 *  - `tax_rule.twig`      — 税率設定 list + inline form
 *  - `csv.twig`           — CSV出力項目設定 form
 *  - `mail.twig`          — メール設定 (mail template editor)
 *  - `shop_master.twig`   — 基本設定 (shop master config)
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class ShopJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- common.* used by the Shop pages ------------------------
            'common.enabled' => '有効',
            'common.disabled' => '無効',
            // --- admin.common.* used by the Shop pages ------------------
            'admin.common.save' => '保存',
            'admin.common.charge' => '手数料',
            'admin.common.payment_method' => '支払方法',
            'admin.common.to_show' => '表示する',
            'admin.common.to_hide' => '非表示にする',
            'admin.common.drag_and_drop_description' => 'ドラッグ＆ドロップやボタン操作で表示順を変更できます。',
            'admin.common.separator__range' => '〜',
            // --- Setting/Shop section headings --------------------------
            'admin.setting.shop' => '店舗設定',
            'admin.setting.shop.shop_setting' => '基本設定',
            'admin.setting.shop.tradelaw_setting' => '特定商取引法設定',
            'admin.setting.shop.agreement_setting' => '利用規約設定',
            'admin.setting.shop.payment_list' => '支払方法一覧',
            'admin.setting.shop.payment_setting' => '支払方法設定',
            'admin.setting.shop.delivery_list' => '配送方法一覧',
            'admin.setting.shop.delivery_setting' => '配送方法設定',
            'admin.setting.shop.tax_setting' => '税率設定',
            'admin.setting.shop.mail_setting' => 'メール設定',
            'admin.setting.shop.csv_setting' => 'CSV出力項目設定',
            'admin.setting.shop.order_status_setting' => '受注対応状況設定',
            'admin.setting.shop.calendar_setting' => '定休日カレンダー設定',
            // --- shop_master --------------------------------------------
            'admin.setting.shop.shop.base_info' => '店舗情報',
            'admin.setting.shop.company_name_kana' => '会社名(カナ)',
            'admin.setting.shop.shop.shop_name' => '店名',
            'admin.setting.shop.shop.shop_name_kana' => '店名(カナ)',
            'admin.setting.shop.shop.shop_name_en' => '店名(英語表記)',
            'admin.setting.shop.shop.business_hour' => '店舗営業時間',
            'admin.setting.shop.shop.email_from' => '送信元メールアドレス(From)',
            'admin.setting.shop.shop.good_traded' => '取り扱い商品説明文',
            'admin.setting.shop.shop.message' => '店舗からのメッセージ',
            'tooltip.setting.shop.shop.email_from' => '店舗からメールを送信した場合の送信元になるメールアドレスです。',
            'tooltip.setting.shop.shop.good_traded' => '店舗が取り扱う商品についての簡単な説明文です。',
            'tooltip.setting.shop.shop.message' => 'フロント側に表示されます。表示位置はデザインテンプレートによって異なります。',
            // --- payment ------------------------------------------------
            'admin.setting.shop.payment.terms_of_use' => '利用条件',
            'admin.setting.shop.payment.unlimited' => '無制限',
            'admin.setting.shop.payment.payment_id' => 'ID',
            'admin.setting.shop.payment.payment_name' => '支払方法名',
            // --- delivery -----------------------------------------------
            'admin.setting.shop.delivery.delivery_name' => '配送業者名',
            'admin.setting.shop.delivery.delivery_sevice_name' => '配送方法名称',
            // --- trade law ----------------------------------------------
            'admin.setting.shop.trade_law.header.name' => '名称',
            'admin.setting.shop.trade_law.header.description' => '説明',
            'admin.setting.shop.trade_law.header.confirm_page_show' => '注文確認画面に表示',
            // --- tax ----------------------------------------------------
            'admin.setting.shop.tax.rounding_type' => '課税規則',
            'admin.setting.shop.tax.base_rate_setting' => '基本税率設定',
            'admin.setting.shop.tax.tax_rate' => '税率',
            'admin.setting.shop.tax.apply_date' => '適用日時',
            'tooltip.setting.shop.tax_setting' => '商品共通の税率を設定できます。新しい税率を追加することで基本税率設定を上書きすることが可能です。',
            // --- mail ---------------------------------------------------
            'admin.setting.shop.mail.mail_template_edit' => 'テンプレート編集',
            'admin.setting.shop.mail.mail_template' => 'テンプレート',
            'admin.setting.shop.mail.mail_template_name' => 'テンプレート名',
            'admin.setting.shop.mail.mail_file_name' => 'ファイル名',
            'admin.setting.shop.mail.mail_subject' => '件名',
            'admin.setting.shop.mail.mail_body' => '本文',
            'admin.setting.shop.mail.mail_text' => 'テキスト',
            'admin.setting.shop.mail.mail_html' => 'HTML',
            'admin.setting.shop.mail.preview' => 'プレビュー',
            'tooltip.setting.shop.mail.mail_template' => '既存のメールテンプレートを選択してください。以下に内容が表示されます。',
            // --- csv ----------------------------------------------------
            'admin.setting.shop.csv.csv_columns' => 'CSV出力項目',
            'admin.setting.shop.csv.csv_type' => 'CSV種別',
            'admin.setting.shop.csv.non_output_colmuns' => '出力しない項目',
            'admin.setting.shop.csv.output_colmuns' => '出力する項目',
            'admin.setting.shop.csv.operation' => '操作項目',
            'admin.setting.shop.csv.operation__output' => '出力',
            'admin.setting.shop.csv.operation__release' => '解除',
            'admin.setting.shop.csv.operation__all_output' => 'すべて出力',
            'admin.setting.shop.csv.operation__all_release' => 'すべて解除',
            'admin.setting.shop.csv.order' => '項目順序',
            'admin.setting.shop.csv.order__up' => 'ひとつ上へ',
            'admin.setting.shop.csv.order__down' => 'ひとつ下へ',
            'admin.setting.shop.csv.order__top' => '一番上へ',
            'admin.setting.shop.csv.order__bottom' => '一番下へ',
            'admin.setting.shop.csv.how_to_use' => "任意の項目を押して選択してから［項目操作］ボタンで移動し、CSV出力する項目を設定してください。\n出力する項目の順序は［項目順序］ボタンで変更することができます。\n複数の項目を選択する場合はctrlキー（Macの場合はcommandキー）を押しながら項目を押します。shiftキーを押しながら選択すると範囲選択ができます。\n",
            'tooltip.setting.shop.csv.csv_columns' => '各種のデータをCSVで出力できます。出力したい項目をこちらで設定することが可能です。',
            'tooltip.setting.shop.csv.csv_type' => '設定したいCSVの種類を指定してください。',
            // --- order status -------------------------------------------
            'admin.setting.shop.order_status.order_status' => '受注対応状況',
            'admin.setting.shop.order_status.id' => 'ID',
            'admin.setting.shop.order_status.admin_order_status_name' => '名称(受注管理)',
            'admin.setting.shop.order_status.customer_order_status_name' => '名称(マイページ)',
            'admin.setting.shop.order_status.color' => '色',
            'admin.setting.shop.order_status.display_order_count' => '件数表示',
            'tooltip.setting.shop.order_status.order_status' => '受注管理およびマイページで表示される対応状況の設定を行います。名称、色、件数表示の設定が可能です。',
            'tooltip.setting.shop.order_status.customer_order_status_name' => '対応状況の名称を設定できます。ここで設定した名称は会員ログイン後のマイページで表示されます。',
            'tooltip.setting.shop.order_status.color' => '受注管理の対応状況の色を設定できます。',
            'tooltip.setting.shop.order_status.display_order_count' => '受注管理の対応状況ごとの受注件数の表示・非表示を設定できます。',
        ];
    }
}
