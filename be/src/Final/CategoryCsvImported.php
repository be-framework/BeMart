<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\CategoryIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_shift;
use function count;
use function explode;
use function str_getcsv;
use function trim;

/**
 * Category CSV imported — Final, real line-by-line ingestion
 * (doImportCategoryCsv).
 *
 *   ImportCategoryCsvInput → CategoryCsvImported  (Direct, unsafe, admin AUTHZ)
 *
 * EC-CUBE's category CSV carries four columns —
 * `カテゴリID, カテゴリ名, 親カテゴリID, カテゴリ削除フラグ`:
 *
 *   - delete-flag `1` + a non-empty id → {@see CategoryStorageInterface::delete}
 *   - otherwise → upsert via {@see CategoryStorageInterface::put}; an empty
 *     id allocates a new one through {@see CategoryIdQueryInterface::next};
 *     an empty parent id becomes a root node; `sortNo` follows row order.
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). Rows
 * with an empty name (and no delete flag) are skipped as malformed. The
 * header row is dropped. `imported` / `deleted` report what the parse
 * sent to storage; durable persistence is exercised by the SQL suite
 * (Fake writes are no-ops, the established convention).
 */
final readonly class CategoryCsvImported
{
    public bool $accepted;

    /** Total non-empty lines INCLUDING the header row; data rows = lineCount - 1. */
    public int $lineCount;

    public int $imported;
    public int $deleted;
    public string $message;

    public function __construct(
        #[Input] string $csv,
        #[Inject] AdminSession $adminSession,
        #[Inject] CategoryStorageInterface $categories,
        #[Inject] CategoryIdQueryInterface $categoryIds,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $trimmed = trim($csv);
        $lines = $trimmed === '' ? [] : explode("\n", $trimmed);
        $this->lineCount = count($lines);

        // Drop the header row.
        array_shift($lines);

        $imported = 0;
        $deleted = 0;
        $sortNo = 0;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $fields = str_getcsv($line);
            $id = trim((string) ($fields[0] ?? ''));
            $name = trim((string) ($fields[1] ?? ''));
            $parentId = trim((string) ($fields[2] ?? ''));
            $deleteFlag = trim((string) ($fields[3] ?? '0'));

            if ($deleteFlag === '1') {
                if ($id !== '') {
                    $categories->delete($id);
                    $deleted++;
                }

                continue;
            }

            if ($name === '') {
                continue;
            }

            $categoryId = $id !== '' ? $id : $categoryIds->next()->value;
            $categories->put(new CategoryEntity(
                categoryId: $categoryId,
                categoryName: $name,
                parentId: $parentId !== '' ? $parentId : null,
                sortNo: $sortNo,
            ));
            $imported++;
            $sortNo++;
        }

        $this->imported = $imported;
        $this->deleted = $deleted;
        $this->accepted = true;
        $this->message = "カテゴリCSVを取り込みました（登録/更新 {$imported}件・削除 {$deleted}件）。";
    }
}
