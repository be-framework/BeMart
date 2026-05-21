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
        ];
    }
}
