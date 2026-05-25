<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **Product** section (`admin/Product/`).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. Covers the ported Product-section pages: the product list
 * (`index.twig`), the four CSV upload screens (`csv_product.twig`,
 * `csv_category.twig`, `csv_class_name.twig`, `csv_class_category.twig`),
 * the tag / class-name / class-category list+inline-form pages
 * (`tag.twig`, `class_name.twig`, `class_category.twig`) and the
 * category page (`category.twig`).
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class ProductJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- admin.common.* used by the Product pages ---------------
            'admin.common.bulk_actions' => '一括操作',
            'admin.common.bulk_registration' => '一括登録を実行',
            'admin.common.close' => '閉じる',
            'admin.common.close_detail' => '詳細を閉じる',
            'admin.common.copy' => '複製',
            'admin.common.count' => '%count%件',
            'admin.common.create_date' => '登録日',
            'admin.common.csv_download' => 'CSVダウンロード',
            'admin.common.csv_format' => 'CSVファイルフォーマット',
            'admin.common.csv_select' => 'CSVファイルを選択',
            'admin.common.csv_skeleton_download' => '雛形ファイルダウンロード',
            'admin.common.csv_upload' => 'CSVファイルをアップロード',
            'admin.common.csv_upload_complete' => 'CSVファイルをアップロードしました',
            'admin.common.csv_upload_error' => 'CSVファイルのアップロードに失敗しました',
            'admin.common.csv_upload_in_progress' => 'CSVファイルのアップロード中...',
            'admin.common.display' => '表示',
            'admin.common.drag_and_drop_description' => '項目の順番はドラッグ＆ドロップでも変更可能です。',
            'admin.common.file_select' => 'ファイルを選択',
            'admin.common.file_select_empty' => '選択されていません',
            'admin.common.open_detail' => '詳細を表示',
            'admin.common.search_detail' => '詳細検索',
            'admin.common.search_invalid_condition' => '検索条件に誤りがあります',
            'admin.common.search_no_result' => '検索条件に合致するデータが見つかりませんでした',
            'admin.common.search_result' => '検索結果：%count%件が該当しました',
            'admin.common.search_try_advanced_search' => '[詳細検索]も試してみましょう',
            'admin.common.search_try_change_condition' => '検索条件を変えて、再度検索をお試しください',
            'admin.common.separator__colon' => '：',
            'admin.common.separator__range' => '〜',
            'admin.common.to_hide' => '表示中 | 非表示にする',
            'admin.common.to_show' => '非表示中 | 表示にする',
            'admin.common.update_date' => '更新日',
            // --- admin.product.* ----------------------------------------
            'admin.product.category' => 'カテゴリ',
            'admin.product.category_all' => 'すべてのカテゴリ',
            'admin.product.category_csv_upload' => 'カテゴリCSV登録',
            'admin.product.category_management' => 'カテゴリ管理',
            'admin.product.class_category' => '規格分類',
            'admin.product.class_category1__short' => '規格1',
            'admin.product.class_category2__short' => '規格2',
            'admin.product.class_category__backend_name' => '管理名',
            'admin.product.class_category_csv_upload' => '規格分類CSV登録',
            'admin.product.class_category_management' => '規格分類管理',
            'admin.product.class_category_name__short' => '分類名',
            'admin.product.class_management' => '規格管理',
            'admin.product.class_name' => '規格名',
            'admin.product.class_name__backend_name' => '管理名',
            'admin.product.class_name_csv_upload' => '規格CSV登録',
            'admin.product.copy__confirm_message' => '商品を複製してよろしいですか？',
            'admin.product.copy__confirm_title' => '商品を複製します',
            'admin.product.display_status' => '公開ステータス',
            'admin.product.display_status__abolished' => '廃止',
            'admin.product.display_status__hide' => '非公開',
            'admin.product.display_status__short' => '公開状態',
            'admin.product.display_status__show' => '公開',
            'admin.product.image__short' => '画像',
            'admin.product.move_to_product_class' => '商品規格の編集',
            'admin.product.move_to_product_class__confirm_title' => '「%name%」の商品規格',
            'admin.product.multi_search_label' => '商品名・商品ID・商品コード',
            'admin.product.name' => '商品名',
            'admin.product.permanently_delete' => '削除',
            'admin.product.permanently_delete__complete' => '完了',
            'admin.product.permanently_delete__complete_message' => '商品の削除処理が完了しました',
            'admin.product.permanently_delete__confirm_message' => '商品を削除してよろしいですか？',
            'admin.product.permanently_delete__confirm_title' => '商品を削除します',
            'admin.product.permanently_delete__in_progress' => '削除中...',
            'admin.product.permanently_delete__system_error' => 'システムエラーが発生しました',
            'admin.product.price' => '価格',
            'admin.product.product_class__confirm' => '規格確認',
            'admin.product.product_code__short' => 'コード',
            'admin.product.product_csv_upload' => '商品CSV登録',
            'admin.product.product_csv_upload__message' => '商品CSVファイルをアップロードします。よろしいですか？',
            'admin.product.product_csv_upload__title' => '商品CSVをアップロードします',
            'admin.product.product_id__short' => 'ID',
            'admin.product.product_list' => '商品一覧',
            'admin.product.product_management' => '商品管理',
            'admin.product.stock' => '在庫数',
            'admin.product.stock__short' => '在庫',
            'admin.product.stock_unlimited__short' => '無制限',
            'admin.product.tag' => 'タグ',
            'admin.product.tag_management' => 'タグ管理',
            // --- admin.setting.* the list / list+form pages borrow ------
            'admin.setting.shop.csv_setting' => 'CSV出力項目設定',
            // --- CSV column-description keys (the csv_* format tables) --
            'admin.product.category_csv.category_id_col' => 'カテゴリID',
            'admin.product.category_csv.category_id_description' => '新規登録の場合は空にしてください。既存のカテゴリを更新する場合は、カテゴリIDを指定してください。',
            'admin.product.category_csv.category_name_col' => 'カテゴリ名',
            'admin.product.category_csv.category_name_description' => '',
            'admin.product.category_csv.parent_category_id_col' => '親カテゴリID',
            'admin.product.category_csv.parent_category_id_description' => '登録済みのカテゴリIDを数字で指定してください',
            'admin.product.category_csv.delete_flag_col' => 'カテゴリ削除フラグ',
            'admin.product.category_csv.delete_flag_description' => '0:登録 1:削除を指定します。未指定の場合、0として扱います。',
            // --- tooltip.* ----------------------------------------------
            'tooltip.product.multi_search_label' => '情報を入力して一覧の絞り込み検索ができます。より詳細な条件を指定するには［詳細検索］を開いてください。',
            'tooltip.product.backend_name' => '管理者用に別名を登録しておくことができます（例：規格名：サイズ　管理名：サイズ（服）、サイズ（靴）等 ）。フロント画面には表示されません。',
            'tooltip.product.csv_upload' => '所定の型のCSVデータを用いて商品を一括で登録することができます。',
            'tooltip.product.csv_format' => '雛形ファイルをダウンロードして編集すれば、簡単に所定の型のCSVデータを作成できます。',
            'tooltip.category.csv_upload' => '所定の型のCSVデータを用いてカテゴリを一括で登録することができます。',
            'tooltip.category.csv_format' => '雛形ファイルをダウンロードして編集すれば、簡単に所定の型のCSVデータを作成できます。',
            'tooltip.class_name.csv_upload' => '所定の型のCSVデータを用いて規格を一括で登録することができます。',
            'tooltip.class_name.csv_format' => '雛形ファイルをダウンロードして編集すれば、簡単に所定の型のCSVデータを作成できます。',
            'tooltip.class_category.csv_upload' => '所定の型のCSVデータを用いて規格分類を一括で登録することができます。',
            'tooltip.class_category.csv_format' => '雛形ファイルをダウンロードして編集すれば、簡単に所定の型のCSVデータを作成できます。',
        ];
    }
}
