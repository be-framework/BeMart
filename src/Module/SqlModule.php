<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

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
use MyVendor\BeMart\Be\Reason\Query\SqlAddressStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlAdminCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlAdminQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlBaseInfoStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlBlockStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlCartCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlCartQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlCategoryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlClassCategoryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlClassNameStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlCsvColumnConfigStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlDeliveryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlEmailUniquenessChecker;
use MyVendor\BeMart\Be\Reason\Query\SqlFavoriteStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlLayoutStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlLoginHistoryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlMailTemplateStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlNewsStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlPageStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlPasswordResetTokenStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlPaymentMethodAdminStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlPluginStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlProductClassQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlProductCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlProductQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlShippingAddressStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTagStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTaxRuleStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTemplateStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTradeLawStorage;
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
use MyVendor\BeMart\Be\Reason\Service\SqlAddressIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlAdminIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlBlockIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlClassCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlClassNameIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlCustomerIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlDeliveryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlNewsIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlPageIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlPaymentMethodAdminIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlTagIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlTaxRuleIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\TagIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\TaxRuleIdGeneratorInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Production SQL cutover module — Phase 2c.
 *
 * AppModule (the dev/test default) binds every storage-interface Reason to
 * an in-memory `Fake*` implementation, which keeps the ~1000 Domain /
 * Resource tests fast and DB-free. {@see ProdModule} installs AppModule
 * and then `override(...)`s this module so the `prod` context runs the
 * SQL-backed Reasons instead.
 *
 * This module is the production-grade extraction of the anonymous override
 * module inside {@see \MyVendor\BeMart\Tests\Resource\Sql\AbstractResourceSqlTestCase}
 * — the binding list there is the source of truth and was proved green by
 * the `bemart-sql` suite. The set is mirrored exactly so prod wiring ==
 * the wiring that suite exercises:
 *
 *   - all 34 storage interfaces  → `Sql*` impl
 *   - the 13 `*IdGeneratorInterface`s → `Sql*IdGenerator` (the Fake
 *     generators emit hex / prefixed handles; the SQL impls require the
 *     numeric autoinc form that matches the real `dtb_*` int PKs)
 *   - MediaQuery runtime is installed once and all query bodies are
 *     resolved from `sql/media-query`.
 *
 * `CustomerIdGeneratorInterface` is bound to `SqlCustomerIdGenerator`:
 * production customer ids must be the numeric autoinc form.
 */
final class SqlModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new MediaQueryRuntimeModule());

        // Storage interfaces — Fake -> Sql. Linked bindings; Ray.Di
        // constructs each Sql class on first request.
        $this->bind(CustomerCommandInterface::class)
            ->to(SqlCustomerCommand::class)
            ->in(Scope::SINGLETON);
        $this->bind(OrderCommandInterface::class)
            ->to(SqlOrderCommand::class)
            ->in(Scope::SINGLETON);
        $this->bind(FavoriteStorageInterface::class)
            ->to(SqlFavoriteStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(CartCommandInterface::class)
            ->to(SqlCartCommand::class)
            ->in(Scope::SINGLETON);
        $this->bind(AddressStorageInterface::class)
            ->to(SqlAddressStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(TagStorageInterface::class)
            ->to(SqlTagStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(BaseInfoStorageInterface::class)
            ->to(SqlBaseInfoStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(TaxRuleStorageInterface::class)
            ->to(SqlTaxRuleStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(NewsStorageInterface::class)
            ->to(SqlNewsStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(PageStorageInterface::class)
            ->to(SqlPageStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(BlockStorageInterface::class)
            ->to(SqlBlockStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(CategoryStorageInterface::class)
            ->to(SqlCategoryStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(ClassNameStorageInterface::class)
            ->to(SqlClassNameStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(ClassCategoryStorageInterface::class)
            ->to(SqlClassCategoryStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(AdminCommandInterface::class)
            ->to(SqlAdminCommand::class)
            ->in(Scope::SINGLETON);
        $this->bind(LayoutStorageInterface::class)
            ->to(SqlLayoutStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(LoginHistoryStorageInterface::class)
            ->to(SqlLoginHistoryStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(PaymentMethodAdminStorageInterface::class)
            ->to(SqlPaymentMethodAdminStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(TradeLawStorageInterface::class)
            ->to(SqlTradeLawStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(ShippingAddressStorageInterface::class)
            ->to(SqlShippingAddressStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(ProductCommandInterface::class)
            ->to(SqlProductCommand::class)
            ->in(Scope::SINGLETON);
        $this->bind(CsvColumnConfigStorageInterface::class)
            ->to(SqlCsvColumnConfigStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(PluginStorageInterface::class)
            ->to(SqlPluginStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(PasswordResetTokenStorageInterface::class)
            ->to(SqlPasswordResetTokenStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(DeliveryStorageInterface::class)
            ->to(SqlDeliveryStorage::class)
            ->in(Scope::SINGLETON);
        $this->bind(MailTemplateStorageInterface::class)
            ->to(SqlMailTemplateStorage::class)
            ->in(Scope::SINGLETON);

        // Id generators — Fake -> Sql. The Fake generators emit hex /
        // prefixed handles; production must use the autoinc-based ids
        // that match the real dtb_* int PKs.
        $this->bind(CustomerIdGeneratorInterface::class)
            ->to(SqlCustomerIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(AddressIdGeneratorInterface::class)
            ->to(SqlAddressIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(TagIdGeneratorInterface::class)
            ->to(SqlTagIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(TaxRuleIdGeneratorInterface::class)
            ->to(SqlTaxRuleIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(NewsIdGeneratorInterface::class)
            ->to(SqlNewsIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(PageIdGeneratorInterface::class)
            ->to(SqlPageIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(BlockIdGeneratorInterface::class)
            ->to(SqlBlockIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(CategoryIdGeneratorInterface::class)
            ->to(SqlCategoryIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(ClassNameIdGeneratorInterface::class)
            ->to(SqlClassNameIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(ClassCategoryIdGeneratorInterface::class)
            ->to(SqlClassCategoryIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(AdminIdGeneratorInterface::class)
            ->to(SqlAdminIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(PaymentMethodAdminIdGeneratorInterface::class)
            ->to(SqlPaymentMethodAdminIdGenerator::class)
            ->in(Scope::SINGLETON);
        $this->bind(DeliveryIdGeneratorInterface::class)
            ->to(SqlDeliveryIdGenerator::class)
            ->in(Scope::SINGLETON);
    }
}
