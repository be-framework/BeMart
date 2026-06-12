<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Reason\Fake\Query\SessionCartStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\SessionFavoriteStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\SessionOrderStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\SessionOrderHistoryQuery;
use MyVendor\BeMart\Be\Reason\Fake\Query\SessionOrderItemQuery;
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
use Ray\Di\Scope;

/** Installs session-backed mutable carts for browser Fake contexts only. */
final class HtmlFakeCartModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(CustomerSession::class)->to(HtmlSessionAdapter::class);
        $this->bind(SessionCartStorage::class)->in(Scope::SINGLETON);
        $this->bind(CartQueryInterface::class)->to(SessionCartStorage::class)->in(Scope::SINGLETON);
        $this->bind(CartCommandInterface::class)->to(SessionCartStorage::class)->in(Scope::SINGLETON);
        $this->bind(SessionOrderStorage::class)->in(Scope::SINGLETON);
        $this->bind(SessionFavoriteStorage::class)->in(Scope::SINGLETON);
        $this->bind(FavoriteStorageInterface::class)->to(SessionFavoriteStorage::class)->in(Scope::SINGLETON);
        $this->bind(OrderQueryInterface::class)->to(SessionOrderStorage::class)->in(Scope::SINGLETON);
        $this->bind(OrderCommandInterface::class)->to(SessionOrderStorage::class)->in(Scope::SINGLETON);
        $this->bind(SessionOrderHistoryQuery::class)->in(Scope::SINGLETON);
        $this->bind(OrderHistoryQueryInterface::class)->to(SessionOrderHistoryQuery::class)->in(Scope::SINGLETON);
        $this->bind(SessionOrderItemQuery::class)->in(Scope::SINGLETON);
        $this->bind(OrderItemQueryInterface::class)->to(SessionOrderItemQuery::class)->in(Scope::SINGLETON);
    }
}
