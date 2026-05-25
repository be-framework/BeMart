<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE CSV登録フォーム — Product Tier-2.
 *
 * PORT of the file-upload form rendered by the four
 * `admin/Product/csv_*.twig` screens (`csv_product`, `csv_category`,
 * `csv_class_name`, `csv_class_category`). The four screens share an
 * identical structure — a single CSV file `<input type="file">` plus a
 * format-description table — so the upload form is ported once and
 * reused across the four CSV-upload resources.
 *
 * EC-CUBE renders the file input through `Form/Type/Admin/CsvImportType`
 * (`admin_csv_import` block prefix).
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminCsvUploadForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('import_file', 'file')
            ->setAttribs(['id' => 'admin_csv_import_import_file', 'class' => 'form-control']);

        // Non-authoritative structural check only — authority is the Be domain.
        $this->filter->validate('import_file')->isNotBlank();
    }
}
