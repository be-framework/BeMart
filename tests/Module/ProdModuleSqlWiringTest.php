<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerQuery;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\SqlCustomerIdGenerator;
use MyVendor\BeMart\Module\AppModule;
use MyVendor\BeMart\Module\ProdModule;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function getenv;

/**
 * Phase 2c — production cutover smoke test.
 *
 * Proves that the `prod` context's override chain actually swaps the
 * in-memory Fake Reasons for the SQL-backed implementations:
 *
 *   ProdModule
 *     install(AppModule)              ← Fake* bindings
 *     override(ProdLoggingOverrideModule)
 *     override(ProdSessionOverrideModule)
 *     override(ProdCsrfOverrideModule)
 *     override(SqlModule)             ← Fake* -> Sql* + PdoProvider
 *
 * The check builds the prod injector exactly as bin/app.php does
 * (APP_CONTEXT=prod -> ProdModule) and asserts that resolving a sample
 * storage interface yields a `Sql*` impl, not a `Fake*` one. AppModule's
 * Fake binding is confirmed unchanged as a negative control so the
 * assertion can't go vacuous.
 *
 * SqlModule's PdoProvider reads DATABASE_URL at runtime. phpunit.xml
 * points DATABASE_URL at `eccubedb_test` (which the bemart-sql bootstrap
 * has created with the EC-CUBE schema). If DATABASE_URL is unset the
 * test skips, mirroring the bemart-sql suite's behaviour in DB-less
 * environments.
 */
final class ProdModuleSqlWiringTest extends TestCase
{
    protected function setUp(): void
    {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl === false || $databaseUrl === '') {
            $this->markTestSkipped('DATABASE_URL not set — prod SQL wiring requires a DB.');
        }
    }

    public function testProdContextResolvesSqlCustomerQueryNotFake(): void
    {
        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        // Building the Resource client proves the whole prod injector
        // (App graph + every overridden binding) wires without error.
        $resource = $injector->getInstance(ResourceInterface::class);
        $this->assertInstanceOf(ResourceInterface::class, $resource);

        // The cutover assertion: a storage interface that AppModule binds
        // to a Fake must resolve to its Sql* impl under prod.
        $customerQuery = $injector->getInstance(CustomerQueryInterface::class);
        $this->assertInstanceOf(
            SqlCustomerQuery::class,
            $customerQuery,
            'ProdModule must override CustomerQueryInterface Fake -> SqlCustomerQuery.',
        );

        // IdGenerators are also part of the cutover — production customer
        // ids must be the numeric autoinc form (SqlCustomerIdGenerator),
        // not the Fake hex.
        $customerIdGenerator = $injector->getInstance(CustomerIdGeneratorInterface::class);
        $this->assertInstanceOf(
            SqlCustomerIdGenerator::class,
            $customerIdGenerator,
            'ProdModule must override CustomerIdGeneratorInterface Fake -> SqlCustomerIdGenerator.',
        );

        // PdoProvider builds a real connection from DATABASE_URL.
        $pdo = $injector->getInstance(PDO::class);
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame(
            PDO::ERRMODE_EXCEPTION,
            $pdo->getAttribute(PDO::ATTR_ERRMODE),
            'PdoProvider must set ATTR_ERRMODE => ERRMODE_EXCEPTION.',
        );
    }

    public function testProdPdoIsSingletonAcrossResolutions(): void
    {
        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        // SqlModule binds PDO as a Singleton — one connection per request
        // lifecycle, shared by every Sql* Reason.
        $this->assertSame(
            $injector->getInstance(PDO::class),
            $injector->getInstance(PDO::class),
            'PDO must be a Singleton under the prod context.',
        );
    }

    public function testDevContextStillBindsFakeCustomerQuery(): void
    {
        // Negative control: AppModule (test/dev default) is untouched, so
        // it must still resolve the Fake — otherwise the cutover assertion
        // above is vacuous.
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );

        $customerQuery = $injector->getInstance(CustomerQueryInterface::class);
        $this->assertNotInstanceOf(
            SqlCustomerQuery::class,
            $customerQuery,
            'AppModule must keep the Fake binding — test/dev contexts stay DB-free.',
        );
    }
}
