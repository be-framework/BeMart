<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **Content** section (`admin/Content/`).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. The Content section is the admin pilot (News list + News
 * edit); its keys were migrated here out of {@see \MyVendor\BeMart\Tests\Resource\EcCubeStub::jaMessages()}
 * when the per-section split landed, so the shared file is append-free
 * for the remaining waves.
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class ContentJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- admin.common.* used by the News list/edit pages --------
            'admin.common.drag_and_drop_description' => '項目の順番はドラッグ＆ドロップでも変更可能です。',
            // --- Content/news section -----------------------------------
            'admin.content.contents_management' => 'コンテンツ管理',
            'admin.content.news_management' => '新着情報管理',
            'admin.content.news.news_registration' => '新着情報登録',
            'admin.content.news.publish_date' => '公開日時',
            'admin.content.news.title' => 'タイトル',
            'admin.content.news.url' => 'URL',
            'admin.content.news.body' => '本文',
            'admin.content.news.display_status' => '公開状態',
            'admin.content.news.display_status__show' => '公開',
            'admin.content.news.display_status__hide' => '非公開',
            'admin.content.news.new_window' => '別ウィンドウで開く',
            'tooltip.content.news.url' => 'リンク先のURLを入力してください。',
            'tooltip.content.news.body' => '本文を入力してください。',
            // --- admin.common.* used by the Content section pages -------
            'admin.common.edit' => '編集',
            'admin.common.delete' => '削除',
            'admin.common.cancel' => 'キャンセル',
            'admin.common.create__new' => '新規作成',
            'admin.common.required' => '必須',
            'admin.common.registration' => '登録',
            'admin.common.upload' => 'アップロード',
            'admin.common.download' => 'ダウンロード',
            'admin.common.display' => '表示',
            'admin.common.copy_path' => 'パスをコピー',
            'admin.common.copy_path_complete' => 'パスをコピーしました',
            'admin.common.device_type' => '端末種別',
            'admin.common.delete_modal__title' => '削除します',
            'admin.common.delete_modal__message' => 'この操作はあとから取り消すことができません。「%name%」を削除してよろしいですか？',
            // --- Content section management page titles -----------------
            'admin.content.file_management' => 'ファイル管理',
            'admin.content.layout_management' => 'レイアウト管理',
            'admin.content.page_management' => 'ページ管理',
            'admin.content.css_management' => 'CSS管理',
            'admin.content.js_management' => 'JavaScript管理',
            'admin.content.block_management' => 'ブロック管理',
            'admin.content.cache_management' => 'キャッシュ管理',
            'admin.content.maintenance_management' => 'メンテナンス管理',
            // --- Content/page (list + edit) -----------------------------
            'admin.content.page_name' => 'ページ名',
            'admin.content.page__card_title' => 'ページ設定',
            'admin.content.page_route_name' => 'ルーティング名',
            'admin.content.page_url' => 'URL',
            'admin.content.page_file_name' => 'ファイル名',
            'admin.content.page_source_code' => 'コード',
            'admin.content.page_layout__card_title' => 'レイアウト設定',
            'admin.content.page_pc' => 'PC',
            'admin.content.page_mobile' => 'モバイル',
            'admin.content.page_meta__card_title' => 'メタ設定',
            'admin.content.page_meta_author' => 'author',
            'admin.content.page_meta_description' => 'description',
            'admin.content.page_meta_keyword' => 'keyword',
            'admin.content.page_meta_robot' => 'robot',
            'admin.content.page_meta_metatag' => 'metatag',
            'admin.content.layout_name' => 'レイアウト名',
            'tooltip.content.page_file_name' => 'ページの内容を記述したtwigテンプレートファイルの名前です。',
            'tooltip.content.page_source_code' => 'テンプレートファイルを編集します。twigで記述します。',
            'tooltip.content.page_layout' => 'このページに適用するレイアウトを選択します。',
            'tooltip.content.page_meta' => 'ページのMETAタグの内容を指定できます。',
            'tooltip.content.page_meta_tags' => 'METAタグの出力をカスタマイズできます。twig内で利用されている変数も利用可能です。空欄の場合は、テンプレートフォルダの meta.twig ファイルが読み込まれます。',
            // --- Content/block (list + edit) ----------------------------
            'admin.content.block_name' => 'ブロック名',
            'admin.content.block__card_title' => 'ブロック設定',
            'admin.content.block_file_name' => 'ファイル名',
            'admin.content.block_source_code' => 'コード',
            'tooltip.content.block_name' => 'ブロックの管理用名称です。',
            'tooltip.content.block_file_name' => 'このブロックの内容を記述したtwigテンプレートファイルの名前です。管理画面ではあとから変更できません。',
            'tooltip.content.block_source_code' => 'テンプレートファイルを編集します。twigで記述します。',
            // --- Content/css + js ---------------------------------------
            'admin.content.css__card_title' => 'CSS設定',
            'admin.content.js__card_title' => 'JavaScript設定',
            'tooltip.content.css_source_code' => 'カスタマイズ用CSSファイルを編集します。CSSで記述します。',
            'tooltip.content.js_source_code' => 'カスタマイズ用JavaScriptファイルを編集します。JavaScriptで記述します。',
            // --- Content/cache + maintenance ----------------------------
            'admin.content.cache__card_title' => 'キャッシュ管理',
            'admin.content.cache_message' => '本番環境にFTPなどでTwigファイルをアップロードして入れ替えた場合、画面を反映させるにはTwigキャッシュを削除する必要があります。',
            'admin.content.cache_delete' => 'キャッシュ削除',
            'admin.content.maintenance__card_title' => 'メンテナンスモード',
            'admin.content.maintenance_message' => "メンテナンスモードを有効にすると、SHOPの機能を一時的に停止し、管理画面のみアクセス可能な状態となります。\n※プラグインのインストール/有効/無効/削除時には、自動的にメンテナンスモードに切り替わります。\n",
            'admin.content.maintenance_switch__on' => '有効にする',
            'admin.content.maintenance_switch__off' => '無効にする',
            // --- Content/layout (designer) ------------------------------
            'admin.content.layout_delete' => 'レイアウトを削除',
            'admin.content.layout_no_page' => 'ページが登録されていません',
            'admin.content.layout__card_title' => 'レイアウト概要',
            'admin.content.layout_edit__card_title' => 'レイアウト編集',
            'admin.content.layout_drag_and_drop_message' => 'ブロックをドラッグ＆ドロップ',
            'admin.content.layout_up' => '上に移動',
            'admin.content.layout_down' => '下に移動',
            'admin.content.layout_move_to' => 'セクションに移動',
            'admin.content.layout_preview' => 'プレビュー',
            'admin.content.layout_preview_select_page' => 'プレビューするページを選択してください',
            'admin.content.layout_preview_code' => 'コードプレビュー',
            'admin.content.layout_preview_code__confirm_title' => 'ブロックのコードプレビュー',
            'admin.content.layout_preview_code__confirm_message' => '編集が必要な場合はブロック編集画面から編集してください（この時レイアウト設定は保存されません）',
            'admin.content.layout_preview_code__confirm_move_to_block' => 'コードを編集',
            'admin.content.layout_move_to__confirm_title' => 'ブロックを移動',
            'admin.content.layout_move_to__confirm_message' => 'ブロックの移動先を選択してください',
            'admin.content.layout_move_to__execute' => '移動',
            'admin.content.layout_section__unused' => '未使用ブロック',
            'admin.content.layout_section__head' => '<head></head>タグ内',
            'admin.content.layout_section__body_after' => '<body>タグ直後',
            'admin.content.layout_section__header' => '#header',
            'admin.content.layout_section__contents_top' => '#contents_top',
            'admin.content.layout_section__side_left' => '#side_left',
            'admin.content.layout_section__main' => 'Main',
            'admin.content.layout_section__main_top' => '#main_top',
            'admin.content.layout_section__main_bottom' => '#main_bottom',
            'admin.content.layout_section__side_right' => '#side_right',
            'admin.content.layout_section__contents_bottom' => '#contents_bottom',
            'admin.content.layout_section__footer' => '#footer',
            'admin.content.layout_section__drawer' => '#drawer',
            'admin.content.layout_section__close_body_before' => '</body>タグ直前',
            'tooltip.content.layout_edit' => 'テンプレートのセクションごとにブロックをドラッグ＆ドロップで配置することができます。',
            // --- Content/file (file manager) ----------------------------
            'admin.content.file.add_file__card_title' => 'ファイル・フォルダを追加',
            'admin.content.file.add_file' => 'ファイルを追加',
            'admin.content.file.add_directory' => 'フォルダを追加',
            'admin.content.file.directory_name' => 'フォルダ名',
            'admin.content.file.file_list__card_title' => 'このフォルダ内のファイル',
            'admin.content.file.updated' => '更新',
            'admin.content.file.directory_tree' => 'フォルダ構成',
            'tooltip.content.file.upload_file' => 'ファイルの追加は複数ファイルを選択してアップロードできます。',
        ];
    }
}
