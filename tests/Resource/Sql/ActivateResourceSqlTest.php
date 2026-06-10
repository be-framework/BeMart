<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;

/**
 * SQL-backed hypermedia coverage for the customer-activation endpoint
 * — mirror of {@see \MyVendor\BeMart\Tests\Resource\ActivateResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/entry/activate`), same POST verb, same
 * body-shape + CSRF assertions. The differences from the Fake-backed
 * sibling:
 *
 *  - the storage bindings (CustomerCommandInterface → SqlCustomerCommand,
 *    CustomerQueryInterface → SqlCustomerQuery) are layered via the
 *    base class's sqlOverrideModule; activation flips a real
 *    dtb_customer row's customer_status_id from 1 to 2.
 *
 *  - the provisional (status-1) customer the Fake test reads from
 *    customers.json is inserted here via {@see insertCustomer} with a
 *    known secret_key, and its numeric dtb_customer.id is the value
 *    the activated-customer body echoes (vs the hex the Fake fixture
 *    carries).
 *
 *  - mtb_customer_status (FK target of customer_status_id) is empty in
 *    the structure-only dump — seeded in setUp.
 *
 * Activation keeps secret_key (the column is NOT NULL UNIQUE; EC-CUBE
 * does not clear it post-activation) — the activated-customer body
 * never surfaces secretKey, so this is invisible to the contract.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings.
 */
final class ActivateResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const PROVISIONAL_KEY = 'pending-secret-key-pilot7-2026abcd';

    /** @var non-empty-string numeric dtb_customer.id of the provisional customer */
    private string $provisionalId;

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_customer_status (FK target) is empty in the dump.
        $this->seedCustomerStatus();

        // A provisional (status-1) customer carrying the activation
        // token — the SQL analogue of the customers.json fixture row.
        $this->provisionalId = (string) $this->insertCustomer([
            'email' => 'provisional@example.com',
            'customer_status_id' => 1,
            'secret_key' => self::PROVISIONAL_KEY,
        ]);
    }

    public function testOnPostActivatesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/entry/activate', [
            'secretKey' => self::PROVISIONAL_KEY,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($this->provisionalId, $ro->body['customerId']);
        $this->assertSame('provisional@example.com', $ro->body['email']);
        $this->assertSame(2, $ro->body['customerStatus']);
        $this->assertArrayHasKey('Location', $ro->headers);

        // Read-back confirms the status flip landed in dtb_customer.
        $stmt = $this->pdo->prepare(
            'SELECT customer_status_id FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $this->provisionalId]);
        $this->assertSame(2, (int) $stmt->fetchColumn());
    }

    public function testOnPostUnknownKeyReturns404(): void
    {
        $ro = $this->resource->post('page://self/entry/activate', [
            'secretKey' => 'unknown-key-not-in-fixture-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertStringContainsString('無効', $ro->body['message']);
    }

    public function testOnPostInvalidKeyFormatReturns400(): void
    {
        $ro = $this->resource->post('page://self/entry/activate', [
            'secretKey' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

}
