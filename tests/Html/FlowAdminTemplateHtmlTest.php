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

use function array_key_last;
use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function preg_match_all;
use function random_bytes;

/**
 * HTML hypermedia walk of the admin template editor — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Path C: this test is independent of the Hypermedia workflow class; it walks
 * the rendered HTML the way a browser would, resolving transitions from ALPS
 * class/rel tokens on forms and anchors.
 *
 * Journey mirrored from FlowAdminTemplateLifecycleTest (Hypermedia):
 *   1. GET /admin/template/template-list  → assert goTemplateInstall affordance
 *   2. follow goTemplateInstall           → GET /admin/template/template-add
 *                                           assert doInstallTemplate form present
 *   3. submit doInstallTemplate (POST)    → 200/303 — installs a zip template
 *   4. follow Location / GET list        → assert doSelectTemplate form
 *   5. submit doSelectTemplate (PUT)      → 200/303 — activates the installed template
 *
   6. delete the installed template (cleanup) so the shared DB is not polluted.
 *
 * Steps skipped (no HTML-followable affordance):
 *   - doDownloadTemplate: rendered as <a href="…"> with a query-string trigger,
 *     not a <form class="…">; no submit() target.
 *   - doDeleteTemplate is a Bootstrap-modal <a href="…?_method=delete"> (not a
 *     <form class="…">), so step 6 issues it as a direct DELETE for cleanup
 *     rather than a followed affordance.
 */
final class FlowAdminTemplateHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-template-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-template-html-csrf-token';

    private static string $templateCode;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$templateCode = 'wf-html-' . bin2hex(random_bytes(4));
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
            '127.0.0.1:8123',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    /** GET the template list; confirm the goTemplateInstall nav affordance is rendered. */
    #[Alps('goTemplateList')]
    public function testOpensTemplateList(): ResourceObject
    {
        $list = $this->resource->get('page://self/admin/template/template-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertAffordance($list, 'goTemplateInstall');

        return $list;
    }

    /**
     * Follow the goTemplateInstall anchor to the template-add page.
     * Confirms the doInstallTemplate form is rendered (class token present).
     */
    #[Alps('goTemplateInstall')]
    #[Depends('testOpensTemplateList')]
    public function testFollowsGoTemplateInstall(ResourceObject $list): ResourceObject
    {
        $add = $this->follow($list, 'goTemplateInstall');

        $this->assertAffordance($add, 'doInstallTemplate');

        return $add;
    }

    /**
     * Submit the doInstallTemplate form with a real zip file.
     *
     * HttpResource.runHttp() sends FileUpload fields as multipart (-F curl),
     * so file upload works end-to-end via submit(). The server responds with
     * 200 or 303; a Location header means the install succeeded and the
     * browser is redirected to the template list.
     */
    #[Alps('doInstallTemplate')]
    #[Depends('testFollowsGoTemplateInstall')]
    public function testInstallsTemplate(ResourceObject $add): ResourceObject
    {
        $installed = $this->submit($add, 'doInstallTemplate', [
            'templateCode' => self::$templateCode,
            'templateName' => 'HTML Workflow Template',
            'file' => FileUpload::fromFile(dirname(__DIR__) . '/fixtures/template-upload.zip'),
        ]);

        $this->assertTrue(
            in_array($installed->code, [Code::OK, Code::SEE_OTHER], true),
            'doInstallTemplate affordance did not succeed: ' . (string) ($installed->view ?? $installed->code),
        );

        return $installed;
    }

    /**
     * After install, follow the redirect (or GET list directly) and submit
     * doSelectTemplate to activate the newly-installed template.
     *
     * The templateId is extracted from the rendered radio input:
     *   <input type="radio" name="template" value="<id>" …>
     * The last radio value is the most recently installed template.
     */
    #[Alps('doSelectTemplate')]
    #[Depends('testInstallsTemplate')]
    public function testSelectsTemplate(ResourceObject $installed): string
    {
        $list = $installed->code === Code::SEE_OTHER
            ? $this->followLocation($installed)
            : $this->resource->get('page://self/admin/template/template-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertAffordance($list, 'doSelectTemplate');

        // Extract the templateId of the installed template from the last radio input.
        // The list is ordered by id ASC so the last entry is the most recently installed.
        $view = (string) ($list->view ?? '');
        $matched = preg_match_all('/name="template"\s+value="([^"]+)"/i', $view, $radioMatches);
        $this->assertGreaterThanOrEqual(
            1,
            $matched,
            'No radio inputs found — template list has no templates after install',
        );
        $templateId = $radioMatches[1][array_key_last($radioMatches[1])];

        $selected = $this->submit($list, 'doSelectTemplate', [
            'templateId' => $templateId,
        ]);

        $this->assertTrue(
            in_array($selected->code, [Code::OK, Code::SEE_OTHER], true),
            'doSelectTemplate affordance did not succeed: ' . (string) ($selected->view ?? $selected->code),
        );

        return $templateId;
    }

    /**
     * Remove the installed template so the shared eccubedb_test is not polluted
     * (template install is stateful — an uncleaned install hides later uploads
     * from the list). The delete affordance is a JS modal anchor, so cleanup
     * uses a direct DELETE rather than a followed affordance.
     */
    #[Alps('doDeleteTemplate')]
    #[Depends('testSelectsTemplate')]
    public function testDeletesInstalledTemplate(string $templateId): void
    {
        $deleted = $this->resource->post('page://self/admin/template/template-list?_method=delete', [
            'templateId' => $templateId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertTrue(
            in_array($deleted->code, [Code::OK, Code::SEE_OTHER], true),
            (string) ($deleted->view ?? $deleted->code),
        );

        $list = $this->resource->get('page://self/admin/template/template-list');
        $this->assertStringNotContainsString(self::$templateCode, (string) ($list->view ?? ''));
    }
}
