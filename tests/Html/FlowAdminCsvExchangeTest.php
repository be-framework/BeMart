<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Koriym\FileUpload\FileUpload;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function random_bytes;

/**
 * HTML hypermedia walk of the admin CSV config + product-CSV import —
 * driven entirely by the rendered HTML's ALPS affordances (class/rel)
 * over real HTTP.
 *
 * Path C: independent of the Hypermedia workflow class; walks rendered
 * HTML the way a browser would, resolving transitions from ALPS
 * class/rel tokens on forms and anchors.
 *
 * Journey mirrored from FlowAdminCsvExchangeTest (Hypermedia):
 *   1. GET  /admin/csv-config           → assertAffordance doUpdateCsv
 *   2. submit doUpdateCsv (POST)        → 200/303 — config saved
 *   3. GET  /admin/product/csv-product  → assertAffordance doImportProductCsv
 *   4. submit doImportProductCsv (POST) → multipart file upload → 200/303
 *   5. cleanup: DELETE the imported product (direct POST _method=delete)
 *
 * Steps skipped (no HTML-followable affordance or not in scope):
 *   - goExportProduct: rendered as <a href="…"> (GET download), not a
 *     <form class="…">; pure download link, no submit() target.
 *   - goExportCategory, doImportCategoryCsv: the Category/Csv template
 *     uses a <textarea name="csv"> rather than a multipart file input;
 *     a FileUpload submit() does not fit that form shape. Skipped.
 *   - goExportOrder, goExportShipping, doImportShippingCsv,
 *     goExportCustomer, goExportClassName, doImportClassNameCsv,
 *     goExportClassCategory, doImportClassCategoryCsv: out of scope for
 *     a focused two-form HTML walk.
 *   - bodyValue('transitionId'), bodyValue('count'): JSON-only mutation
 *     summary fields; HTML renders state in controls/page text.
 */
final class FlowAdminCsvExchangeTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-csv-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csv-html-csrf-token';

    private static string $importedProductCode;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$importedProductCode = 'wf-html-csv-' . bin2hex(random_bytes(4));
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
            '127.0.0.1:8128',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    /** GET the CSV config page; confirm the doUpdateCsv form affordance is rendered. */
    #[Alps('goCsv')]
    public function testOpensCsvConfigPage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/csv-config');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doUpdateCsv');

        return $page;
    }

    /**
     * Submit the doUpdateCsv form.
     *
     * The HTML form's JS normally serializes the <select> options into
     * hidden column inputs. In this headless walk we pass csvOutput and
     * csvNotOutput directly — onPost's normalizeHtmlColumns() accepts both
     * shapes (structured columns[] or the plain select arrays).
     */
    #[Alps('doUpdateCsv')]
    #[Depends('testOpensCsvConfigPage')]
    public function testUpdatesCsvConfig(ResourceObject $page): ResourceObject
    {
        $updated = $this->submit($page, 'doUpdateCsv', [
            'csvType' => '3',
            'csvOutput' => ['paymentTotal', 'orderNo'],
            'csvNotOutput' => ['orderDate'],
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateCsv affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    /**
     * GET the product CSV upload screen directly (untagged nav link).
     * Confirms the doImportProductCsv form (class token) is rendered.
     */
    #[Alps('goExportProduct')]
    #[Depends('testUpdatesCsvConfig')]
    public function testOpensProductCsvUploadScreen(ResourceObject $updated): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/product/csv-product');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doImportProductCsv');

        return $page;
    }

    /**
     * Submit the doImportProductCsv form as a multipart file upload.
     *
     * HttpResource.runHttp() sends FileUpload fields as multipart (-F curl),
     * so file upload works end-to-end via submit(). The fixture CSV carries
     * a unique productCode so the imported row can be cleaned up afterwards.
     */
    #[Alps('doImportProductCsv')]
    #[Depends('testOpensProductCsvUploadScreen')]
    public function testImportsProductCsvViaUpload(ResourceObject $uploadPage): ResourceObject
    {
        // Build a one-row CSV with a unique product code so cleanup is precise.
        $productCode = self::$importedProductCode;
        $csv = "productCode,productName,price02,stock,productStatus,description,searchWord,note\n"
            . "{$productCode},HTML CSV Walk Product,1480,5,1,Imported by HTML walk,,\n";

        // Write to a temp file so FileUpload::fromFile() can wrap it.
        $tmp = \tempnam(\sys_get_temp_dir(), 'bemart-wf-csv-');
        \file_put_contents($tmp, $csv);

        try {
            $imported = $this->submit($uploadPage, 'doImportProductCsv', [
                'import_file' => FileUpload::fromFile($tmp),
            ]);
        } finally {
            \unlink($tmp);
        }

        $this->assertTrue(
            in_array($imported->code, [Code::OK, Code::SEE_OTHER], true),
            'doImportProductCsv affordance did not succeed: ' . (string) ($imported->view ?? $imported->code),
        );

        return $imported;
    }

    /**
     * Verify the imported product is visible in the product list, then
     * delete it so the shared eccubedb_test is not polluted.
     *
     * The doDeleteProduct affordance is a JS-modal anchor in the product
     * list (not a <form class="…">), so cleanup uses a direct POST with
     * _method=delete rather than a followed affordance — identical to the
     * doDeleteTemplate cleanup pattern in FlowAdminTemplateLifecycleTest.
     */
    #[Alps('doDeleteProduct')]
    #[Depends('testImportsProductCsvViaUpload')]
    public function testDeletesImportedProduct(ResourceObject $imported): void
    {
        $productCode = self::$importedProductCode;

        // Confirm the import landed by reading the product directly.
        $verify = $this->resource->get('page://self/admin/product', ['productCode' => $productCode]);
        $this->assertSame(Code::OK, $verify->code, (string) ($verify->view ?? $verify->code));
        $this->assertStringContainsString(
            $productCode,
            (string) ($verify->view ?? ''),
            'Imported product not visible after CSV import',
        );

        // Cleanup: soft-delete via direct POST _method=delete.
        $deleted = $this->resource->post('page://self/admin/product?_method=delete', [
            'productCode' => $productCode,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertTrue(
            in_array($deleted->code, [Code::OK, Code::SEE_OTHER], true),
            'cleanup delete did not succeed: ' . (string) ($deleted->view ?? $deleted->code),
        );
    }
}
