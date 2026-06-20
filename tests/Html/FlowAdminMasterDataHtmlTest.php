<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function preg_match;
use function random_bytes;

/**
 * HTML hypermedia walk of the admin master-data editor — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Path C: this test does NOT extend the Hypermedia workflow class; it walks the
 * rendered HTML the way a browser would, resolving transitions from ALPS
 * class/rel tokens on forms and anchors.
 *
 * Journey mirrored from FlowAdminMasterDataUpdateTest (Hypermedia):
 *   1. GET /admin/master-data            → assertAffordance doSelectMasterData
 *   2. submit doSelectMasterData (PUT)   → assertAffordance doUpdateMasterData
 *   3. submit doUpdateMasterData (PUT)   → 200/303 + Location
 *   4. followLocation                    → updated name appears in rendered page
 *
 * Skipped (no HTML affordance):
 *   - bodyValue('transitionId') checks  → JSON-only; HTML renders state in
 *     controls, not a transitionId field
 *   - bodyValue('count')                → JSON-only mutation summary field
 */
final class FlowAdminMasterDataHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-master-data-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-master-data-html-csrf-token';
    private const MASTER_TYPE = 'payment';

    private static string $updatedName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$updatedName = 'HTML Master Payment ' . bin2hex(random_bytes(4));
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return new HttpResource(
            '127.0.0.1:8120',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goMasterData')]
    public function testOpensMasterDataPage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/master-data', ['masterType' => self::MASTER_TYPE]);

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doSelectMasterData');

        return $page;
    }

    #[Depends('testOpensMasterDataPage')]
    #[Alps('doSelectMasterData')]
    public function testSelectsMasterDataType(ResourceObject $page): ResourceObject
    {
        $selected = $this->submit($page, 'doSelectMasterData', [
            'masterType' => self::MASTER_TYPE,
        ]);

        $this->assertSame(Code::OK, $selected->code, (string) ($selected->view ?? $selected->code));
        // After selecting, the update form must be present with the rows table.
        $this->assertAffordance($selected, 'doUpdateMasterData');

        return $selected;
    }

    #[Depends('testSelectsMasterDataType')]
    #[Alps('doUpdateMasterData')]
    public function testUpdatesMasterDataRow(ResourceObject $selected): ResourceObject
    {
        // Extract the first row's id from the rendered form controls.
        // The template renders: <input type="text" name="rows[0][id]" … value="…">
        $view = (string) ($selected->view ?? '');
        $this->assertSame(
            1,
            preg_match('/name="rows\[0\]\[id\]"[^>]*value="([^"]+)"/i', $view, $match),
            'rows[0][id] input not found in rendered master-data page',
        );
        $firstRowId = $match[1];

        $updated = $this->submit($selected, 'doUpdateMasterData', [
            'masterType' => self::MASTER_TYPE,
            'rows' => [
                ['id' => $firstRowId, 'name' => self::$updatedName],
            ],
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateMasterData affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    #[Depends('testUpdatesMasterDataRow')]
    #[Alps('goMasterData')]
    public function testReadsUpdatedMasterData(ResourceObject $updated): void
    {
        // Follow the Location header when the response is 303; otherwise GET
        // the master-data page directly (200 case re-renders inline).
        $read = $updated->code === Code::SEE_OTHER
            ? $this->followLocation($updated)
            : $this->resource->get('page://self/admin/master-data', ['masterType' => self::MASTER_TYPE]);

        $this->assertSame(Code::OK, $read->code, (string) ($read->view ?? $read->code));
        $this->assertStringContainsString(
            self::$updatedName,
            (string) ($read->view ?? ''),
            'Updated name not found in rendered master-data page after update',
        );
    }
}
