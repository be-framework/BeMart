<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin BaseInfo POST endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminBaseInfoResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/base-info`), same body-shape assertions,
 * same AUTHZ + CSRF + SemanticVariable branches. The differences:
 *
 *  - the storage binding (BaseInfoStorageInterface → SqlBaseInfoStorage)
 *    is layered via the base class's sqlOverrideModule; persistence is
 *    against the real dtb_base_info row id=1 (singleton).
 *
 *  - the happy-path POST sends `pref=13` and the SQL backend writes
 *    that into `dtb_base_info.pref_id`, which has a FK to mtb_pref.id.
 *    The structure-only schema dump leaves mtb_pref empty, so we seed
 *    `id=13` first via {@see insertPref} — same convention as
 *    {@see AddressBookResourceSqlTest}. The Fake-backed sibling has
 *    no such constraint and skips this seed.
 *
 *  - the idempotent-replay assertion needs the SAME defaults on both
 *    sides. SqlBaseInfoStorage returns BaseInfoStorageInterface's installer
 *    defaults when dtb_base_info is empty (the first GET in the test
 *    triggers that fall-through). The replay then submits those exact
 *    fields back and the Final reports `changed=false` — same shape as
 *    the Fake-backed sibling.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminBaseInfoResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_pref is empty in the structure-only dump; seed the one
        // prefecture id our happy-path payload uses so the FK
        // dtb_base_info.pref_id → mtb_pref.id is satisfiable on the
        // first UPDATE.
        $this->insertPref(13, 'Tokyo');
    }

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
                $this->bind(AdminSession::class)
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

    public function testOnPostHappyPathUpdatesBaseInfo(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
            'shopKana' => 'シンショップ',
            'phoneNumber' => '03-1234-5678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('新ショップ', $ro->body['shopName']);
        $this->assertSame('シンショップ', $ro->body['shopKana']);
        $this->assertSame('03-1234-5678', $ro->body['phoneNumber']);
        $this->assertTrue($ro->body['changed']);

        // Read-back through the Resource layer to confirm persistence
        // landed in dtb_base_info (vs only living in the Final).
        $next = $this->resource->get('page://self/admin/base-info');
        $this->assertSame(Code::OK, $next->code);
        $this->assertSame('新ショップ', $next->body['shopName']);
        $this->assertSame(13, $next->body['pref']);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        // First GET hits SqlBaseInfoStorage's installer-default
        // fall-through (dtb_base_info empty). Submit those exact values
        // back — the Final compares value-equal and reports changed=false
        // WITHOUT calling update(), so the storage stays empty for the
        // second cycle's identical fall-through. Same shape as the
        // Fake-backed sibling.
        $seed = $this->resource->get('page://self/admin/base-info');
        $this->assertSame(Code::OK, $seed->code);

        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => $seed->body['shopName'],
            'shopKana' => $seed->body['shopKana'],
            'shopNameEng' => $seed->body['shopNameEng'],
            'companyName' => $seed->body['companyName'],
            'postalCode' => $seed->body['postalCode'],
            'pref' => $seed->body['pref'],
            'addr01' => $seed->body['addr01'],
            'addr02' => $seed->body['addr02'],
            'phoneNumber' => $seed->body['phoneNumber'],
            'businessHour' => $seed->body['businessHour'],
            'shopEmail01' => $seed->body['shopEmail01'],
            'shopMessage' => $seed->body['shopMessage'],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostEmptyShopNameReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '   ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostBadPhoneNumberReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
            'phoneNumber' => 'not-digits',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
