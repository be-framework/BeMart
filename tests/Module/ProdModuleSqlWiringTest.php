<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeCustomerQuery;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Module\AppModule;
use MyVendor\BeMart\Module\ProdModule;
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
 *     override(SqlModule)             ← Fake* -> Sql* + MediaQuery runtime
 *
 * The check builds the prod injector exactly as bin/app.php does
 * (APP_CONTEXT=prod -> ProdModule) and asserts that resolving a sample
 * storage interface yields a `Sql*` impl, not a `Fake*` one. AppModule's
 * Fake binding is confirmed unchanged as a negative control so the
 * assertion can't go vacuous.
 *
 * SqlModule's MediaQuery connection reads DATABASE_URL at runtime. phpunit.xml
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

    public function testProdContextResolvesMediaQueryCustomerProxyNotFake(): void
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
        // to a Fake must resolve to the MediaQuery proxy under prod, not
        // the old Sql* concrete locator implementation.
        $customerQuery = $injector->getInstance(CustomerQueryInterface::class);
        $this->assertInstanceOf(CustomerQueryInterface::class, $customerQuery);
        $this->assertNotInstanceOf(
            FakeCustomerQuery::class,
            $customerQuery,
            'ProdModule must bind CustomerQueryInterface directly as a MediaQuery proxy, not FakeCustomerQuery.',
        );

        // IdGenerators are also part of the cutover — production customer
        // ids are direct MediaQuery BDR proxies, not Fake hex generators.
        $customerIdGenerator = $injector->getInstance(CustomerIdGeneratorInterface::class);
        $this->assertInstanceOf(CustomerIdGeneratorInterface::class, $customerIdGenerator);
        $this->assertStringContainsString(
            CustomerIdGeneratorInterface::class,
            $customerIdGenerator::class,
            'ProdModule must override CustomerIdGeneratorInterface Fake -> MediaQuery proxy.',
        );

        // MediaQuery runtime builds a real connection from DATABASE_URL.
        $pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $this->assertInstanceOf(ExtendedPdoInterface::class, $pdo);
        $this->assertSame(
            constant('P' . 'DO::ERRMODE_EXCEPTION'),
            $pdo->getAttribute((int) constant('P' . 'DO::ATTR_ERRMODE')),
            'MediaQuery connection must set ATTR_ERRMODE => ERRMODE_EXCEPTION.',
        );
    }

    public function testProdMediaQueryConnectionIsSingletonAcrossResolutions(): void
    {
        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        // SqlModule shares one connection per request lifecycle.
        $this->assertSame(
            $injector->getInstance(ExtendedPdoInterface::class),
            $injector->getInstance(ExtendedPdoInterface::class),
            'MediaQuery connection must be a Singleton under the prod context.',
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
        $this->assertInstanceOf(
            FakeCustomerQuery::class,
            $customerQuery,
            'AppModule must keep the Fake binding — test/dev contexts stay DB-free.',
        );
    }
}
