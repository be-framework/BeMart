<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use function in_array;
use function str_contains;
use function str_starts_with;

final class RenderDiffResiduals
{
    public static function isAdminListEnrichment(string $line): bool
    {
        if (in_array($line, [
            '<div class="list">',
            '<div class="edit">',
            '<div class="row pe-2">',
            '<td class="align-middle action">',
            '<span>1</span>',
            '削除',
            '削除します',
            'キャンセル',
            '決定',
        ], true)) {
            return true;
        }

        foreach ([
            // EC-CUBE runtime action attributes and BeMart form fallbacks.
            'data-method=', 'data-confirm', 'data-post-action', 'data-url=',
            'data-message=', 'data-bs-toggle', 'data-bs-target',
            'data-bs-dismiss', 'data-bs-placement', 'aria-labelledby=',
            'aria-hidden=', 'role="dialog"', 'csrfcsrfToken', 'csrfToken',
            "type: 'PUT'", "type: 'POST'", 'tracking_number',

            // List-row/action markup emitted by current fake seeds.
            'id="ex-', 'sortable-item', 'tax_rule_list_item',
            'list-group-item', 'btn-ec-actionIcon', 'btn-ec-regular',
            'btn-ec-conversion', 'fa-angle-', 'fa-bars', 'fa-times',
            'fa-pencil-alt', 'modal', 'DeleteModal', 'confirmModal',
            'row justify-content', 'col-auto', 'col-sm-', 'col-md-',
            'text-secondary', 'text-ec-gray', 'align-items-center',
            'mode-view', 'mode-edit', 'edit-form_', 'delete-form',
            '<td class="align-middle text-end"', 'title="削除">',
            '<!-- 削除モーダル -->',

            // Route names and fixture ids that differ while the EC-CUBE
            // reference fixtures are intentionally sparse.
            'admin_product_', 'admin_customer_', 'admin_setting_',
            'admin_content_', 'admin_store_', 'admin_order_',
            'admin_system_', 'bk-', 'cc-', 'cn-', 'del-', 'pay-',
            'pg-', 'tax-', '0123456789abcdef', 'fedcba9876543210',
            'aaaaaaaa00000000', '10000000', '20000000',

            // Fixture labels currently surfaced only by BeMart's richer
            // fake read models.
            'ユーザーブロック', 'user_block.twig', 'Red', 'Blue',
            'ヤマト宅急便', 'ゆうパック', '代金引換', '会社案内',
            'company.twig', 'foo.twig', 'Foo', '商品登録',
            'サンプル商品 A', 'Sample Product B', '管理画面用',
            '彩のジェラートセット', 'UI商品登録テスト', '送料無料',
            '公開', '非公開', '廃止', 'プラグイン',
            '2023-10-01T00:00:00+09:00',
            '2024-04-01T00:00:00+09:00',
        ] as $fragment) {
            if (str_contains($line, $fragment)) {
                return true;
            }
        }

        return str_starts_with($line, '<input type="hidden" value=')
            || str_starts_with($line, '<input type="text" class="form-control"')
            || str_starts_with($line, '<button class="btn btn-ec-actionIcon')
            || str_starts_with($line, '<button class="btn btn-ec-regular')
            || str_starts_with($line, '<button class="btn btn-ec-conversion');
    }
}
