<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 特定商取引法設定フォーム — Setting/Shop Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/TradeLawType` leaf fields. The
 * existing Be domain stores the page as a body blob, so the resource
 * parses that blob into renderer rows and this form defines the row
 * controls.
 */
final class AdminTradeLawForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        for ($index = 1; $index <= 6; $index++) {
            $prefix = self::fieldPrefix($index);
            $viewPrefix = 'form_TradeLaws_' . ($index - 1);

            $this->setField($prefix . '_name', 'text')
                ->setAttribs(['id' => $viewPrefix . '_name', 'class' => 'form-control']);
            $this->setField($prefix . '_description', 'textarea')
                ->setAttribs(['id' => $viewPrefix . '_description', 'class' => 'form-control', 'rows' => '3']);
            $this->setField($prefix . '_displayOrderScreen', 'checkbox')
                ->setAttribs(['id' => $viewPrefix . '_displayOrderScreen'])
                ->setOptions(['1' => '']);
        }
    }

    /**
     * Consumes the renderer rows from {@see \MyVendor\BeMart\Resource\Page\Admin\TradeLaw}.
     * The row also carries `*Key` field-name hints for the template; this
     * setter only reads `id`/`name`/`description`/`displayOrderScreen`, so
     * the shape is unsealed (`...`).
     *
     * @param list<array{id: int, name: string, description: string, displayOrderScreen: bool, ...}> $rows
     */
    public function fillRows(array $rows): void
    {
        $values = [];
        foreach ($rows as $row) {
            $prefix = self::fieldPrefix($row['id']);
            $values[$prefix . '_name'] = $row['name'];
            $values[$prefix . '_description'] = $row['description'];
            $values[$prefix . '_displayOrderScreen'] = $row['displayOrderScreen'] ? '1' : null;
        }

        $this->fill($values);
    }

    private static function fieldPrefix(int $id): string
    {
        return 'trade_law_' . $id;
    }

    /** @return array{nameKey: string, descriptionKey: string, displayOrderScreenKey: string} */
    public static function fieldKeys(int $id): array
    {
        $prefix = self::fieldPrefix($id);

        return [
            'nameKey' => $prefix . '_name',
            'descriptionKey' => $prefix . '_description',
            'displayOrderScreenKey' => $prefix . '_displayOrderScreen',
        ];
    }
}
