<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **Setting/System** section
 * (`admin/Setting/System/`).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. Covers the ported Setting/System pages:
 *
 *  - `member.twig`         — メンバー管理 list
 *    ({@see \MyVendor\BeMart\Tests\Resource\AdminMemberListHtmlRenderTest})
 *  - `login_history.twig`  — ログイン履歴 list/search
 *    ({@see \MyVendor\BeMart\Tests\Resource\AdminLoginHistoryHtmlRenderTest})
 *
 * Keys for the remaining Setting/System pages (`member_edit`,
 * `authority`, `system`, `log`, `masterdata`, `security`,
 * `two_factor_auth_edit`) are kept here too so that, when those pages
 * are ported, the wave only adds its templates + tests, not a new
 * ja-keys file.
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class SystemJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- common.* used by the System pages ----------------------
            'common.enabled' => '有効',
            'common.disabled' => '無効',
            // --- admin.common.* used by the System pages ----------------
            'admin.common.save' => '保存',
            'admin.common.authority' => '権限',
            // --- Setting/System section headings ------------------------
            'admin.setting.system' => 'システム設定',
            'admin.setting.system.authority_management' => '権限管理',
            'admin.setting.system.security_management' => 'セキュリティ管理',
            'admin.setting.system.log_display' => 'ログ表示',
            'admin.setting.system.login_history' => 'ログイン履歴',
            'admin.setting.system.master_data_management' => 'マスタデータ管理',
            'admin.setting.system.system_info' => 'システム情報',
            'admin.setting.system.member_management' => 'メンバー管理',
            'admin.setting.system.member_password_change' => 'パスワード変更',
            // --- member / member_edit -----------------------------------
            'admin.setting.system.member.member_registration' => 'メンバー登録',
            'admin.setting.system.member.name' => '名前',
            'admin.setting.system.member.department' => '所属',
            'admin.setting.system.member.login_id' => 'ログインID',
            'admin.setting.system.member.password' => 'パスワード',
            'admin.setting.system.member.password_confirm' => 'パスワード(確認)',
            'admin.setting.system.member.work' => '稼働',
            'admin.setting.system.member.two_factor_auth_enabled' => '2段階認証',
            'admin.setting.system.member.two_factor_auth_completed' => '2段階認証の設定は完了しています。',
            'admin.setting.system.member.two_factor_auth_incompleted' => '2段階認証の設定が未完了です。このユーザでログインし、設定を完了させてください。',
            'admin.setting.system.member.delete__confirm_title' => 'メンバーを削除します。',
            'admin.setting.system.member.delete__confirm_message' => 'メンバーを削除してよろしいですか？',
            'admin.setting.system.member.work_can_not_change' => '非稼働に変更することはできません。',
            'tooltip.setting.system.member.authority' => '権限管理で設定した権限を選択できます。',
            'tooltip.setting.system.member.work' => '一時的に非稼働にすることが可能です。必要ない場合はメンバー一覧より削除してください。',
            'tooltip.setting.system.member.two_factor_auth_enabled' => '有効にすると2段階認証でのログインが必要になります。2段階認証のデバイス設定をしていない状態でログインした場合、設定後にログインできるようになります。',
            // --- login_history ------------------------------------------
            'admin.setting.system.login_history.multi_search_label' => 'ログインID・IPアドレス',
            'admin.setting.system.login_history.create_date' => 'ログイン試行日',
            'admin.setting.system.login_history.create_date__start' => 'ログイン試行日(開始)',
            'admin.setting.system.login_history.create_date__end' => 'ログイン試行日(終了)',
            'admin.setting.system.login_history.user_name' => 'ログインID',
            'admin.setting.system.login_history.client_ip' => 'IPアドレス',
            'admin.setting.system.login_history.status' => 'ステータス',
            'tooltip.setting.system.login_history.multi_search_label' => '情報を入力して一覧の絞り込み検索ができます。より詳細な条件を指定するには［詳細検索］を開いてください。',
            // --- common search-result labels the list pages borrow ------
            'admin.common.search' => '検索',
            'admin.common.search_detail' => '詳細検索',
            'admin.common.search_result' => '検索結果：%count%件が該当しました',
            'admin.common.search_no_result' => '検索条件に合致するデータが見つかりませんでした',
            'admin.common.search_invalid_condition' => '検索条件に誤りがあります',
            'admin.common.search_try_change_condition' => '検索条件を変えて、再度検索をお試しください',
            'admin.common.search_try_advanced_search' => '[詳細検索]も試してみましょう',
            'admin.common.separator__range' => '〜',
            'admin.common.count' => '%count%件',
            // --- log ----------------------------------------------------
            'admin.setting.system.log.line_number' => '行',
            'admin.setting.system.log.read' => '読み込む',
            'admin.setting.system.log.log_level' => 'ログレベル',
            'admin.setting.system.log.keyword' => 'キーワード検索',
            'admin.setting.system.log.keyword_placeholder' => 'クラス名、エラーメッセージなど',
            'admin.setting.system.log.no_logs' => 'ログが見つかりません',
            'tooltip.setting.system.log_display' => 'アクセスやエラー状況などのログを確認できます。',
            // --- master data --------------------------------------------
            'admin.setting.system.master_data.select' => '選択',
            'admin.setting.system.master_data.id' => 'ID',
            'admin.setting.system.master_data.name' => 'Name',
            'tooltip.setting.system.master_data_management' => '各種マスターデータを管理します。',
            // --- system info --------------------------------------------
            'admin.setting.system.system.php_info' => 'PHP情報',
            'tooltip.setting.system.system_info' => 'このEC-CUBEに関係しているシステム環境の情報を表示しています。',
            // --- authority ----------------------------------------------
            'admin.setting.system.authority__card_title' => '権限設定',
            'admin.setting.system.authority.authority' => '権限',
            'admin.setting.system.authority.deny_url' => '拒否URL',
            'admin.setting.system.authority.add_row' => '行を追加',
            // --- security -----------------------------------------------
            'admin.setting.system.security_admin_url__card_title' => '管理画面URL設定',
            'admin.setting.system.security_admin_ip_limit__card_title' => '管理画面IP制限設定',
            'admin.setting.system.security_front_ip_limit__card_title' => 'フロント画面IP制限設定',
            'admin.setting.system.security_connect_card_title' => '接続設定',
            'admin.setting.system.security.admin_url' => '管理画面URL',
            'admin.setting.system.security.ip_limit' => 'IP制限(許可リスト)',
            'admin.setting.system.security.ip_limit_deny' => 'IP制限(拒否リスト)',
            'admin.setting.system.security.force_ssl' => 'SSLを強制',
            'admin.setting.system.security.trusted_hosts' => '信頼できるホスト名',
            // --- two-factor auth ----------------------------------------
            'admin.setting.system.two_factor_auth_title' => '2段階認証',
            'admin.setting.system.two_factor_auth.qr' => 'QRコード',
            'admin.setting.system.two_factor_auth.device_token' => 'トークン',
        ];
    }
}
