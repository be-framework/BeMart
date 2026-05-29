<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\CsvDocument;
use Ray\Di\Di\Inject;

/**
 * Class-name CSV exported — Final, the EC-CUBE-format 規格名 CSV download
 * (goExportClassName).
 *
 *   ExportClassNameInput → ClassNameCsvExported   (Direct, safe)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * CSV body + headers come from {@see ClassCsvCompatibilityInterface}.
 */
final readonly class ClassNameCsvExported
{
    public CsvDocument $document;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassCsvCompatibilityInterface $csv,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->document = $csv->exportClassName();
    }
}
