<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function count;
use function explode;
use function trim;

/**
 * Category CSV imported — Final, **Phase 2 stub** (Wave 7).
 *
 *   ImportCategoryCsvInput → CategoryCsvImported (Direct, admin AUTHZ)
 *
 * Surfaces only an AUTHZ check + a coarse line count. Real CSV
 * line-by-line ingestion (parent_id resolution, dry-run, validation)
 * is deferred — the call returns `accepted=false` with a notice so
 * the caller cannot mistake the stub for a real import. No durable
 * state is touched.
 *
 * This Final exists so the URL shape and the AUTHZ ladder can be
 * wired through the Resource layer ahead of the real implementation.
 */
final readonly class CategoryCsvImported
{
    public bool $accepted;
    public int $lineCount;
    public string $message;

    public function __construct(
        #[Input] string $csv,
        #[Inject] AdminSession $adminSession,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $trimmed = trim($csv);
        $lines = $trimmed === '' ? [] : explode("\n", $trimmed);

        $this->accepted = false;
        $this->lineCount = count($lines);
        $this->message = 'CSV import is a Phase 2 stub — no rows were persisted.';
    }
}
