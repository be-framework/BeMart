<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use Override;

use function bin2hex;
use function ctype_digit;
use function random_bytes;

final class SqlCustomerCommand implements CustomerCommandInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function register(CustomerEntity $customer): void
    {
        if (! ctype_digit($customer->customerId)) {
            return;
        }
        $values = $this->values($customer);
        $values['secretKey'] = $customer->secretKey ?? bin2hex(random_bytes(16));
        $this->db->exec('customer_register', $values);
    }

    #[Override]
    public function activate(string $customerId): void
    {
        if (ctype_digit($customerId)) {
            $this->db->exec('customer_activate', ['id' => (int) $customerId]);
        }
    }

    #[Override]
    public function update(CustomerEntity $customer): void
    {
        if (! ctype_digit($customer->customerId)) {
            return;
        }
        $values = $this->values($customer);
        $values['secretKey'] = $customer->secretKey ?? '';
        $this->db->exec('customer_update', $values);
    }

    #[Override]
    public function updatePassword(string $customerId, string $passwordHash): void
    {
        if (ctype_digit($customerId)) {
            $this->db->exec('customer_update_password', ['id' => (int) $customerId, 'password' => $passwordHash]);
        }
    }

    /** @return array<string, mixed> */
    private function values(CustomerEntity $customer): array
    {
        return [
            'id' => (int) $customer->customerId,
            'customerStatus' => $customer->customerStatus,
            'sex' => $customer->sex,
            'job' => $customer->job,
            'pref' => $customer->pref,
            'name01' => $customer->name01,
            'name02' => $customer->name02,
            'kana01' => $customer->kana01,
            'kana02' => $customer->kana02,
            'companyName' => $customer->companyName,
            'postalCode' => $customer->postalCode,
            'addr01' => $customer->addr01,
            'addr02' => $customer->addr02,
            'email' => $customer->email,
            'phoneNumber' => $customer->phoneNumber,
            'birth' => $customer->birth,
            'password' => $customer->passwordHash,
            'point' => $customer->initialPoint,
        ];
    }
}
