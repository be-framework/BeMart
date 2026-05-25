<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function count;
use function explode;
use function trim;

/**
 * Admin shipping CSV imported — Final, **Phase 2 stub** (Wave 9η).
 *
 *   AdminImportShippingCsvInput → AdminShippingCsvImported
 *                                  (Direct, admin AUTHZ)
 *
 * Mirrors the Wave 8 {@see CategoryCsvImported} stub. Surfaces only
 * an AUTHZ check + a coarse line count. Real CSV ingestion (tracking-
 * number column, shipDate parsing, dry-run, validation) is deferred —
 * the call returns `accepted=false` with a notice so the caller cannot
 * mistake the stub for a real import. No durable state is touched.
 */
final readonly class AdminShippingCsvImported
{
    public bool $accepted;
    public int $lineCount;
    public string $message;

    public function __construct(
        #[Input] string $csv,
        #[Inject] AdminSessionInterface $adminSession,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $trimmed = trim($csv);
        $lines = $trimmed === '' ? [] : explode("\n", $trimmed);

        $this->accepted = false;
        $this->lineCount = count($lines);
        $this->message = 'Shipping CSV import is a Phase 2 stub — no rows were persisted.';
    }
}
