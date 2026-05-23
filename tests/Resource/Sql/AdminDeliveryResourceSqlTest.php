<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function assert;
use function is_string;
use function str_contains;

/**
 * SQL-backed hypermedia coverage for the admin Delivery endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminDeliveryResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/delivery/delivery-list` and
 * `page://self/admin/delivery/delivery`), same body-shape assertions,
 * same AUTHN / AUTHZ / CSRF branches. The only differences are:
 *
 *  - the storage binding (DeliveryStorageInterface → SqlDeliveryStorage)
 *    and id generator (DeliveryIdGeneratorInterface →
 *    direct MediaQuery delivery id proxy) are layered via the base class's
 *    sqlOverrideModule; persistence is against the real dtb_delivery
 *    table.
 *
 *  - deliveryIds are numeric strings drawn from dtb_delivery.id, not the
 *    32-char hex the FakeDeliveryIdGenerator emits. Both suites assert
 *    "the response carries a deliveryId" but only the Fake side ever
 *    observes the hex handle — SqlDeliveryStorage rejects it as
 *    non-numeric on lookup (the same 404 path as any unknown id, by
 *    design).
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminDeliveryResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

    protected function extraOverride(): AbstractModule|null
    {
        $adminId = $this->currentAdminId;

        return new class ($adminId) extends AbstractModule {
            /** @param non-empty-string|null $adminId */
            public function __construct(private readonly string|null $adminId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)
                    ->toInstance(new FakeAdminSession($this->adminId));
            }
        };
    }

    /**
     * Swap the admin session adminId and rebuild the Resource client
     * so the new binding takes effect — same shape as the Fake-backed
     * sibling's `rebindAdminSession`.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    /**
     * Seed a single delivery method through the resource layer and
     * return the server-generated deliveryId — mirrors the Fake-backed
     * sibling's helper exactly. The POST drives the full Becoming chain
     * (Input → Final → direct MediaQuery delivery id proxy → SqlDeliveryStorage) so
     * the row appears in the same transactional state every subsequent
     * assertion will see.
     */
    private function seed(string $name): string
    {
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => $name,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['deliveryId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => 'ヤマト宅急便',
            'visible' => true,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('ヤマト宅急便', $ro->body['deliveryName']);
        $this->assertTrue($ro->body['visible']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => 'ヤマト',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => 'ヤマト',
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testListReturnsRows(): void
    {
        $this->seed('ヤマト宅急便');
        $this->seed('ゆうパック');

        $ro = $this->resource->get('page://self/admin/delivery/delivery-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/delivery/delivery-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testPutEditsMaster(): void
    {
        $id = $this->seed('ヤマト');

        $ro = $this->resource->put('page://self/admin/delivery/delivery', [
            'deliveryId' => $id,
            'deliveryName' => 'ヤマト宅急便',
            'visible' => false,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('ヤマト宅急便', $ro->body['deliveryName']);
        $this->assertFalse($ro->body['visible']);
    }

    public function testPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/delivery/delivery', [
            'deliveryId' => 'nonexistent-zzz',
            'deliveryName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('ヤマト宅急便');

        $ro = $this->resource->delete('page://self/admin/delivery/delivery', [
            'deliveryId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['deliveryId']);
    }

    public function testDeleteRejectsMissingCsrf(): void
    {
        $id = $this->seed('ヤマト宅急便');
        $ro = $this->resource->delete('page://self/admin/delivery/delivery', [
            'deliveryId' => $id,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }
}
