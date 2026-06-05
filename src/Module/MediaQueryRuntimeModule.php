<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Aura\Sql\ExtendedPdoInterface;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\BaseInfoStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderMailHistoryCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderHistoryQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PasswordResetTokenStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductStatusCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingTrackingQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TwoFactorAuthStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\AddressIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\BlockIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CategoryIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\DeliveryIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PageIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\TagIdQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleIdQueryInterface;
use Override;
use Ray\AuraSqlModule\AuraSqlBaseModule;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;

final class MediaQueryRuntimeModule extends AbstractModule
{
    public function __construct(
        private readonly ExtendedPdoInterface|null $connection = null,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->install(new MediaQueryProxyModule());

        if ($this->connection !== null) {
            $this->bind(ExtendedPdoInterface::class)->toInstance($this->connection);
            $this->install(new AuraSqlBaseModule('mysql:'));

            return;
        }

        $database = DatabaseUrl::fromEnvironment();
        /** @psalm-suppress InvalidArgument AuraSqlModule accepts driver option arrays keyed by driver constants. */
        $this->install(new AuraSqlModule(
            $database->dsn,
            $database->user,
            $database->pass,
            options: $database->options,
        ));
    }

    /** @return list<class-string> */
    public static function queryClasses(): array
    {
        return [
            AddressStorageInterface::class,
            AdminCommandInterface::class,
            AdminQueryInterface::class,
            BaseInfoStorageInterface::class,
            BlockStorageInterface::class,
            CartCommandInterface::class,
            CartQueryInterface::class,
            CategoryStorageInterface::class,
            ClassCategoryStorageInterface::class,
            ClassNameStorageInterface::class,
            CsvColumnConfigStorageInterface::class,
            CustomerCommandInterface::class,
            CustomerQueryInterface::class,
            DeliveryStorageInterface::class,
            EmailUniquenessQueryInterface::class,
            FavoriteStorageInterface::class,
            LayoutStorageInterface::class,
            LoginHistoryStorageInterface::class,
            MailTemplateStorageInterface::class,
            NewsStorageInterface::class,
            OrderCommandInterface::class,
            OrderMailHistoryCommandInterface::class,
            OrderHistoryQueryInterface::class,
            OrderItemCommandInterface::class,
            OrderItemQueryInterface::class,
            OrderQueryInterface::class,
            PageStorageInterface::class,
            PasswordResetTokenStorageInterface::class,
            PaymentMethodAdminStorageInterface::class,
            PluginStorageInterface::class,
            ProductClassQueryInterface::class,
            ProductCommandInterface::class,
            ProductStatusCommandInterface::class,
            ProductQueryInterface::class,
            ShippingAddressStorageInterface::class,
            ShippingTrackingQueryInterface::class,
            TagStorageInterface::class,
            TaxRuleStorageInterface::class,
            TemplateStorageInterface::class,
            TradeLawStorageInterface::class,
            TwoFactorAuthStorageInterface::class,
            AddressIdQueryInterface::class,
            AdminIdQueryInterface::class,
            BlockIdQueryInterface::class,
            CategoryIdQueryInterface::class,
            ClassCategoryIdQueryInterface::class,
            ClassNameIdQueryInterface::class,
            CustomerIdQueryInterface::class,
            DeliveryIdQueryInterface::class,
            NewsIdQueryInterface::class,
            PageIdQueryInterface::class,
            PaymentMethodAdminIdQueryInterface::class,
            TagIdQueryInterface::class,
            TaxRuleIdQueryInterface::class,
        ];
    }
}
