<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the customer-withdrawal endpoint
 * — mirror of {@see \MyVendor\BeMart\Tests\Resource\WithdrawResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/mypage/withdraw`), same GET / POST verbs,
 * same body-shape + AUTHN + CSRF assertions. The differences from the
 * Fake-backed sibling:
 *
 *  - the storage bindings (CustomerCommandInterface → SqlCustomerCommand,
 *    CustomerQueryInterface → SqlCustomerQuery, CartCommandInterface →
 *    SqlCartCommand) are layered via the base class's sqlOverrideModule;
 *    withdrawal rewrites a real dtb_customer row (email → reserved
 *    `.test` dummy, customer_status_id → 3) and clears the
 *    customer's dtb_cart rows.
 *
 *  - the logged-in customer (alice) is inserted via {@see insertCustomer}
 *    and her numeric dtb_customer.id drives the CustomerSession
 *    binding — CustomerWithdrawn reads `findById(session->customerId)`,
 *    so the session id MUST be the real row id. The dummy email is
 *    `withdrawn-{numeric-id}@example.test`, derived from that id.
 *
 *  - mtb_customer_status (FK target of customer_status_id) is empty in
 *    the structure-only dump — seeded in setUp.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings.
 */
final class WithdrawResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** @var non-empty-string|null numeric dtb_customer.id bound on the session */
    private string|null $sessionCustomerId = null;

    /** @var non-empty-string numeric dtb_customer.id of alice */
    private string $aliceId;

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_customer_status (FK target) is empty in the dump.
        $this->seedCustomerStatus();

        $this->aliceId = (string) $this->insertCustomer([
            'email' => 'alice@example.com',
            'name01' => '山田',
            'name02' => 'アリス',
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

    public function testOnGetReturnsFormMetadata(): void
    {
        $ro = $this->resource->get('page://self/mypage/withdraw');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goMypageWithdraw', $ro->body['transitionId']);
        $this->assertSame(['sessionPrefix', 'csrfToken'], $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/mypage/withdraw', $ro->body['submitTo']['href']);
        $this->assertNull($ro->body['csrfToken']);
    }

    public function testOnGetShowsCurrentCustomer(): void
    {
        $ro = $this->resource->get('page://self/mypage/withdraw');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame('アリス', $ro->body['name02']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage/withdraw');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }

    public function testOnPostHappyPathReturns200(): void
    {
        $dummyEmail = 'withdrawn-' . $this->aliceId . '@example.test';

        $ro = $this->resource->post('page://self/mypage/withdraw', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        $this->assertSame($dummyEmail, $ro->body['dummyEmail']);
        $this->assertTrue($ro->body['cleared']);
        $this->assertStringContainsString('退会', $ro->body['message']);

        // Read-back confirms the withdrawn shape landed: email rewritten
        // to the reserved dummy, status flipped to 3 (withdrawn).
        $stmt = $this->pdo->prepare(
            'SELECT email, customer_status_id FROM dtb_customer WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $this->aliceId]);
        $row = $stmt->fetch();
        $this->assertIsArray($row);
        $this->assertSame($dummyEmail, $row['email']);
        $this->assertSame(3, (int) $row['customer_status_id']);
    }

    public function testOnPostWithoutSessionReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->post('page://self/mypage/withdraw', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }

}
