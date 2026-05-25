<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;

use function ctype_digit;

/**
 * SQL-backed hypermedia coverage for the customer-registration
 * endpoint — mirror of {@see \MyVendor\BeMart\Tests\Resource\EntryResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/entry`), same GET / POST verbs, same
 * body-shape + CSRF assertions. The differences from the Fake-backed
 * sibling:
 *
 *  - the storage bindings (CustomerCommandInterface → SqlCustomerCommand,
 *    CustomerIdGeneratorInterface → direct MediaQuery customer id proxy,
 *    EmailUniquenessQueryInterface → SqlEmailUniquenessChecker,
 *    CustomerQueryInterface → SqlCustomerQuery) are layered via the
 *    base class's sqlOverrideModule; registration writes a real
 *    dtb_customer row.
 *
 *  - `customerId` is a numeric dtb_customer.id (direct MediaQuery customer id proxy
 *    pre-allocates MAX(id)+1) — NOT the 32-char hex the Fake generator
 *    emits. The Fake test pins the hex shape; this sibling pins the
 *    numeric shape. Per G-23 the Fake-backed Resource test stays
 *    untouched: the customerId shape is a storage detail, not a
 *    client-visible contract change (it is still an opaque handle).
 *
 *  - mtb_customer_status (FK target of customer_status_id) and
 *    mtb_pref (FK target of pref_id) are empty in the structure-only
 *    dump — seeded in setUp.
 *
 *  - the "duplicate email" case seeds the colliding row first (the
 *    Fake test relies on a customers.json fixture entry).
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings — Fake green AND SQL green =
 * client-observable equivalence.
 */
final class EntryResourceSqlTest extends AbstractResourceSqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // FK targets empty in the structure-only dump.
        $this->seedCustomerStatus();
        $this->insertPref(13, '東京都');
    }

    public function testOnGetReturnsFormMetadata(): void
    {
        $ro = $this->resource->get('page://self/entry');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goCustomerRegistration', $ro->body['transitionId']);
        $this->assertContains('email', $ro->body['fields']);
        $this->assertContains('password', $ro->body['fields']);
        $this->assertContains('name01', $ro->body['fields']);
        $this->assertContains('csrfToken', $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/entry', $ro->body['submitTo']['href']);
        $this->assertNull($ro->body['csrfToken']);
    }

    public function testOnPostRegistersAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/entry', [
            'email' => 'first@example.com',
            'password' => 'first-passphrase-2026',
            'name01' => '一郎',
            'name02' => '田中',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('first@example.com', $ro->body['email']);
        $this->assertSame('一郎', $ro->body['name01']);
        $this->assertSame(100, $ro->body['initialPoint']);
        $this->assertSame(2, $ro->body['customerStatus']);
        // SQL backing: customerId is the numeric dtb_customer.id, not
        // the 32-char hex the Fake generator emits.
        $this->assertTrue(ctype_digit($ro->body['customerId']));
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostPersistsRowReadableByQuery(): void
    {
        // Read-after-write through the resource layer is not possible
        // (entry has no GET-by-id); assert persistence directly against
        // dtb_customer so the row landing is proven, not just the Final.
        $ro = $this->resource->post('page://self/entry', [
            'email' => 'persisted@example.com',
            'password' => 'persisted-passphrase-2026',
            'name01' => '保存',
            'name02' => '太郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $ro->code);

        $stmt = $this->pdo->prepare(
            'SELECT name01, customer_status_id, point FROM dtb_customer WHERE email = :email',
        );
        $stmt->execute([':email' => 'persisted@example.com']);
        $row = $stmt->fetch();
        $this->assertIsArray($row);
        $this->assertSame('保存', $row['name01']);
        $this->assertSame(2, (int) $row['customer_status_id']);
        $this->assertSame(100, (int) $row['point']);
    }

    public function testOnPostCarriesOptionalFields(): void
    {
        $ro = $this->resource->post('page://self/entry', [
            'email' => 'second@example.com',
            'password' => 'second-passphrase-2026',
            'name01' => '二郎',
            'name02' => '田中',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-2-3',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
    }

    public function testOnPostDuplicateEmailReturns409(): void
    {
        // Seed the colliding row (the Fake test leans on a fixture).
        $this->insertCustomer(['email' => 'taken@example.com']);

        $ro = $this->resource->post('page://self/entry', [
            'email' => 'taken@example.com',
            'password' => 'try-to-overwrite-2026',
            'name01' => '別人',
            'name02' => 'A',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
        $this->assertSame('taken@example.com', $ro->body['email']);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $ro = $this->resource->post('page://self/entry', [
            'email' => 'not-an-email',
            'password' => 'whatever-2026',
            'name01' => '佐藤',
            'name02' => '五郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertNotEmpty($ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/entry', [
            'email' => 'no-csrf@example.com',
            'password' => 'whatever-2026',
            'name01' => '佐藤',
            'name02' => '七郎',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
