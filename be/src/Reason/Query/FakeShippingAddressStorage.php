<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use Override;

use function array_values;

/**
 * In-memory fake for {@see ShippingAddressStorageInterface}.
 *
 * Singleton-bound so a request's POST/PUT is visible to a subsequent
 * GET inside the same test (same convention as
 * {@see FakeFinalizedOrderStorage} et al.).
 */
final class FakeShippingAddressStorage implements ShippingAddressStorageInterface
{
    /** @var array<string, ShippingAddressEntity> */
    private array $addresses = [];

    /**
     * Tracking number per orderNo — dtb_shipping's `tracking_number`
     * column, kept separate from the address-field projection. A row
     * with no entry has no tracking number set yet.
     *
     * @var array<string, string>
     */
    private array $trackingNumbers = [];

    #[Override]
    public function getByOrderNo(string $orderNo): ShippingAddressEntity|null
    {
        return $this->addresses[$orderNo] ?? null;
    }

    #[Override]
    public function put(ShippingAddressEntity $address): void
    {
        $this->addresses[$address->orderNo] = $address;
    }

    /** @return list<ShippingAddressEntity> */
    #[Override]
    public function listAll(): array
    {
        return array_values($this->addresses);
    }

    #[Override]
    public function updateTrackingNumber(string $orderNo, string $trackingNumber): void
    {
        // The order's existence is gated by the Final via OrderQuery;
        // here we just record the tracking number against the orderNo.
        $this->trackingNumbers[$orderNo] = $trackingNumber;
    }

    #[Override]
    public function trackingNumberByOrderNo(string $orderNo): string|null
    {
        return $this->trackingNumbers[$orderNo] ?? null;
    }
}
