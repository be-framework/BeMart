<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\LoginHistoryListFetched;
use MyVendor\BeMart\Be\Input\GetLoginHistoryListInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class LoginHistoryListFetchedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    private function build(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathListsEntriesSortedNewestFirst(): void
    {
        $final = ($this->becoming)(new GetLoginHistoryListInput());

        $this->assertInstanceOf(LoginHistoryListFetched::class, $final);
        $this->assertGreaterThanOrEqual(4, $final->count);

        // Newest entry first (DESC by timestamp).
        $first = $final->entries[0];
        $this->assertArrayHasKey('timestamp', $first);
        $this->assertArrayHasKey('loginId', $first);
        $this->assertArrayHasKey('success', $first);
        $this->assertArrayHasKey('clientIp', $first);

        // Confirm DESC ordering by comparing consecutive timestamps.
        for ($i = 1; $i < $final->count; $i++) {
            $this->assertGreaterThanOrEqual(
                $final->entries[$i]['timestamp'],
                $final->entries[$i - 1]['timestamp'],
            );
        }
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetLoginHistoryListInput());
    }
}
