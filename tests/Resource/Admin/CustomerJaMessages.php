<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **Customer** section (`admin/Customer/`).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. Covers the two ported Customer pages — `index.twig`
 * (会員一覧 list/search, {@see \MyVendor\BeMart\Tests\Resource\AdminCustomerListHtmlRenderTest})
 * and `edit.twig` (会員登録/編集 form,
 * {@see \MyVendor\BeMart\Tests\Resource\AdminCustomerHtmlRenderTest}).
 * A few keys for the not-yet-ported `delivery_edit.twig` (お届け先登録
 * address sub-page — needs a new `Customer/DeliveryEdit` resource, a
 * separate future wave) are kept here so that wave only adds its
 * templates + tests, not a new ja-keys file.
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class CustomerJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- admin.common.* used by the Customer pages --------------
            'admin.common.address' => '住所',
            'admin.common.birth_day' => '誕生日',
            'admin.common.company_name' => '会社名',
            'admin.common.count' => '%count%件',
            'admin.common.create_date' => '登録日',
            'admin.common.csv_download' => 'CSVダウンロード',
            'admin.common.gender' => '性別',
            'admin.common.job' => '職業',
            'admin.common.kana' => 'お名前(カナ)',
            'admin.common.mail_address' => 'メールアドレス',
            'admin.common.name' => 'お名前',
            'admin.common.password' => 'パスワード',
            'admin.common.password_for_confirmation' => 'パスワード(確認用)',
            'admin.common.phone_number' => '電話番号',
            'admin.common.point' => 'ポイント',
            'admin.common.postal_symbol' => '〒',
            'admin.common.pref' => '都道府県',
            'admin.common.search_detail' => '詳細検索',
            'admin.common.search_invalid_condition' => '検索条件に誤りがあります',
            'admin.common.search_no_result' => '検索条件に合致するデータが見つかりませんでした',
            'admin.common.search_result' => '検索結果：%count%件が該当しました',
            'admin.common.search_try_advanced_search' => '[詳細検索]も試してみましょう',
            'admin.common.search_try_change_condition' => '検索条件を変えて、再度検索をお試しください',
            'admin.common.separator__range' => '〜',
            'admin.common.shop_memo' => 'ショップ用メモ欄',
            'admin.common.update_date' => '更新日',
            // --- Customer section ---------------------------------------
            'admin.customer.birth_month' => '誕生月',
            'admin.customer.customer_address' => 'お届け先住所',
            'admin.customer.customer_address__add' => 'お届け先住所を追加',
            'admin.customer.customer_address_count_is_over' => 'お届け先登録の上限の%eccube_deliv_addr_max%件に達しています。お届け先を入力したい場合は、削除か変更を行ってください。',
            'admin.customer.customer_address_info' => 'お届け先情報',
            'admin.customer.customer_address_registration' => 'お届け先登録',
            'admin.customer.customer_id' => '会員ID',
            'admin.customer.customer_info' => '会員情報',
            'admin.customer.customer_list' => '会員一覧',
            'admin.customer.customer_management' => '会員管理',
            'admin.customer.customer_registration' => '会員登録',
            'admin.customer.customer_status' => '会員種別',
            'admin.customer.multi_search_label' => '会員ID・メールアドレス・お名前',
            'admin.customer.no_customer_address' => 'この会員のお届け先がありません',
            'admin.customer.no_purchase_history' => 'この会員の購入履歴がありません',
            'admin.customer.purchase_history' => '注文履歴',
            'admin.customer.resend' => '仮会員メール再送',
            'admin.customer.resend_confirm_message' => '仮登録メールを再送してもよろしいですか？',
            'admin.customer.resend_confirm_title' => '仮会員メールを再送します。',
            // --- admin.order.* the Customer pages borrow ----------------
            'admin.order.last_buy_date' => '最終購入日',
            'admin.order.order_date' => '注文日',
            'admin.order.order_no' => '注文番号',
            'admin.order.order_status' => '対応状況',
            'admin.order.purchase_count' => '購入件数',
            'admin.order.purchase_price' => '購入金額',
            'admin.order.purchase_product' => '購入商品名',
            // --- admin.setting.* the list page borrows (CSV setting link)
            'admin.setting.shop.csv_setting' => 'CSV出力項目設定',
            // --- tooltip.customer.* -------------------------------------
            'tooltip.customer.customer_address' => 'この会員が登録しているお届け先です。この画面から追加することもできます。',
            'tooltip.customer.customer_id' => '自動的に採番される会員のIDです。',
            'tooltip.customer.multi_search_label' => '情報を入力して一覧の絞り込み検索ができます。より詳細な条件を指定するには［詳細検索］を開いてください。',
            'tooltip.customer.purchase_history' => 'この会員が注文をした履歴が表示されます。',
            'tooltip.customer.shop_memo' => '店舗用のメモ欄です。フロント側には表示されません。',
        ];
    }
}
