<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerIdQueryInterface;
use MyVendor\BeMart\Module\ProdModule;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function getenv;

/**
 * Phase 2c — production cutover smoke test.
 *
 * Proves that prod uses the real SQL-backed MediaQuery runtime, while
 * dev/test use the same public #[DbQuery] proxies over Ray.FakeQuery fixture JSONs.
 *
 *   ProdModule = AppModule + session/csrf adapters + SqlModule
 *   TestModule = AppModule + dev logging + FakeModule
 *
 * The important invariant is that public query interfaces are direct
 * MediaQuery proxies in both contexts; only the interceptor backend changes.
 *
 * SqlModule's MediaQuery connection reads DATABASE_URL at runtime. phpunit.xml
 * points DATABASE_URL at `eccubedb_test` (which the bemart-sql bootstrap
 * has created with the EC-CUBE schema). If DATABASE_URL is unset the
 * test skips, mirroring the bemart-sql suite's behaviour in DB-less
 * environments.
 */
final class ProdModuleSqlWiringTest extends TestCase
{
    public function testProdContextResolvesMediaQueryCustomerProxyNotFake(): void
    {
        $this->skipWithoutDatabaseUrl();

        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        // Building the Resource client proves the whole prod injector
        // (App graph + every overridden binding) wires without error.
        $resource = $injector->getInstance(ResourceInterface::class);
        $this->assertInstanceOf(ResourceInterface::class, $resource);

        // The cutover assertion: public query interfaces resolve to
        // MediaQuery direct proxies, not Fake* or Sql* concrete classes.
        $customerQuery = $injector->getInstance(CustomerQueryInterface::class);
        $this->assertInstanceOf(CustomerQueryInterface::class, $customerQuery);
        $this->assertStringContainsString(
            CustomerQueryInterface::class,
            $customerQuery::class,
            'ProdModule must bind CustomerQueryInterface directly as a MediaQuery proxy.',
        );

        // IdQueries are also part of the cutover — production customer
        // ids are direct MediaQuery BDR proxies, not FakeQuery hex fixtures.
        $customerIdProvider = $injector->getInstance(CustomerIdQueryInterface::class);
        $this->assertInstanceOf(CustomerIdQueryInterface::class, $customerIdProvider);
        $this->assertStringContainsString(
            CustomerIdQueryInterface::class,
            $customerIdProvider::class,
            'ProdModule must override CustomerIdQueryInterface Fake -> MediaQuery proxy.',
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
        $this->skipWithoutDatabaseUrl();

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

    public function testDevContextUsesMediaQueryProxyOverRayFakeQuery(): void
    {
        // Negative control: dev/test stay DB-free, but the public interface
        // is still a MediaQuery proxy. Ray.FakeQuery intercepts the DbQuery call.
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );

        $customerQuery = $injector->getInstance(CustomerQueryInterface::class);
        $this->assertInstanceOf(CustomerQueryInterface::class, $customerQuery);
        $this->assertStringContainsString(
            CustomerQueryInterface::class,
            $customerQuery::class,
            'TestModule must bind CustomerQueryInterface as a MediaQuery proxy.',
        );

        $customer = $customerQuery->byEmail('alice@example.com');
        $this->assertSame('alice@example.com', $customer?->email);
    }

    private function skipWithoutDatabaseUrl(): void
    {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl === false || $databaseUrl === '') {
            $this->markTestSkipped('DATABASE_URL not set — prod context requires SQL wiring.');
        }

        $parts = \parse_url($databaseUrl);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'], $parts['user'], $parts['path'])) {
            $this->markTestSkipped('DATABASE_URL malformed — prod context requires SQL wiring.');
        }

        $serverDsn = \sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $parts['host'],
            $parts['port'] ?? 3306,
        );

        try {
            $pdo = new \PDO($serverDsn, $parts['user'], $parts['pass'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (\PDOException $e) {
            $this->markTestSkipped('DATABASE_URL unreachable — prod context requires SQL wiring: ' . $e->getMessage());
        }
    }

}
