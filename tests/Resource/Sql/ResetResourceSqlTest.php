<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;

use function assert;
use function is_string;

/**
 * SQL-backed hypermedia coverage for the forgot-password / reset-password
 * endpoints — mirror of {@see \MyVendor\BeMart\Tests\Resource\ResetResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/forgot-password`, `page://self/reset`), same
 * POST verbs, same body-shape + CSRF assertions. The differences from
 * the Fake-backed sibling:
 *
 *  - the storage bindings (PasswordResetTokenStorageInterface →
 *    SqlPasswordResetTokenStorage, CustomerQueryInterface →
 *    SqlCustomerQuery, CustomerCommandInterface → SqlCustomerCommand)
 *    are layered via the base class's sqlOverrideModule; the whole
 *    reset round-trip runs against a real dtb_customer row.
 *
 *  - EC-CUBE 4.3 has NO password-reset-token table — the token lives as
 *    the `reset_key` / `reset_expire` columns on dtb_customer (Option A,
 *    no schema change). So `issueResetKey()` reads the freshly-written
 *    `reset_key` straight off alice's row rather than from FakeMailer's
 *    captured-mail buffer. This is a STRONGER assertion: it proves the
 *    SQL storage's `put()` UPDATE landed, not merely that mail was sent.
 *
 *  - `customerId` in the reset response body is alice's numeric
 *    dtb_customer.id (SqlPasswordResetTokenStorage reconstructs the
 *    Entity from the row id) — NOT the 32-char hex the Fake-backed
 *    ResetResourceTest pins. Per G-23 the customerId shape is a storage
 *    detail, not a client-visible contract change; the Fake-backed
 *    Resource test stays untouched.
 *
 *  - the expired-token case seeds `reset_key` / `reset_expire` directly
 *    onto alice's row (expiry in the past) — the SQL analogue of the
 *    Fake test's `tokenStorage->put(...)` seed.
 *
 *  - mtb_customer_status (FK target of customer_status_id) is empty in
 *    the structure-only dump — seeded in setUp.
 *
 *  - the reset KEY comes from the dedicated {@see \MyVendor\BeMart\Be\Reason\Service\ResetKeyGeneratorInterface}
 *    (CSPRNG-backed, 32-char hex), NOT from the customer-id generator —
 *    so this suite needs no generator rebind. Earlier the forgot-password
 *    issuer {@see \MyVendor\BeMart\Be\Final\PasswordResetRequested} reused
 *    CustomerIdGeneratorInterface to mint the key; under SQL the
 *    `SqlCustomerIdGenerator` `MAX(id)+1` output is a 1-2 digit string,
 *    far short of the ResetKey semantic floor of 16 chars, so the reset
 *    endpoint 400'd. The fix split the concern into its own generator;
 *    whichever customer-id generator is bound is now irrelevant to reset.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings — Fake green AND SQL green =
 * client-observable equivalence.
 */
final class ResetResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const ALICE_EMAIL = 'alice@example.com';
    private const NEW_PASSWORD = 'new-password-pilot15-2026';

    /** @var non-empty-string numeric dtb_customer.id of alice */
    private string $aliceId;

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_customer_status (FK target of customer_status_id) is empty
        // in the structure-only dump.
        $this->seedCustomerStatus();

        // The customer who will request the reset — SQL analogue of the
        // customers.json alice row used by the Fake-backed sibling.
        $this->aliceId = (string) $this->insertCustomer([
            'email' => self::ALICE_EMAIL,
            'name01' => '山田',
            'name02' => 'アリス',
            'customer_status_id' => 2,
        ]);
    }

    /**
     * Drive the forgot-password endpoint, then read the freshly-issued
     * reset key straight off alice's dtb_customer row. EC-CUBE stores
     * the token in the `reset_key` column, so a successful issue is
     * directly observable in SQL — proving SqlPasswordResetTokenStorage's
     * put() UPDATE landed.
     *
     * @return non-empty-string
     */
    private function issueResetKey(): string
    {
        $ro = $this->resource->post('page://self/forgot-password', [
            'email' => self::ALICE_EMAIL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);

        $stmt = $this->pdo->prepare(
            'SELECT reset_key FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $this->aliceId]);
        $resetKey = $stmt->fetchColumn();

        $this->assertIsString($resetKey);
        $this->assertNotSame('', $resetKey);
        assert(is_string($resetKey) && $resetKey !== '');

        return $resetKey;
    }

    public function testHappyPath(): void
    {
        $resetKey = $this->issueResetKey();

        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        // SQL backing: customerId is alice's numeric dtb_customer.id.
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        // No email, no other profile fields — minimize info leak.
        $this->assertArrayNotHasKey('email', $ro->body);

        // The new password landed in dtb_customer.password.
        $stmt = $this->pdo->prepare(
            'SELECT password FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $this->aliceId]);
        $hash = $stmt->fetchColumn();
        $this->assertIsString($hash);
        $this->assertNotSame('hashed-password', $hash);

        // Single-use: the token was consumed — reset_key cleared.
        $stmt = $this->pdo->prepare(
            'SELECT reset_key, reset_expire FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $this->aliceId]);
        $row = $stmt->fetch();
        $this->assertIsArray($row);
        $this->assertNull($row['reset_key']);
        $this->assertNull($row['reset_expire']);
    }

    public function testUnknownKeyReturns400(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => 'unknown-reset-key-not-in-storage-zzzz',
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertStringContainsString('無効', $ro->body['message']);
    }

    public function testReusedKeyReturns400(): void
    {
        $resetKey = $this->issueResetKey();

        $first = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $first->code);

        // Single-use: the token was consumed by the first reset
        // (delete() nulled reset_key) — the second attempt misses.
        $second = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::BAD_REQUEST, $second->code);
        $this->assertStringContainsString('無効', $second->body['message']);
    }

    public function testExpiredKeyReturns400(): void
    {
        // Seed a token with reset_expire in the past directly onto
        // alice's row — the SQL analogue of the Fake test's
        // tokenStorage->put(...) seed. SqlPasswordResetTokenStorage
        // returns the row unfiltered; the PasswordResetCompleted
        // consumer rejects it on the `expiresAt < now` check.
        $resetKey = 'expired-token-key-pilot15-aaaa1111';
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_customer SET reset_key = :reset_key, '
            . 'reset_expire = :reset_expire WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $this->aliceId,
            ':reset_key' => $resetKey,
            ':reset_expire' => '2020-01-01 00:00:00',
        ]);

        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertStringContainsString('無効', $ro->body['message']);
    }

    public function testInvalidPasswordFormatReturns400(): void
    {
        $resetKey = $this->issueResetKey();

        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => $resetKey,
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testInvalidKeyFormatReturns400(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => 'short',
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => 'some-key-which-shape-passes-validation',
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testForgotPasswordUnknownEmailStillReturns200(): void
    {
        // Anti-enumeration: a forgot-password request for an
        // unregistered email succeeds with no token issued — the
        // caller cannot tell the email is unknown. No reset_key is
        // written anywhere.
        $ro = $this->resource->post('page://self/forgot-password', [
            'email' => 'nobody-here@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);

        // alice's row was NOT touched — no token leaked onto it.
        $stmt = $this->pdo->prepare(
            'SELECT reset_key FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $this->aliceId]);
        $this->assertNull($stmt->fetchColumn());
    }
}
