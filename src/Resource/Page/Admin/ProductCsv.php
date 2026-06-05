<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCreated;
use MyVendor\BeMart\Be\Final\AdminProductCsvExported;
use MyVendor\BeMart\Be\Input\AdminCreateProductInput;
use MyVendor\BeMart\Be\Input\AdminExportProductInput;

use function array_flip;
use function assert;
use function fclose;
use function fgetcsv;
use function fopen;
use function fwrite;
use function is_string;
use function rewind;
use function trim;

/**
 * EC-CUBE goExportProduct — 商品CSVをエクスポートする (Wave 8 admin).
 *
 * onGet only — safe download. Admin-only.
 *
 * POST imports rows using the same default columns as the export
 * projection: productCode, productName, price02, stock, productStatus,
 * description, searchWord, note. Each row is handed to the existing
 * doCreateProduct Be chain so AUTHZ, semantic validation and duplicate
 * detection stay in the same place as the form flow.
 *
 * Failure mapping:
 *   - UnauthorizedAdminAccessException → 403
 *
 * Success: 200 with the CSV as the response body's `csv` field and
 * the row count as `count`. The current first iteration returns the
 * CSV in the JSON body for testability; an HTTP-streaming Phase 2
 * variant will set `Content-Type: text/csv` and stream the bytes
 * directly. The shape here exists so the BEAR + Be integration is
 * proven end-to-end before adding stream plumbing.
 */
class ProductCsv extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    #[Link(rel: 'doImportProductCsv', href: 'page://self/admin/product-csv', method: 'post')]
    #[Link(rel: 'goExportCategory', href: 'page://self/admin/category/csv')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new AdminExportProductInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminProductCsvExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->headers['Content-Disposition'] = 'attachment; filename="products.csv"';
        $this->body = [
            'csv' => $final->csv,
            'count' => $final->count,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $csv
     */
    #[Link(rel: 'goExportCategory', href: 'page://self/admin/category/csv')]
    #[CsrfProtected]
    public function onPost(string $csv): static
    {
        $handle = fopen('php://memory', 'w+');
        if ($handle === false) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'CSVを読み込めませんでした。'];

            return $this;
        }

        fwrite($handle, $csv);
        rewind($handle);

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if (! is_array($header)) {
            fclose($handle);
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'CSVヘッダーがありません。'];

            return $this;
        }

        $columns = array_flip($header);
        if (! isset($columns['productCode'], $columns['productName'], $columns['price02'])) {
            fclose($handle);
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => '商品CSVの必須列がありません。'];

            return $this;
        }

        $count = 0;
        $productCodes = [];
        while (true) {
            $row = fgetcsv($handle, 0, ',', '"', '\\');
            if ($row === false || $row === null) {
                break;
            }

            $productCode = trim((string) ($row[$columns['productCode']] ?? ''));
            if ($productCode === '') {
                continue;
            }

            $stockCell = isset($columns['stock'], $row[$columns['stock']]) ? trim((string) $row[$columns['stock']]) : '';
            $statusCell = isset($columns['productStatus'], $row[$columns['productStatus']]) ? trim((string) $row[$columns['productStatus']]) : '';
            $descriptionColumn = $columns['description'] ?? null;
            $searchWordColumn = $columns['searchWord'] ?? null;
            $noteColumn = $columns['note'] ?? null;
            $description = $descriptionColumn !== null && isset($row[$descriptionColumn]) ? trim($row[$descriptionColumn]) : null;
            $searchWord = $searchWordColumn !== null && isset($row[$searchWordColumn]) ? trim($row[$searchWordColumn]) : null;
            $note = $noteColumn !== null && isset($row[$noteColumn]) ? trim($row[$noteColumn]) : null;

            try {
                $final = ($this->becoming)(new AdminCreateProductInput(
                    productCode: $productCode,
                    productName: trim((string) ($row[$columns['productName']] ?? '')),
                    price02: (int) trim((string) ($row[$columns['price02']] ?? '0')),
                    stock: $stockCell === '' ? null : (int) $stockCell,
                    productStatus: $statusCell === '' ? null : (int) $statusCell,
                    description: $description,
                    searchWord: $searchWord,
                    note: $note,
                ));
            } catch (SemanticVariableException $e) {
                fclose($handle);
                $this->code = Code::BAD_REQUEST;
                $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.', 'productCode' => $productCode];

                return $this;
            } catch (UnauthorizedAdminAccessException) {
                fclose($handle);
                $this->code = Code::FORBIDDEN;
                $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

                return $this;
            } catch (ProductCodeAlreadyInUseException) {
                fclose($handle);
                $this->code = 409;
                $this->body = ['message' => 'この商品コードは既に使用されています。', 'productCode' => $productCode];

                return $this;
            }

            assert($final instanceof AdminProductCreated);
            $count++;
            $productCodes[] = $final->productCode;
        }

        fclose($handle);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'doImportProductCsv',
            'count' => $count,
            'productCodes' => $productCodes,
        ];

        return $this;
    }
}
