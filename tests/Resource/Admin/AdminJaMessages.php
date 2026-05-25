<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

use MyVendor\BeMart\Tests\Resource\EcCubeStub;

/**
 * Per-section ja-messages mechanism for the admin-HTML render-diff tests.
 *
 * Why this exists — parallel-wave safety
 * --------------------------------------
 * The admin UI fans out into ~8 section-waves (Product / Order / Customer
 * / Content / Setting / Store / Top — see var/templates/README.md). Each
 * wave's render-diff tests substitute `{{ 'key'|trans }}` with the
 * Japanese literal from EC-CUBE's `messages.ja.yaml`. If every wave
 * appended its keys to the SHARED {@see EcCubeStub::jaMessages()} method,
 * that method would be a merge-conflict hotspot — two waves editing the
 * same lines block parallel work.
 *
 * This class makes the admin `trans` keys **per-section**:
 *
 *  - {@see EcCubeStub::jaMessages()} stays the FROZEN storefront baseline
 *    (Cart / Login / Entry / ... + the default-theme frame). Admin waves
 *    NEVER touch it.
 *  - {@see chrome()} carries the keys EVERY admin page needs — the admin
 *    frame (`default_frame.twig`) + sidebar (`nav.twig`) chrome. Shared
 *    infra; stable, edited only when the frame port itself changes.
 *  - Each section ships its OWN keys in a per-section file next to this
 *    one — `Admin/<Section>JaMessages.php`, a class with a `keys()`
 *    static returning `array<string,string>`. Adding a wave = adding ONE
 *    new file; no shared file is touched.
 *
 * A section's render test merges the three layers with {@see forSection()}
 * and feeds the result to its `trans` stub. See {@see CustomerJaMessages}
 * for the worked example and `AdminCustomerListHtmlRenderTest` for the
 * call site.
 */
final class AdminJaMessages
{
    /**
     * The admin-frame + sidebar chrome `trans` keys — needed by EVERY
     * admin page (`admin/default_frame.twig` + `admin/nav.twig`). Values
     * copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
     *
     * @return array<string, string>
     */
    public static function chrome(): array
    {
        return [
            'admin.home' => 'ホーム',
            'admin.info' => '情報',
            'admin.info.official_site' => '公式サイト',
            'admin.info.community' => '開発コミュニティ',
            'admin.info.document' => 'ドキュメント',
            'admin.info.manual' => '管理・運用マニュアル',
            'admin.header.last_login' => '最終ログイン',
            'admin.header.change_password' => 'パスワード変更',
            'admin.header.two_factor_auth' => '2段階認証 設定',
            'admin.header.logout' => 'ログアウト',
            'admin.header.user_name' => '%name% 様',
            // admin.common.* shared by the frame's page-title area and by
            // every section's action buttons / delete modals.
            'admin.common.create__new' => '新規作成',
            'admin.common.registration' => '登録',
            'admin.common.registration__new' => '新規登録',
            'admin.common.edit' => '編集',
            'admin.common.delete' => '削除',
            'admin.common.cancel' => 'キャンセル',
            'admin.common.decision' => '決定',
            'admin.common.id' => 'ID',
            'admin.common.up' => '上へ',
            'admin.common.down' => '下へ',
            'admin.common.required' => '必須',
            'admin.common.search' => '検索',
            'admin.common.send' => '送信',
            'admin.common.delete_modal__title' => '削除します',
            'admin.common.delete_modal__message' => 'この操作はあとから取り消すことができません。「%name%」を削除してよろしいですか？',
        ];
    }

    /**
     * Builds the full ja-message map for one admin section's render test:
     * the frozen storefront baseline + the shared admin chrome + the
     * section's own keys.
     *
     * @param array<string, string> $sectionKeys the section's keys, from
     *        the section's `Admin/<Section>JaMessages::keys()`
     *
     * @return array<string, string>
     */
    public static function forSection(array $sectionKeys): array
    {
        return [
            ...EcCubeStub::jaMessages(),
            ...self::chrome(),
            ...$sectionKeys,
        ];
    }
}
