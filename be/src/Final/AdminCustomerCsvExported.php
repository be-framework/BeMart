<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function count;
use function fclose;
use function fopen;
use function fputcsv;
use function rewind;
use function stream_get_contents;

/**
 * Admin customer CSV exported — Final, the back-office CSV download
 * payload (Wave 9, goExportCustomer).
 *
 *   AdminExportCustomerInput → AdminCustomerCsvExported  (Direct, safe)
 *
 * AUTHZ — admin firewall:
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Format is RFC 4180 — header row + one row per customer. Encoded via
 * PHP's native fputcsv() so quoting / escaping is identical to what
 * EC-CUBE downstream tooling expects. Mirrors Wave 8β's
 * {@see CategoryCsvExported} and Wave 8α's
 * {@see AdminProductCsvExported}.
 *
 * Column scope (Wave 9 first iteration): identification + contact +
 * status. The full dtb_customer carries password / secretKey / bonus
 * point ledger fields; those are intentionally NOT emitted —
 * passwordHash MUST NOT leak even to an admin export, and the bonus
 * ledger lives in a separate table.
 *
 * Wave 9 cap: emits up to 5,000 rows. The Phase 2 search-condition
 * filter (mirrors goCustomerList) will narrow the corpus before the
 * cap bites.
 */
final readonly class AdminCustomerCsvExported
{
    public string $csv;
    public int $rowCount;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        // No filter (Wave 9 first iteration); reuse the Wave 8β
        // CustomerQueryInterface::search surface with both keywords null.
        $rows = $customerQuery->search(null, null, 5000);

        $handle = fopen('php://temp', 'rb+');
        \assert($handle !== false);

        // PHP 8.4 deprecates the implicit $escape default; pass '' so
        // RFC 4180 quoting remains stable across versions.
        fputcsv($handle, [
            'customerId',
            'email',
            'name01',
            'name02',
            'kana01',
            'kana02',
            'companyName',
            'phoneNumber',
            'postalCode',
            'pref',
            'addr01',
            'addr02',
            'customerStatus',
        ], ',', '"', '');

        foreach ($rows as $row) {
            \assert($row instanceof CustomerEntity);
            fputcsv($handle, [
                $row->customerId,
                $row->email,
                $row->name01,
                $row->name02,
                $row->kana01 ?? '',
                $row->kana02 ?? '',
                $row->companyName ?? '',
                $row->phoneNumber ?? '',
                $row->postalCode ?? '',
                $row->pref ?? '',
                $row->addr01 ?? '',
                $row->addr02 ?? '',
                (string) $row->customerStatus,
            ], ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        \assert($csv !== false);
        fclose($handle);

        $this->csv = $csv;
        $this->rowCount = count($rows);
    }
}
