<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE CSV出力項目設定フォーム — Setting/Shop Tier-2.
 *
 * PORT of the dynamic form builder in EC-CUBE's CsvController. The
 * three controls match `admin/Setting/Shop/csv.twig`: CSV type, output
 * columns, and non-output columns.
 */
final class AdminCsvConfigForm extends AbstractForm
{
    /** @var array<int, string> — numeric-string keys coerce to int (EC-CUBE CsvType ids) */
    private const CSV_TYPES = [
        '1' => '受注CSV',
        '2' => '会員CSV',
        '3' => '商品CSV',
        '4' => '配送CSV',
    ];

    /** @var array<string, string> */
    private const OUTPUT_COLUMNS = [
        'orderNo' => '注文番号',
        'orderDate' => '注文日時',
        'customerName' => '顧客名',
        'paymentTotal' => 'お支払い合計',
    ];

    /** @var array<string, string> */
    private const NOT_OUTPUT_COLUMNS = [
        'paymentMethod' => '支払方法',
        'deliveryName' => '配送方法',
        'trackingNumber' => 'お問い合わせ番号',
    ];

    #[Override]
    public function init(): void
    {
        $this->setField('csvType', 'select')
            ->setAttribs(['id' => 'csv-type', 'class' => 'form-select'])
            ->setOptions(self::CSV_TYPES);
        $this->setField('csvNotOutput', 'select')
            ->setAttribs(['id' => 'csv-not-output', 'class' => 'form-select', 'multiple' => 'multiple', 'size' => '30'])
            ->setOptions(self::NOT_OUTPUT_COLUMNS);
        $this->setField('csvOutput', 'select')
            ->setAttribs(['id' => 'csv-output', 'class' => 'form-select', 'multiple' => 'multiple', 'size' => '30'])
            ->setOptions(self::OUTPUT_COLUMNS);

        $this->fill([
            'csvType' => '1',
            'csvOutput' => ['orderNo', 'orderDate', 'customerName', 'paymentTotal'],
            'csvNotOutput' => [],
        ]);
    }

    /** @return array<string, string> */
    public static function outputColumns(): array
    {
        return self::OUTPUT_COLUMNS;
    }

    /** @return array<string, string> */
    public static function notOutputColumns(): array
    {
        return self::NOT_OUTPUT_COLUMNS;
    }
}
