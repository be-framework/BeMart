<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Admin;

/**
 * `trans` keys for the admin **Store** section (`admin/Store/`).
 *
 * Per-section ja-keys file — see {@see AdminJaMessages} for the
 * mechanism. Covers the Tier-1 Store pages whose BEAR resource already
 * exists and was ported in this wave:
 *
 *  - `plugin.twig` (インストールプラグイン一覧 list,
 *    {@see \MyVendor\BeMart\Tests\Resource\AdminPluginListHtmlRenderTest})
 *  - `plugin_install.twig` (独自プラグインのアップロード form,
 *    {@see \MyVendor\BeMart\Tests\Resource\AdminPluginInstallHtmlRenderTest})
 *  - `template.twig` (テンプレート一覧 list,
 *    {@see \MyVendor\BeMart\Tests\Resource\AdminTemplateListHtmlRenderTest})
 *
 * The not-yet-ported Store marketplace pages (`plugin_search`,
 * `plugin_confirm`, `plugin_confirm_uninstall`, `plugin_handler`,
 * `authentication_setting`, `template_add`) need brand-new BEAR
 * resources / `onGet` branches — deferred to a future wave. A subset of
 * their keys are kept here so that wave only adds its templates + tests,
 * not a new ja-keys file.
 *
 * Values copied verbatim from `src/Eccube/Resource/locale/messages.ja.yaml`.
 */
final class StoreJaMessages
{
    /** @return array<string, string> */
    public static function keys(): array
    {
        return [
            // --- common.* shared by the Store pages ---------------------
            'common.enabled' => '有効',
            'common.disabled' => '無効',
            'common.back' => '戻る',
            // --- admin.common.* the Store pages borrow ------------------
            'admin.common.upload' => 'アップロード',
            'admin.common.download' => 'ダウンロード',
            // --- admin.store root ---------------------------------------
            'admin.store' => 'オーナーズストア',
            // --- Store / plugin list (plugin.twig + table partials) -----
            'admin.store.plugin' => 'プラグイン',
            'admin.store.plugin.plugin_list' => 'プラグイン一覧',
            'admin.store.plugin.plugin_owners_install' => 'プラグインを探す',
            'admin.store.plugin.install' => 'インストール',
            'admin.store.plugin.help' => 'オーナーズストアへの接続に問題があるため、詳細な情報を取得できませんでした。',
            'admin.store.plugin.popup.delete.confirm.title' => 'プラグインの削除を確認する',
            'admin.store.plugin.popup.delete.confirm.message' => 'このプラグインを削除してもよろしいですか？',
            'admin.store.plugin.809' => 'インストールプラグイン一覧',
            'admin.store.plugin.810' => 'オーナーズストア',
            'admin.store.plugin.811' => 'オーナーズストアから新規追加',
            'admin.store.plugin.812' => 'オーナーズストアのプラグイン',
            'admin.store.plugin.813' => 'アップロードして新規追加',
            'admin.store.plugin.814' => 'ユーザー独自プラグイン',
            'admin.store.plugin.815' => '未登録プラグイン',
            'admin.store.plugin.816' => 'プラグイン名',
            // --- plugin_table.twig --------------------------------------
            'admin.store.plugin_table.887' => 'プラグイン名',
            'admin.store.plugin_table.888' => 'バージョン',
            'admin.store.plugin_table.889' => 'コード',
            'admin.store.plugin_table.890' => 'アップデート',
            'admin.store.plugin_table.891' => '設定',
            'admin.store.plugin_table.897' => 'README',
            'admin.store.plugin_table.898' => 'アップデート',
            'admin.store.plugin_table.900' => 'ユーザー独自プラグインはインストールされていません。',
            'admin.store.plugin_table.901' => '状態',
            'admin.store.plugin_table.902' => '有効にする',
            'admin.store.plugin_table.903' => '無効にする',
            // --- plugin_table_official.twig -----------------------------
            'admin.store.plugin_table_official.901' => 'プラグイン名',
            'admin.store.plugin_table_official.905' => 'アップデート',
            'admin.store.plugin_table_official.906' => '設定',
            'admin.store.plugin_table_official.916' => 'アップデート',
            'admin.store.plugin_table_official.917' => '最新バージョン : %version%',
            'admin.store.plugin_table_official.918' => '対応バージョン : %versions%',
            'admin.store.plugin_table_official.919' => '%update_date%',
            'admin.store.plugin_table_official.920' => '最新版です',
            'admin.store.plugin_table_official.923' => 'オーナーズストアのプラグインはインストールされていません。',
            'admin.store.plugin_table_official.924' => '有効にする',
            'admin.store.plugin_table_official.925' => '無効にする',
            // --- unregisterd_plugin_table.twig --------------------------
            'admin.store.unregistered_plugin_table.941' => 'プラグイン名',
            'admin.store.unregistered_plugin_table.942' => 'バージョン',
            'admin.store.unregistered_plugin_table.943' => 'コード',
            'admin.store.unregistered_plugin_table.944' => '状態',
            'admin.store.unregistered_plugin_table.945' => '操作',
            'admin.store.unregistered_plugin_table.946' => '不明',
            'admin.store.unregistered_plugin_table.947' => '不明',
            'admin.store.unregistered_plugin_table.949' => '不明',
            'admin.store.unregistered_plugin_table.950' => '設定',
            // --- plugin_install.twig ------------------------------------
            'admin.store.install.label' => 'プラグイン<br>(zip、tar、tar.gz形式)',
            // --- Store / template (template.twig + template_add.twig) ---
            'admin.store.template' => 'テンプレート',
            'admin.store.template.template_list' => 'テンプレート一覧',
            'admin.store.template.template_install' => 'アップロード',
            'admin.store.template.template_install__page_title' => 'テンプレートのアップロード',
            'admin.store.template.select' => '選択',
            'admin.store.template.save_path' => '保存先',
            'admin.store.template.download' => 'ダウンロード',
            'admin.store.template.upload_new_template' => '新規テンプレートアップロード',
            'admin.store.template.template_name' => 'テンプレート名',
            'admin.store.template.template_code' => 'テンプレートコード',
            'admin.store.template.template_file' => 'テンプレートファイル',
            'admin.store.template.file_format' => 'File format: zip,tar,tar.gz',
        ];
    }
}
