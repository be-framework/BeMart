<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class-name CSV imported — Final, proof an admin uploaded the 規格名 CSV
 * (doImportClassNameCsv).
 *
 *   ImportClassNameCsvInput → ClassNameCsvImported   (Direct, unsafe)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * parse/persist is delegated to {@see ClassCsvCompatibilityInterface};
 * `accepted` is the number of data rows taken.
 */
final readonly class ClassNameCsvImported
{
    public int $accepted;

    public function __construct(
        #[Input] string $csv,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassCsvCompatibilityInterface $csvService,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->accepted = $csvService->importClassName($csv);
    }
}
