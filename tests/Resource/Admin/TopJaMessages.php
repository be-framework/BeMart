<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **top-level** section (the templates
 * directly under `admin/`, not in a section subdirectory).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. Covers the top-level admin pages ported in the top-level
 * wave:
 *
 *  - `index.twig`               — 管理画面ダッシュボード (home / dashboard)
 *  - `login.twig`               — 管理者ログイン
 *  - `change_password.twig`     — パスワード変更
 *  - `two_factor_auth.twig`     — 2段階認証 (challenge)
 *  - `two_factor_auth_set.twig` — 2段階認証 デバイス登録
 *  - `empty_page.twig`          — プラグイン拡張用スロット (chrome only)
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class TopJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- login.twig ---------------------------------------------
            'admin.login' => 'ログイン',
            'admin.login.enable_javascript' => 'JavaScript を有効にしてご利用ください',
            'admin.login.login' => 'ログイン',
            'admin.login.loginId' => 'ログインID',
            'admin.login.password' => 'パスワード',
            // --- change_password.twig -----------------------------------
            'admin.change_password' => 'パスワード変更',
            'admin.change_password.current_password' => '現在のパスワード',
            'admin.change_password.new_password' => '新しいパスワード',
            'admin.change_password.new_password_confirm' => '新しいパスワード(確認)',
            'admin.change_password.password_changed' => 'パスワードを更新しました',
            // --- index.twig (dashboard) ---------------------------------
            'admin.home' => 'ホーム',
            'admin.home.order_status_title' => '注文状況',
            'admin.home.shop_status_title' => 'ショップ状況',
            'admin.home.shop_status_out_of_stock' => '在庫切れ商品数',
            'admin.home.shop_status_products' => '取扱商品数',
            'admin.home.shop_status_customers' => '会員数',
            'admin.home.sales_summary_title' => '売上状況',
            'admin.home.sales_summary_value' => '%amount% / %count% 件',
            'admin.home.sales_summary_this_month' => '今月の売上金額 / 売上件数',
            'admin.home.sales_summary_today' => '今日の売上金額 / 売上件数',
            'admin.home.sales_summary_yesterday' => '昨日の売上金額 / 売上件数',
            'admin.home.sales_summary_weekly' => '週間',
            'admin.home.sales_summary_monthly' => '月間',
            'admin.home.sales_summary_yearly' => '年間',
            'admin.home.sales_summary_amount' => '売上金額',
            'admin.home.sales_summary_count' => '売上件数',
            'admin.home.recommend_plugins_title' => 'おすすめのプラグイン',
            'admin.home.recommend_plugins.owner_store' => 'オーナーズストア',
            'admin.home.news_title' => 'お知らせ',
            // --- two_factor_auth.twig / two_factor_auth_set.twig --------
            'admin.setting.system.two_factor_auth_title' => '2段階認証',
            'admin.setting.system.two_factor_auth.qr' => 'QRコード',
            'admin.setting.system.two_factor_auth.device_token' => 'トークン',
            'admin.setting.system.two_factor_auth.auth' => '認証',
            'tooltip.setting.system.two_factor_auth.qr_code' => 'QRコードを2段階認証用スマートフォンアプリで読み込み、表示された6桁の数字を入力してください。',
        ];
    }
}
