<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Aura\Sql\ExtendedPdoInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Query\InternalDbQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\TemplateStorageInterface;
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
        $queries = Queries::fromClasses([
            CustomerQueryInterface::class,
            EmailUniquenessCheckerInterface::class,
            AdminQueryInterface::class,
            ProductClassQueryInterface::class,
            ProductQueryInterface::class,
            TemplateStorageInterface::class,
            CartQueryInterface::class,
            OrderQueryInterface::class,
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
            InternalDbQueryInterface::class,
        ]);

        /**
         * MediaQuerySqlModule still scans a directory; the direct-proxy
         * cutover deliberately follows Ray.MediaQuery's documented advanced
         * pattern and registers the existing interfaces explicitly.
         *
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
}
