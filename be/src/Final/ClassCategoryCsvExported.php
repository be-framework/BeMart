<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\CsvDocument;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class-category CSV exported — Final, the EC-CUBE-format 規格分類 CSV
 * download (goExportClassCategory).
 *
 *   ExportClassCategoryInput → ClassCategoryCsvExported   (Direct, safe)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * CSV body + headers come from {@see ClassCsvCompatibilityInterface},
 * optionally scoped to one class-name axis.
 */
final readonly class ClassCategoryCsvExported
{
    public CsvDocument $document;

    public function __construct(
        #[Input] string|null $classNameId,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassCsvCompatibilityInterface $csv,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->document = $csv->exportClassCategory($classNameId);
    }
}
