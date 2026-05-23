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
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PasswordResetTokenStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AddressIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\BlockIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CategoryIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCategoryIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassNameIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\DeliveryIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\NewsIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PageIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodAdminIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\TagIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\TaxRuleIdGeneratorInterface;
use Override;
use Ray\AuraSqlModule\AuraSqlBaseModule;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryBaseModule;
use Ray\MediaQuery\MediaQueryDbModule;
use Ray\MediaQuery\Queries;

use function dirname;

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
        $root = dirname(__DIR__, 2);
        $queries = Queries::fromClasses(self::queryClasses());

        /**
         * @psalm-suppress InternalClass
         * @psalm-suppress InternalMethod
         */
        $this->install(new MediaQueryBaseModule($queries));
        /**
         * @psalm-suppress InternalClass
         * @psalm-suppress InternalMethod
         */
        $this->install(new MediaQueryDbModule(new DbQueryConfig($root . '/sql/media-query')));

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
            EmailUniquenessCheckerInterface::class,
            FavoriteStorageInterface::class,
            LayoutStorageInterface::class,
            LoginHistoryStorageInterface::class,
            MailTemplateStorageInterface::class,
            NewsStorageInterface::class,
            OrderCommandInterface::class,
            OrderQueryInterface::class,
            PageStorageInterface::class,
            PasswordResetTokenStorageInterface::class,
            PaymentMethodAdminStorageInterface::class,
            PluginStorageInterface::class,
            ProductClassQueryInterface::class,
            ProductCommandInterface::class,
            ProductQueryInterface::class,
            ShippingAddressStorageInterface::class,
            TagStorageInterface::class,
            TaxRuleStorageInterface::class,
            TemplateStorageInterface::class,
            TradeLawStorageInterface::class,
            AddressIdGeneratorInterface::class,
            AdminIdGeneratorInterface::class,
            BlockIdGeneratorInterface::class,
            CategoryIdGeneratorInterface::class,
            ClassCategoryIdGeneratorInterface::class,
            ClassNameIdGeneratorInterface::class,
            CustomerIdGeneratorInterface::class,
            DeliveryIdGeneratorInterface::class,
            NewsIdGeneratorInterface::class,
            PageIdGeneratorInterface::class,
            PaymentMethodAdminIdGeneratorInterface::class,
            TagIdGeneratorInterface::class,
            TaxRuleIdGeneratorInterface::class,
        ];
    }
}
