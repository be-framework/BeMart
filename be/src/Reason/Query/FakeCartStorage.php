<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

/**
 * In-memory Cart store shared by FakeCartQuery + FakeCartCommand.
 *
 * Bound as Singleton so a single request's Query reads what its Command
 * just wrote. Phase 2 will replace this with dtb_cart + dtb_cart_item DAOs.
 */
final class FakeCartStorage
{
    /** @var array<string, CartEntity>|null */
    private array|null $carts = null;

    public function get(string $cartKey): CartEntity|null
    {
        return $this->load()[$cartKey] ?? null;
    }

    public function put(CartEntity $cart): void
    {
        $this->load();
        $this->carts[$cart->cartKey] = $cart;
    }

    public function removeByPreOrderId(string $preOrderId): void
    {
        $this->load();
        foreach ($this->carts as $cartKey => $cart) {
            if ($cart->preOrderId === $preOrderId) {
                unset($this->carts[$cartKey]);
            }
        }
    }

    /**
     * Drop every cart whose key begins with `{sessionPrefix}_`. Used by
     * doWithdrawCustomer to clear all session-scoped carts at once
     * (mirror of getBySessionPrefix's read side).
     */
    public function removeBySessionPrefix(string $sessionPrefix): void
    {
        $rows = $this->load();
        $prefix = $sessionPrefix . '_';
        foreach ($rows as $cartKey => $_cart) {
            if (str_starts_with($cartKey, $prefix)) {
                unset($rows[$cartKey]);
            }
        }

        $this->carts = $rows;
    }

    public function getByPreOrderId(string $preOrderId): CartEntity|null
    {
        foreach ($this->load() as $cart) {
            if ($cart->preOrderId === $preOrderId) {
                return $cart;
            }
        }

        return null;
    }

    /**
     * All carts whose cartKey begins with the supplied sessionPrefix.
     * cartKey = `{sessionPrefix}_{saleTypeId}` so the prefix scopes a
     * shopping session into N carts (one per sale type). Pilot 9 (goCart).
     *
     * @return list<CartEntity>
     */
    public function getBySessionPrefix(string $sessionPrefix): array
    {
        $prefix = $sessionPrefix . '_';
        $out = [];
        foreach ($this->load() as $cartKey => $cart) {
            if (str_starts_with($cartKey, $prefix)) {
                $out[] = $cart;
            }
        }

        return $out;
    }

    /** @return array<string, CartEntity> */
    private function load(): array
    {
        if ($this->carts !== null) {
            return $this->carts;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/carts.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var array<string, array{cartKey: string, saleTypeId: int, saleTypeName: string, items: list<array{productCode: string, quantity: int, price: int, productClassId?: int, productId?: int, productName?: string, mainImage?: string|null, classCategoryName1?: string|null, className1?: string|null, classCategoryName2?: string|null, className2?: string|null}>, totalPrice: int, deliveryFeeTotal: int, preOrderId: string}|string> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON object: %s', $path));
        }

        $carts = [];
        foreach ($rows as $key => $row) {
            if ($key === '$comment' || ! is_array($row)) {
                continue;
            }

            $items = [];
            foreach ($row['items'] as $item) {
                // Display fields are optional in the fixture: a cart
                // item only needs dtb_cart_item's real columns
                // (productCode/quantity/price); FakeCartQuery re-derives
                // the rest on read, mirroring SqlCartQuery's JOIN.
                $items[] = new CartItemEntity(
                    productCode: $item['productCode'],
                    quantity: $item['quantity'],
                    price: $item['price'],
                    productClassId: $item['productClassId'] ?? 0,
                    productId: $item['productId'] ?? 0,
                    productName: $item['productName'] ?? '',
                    mainImage: $item['mainImage'] ?? null,
                    classCategoryName1: $item['classCategoryName1'] ?? null,
                    className1: $item['className1'] ?? null,
                    classCategoryName2: $item['classCategoryName2'] ?? null,
                    className2: $item['className2'] ?? null,
                );
            }

            $carts[$row['cartKey']] = new CartEntity(
                cartKey: $row['cartKey'],
                saleTypeId: $row['saleTypeId'],
                saleTypeName: $row['saleTypeName'],
                items: $items,
                totalPrice: $row['totalPrice'],
                deliveryFeeTotal: $row['deliveryFeeTotal'],
                preOrderId: $row['preOrderId'],
            );
        }

        return $this->carts = $carts;
    }
}
