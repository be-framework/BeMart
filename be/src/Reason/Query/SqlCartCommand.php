<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use Override;
use PDO;
use RuntimeException;
use Throwable;

use function sprintf;

/**
 * Real PDO-backed Cart command — Phase 2a Step 4.
 *
 * Mirrors {@see FakeCartCommand} against the live EC-CUBE 4.3 schema.
 *
 * Semantics per method:
 *
 *  - `save()` is an upsert by `cart_key`. The schema has no UNIQUE
 *    index on `cart_key` (only `pre_order_id` carries UNIQUE), so the
 *    cleanest "replace the cart wholesale" path is DELETE-by-cart_key
 *    (which cascades to dtb_cart_item via `ON DELETE CASCADE`) and
 *    then INSERT fresh. We wrap that in a transaction to keep the
 *    read-side query from seeing a half-replaced state.
 *
 *  - `clearByPreOrderId()` is a single DELETE on the UNIQUE `pre_order_id`
 *    column. Items cascade. Missing row is a no-op (the checkout
 *    already succeeded — a stale fixture must not break the Final).
 *
 *  - `clearBySessionPrefix()` deletes every cart whose `cart_key`
 *    starts with `{prefix}_`. The LIKE pattern escapes `_` and `%`
 *    inside the user-influenced prefix so a session id of e.g.
 *    `evil_%` can't widen the scan.
 *
 * Transaction strategy — savepoint when nested
 * --------------------------------------------
 * The test base class wraps every test in a transaction it rolls back
 * during tearDown. MySQL/MariaDB do not truly nest transactions: a
 * second `BEGIN` silently commits the outer one. To keep the
 * test-time isolation contract intact while still giving production
 * callers an atomic save, we use a SAVEPOINT when `inTransaction()`
 * is already true, and a full BEGIN/COMMIT otherwise. The choice is
 * encapsulated in {@see withAtomic()}.
 *
 * `productCode` → `product_class_id` resolution
 * ---------------------------------------------
 * `CartItemEntity::productCode` is the BeMart-side handle held on
 * `dtb_product_class.product_code`. `dtb_cart_item.product_class_id`
 * stores the surrogate id of the default product_class. We resolve
 * the code → id via JOIN dtb_product_class → dtb_product, filtering
 * to the default class (`class_category_id1` IS NULL AND
 * `class_category_id2` IS NULL — same convention used by
 * {@see SqlFavoriteStorage}). Unknown codes throw a RuntimeException
 * — Cart::save() is the wrong moment to silently drop an item.
 *
 * DI is intentionally NOT wired in Phase 2a; FakeCartCommand remains
 * the bound implementation.
 */
final class SqlCartCommand implements CartCommandInterface
{
    private const SAVEPOINT_NAME = 'sql_cart_command_save';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function save(CartEntity $cart): void
    {
        // Pre-resolve all product_class ids OUTSIDE the transaction so
        // an unknown code aborts before we touch any rows. Throws on
        // first miss with a clear message naming the offending code.
        $resolved = [];
        foreach ($cart->items as $item) {
            $resolved[] = [
                'productClassId' => $this->resolveProductClassId($item->productCode),
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        }

        $this->withAtomic(function () use ($cart, $resolved): void {
            // DELETE first — cascades to dtb_cart_item rows. Then
            // INSERT fresh header + items. Using DELETE+INSERT rather
            // than UPSERT avoids juggling 12 columns of conflict
            // updates and keeps the items collection authoritative
            // (no stale orphan items).
            $delete = $this->pdo->prepare('DELETE FROM dtb_cart WHERE cart_key = :cart_key');
            $delete->execute([':cart_key' => $cart->cartKey]);

            $insertCart = $this->pdo->prepare(
                'INSERT INTO dtb_cart '
                . '(cart_key, pre_order_id, total_price, delivery_fee_total, '
                . 'create_date, update_date, add_point, use_point, discriminator_type) '
                . 'VALUES (:cart_key, :pre_order_id, :total_price, :delivery_fee_total, '
                . 'NOW(), NOW(), 0, 0, :discriminator)',
            );
            $insertCart->execute([
                ':cart_key' => $cart->cartKey,
                ':pre_order_id' => $cart->preOrderId === '' ? null : $cart->preOrderId,
                ':total_price' => $cart->totalPrice,
                ':delivery_fee_total' => $cart->deliveryFeeTotal,
                ':discriminator' => 'cart',
            ]);
            $cartId = (int) $this->pdo->lastInsertId();

            if ($resolved === []) {
                return;
            }

            $insertItem = $this->pdo->prepare(
                'INSERT INTO dtb_cart_item '
                . '(product_class_id, cart_id, price, quantity, discriminator_type) '
                . 'VALUES (:product_class_id, :cart_id, :price, :quantity, :discriminator)',
            );
            foreach ($resolved as $item) {
                $insertItem->execute([
                    ':product_class_id' => $item['productClassId'],
                    ':cart_id' => $cartId,
                    ':price' => $item['price'],
                    ':quantity' => $item['quantity'],
                    ':discriminator' => 'cartitem',
                ]);
            }
        });
    }

    #[Override]
    public function clearByPreOrderId(string $preOrderId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM dtb_cart WHERE pre_order_id = :pre_order_id');
        $stmt->execute([':pre_order_id' => $preOrderId]);
    }

    #[Override]
    public function clearBySessionPrefix(string $sessionPrefix): void
    {
        $pattern = $this->escapeLike($sessionPrefix) . '\\_%';
        $stmt = $this->pdo->prepare('DELETE FROM dtb_cart WHERE cart_key LIKE :pattern');
        $stmt->execute([':pattern' => $pattern]);
    }

    /**
     * Run $work in either a fresh transaction (production) or a
     * SAVEPOINT (test, when the suite has already opened a tx).
     *
     * Throws propagate out — callers do not catch save failures, the
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

    /**
     * Resolve a BeMart `productCode` (held on `dtb_product_class`) to
     * the surrogate `dtb_product_class.id` used by the cart_item FK.
     * Throws when the code can't be resolved — Cart::save() must not
     * silently drop items.
     */
    private function resolveProductClassId(string $productCode): int
    {
        $sql = 'SELECT pc.id FROM dtb_product_class pc '
            . 'INNER JOIN dtb_product p ON p.id = pc.product_id '
            . 'WHERE pc.product_code = :product_code '
            . 'AND pc.class_category_id1 IS NULL '
            . 'AND pc.class_category_id2 IS NULL '
            . 'LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_code' => $productCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException(sprintf(
                'SqlCartCommand: unknown productCode "%s" — cannot resolve '
                . 'to dtb_product_class.id (no default class row).',
                $productCode,
            ));
        }

        return (int) $row['id'];
    }

    /**
     * Escape `%` and `_` so substring keywords can't smuggle wildcards.
     * Uses `\` as the escape character (MySQL default for LIKE).
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
