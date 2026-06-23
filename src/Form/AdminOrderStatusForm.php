<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 受注対応状況設定フォーム — Setting/Shop Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/OrderStatusSettingType` leaf
 * fields used by `admin/Setting/Shop/order_status.twig`. This form is
 * renderer-only; the current Be domain has an order-status transition
 * for orders, not a transition for editing the status master labels.
 */
final class AdminOrderStatusForm extends AbstractForm
{
    /** @var list<array{id: int, name: string, customerName: string, color: string, displayOrderCount: bool}> */
    public const DEFAULT_ROWS = [
        ['id' => 1, 'name' => '新規受付', 'customerName' => '注文受付', 'color' => '#437ec4', 'displayOrderCount' => true],
        ['id' => 6, 'name' => '入金済み', 'customerName' => '入金済み', 'color' => '#5cb85c', 'displayOrderCount' => true],
        ['id' => 4, 'name' => '対応中', 'customerName' => '対応中', 'color' => '#f0ad4e', 'displayOrderCount' => true],
        ['id' => 5, 'name' => '発送済み', 'customerName' => '発送済み', 'color' => '#5bc0de', 'displayOrderCount' => true],
        ['id' => 3, 'name' => 'キャンセル', 'customerName' => 'キャンセル', 'color' => '#d9534f', 'displayOrderCount' => false],
    ];

    #[Override]
    public function init(): void
    {
        foreach (self::DEFAULT_ROWS as $index => $row) {
            $prefix = self::fieldPrefix($row['id']);
            $viewPrefix = 'form_OrderStatuses_' . $index;

            $this->setField($prefix . '_customer_order_status_name', 'text')
                ->setAttribs(['id' => $viewPrefix . '_customer_order_status_name', 'class' => 'form-control']);
            $this->setField($prefix . '_name', 'text')
                ->setAttribs(['id' => $viewPrefix . '_name', 'class' => 'form-control']);
            $this->setField($prefix . '_color', 'color')
                ->setAttribs(['id' => $viewPrefix . '_color', 'class' => 'form-control form-control-color']);
            // Scalar (non-array) checkbox: `setOptions()` would render
            // `name="..._display_order_count[]"` (an array), which the
            // `string|null` resource boundary rejects with a 400. A scalar
            // `value=1` checkbox matches the working EntryForm convention.
            $this->setField($prefix . '_display_order_count', 'checkbox')
                ->setAttribs(['id' => $viewPrefix . '_display_order_count', 'value' => '1']);
        }

        $this->fillDefaults();
    }

    /** @return list<array{id: int, nameKey: string, customerNameKey: string, colorKey: string, displayOrderCountKey: string}> */
    public static function rows(): array
    {
        $rows = [];
        foreach (self::DEFAULT_ROWS as $row) {
            $prefix = self::fieldPrefix($row['id']);
            $rows[] = [
                'id' => $row['id'],
                'nameKey' => $prefix . '_name',
                'customerNameKey' => $prefix . '_customer_order_status_name',
                'colorKey' => $prefix . '_color',
                'displayOrderCountKey' => $prefix . '_display_order_count',
            ];
        }

        return $rows;
    }

    private static function fieldPrefix(int $id): string
    {
        return 'order_status_' . $id;
    }

    private function fillDefaults(): void
    {
        $values = [];
        foreach (self::DEFAULT_ROWS as $row) {
            $prefix = self::fieldPrefix($row['id']);
            $values[$prefix . '_customer_order_status_name'] = $row['customerName'];
            $values[$prefix . '_name'] = $row['name'];
            $values[$prefix . '_color'] = $row['color'];
            $values[$prefix . '_display_order_count'] = $row['displayOrderCount'] ? '1' : null;
        }

        $this->fill($values);
    }
}
