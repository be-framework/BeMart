<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;

use function bin2hex;
use function random_bytes;

/**
 * Storage-layer coverage for {@see CustomerCommandInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see AdminCommandInterfaceTest}'s write half. Per
 * G-23 the client-observable contract lives in the Resource-layer
 * siblings ({@see \MyVendor\BeMart\Tests\Resource\Sql\EntryResourceSqlTest}
 * et al.); this file pins the per-method SQL paths against the same
 * column↔field projection {@see CustomerQueryInterface} reads back, so a
 * read-after-write round-trips exactly.
 *
 * Surprises this suite locks in:
 *  - `secret_key` is NOT NULL UNIQUE — register() must always supply a
 *    value even when the CustomerEntity carries a null secretKey.
 *  - customer_status_id is an FK to the (empty-in-dump)
 *    mtb_customer_status master — seedCustomerStatus must run first.
 *  - activate() keeps secret_key (cannot null a NOT NULL column,
 *    cannot empty it without colliding on the UNIQUE index).
 */
final class SqlCustomerCommandTest extends AbstractSqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // mtb_customer_status is empty in the structure-only dump; seed
        // the EC-CUBE canonical rows so dtb_customer's FK
        // (customer_status_id) is satisfiable on every write.
        $this->seedCustomerStatus();
    }

    /**
     * Build a CustomerEntity with sensible defaults so each test only
     * states the fields it cares about.
     *
     * @param array<string, mixed> $overrides
     */
    private function entity(array $overrides = []): CustomerEntity
    {
        $defaults = [
            'customerId' => '0',
            'email' => 'fresh@example.com',
            'passwordHash' => '$2y$12$hash',
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'companyName' => null,
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            // pref defaults null — mtb_pref is empty in the structure-
            // only dump, so a non-null pref_id would raise FK 1452.
            // Tests that exercise the pref column seed mtb_pref first.
            'pref' => null,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'birth' => null,
            'sex' => null,
            'job' => null,
            'initialPoint' => 100,
            'customerStatus' => 2,
            'secretKey' => null,
        ];
        $v = [...$defaults, ...$overrides];

        return new CustomerEntity(
            customerId: $v['customerId'],
            email: $v['email'],
            passwordHash: $v['passwordHash'],
            name01: $v['name01'],
            name02: $v['name02'],
            kana01: $v['kana01'],
            kana02: $v['kana02'],
            companyName: $v['companyName'],
            phoneNumber: $v['phoneNumber'],
            postalCode: $v['postalCode'],
            pref: $v['pref'],
            addr01: $v['addr01'],
            addr02: $v['addr02'],
            birth: $v['birth'],
            sex: $v['sex'],
            job: $v['job'],
            initialPoint: $v['initialPoint'],
            customerStatus: $v['customerStatus'],
            secretKey: $v['secretKey'],
        );
    }

    public function testRegisterInsertsRowWithProvidedId(): void
    {
        // mtb_pref is empty in the structure-only dump — seed Tokyo so
        // the pref_id FK holds for this round-trip-every-field case.
        $this->insertPref(13, '東京都');

        $generator = $this->sql(CustomerIdGeneratorInterface::class);
        $newId = $generator->generate()->value; // numeric string

        $command = $this->sql(CustomerCommandInterface::class);
        $command->register($this->entity([
            'customerId' => $newId,
            'email' => 'new-customer@example.com',
            'pref' => 13,
        ]));

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findById($newId);

        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame($newId, $read->customerId);
        $this->assertSame('new-customer@example.com', $read->email);
        $this->assertSame('山田', $read->name01);
        $this->assertSame('太郎', $read->name02);
        $this->assertSame('ヤマダ', $read->kana01);
        $this->assertSame(13, $read->pref);
        $this->assertSame(2, $read->customerStatus);
    }

    public function testRegisterGeneratesSecretKeyWhenEntityHasNull(): void
    {
        // CustomerRegistered builds a CustomerEntity with secretKey=null
        // (an active customer carries no token). secret_key is NOT NULL
        // UNIQUE — register() must supply one so the INSERT succeeds.
        $generator = $this->sql(CustomerIdGeneratorInterface::class);
        $newId = $generator->generate()->value;

        $command = $this->sql(CustomerCommandInterface::class);
        $command->register($this->entity([
            'customerId' => $newId,
            'email' => 'no-secret@example.com',
            'secretKey' => null,
        ]));

        // The row landed despite the null secretKey on the entity.
        $stmt = $this->pdo->prepare('SELECT secret_key FROM dtb_customer WHERE id = :id');
        $stmt->execute([':id' => (int) $newId]);
        $secretKey = $stmt->fetchColumn();
        $this->assertIsString($secretKey);
        $this->assertNotSame('', $secretKey, 'register must write a non-empty unique secret_key');
    }

    public function testRegisterKeepsProvidedProvisionalSecretKey(): void
    {
        // A provisional (status-1) customer carries the activation
        // token; register() must persist it verbatim so the activation
        // flow can later look the customer up by it.
        $generator = $this->sql(CustomerIdGeneratorInterface::class);
        $newId = $generator->generate()->value;
        $token = bin2hex(random_bytes(16));

        $command = $this->sql(CustomerCommandInterface::class);
        $command->register($this->entity([
            'customerId' => $newId,
            'email' => 'provisional@example.com',
            'customerStatus' => 1,
            'secretKey' => $token,
        ]));

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findBySecretKey($token);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame('provisional@example.com', $read->email);
        $this->assertSame(1, $read->customerStatus);
        $this->assertSame($token, $read->secretKey);
    }

    public function testRegisterIsNoOpForNonNumericId(): void
    {
        // FakeCustomerIdGenerator emits 32-char hex; CustomerCommandInterface
        // must reject it silently rather than coerce it into an int PK.
        $command = $this->sql(CustomerCommandInterface::class);
        $command->register($this->entity([
            'customerId' => '0123456789abcdef0123456789abcdef',
            'email' => 'reject-me@example.com',
        ]));

        $query = $this->sql(CustomerQueryInterface::class);
        $this->assertNull($query->findByEmail('reject-me@example.com'));
    }

    public function testRegisterPersistsInitialPointAsPoint(): void
    {
        $generator = $this->sql(CustomerIdGeneratorInterface::class);
        $newId = $generator->generate()->value;

        $command = $this->sql(CustomerCommandInterface::class);
        $command->register($this->entity([
            'customerId' => $newId,
            'email' => 'points@example.com',
            'initialPoint' => 100,
        ]));

        $stmt = $this->pdo->prepare('SELECT point FROM dtb_customer WHERE id = :id');
        $stmt->execute([':id' => (int) $newId]);
        $this->assertSame(100, (int) $stmt->fetchColumn());
    }

    public function testActivateFlipsStatusToActive(): void
    {
        $token = bin2hex(random_bytes(16));
        $id = $this->insertCustomer([
            'email' => 'pending@example.com',
            'customer_status_id' => 1,
            'secret_key' => $token,
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->activate((string) $id);

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame(2, $read->customerStatus);
    }

    public function testActivateKeepsSecretKey(): void
    {
        // EC-CUBE keeps secret_key after activation; the column is NOT
        // NULL UNIQUE so it cannot be nulled and emptying it would
        // collide on the UNIQUE index for the second activation.
        $token = bin2hex(random_bytes(16));
        $id = $this->insertCustomer([
            'email' => 'keep-key@example.com',
            'customer_status_id' => 1,
            'secret_key' => $token,
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->activate((string) $id);

        $stmt = $this->pdo->prepare('SELECT secret_key FROM dtb_customer WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame($token, $stmt->fetchColumn());
    }

    public function testActivateIsIdempotent(): void
    {
        $id = $this->insertCustomer([
            'email' => 'already-active@example.com',
            'customer_status_id' => 2,
            'secret_key' => bin2hex(random_bytes(16)),
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->activate((string) $id);
        $command->activate((string) $id); // replay — must not raise

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame(2, $read->customerStatus);
    }

    public function testActivateIsNoOpForNonNumericId(): void
    {
        $id = $this->insertCustomer([
            'email' => 'untouched@example.com',
            'customer_status_id' => 1,
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->activate('0123456789abcdef0123456789abcdef');

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame(1, $read->customerStatus);
    }

    public function testUpdateOverwritesEditableFields(): void
    {
        $id = $this->insertCustomer([
            'email' => 'before@example.com',
            'name01' => '旧姓',
            'name02' => '旧名',
            'customer_status_id' => 2,
        ]);

        $query = $this->sql(CustomerQueryInterface::class);
        $current = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $current);

        // Merge the way CustomerUpdated does: persisted state + new fields.
        $merged = $this->entity([
            'customerId' => (string) $id,
            'email' => 'after@example.com',
            'name01' => '新姓',
            'name02' => '新名',
            'passwordHash' => $current->passwordHash,
            'customerStatus' => $current->customerStatus,
            'secretKey' => $current->secretKey,
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->update($merged);

        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame('after@example.com', $read->email);
        $this->assertSame('新姓', $read->name01);
        $this->assertSame('新名', $read->name02);
    }

    public function testUpdatePersistsWithdrawnShape(): void
    {
        // CustomerWithdrawn writes a status-3 entity with a dummy email.
        $id = $this->insertCustomer([
            'email' => 'leaving@example.com',
            'customer_status_id' => 2,
        ]);

        $query = $this->sql(CustomerQueryInterface::class);
        $current = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $current);

        $withdrawn = $this->entity([
            'customerId' => (string) $id,
            'email' => 'withdrawn-' . $id . '@example.invalid',
            'passwordHash' => $current->passwordHash,
            'name01' => $current->name01,
            'name02' => $current->name02,
            'customerStatus' => 3,
            'secretKey' => $current->secretKey,
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->update($withdrawn);

        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame('withdrawn-' . $id . '@example.invalid', $read->email);
        $this->assertSame(3, $read->customerStatus);
        // The original email slot is freed for re-registration.
        $this->assertNull($query->findByEmail('leaving@example.com'));
    }

    public function testUpdateIsNoOpForNonNumericId(): void
    {
        $id = $this->insertCustomer([
            'email' => 'safe@example.com',
            'name01' => 'Original',
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->update($this->entity([
            'customerId' => '0123456789abcdef0123456789abcdef',
            'email' => 'hijacked@example.com',
            'name01' => 'Hijacked',
        ]));

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame('safe@example.com', $read->email);
        $this->assertSame('Original', $read->name01);
    }

    public function testUpdatePasswordSwapsSingleColumn(): void
    {
        $id = $this->insertCustomer([
            'email' => 'reset-me@example.com',
            'name01' => '保持',
            'password' => '$2y$12$oldhash',
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->updatePassword((string) $id, '$2y$12$newhash');

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame('$2y$12$newhash', $read->passwordHash);
        // Unrelated fields untouched through the narrow update.
        $this->assertSame('reset-me@example.com', $read->email);
        $this->assertSame('保持', $read->name01);
    }

    public function testUpdatePasswordIsNoOpForNonNumericId(): void
    {
        $id = $this->insertCustomer([
            'email' => 'pw-safe@example.com',
            'password' => '$2y$12$untouched',
        ]);

        $command = $this->sql(CustomerCommandInterface::class);
        $command->updatePassword('0123456789abcdef0123456789abcdef', '$2y$12$hijacked');

        $query = $this->sql(CustomerQueryInterface::class);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(CustomerEntity::class, $read);
        $this->assertSame('$2y$12$untouched', $read->passwordHash);
    }

    public function testCustomerIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(CustomerIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->generate()->value);

        $firstId = $this->insertCustomer(['email' => 'gen-1@example.com']);
        $secondId = $this->insertCustomer(['email' => 'gen-2@example.com']);
        $this->assertSame((string) ($secondId + 1), $generator->generate()->value);
        $this->assertGreaterThan($firstId, $secondId);
    }
}
