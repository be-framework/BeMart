<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use Override;
use PDO;
use RuntimeException;
use Throwable;

use function sprintf;

/**
 * Real PDO-backed Product command — Phase 2b.
 *
 * Mirrors {@see FakeProductCommand} against the live EC-CUBE 4.3
 * schema. Pure prepared statements: no Doctrine, no ORM.
 *
 * The "two-row write" — dtb_product + dtb_product_class
 * -----------------------------------------------------
 * {@see ProductEntity} is a flattened Product × default-ProductClass
 * row (see {@see SqlProductQuery} class doc for the column split). It
 * is NOT backed by a single table, so a `create` / `copy` must INSERT
 * BOTH:
 *
 *   1. `dtb_product`        — the catalog header (name / status /
 *      description_detail / search_word / note). AUTO_INCREMENT `id`
 *      is the internal surrogate; the BeMart slice never exposes it.
 *   2. `dtb_product_class`  — the DEFAULT class row (`product_code` +
 *      `price02` + `stock`, both `class_category_id*` axes NULL). This
 *      is the row {@see SqlProductQuery} resolves the natural key
 *      `productCode` against.
 *
 * The natural key is `productCode` (a string the caller supplies on
 * the Entity) — `dtb_product.id` is autoinc and internal, so there is
 * NO ProductIdGenerator pairing: `create` lets MySQL mint the surrogate
 * id and immediately uses it for the class-row INSERT.
 *
 * Both INSERTs run inside one atomic unit ({@see withAtomic}) — a
 * SAVEPOINT when the suite has already opened a transaction, a full
 * BEGIN/COMMIT otherwise. Same shape as {@see SqlCsvColumnConfigStorage}.
 *
 * `update` resolves the existing `dtb_product.id` via the class row's
 * `product_code`, then UPDATEs the header columns on `dtb_product` and
 * `price02` / `stock` on the default class row. `productCode` itself is
 * the selector and is never re-keyed (a rename is copy + delete).
 *
 * `delete` is a SOFT delete — it flips `dtb_product.product_status_id`
 * to STATUS_WITHDRAWN (=3), exactly as {@see FakeProductCommand::delete}
 * does. The row is never physically removed: order-history snapshots
 * reference frozen product-copy data and must survive. Idempotent — a
 * second call against an already-withdrawn product is a no-op.
 *
 * `bulkUpdateStatus` flips `dtb_product.product_status_id` for a list
 * of productCodes, returning the count of rows actually changed
 * (matching the Fake's "idempotent re-application is NOT counted"
 * contract).
 *
 * `product_status_id` is a NULLABLE FK to the empty
 * `mtb_product_status` master — any non-NULL value would raise FK 1452
 * against the structure-only dump, so a SQL test that exercises a
 * create / status flip MUST seed the master first via
 * {@see \MyVendor\BeMart\Be\Tests\Sql\SqlFixturesTrait::seedProductStatus}.
 *
 * DI is intentionally NOT wired in production (FakeProductCommand
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlProductCommand implements ProductCommandInterface
{
    private const SAVEPOINT_NAME = 'sql_product_command';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function create(ProductEntity $product): void
    {
        $this->withAtomic(function () use ($product): void {
            $productId = $this->insertProductHeader($product);
            $this->insertDefaultClass($productId, $product);
        });
    }

    #[Override]
    public function update(ProductEntity $product): void
    {
        $productId = $this->findProductId($product->productCode);
        if ($productId === null) {
            // The Final (AdminProductUpdated) has already verified
            // existence via ProductQuery::item before calling update;
            // a miss here would mean the row vanished between the read
            // and the write — silently no-op rather than raise, the
            // same shape FakeProductCommand's storage-put-overwrite has
            // for an unknown code.
            return;
        }

        $this->withAtomic(function () use ($productId, $product): void {
            $headerStmt = $this->pdo->prepare(
                'UPDATE dtb_product SET '
                . 'name = :name, '
                . 'product_status_id = :product_status_id, '
                . 'description_detail = :description, '
                . 'search_word = :search_word, '
                . 'note = :note, '
                . 'update_date = NOW() '
                . 'WHERE id = :id',
            );
            $headerStmt->execute([
                ':id' => $productId,
                ':name' => $product->productName,
                ':product_status_id' => $product->productStatus,
                ':description' => $product->description,
                ':search_word' => $product->searchWord,
                ':note' => $product->note,
            ]);

            $classStmt = $this->pdo->prepare(
                'UPDATE dtb_product_class SET '
                . 'price02 = :price02, '
                . 'stock = :stock, '
                . 'update_date = NOW() '
                . 'WHERE product_id = :product_id '
                . 'AND class_category_id1 IS NULL '
                . 'AND class_category_id2 IS NULL',
            );
            $classStmt->execute([
                ':product_id' => $productId,
                ':price02' => $product->price02,
                ':stock' => $product->stock,
            ]);
        });
    }

    #[Override]
    public function delete(string $productCode): void
    {
        $productId = $this->findProductId($productCode);
        if ($productId === null) {
            // Unknown code — silent no-op, matching FakeProductCommand.
            return;
        }

        // Soft delete: flip product_status_id to STATUS_WITHDRAWN.
        // The WHERE clause excludes already-withdrawn rows so a replay
        // is a genuine no-op (0 rows affected) — idempotent, the same
        // contract FakeProductCommand::delete has. Distinct named
        // placeholders per occurrence — emulated prepares reject a
        // re-used name (G-23 pre-flight rule).
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_product SET '
            . 'product_status_id = :set_status, '
            . 'update_date = NOW() '
            . 'WHERE id = :id '
            . 'AND (product_status_id IS NULL OR product_status_id <> :where_status)',
        );
        $stmt->execute([
            ':id' => $productId,
            ':set_status' => ProductEntity::STATUS_WITHDRAWN,
            ':where_status' => ProductEntity::STATUS_WITHDRAWN,
        ]);
    }

    #[Override]
    public function copy(string $sourceCode, string $newCode): ProductEntity
    {
        $source = (new SqlProductQuery($this->pdo))->item($sourceCode);
        if ($source === null) {
            throw new RuntimeException(sprintf('Product not found: %s', $sourceCode));
        }

        // The clone carries the source's price / stock / description /
        // etc., the productName prefixed with "(コピー) " and is created
        // in STATUS_VISIBLE regardless of the source's status — exactly
        // FakeProductCommand::copy's contract (admin convention: the
        // copy is a fresh draft).
        $copy = new ProductEntity(
            productCode: $newCode,
            productName: '(コピー) ' . $source->productName,
            price02: $source->price02,
            stock: $source->stock,
            productStatus: ProductEntity::STATUS_VISIBLE,
            description: $source->description,
            searchWord: $source->searchWord,
            note: $source->note,
        );

        $this->withAtomic(function () use ($copy): void {
            $productId = $this->insertProductHeader($copy);
            $this->insertDefaultClass($productId, $copy);
        });

        return $copy;
    }

    /**
     * @param list<string> $productCodes
     */
    #[Override]
    public function bulkUpdateStatus(array $productCodes, int $newStatus): int
    {
        $changed = 0;
        $this->withAtomic(function () use ($productCodes, $newStatus, &$changed): void {
            // Distinct named placeholders per occurrence — emulated
            // prepares reject a re-used name (G-23 pre-flight rule).
            $stmt = $this->pdo->prepare(
                'UPDATE dtb_product SET '
                . 'product_status_id = :set_status, '
                . 'update_date = NOW() '
                . 'WHERE id = :id '
                . 'AND (product_status_id IS NULL OR product_status_id <> :where_status)',
            );

            foreach ($productCodes as $code) {
                $productId = $this->findProductId($code);
                if ($productId === null) {
                    // Unknown code — silently skipped.
                    continue;
                }

                $stmt->execute([
                    ':id' => $productId,
                    ':set_status' => $newStatus,
                    ':where_status' => $newStatus,
                ]);
                // rowCount() is 0 when the status already equalled
                // $newStatus (the WHERE excludes a no-op flip) — that
                // matches the Fake's "idempotent re-application is NOT
                // counted" contract.
                $changed += $stmt->rowCount();
            }
        });

        return $changed;
    }

    /**
     * Resolve a customer-facing productCode to the internal
     * `dtb_product.id` via the default class row. Returns null when no
     * default class row carries that code (an honest miss — same shape
     * SqlProductQuery uses).
     */
    private function findProductId(string $productCode): int|null
    {
        $stmt = $this->pdo->prepare(
            'SELECT product_id FROM dtb_product_class '
            . 'WHERE product_code = :product_code '
            . 'AND class_category_id1 IS NULL '
            . 'AND class_category_id2 IS NULL '
            . 'ORDER BY id ASC LIMIT 1',
        );
        $stmt->execute([':product_code' => $productCode]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * INSERT the dtb_product header row. Returns the AUTO_INCREMENT id.
     *
     * creator_id is NULL (dtb_member is empty in the structure-only
     * dump — any non-NULL value would raise FK 1452, same shape as
     * SqlCategoryStorage / SqlDeliveryStorage). description_list is
     * NULL (the BeMart slice carries only the single `description`
     * field, mapped to description_detail; the list-page blurb has no
     * UI here). free_area is NULL (same — no admin UI in the slice).
     */
    private function insertProductHeader(ProductEntity $product): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO dtb_product '
            . '(creator_id, product_status_id, name, note, '
            . 'description_list, description_detail, search_word, '
            . 'free_area, create_date, update_date, discriminator_type) '
            . 'VALUES (NULL, :product_status_id, :name, :note, '
            . 'NULL, :description, :search_word, '
            . 'NULL, NOW(), NOW(), :discriminator)',
        );
        $stmt->execute([
            ':product_status_id' => $product->productStatus,
            ':name' => $product->productName,
            ':note' => $product->note,
            ':description' => $product->description,
            ':search_word' => $product->searchWord,
            ':discriminator' => 'product',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * INSERT the DEFAULT dtb_product_class row for a freshly-created
     * product header — both `class_category_id*` axes NULL, carrying
     * the `product_code` + `price02` + `stock` the BeMart slice reads
     * back. sale_type_id is NULL (the Product slice projects no
     * sale-type axis; the FK to the empty mtb_sale_type master would
     * otherwise raise 1452). stock_unlimited is derived: a null stock
     * is an unlimited-stock product (=1), a numeric stock is limited
     * (=0) — the same convention `var/fake/products.json` implies.
     */
    private function insertDefaultClass(int $productId, ProductEntity $product): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO dtb_product_class '
            . '(product_id, sale_type_id, class_category_id1, '
            . 'class_category_id2, creator_id, product_code, '
            . 'price02, stock, stock_unlimited, visible, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:product_id, NULL, NULL, '
            . 'NULL, NULL, :product_code, '
            . ':price02, :stock, :stock_unlimited, 1, '
            . 'NOW(), NOW(), :discriminator)',
        );
        $stmt->execute([
            ':product_id' => $productId,
            ':product_code' => $product->productCode,
            ':price02' => $product->price02,
            ':stock' => $product->stock,
            ':stock_unlimited' => $product->stock === null ? 1 : 0,
            ':discriminator' => 'productclass',
        ]);
    }

    /**
     * Run $work in either a fresh transaction (production) or a
     * SAVEPOINT (test, when the suite has already opened a tx).
     *
     * Throws propagate out — callers do not catch write failures, the
     * exception will bubble up through the Final.
     */
    private function withAtomic(callable $work): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->exec('SAVEPOINT ' . self::SAVEPOINT_NAME);
            try {
                $work();
                $this->pdo->exec('RELEASE SAVEPOINT ' . self::SAVEPOINT_NAME);
            } catch (Throwable $e) {
                $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . self::SAVEPOINT_NAME);

                throw $e;
            }

            return;
        }

        $this->pdo->beginTransaction();
        try {
            $work();
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
