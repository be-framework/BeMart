<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\AdminCustomerCsvExported;
use MyVendor\BeMart\Be\Input\AdminExportCustomerInput;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * goExportCustomer as the sink of the anonymous-to-admin CSV formula
 * injection chain: `POST /entry` stores name01 verbatim (by design — the
 * customer's own name is not the exporter's business), and the admin
 * download is where a spreadsheet would evaluate it.
 */
final class AdminCustomerCsvExportedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private const PAYLOAD = "+cmd|' /C calc'!A0";

    private function becomingWith(CustomerEntity $customer): BecomingInterface
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $query = new class ($customer) implements CustomerQueryInterface {
            public function __construct(private readonly CustomerEntity $customer)
            {
            }

            public function byEmail(string $email): CustomerEntity|null
            {
                return null;
            }

            public function bySecretKey(string $secretKey): CustomerEntity|null
            {
                return null;
            }

            public function item(string $customerId): CustomerEntity|null
            {
                return null;
            }

            public function search(string|null $nameKeyword, string|null $emailKeyword, int $limit = 50): array
            {
                return [$this->customer];
            }
        };

        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session, $query) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly CustomerQueryInterface $query,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                $this->bind(CustomerQueryInterface::class)->toInstance($this->query);
            }
        });

        return (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))
            ->getInstance(BecomingInterface::class);
    }

    private function customer(string $name01, int $customerStatus): CustomerEntity
    {
        return new CustomerEntity(
            customerId: '0123456789abcdef0123456789abcdef',
            email: 'formula@example.com',
            passwordHash: 'irrelevant',
            name01: $name01,
            name02: '太郎',
            kana01: null,
            kana02: null,
            companyName: null,
            phoneNumber: null,
            postalCode: null,
            pref: null,
            addr01: null,
            addr02: null,
            birth: null,
            sex: null,
            job: null,
            initialPoint: 0,
            customerStatus: $customerStatus,
        );
    }

    public function testFormulaCellIsNeutralizedInTheExportedCsv(): void
    {
        $becoming = $this->becomingWith($this->customer(self::PAYLOAD, 2));

        $final = ($becoming)(new AdminExportCustomerInput());

        $this->assertInstanceOf(AdminCustomerCsvExported::class, $final);
        // fputcsv encloses the cell because the payload contains spaces;
        // what matters is the leading apostrophe inside the enclosure.
        $this->assertStringContainsString(',"\'' . self::PAYLOAD . '",', $final->csv);
        $this->assertStringNotContainsString(',"' . self::PAYLOAD . '",', $final->csv);
    }

    public function testNumericCellStaysANumber(): void
    {
        // customerStatus is the only numeric column in this export; the
        // guard must not turn a number into a quoted text cell.
        $becoming = $this->becomingWith($this->customer('山田', 2));

        $final = ($becoming)(new AdminExportCustomerInput());

        $this->assertInstanceOf(AdminCustomerCsvExported::class, $final);
        $this->assertStringEndsWith(",,2\n", $final->csv);
    }
}
