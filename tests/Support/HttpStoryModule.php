<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderHistoryQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\AbstractModule;

/**
 * Gives the Fake HAL app under `php -S` the bindings of the in-process stories: the FakeQuery fixtures in place of the
 * session-backed storages HtmlFakeCartModule binds, and the given customer (a guest when null).
 */
final class HttpStoryModule extends AbstractModule
{
    /** @param non-empty-string|null $customerId */
    public function __construct(
        private readonly string|null $customerId,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        // A null binding is answered by the FakeQuery interceptor the Fake context already installs
        $this->bind(CartQueryInterface::class)->toNull();
        $this->bind(CartCommandInterface::class)->toNull();
        $this->bind(FavoriteStorageInterface::class)->toNull();
        $this->bind(OrderQueryInterface::class)->toNull();
        $this->bind(OrderCommandInterface::class)->toNull();
        $this->bind(OrderHistoryQueryInterface::class)->toNull();
        $this->bind(OrderItemQueryInterface::class)->toNull();
        $this->bind(CustomerSession::class)->toInstance(new FakeSession($this->customerId));
    }
}
