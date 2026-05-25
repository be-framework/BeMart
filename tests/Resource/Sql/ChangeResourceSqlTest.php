<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the customer profile-update
 * endpoint — mirror of {@see \MyVendor\BeMart\Tests\Resource\ChangeResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/mypage/change`), same GET / POST verbs, same
 * body-shape + AUTHN + CSRF assertions. The differences from the
 * Fake-backed sibling:
 *
 *  - the storage bindings (CustomerCommandInterface → SqlCustomerCommand,
 *    CustomerQueryInterface → SqlCustomerQuery,
 *    EmailUniquenessQueryInterface → SqlEmailUniquenessChecker) are
 *    layered via the base class's sqlOverrideModule; the profile edit
 *    runs against a real dtb_customer row.
 *
 *  - the logged-in customer (alice) is inserted via {@see insertCustomer}
 *    and her numeric dtb_customer.id drives the CustomerSession
 *    binding — CustomerUpdated reads `findById(session->customerId)`,
 *    so the session id MUST be the real row id (the Fake test
 *    hard-codes the hex customers.json carries; SqlCustomerQuery::findById
 *    rejects non-numeric ids).
 *
 *  - the email-collision case seeds the colliding row (bob) first.
 *
 *  - mtb_customer_status (FK target of customer_status_id) and
 *    mtb_pref (FK target of pref_id) are empty in the structure-only
 *    dump — seeded in setUp.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings.
 */
final class ChangeResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** @var non-empty-string|null numeric dtb_customer.id bound on the session */
    private string|null $sessionCustomerId = null;

    /** @var non-empty-string numeric dtb_customer.id of alice */
    private string $aliceId;

    protected function setUp(): void
    {
        parent::setUp();

        // FK targets empty in the structure-only dump.
        $this->seedCustomerStatus();
        $this->insertPref(13, '東京都');

        // The logged-in customer — SQL analogue of the customers.json
        // alice row used by the Fake-backed sibling.
        $this->aliceId = (string) $this->insertCustomer([
            'email' => 'alice@example.com',
            'name01' => '山田',
            'name02' => 'アリス',
            'kana01' => 'ヤマダ',
            'kana02' => 'アリス',
            'phone_number' => '0312345678',
            'postal_code' => '1500001',
            'pref_id' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'customer_status_id' => 2,
        ]);

        $this->rebindSession($this->aliceId);
    }

    /** @param non-empty-string|null $customerId */
    private function rebindSession(string|null $customerId): void
    {
        $this->sessionCustomerId = $customerId;
        $this->resource = $this->buildResource();
    }

    protected function extraOverride(): AbstractModule|null
    {
        $customerId = $this->sessionCustomerId;

        return new class ($customerId) extends AbstractModule {
            /** @param non-empty-string|null $customerId */
            public function __construct(private readonly string|null $customerId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)
                    ->toInstance(new FakeSession($this->customerId));
            }
        };
    }

    public function testOnGetReturnsFormPrePopulated(): void
    {
        $ro = $this->resource->get('page://self/mypage/change');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame('アリス', $ro->body['name02']);
        $this->assertSame('ヤマダ', $ro->body['kana01']);
        $this->assertSame('0312345678', $ro->body['phoneNumber']);
        $this->assertSame('1500001', $ro->body['postalCode']);
        $this->assertSame(13, $ro->body['pref']);
        $this->assertSame('渋谷区', $ro->body['addr01']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/mypage/change', $ro->body['submitTo']['href']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage/change');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }

    public function testOnPostPatchesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/mypage/change', [
            'email' => 'alice@example.com',
            'phoneNumber' => '0309998888',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);

        // Read-back confirms the patch landed in dtb_customer.
        $next = $this->resource->get('page://self/mypage/change');
        $this->assertSame('0309998888', $next->body['phoneNumber']);
    }

    public function testOnPostEmailCollisionReturns409(): void
    {
        // Seed the colliding customer (the Fake test leans on a fixture).
        $this->insertCustomer([
            'email' => 'bob@example.com',
            'customer_status_id' => 2,
        ]);

        $ro = $this->resource->post('page://self/mypage/change', [
            'email' => 'bob@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
        $this->assertSame('bob@example.com', $ro->body['email']);
    }

    public function testOnPostWithoutSessionReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->post('page://self/mypage/change', [
            'email' => 'alice@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/mypage/change', [
            'email' => 'alice@example.com',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $ro = $this->resource->post('page://self/mypage/change', [
            'email' => 'not-an-email',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }
}
