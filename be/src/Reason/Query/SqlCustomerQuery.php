<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use Override;

use function ctype_digit;
use function str_replace;

final class SqlCustomerQuery implements CustomerQueryInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function findByEmail(string $email): CustomerEntity|null
    {
        $row = $this->db->row('customer_find_by_email', ['email' => $email]);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function findBySecretKey(string $secretKey): CustomerEntity|null
    {
        $row = $this->db->row('customer_find_by_secret_key', ['secretKey' => $secretKey]);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function findById(string $customerId): CustomerEntity|null
    {
        if (! ctype_digit($customerId)) {
            return null;
        }
        $row = $this->db->row('customer_find_by_id', ['id' => (int) $customerId]);
        return $row === null ? null : $this->hydrate($row);
    }

    /** @return list<CustomerEntity> */
    #[Override]
    public function search(?string $nameKeyword, ?string $emailKeyword, int $limit = 50): array
    {
        $hasName = $nameKeyword !== null && $nameKeyword !== '';
        $hasEmail = $emailKeyword !== null && $emailKeyword !== '';
        $values = ['limit' => $limit];
        if ($hasName) {
            $pattern = '%' . $this->escapeLike($nameKeyword) . '%';
            $values += ['nameA' => $pattern, 'nameB' => $pattern, 'nameC' => $pattern];
        }
        if ($hasEmail) {
            $values['emailKeyword'] = '%' . $this->escapeLike($emailKeyword) . '%';
        }
        $query = match (true) {
            $hasName && $hasEmail => 'customer_search_name_email',
            $hasName => 'customer_search_name',
            $hasEmail => 'customer_search_email',
            default => 'customer_search_all',
        };

        return array_map($this->hydrate(...), $this->db->rows($query, $values));
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): CustomerEntity
    {
        $secretKey = (string) $row['secret_key'];
        return new CustomerEntity(
            customerId: (string) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password'],
            name01: (string) $row['name01'],
            name02: (string) $row['name02'],
            kana01: $row['kana01'] === null ? null : (string) $row['kana01'],
            kana02: $row['kana02'] === null ? null : (string) $row['kana02'],
            companyName: $row['company_name'] === null ? null : (string) $row['company_name'],
            phoneNumber: $row['phone_number'] === null ? null : (string) $row['phone_number'],
            postalCode: $row['postal_code'] === null ? null : (string) $row['postal_code'],
            pref: $row['pref_id'] === null ? null : (int) $row['pref_id'],
            addr01: $row['addr01'] === null ? null : (string) $row['addr01'],
            addr02: $row['addr02'] === null ? null : (string) $row['addr02'],
            birth: $row['birth'] === null ? null : (string) $row['birth'],
            sex: $row['sex_id'] === null ? null : (int) $row['sex_id'],
            job: $row['job_id'] === null ? null : (int) $row['job_id'],
            initialPoint: 0,
            customerStatus: (int) $row['customer_status_id'],
            secretKey: $secretKey === '' ? null : $secretKey,
        );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
