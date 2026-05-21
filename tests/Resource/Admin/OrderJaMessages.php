<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **Order** section (`admin/Order/`).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. Covers the ported Order pages — currently `index.twig`
 * (受注一覧 list/search, {@see \MyVendor\BeMart\Tests\Resource\AdminOrderListHtmlRenderTest})
 * including the `confirmationModal_js.twig` JS partial it includes in
 * `{% block javascript %}`. Keys for the not-yet-ported Order form
 * pages (`edit.twig`, `shipping.twig`, `mail.twig`, `mail_confirm.twig`,
 * `order_pdf.twig`, `csv_shipping.twig`) are kept here so those waves
 * only add their templates + tests, not a new ja-keys file.
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class OrderJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- admin.common.* used by the Order pages (not in chrome) -
            'admin.common.bulk_actions' => '一括操作',
            'admin.common.close' => '閉じる',
            'admin.common.count' => '%count%件',
            'admin.common.create' => '作成',
            'admin.common.csv_download' => 'CSVダウンロード',
            'admin.common.execute' => '実行',
            'admin.common.mail_address' => 'メールアドレス',
            'admin.common.payment_method' => '支払方法',
            'admin.common.phone_number' => '電話番号',
            'admin.common.postal_symbol' => '〒',
            'admin.common.search_detail' => '詳細検索',
            'admin.common.search_invalid_condition' => '検索条件に誤りがあります',
            'admin.common.search_no_result' => '検索条件に合致するデータが見つかりませんでした',
            'admin.common.search_result' => '検索結果：%count%件が該当しました',
            'admin.common.search_try_advanced_search' => '[詳細検索]も試してみましょう',
            'admin.common.search_try_change_condition' => '検索条件を変えて、再度検索をお試しください',
            'admin.common.separator__range' => '〜',
            'admin.common.system_error' => 'システムエラーが発生しました',
            'admin.common.update_date' => '更新日',
            // --- Order section ------------------------------------------
            'admin.order.bulk_action__complete_message' => '完了しました。',
            'admin.order.bulk_action__confirm_view_mail_body' => 'メールの文面を確認',
            'admin.order.bulk_action__in_progress_message' => '処理中...',
            'admin.order.change_status' => '対応状況の変更',
            'admin.order.delete__confirm_message' => '注文情報を削除してよろしいですか？',
            'admin.order.delete__confirm_title' => '注文情報を削除します',
            'admin.order.delivery' => 'お届け先',
            'admin.order.delivery_date' => 'お届け日',
            'admin.order.delivery_note_create_date' => '発行日',
            'admin.order.delivery_note_line1' => '1行目',
            'admin.order.delivery_note_line2' => '2行目',
            'admin.order.delivery_note_line3' => '3行目',
            'admin.order.delivery_note_memo' => '備考',
            'admin.order.delivery_note_message' => 'メッセージ',
            'admin.order.delivery_note_output_format' => '出力形式',
            'admin.order.delivery_note_title' => 'タイトル',
            'admin.order.mail' => 'メール通知',
            'admin.order.mail_body' => '本文',
            'admin.order.mail_confirm' => '送信内容を確認',
            'admin.order.mail_destination_info' => 'メール送信先',
            'admin.order.mail_mail_info' => 'メール内容',
            'admin.order.mail_purchase_product_count' => '他%count%点',
            'admin.order.mail_send' => '送信',
            'admin.order.mail_subject' => '件名',
            'admin.order.mail_template' => 'テンプレート',
            'admin.order.message_short' => 'お問合せ',
            'admin.order.multi_search_label' => '注文番号・お名前・会社名・メールアドレス・電話番号',
            'admin.order.not_payment' => '未入金',
            'admin.order.order_csv' => '受注CSV',
            'admin.order.order_date' => '注文日',
            'admin.order.order_list' => '受注一覧',
            'admin.order.order_management' => '受注管理',
            'admin.order.order_no' => '注文番号',
            'admin.order.order_registration' => '受注登録',
            'admin.order.order_status' => '対応状況',
            'admin.order.orderer' => '注文者',
            'admin.order.orderer_company_name' => '注文者会社名',
            'admin.order.orderer_kana' => '注文者名(カナ)',
            'admin.order.orderer_name' => '注文者名',
            'admin.order.output_delivery_note' => '納品書を出力',
            'admin.order.output_delivery_note_short' => '納品書出力',
            'admin.order.payment_date' => '入金日',
            'admin.order.purchase_price' => '購入金額',
            'admin.order.purchase_product' => '購入商品名',
            'admin.order.send_mail' => 'メールする',
            'admin.order.shipping_csv' => '出荷CSV',
            'admin.order.shipping_id' => '出荷ID',
            'admin.order.shipping_mail' => '出荷メール',
            'admin.order.shipping_mail__completed' => '%date%に出荷メールを送信済です。',
            'admin.order.shipping_mail_send' => '出荷メール送信',
            'admin.order.shipping_mail_send__complete_message' => '%count%件のメールを送信しました。',
            'admin.order.shipping_mail_send__confirm_message' => '出荷お知らせメールを送信します。メールの文面を確認してから［送信］ボタンを押してください。この操作は取り消すことができません。ご注意ください。',
            'admin.order.shipping_mail_send__confirm_title' => '出荷お知らせメールを送信します',
            'admin.order.shipping_status' => '出荷状況',
            'admin.order.to_shipped' => '出荷済にする',
            'admin.order.to_shipped__confirm_message' => '出荷情報を出荷済みにします。同時におこなう操作を選択してから［実行］ボタンを押してください。この操作は取り消すことができません。ご注意ください。',
            'admin.order.to_shipped__confirm_send_mail' => 'メールの送信',
            'admin.order.to_shipped__confirm_send_mail_in_same_time' => '出荷お知らせメールを同時に送る',
            'admin.order.to_shipped__confirm_title' => '出荷済に更新します',
            'admin.order.tracking_number' => 'お問い合わせ番号',
            'admin.order.unshipped' => '未出荷',
            // --- admin.setting.* the index page borrows -----------------
            'admin.setting.shop.csv_setting' => 'CSV出力項目設定',
            // --- tooltip.order.* ----------------------------------------
            'tooltip.order.bulk_actions' => 'チェックを入れた受注に対して、一括処理を行います。充分に確認をしてから実行してください。',
            'tooltip.order.multi_search_label' => '情報を入力して一覧の絞り込み検索ができます。より詳細な条件を指定するには［詳細検索］を開いてください。',
            'tooltip.order.order_search_status' => '受注の対応状況による絞り込み検索ができます。カッコ内は対応すべき受注の件数が表示されています。',
        ];
    }
}
