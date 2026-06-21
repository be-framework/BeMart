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
 * HTML hypermedia walk of the admin mail-template editor — driven entirely by
 * the rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Ported from tests/Hypermedia/FlowAdminMailTemplateMaintenanceTest.php.
 *
 * Steps walked:
 *   1. testOpensMailTemplatePage  — GET /admin/mail-template (200 + doCreateMailTemplate form)
 *   2. testCreatesMailTemplate    — submit doCreateMailTemplate → 201|303 + Location
 *   3. testSelectsCreatedTemplate — GET /admin/mail-template?mailTemplateId=N → doUpdateMailTemplate
 *   4. testUpdatesMailTemplate    — submit doUpdateMailTemplate → 200|303 + updated subject
 *
 * Steps skipped (no HTML affordance rendered):
 *   - doDeleteMailTemplate: The template renders delete as a modal <a rel="doDeleteMailTemplate">
 *     anchor — not a <form class="doDeleteMailTemplate">, so submit() cannot resolve it.
 *     Deletion requires JavaScript to trigger; it is a GET-navigable link, not a form.
 *   - goOrderMail / goOrderMailConfirm / doSendOrderMail: These are storefront checkout +
 *     order-mail flows that require many setup steps (payment, product, cart, non-member
 *     checkout) not representable as HTML-form affordances within this walk's scope.
 *   - goMailTemplateList after delete: Skipped because doDeleteMailTemplate itself is skipped.
 */
final class FlowAdminMailTemplateMaintenanceTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-mail-template-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-mail-template-html-csrf-token';

    private static string $mailTemplateName;
    private static string $mailSubject;
    private static string $updatedSubject;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$mailTemplateName = 'HTML Mail Template ' . $suffix;
        self::$mailSubject = 'Initial HTML subject ' . $suffix;
        self::$updatedSubject = 'Updated HTML subject ' . $suffix;
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
            '127.0.0.1:8122',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goMailTemplateList')]
    public function testOpensMailTemplatePage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doCreateMailTemplate');
        $this->assertAffordance($page, 'doUpdateMailTemplate');

        return $page;
    }

    #[Alps('doCreateMailTemplate')]
    #[Depends('testOpensMailTemplatePage')]
    public function testCreatesMailTemplate(ResourceObject $page): ResourceObject
    {
        $created = $this->submit($page, 'doCreateMailTemplate', [
            'mailTemplateName' => self::$mailTemplateName,
            'fileName' => 'Mail/html-flow-' . bin2hex(random_bytes(4)) . '.twig',
            'mailSubject' => self::$mailSubject,
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doCreateMailTemplate affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goMailTemplateList')]
    #[Depends('testCreatesMailTemplate')]
    public function testSelectsCreatedTemplate(ResourceObject $created): ResourceObject
    {
        // Resolve the mail-template list page to find the newly created template id.
        $listPage = $this->header($created, 'Location') !== null
            ? $this->followLocation($created)
            : $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::OK, $listPage->code, (string) ($listPage->view ?? $listPage->code));
        $this->assertStringContainsString(self::$mailTemplateName, (string) ($listPage->view ?? ''));

        // Extract the mailTemplateId from the rendered <option> list.
        $view = (string) ($listPage->view ?? '');
        $this->assertSame(
            1,
            preg_match('/<option value="(\d+)">\s*' . preg_quote(self::$mailTemplateName, '/') . '\s*<\/option>/s', $view, $match),
            'newly created template not found in mail-template dropdown',
        );
        $mailTemplateId = (int) $match[1];

        // GET the edit page with the specific template selected.
        $edit = $this->resource->get('page://self/admin/mail-template', ['mailTemplateId' => $mailTemplateId]);

        $this->assertSame(Code::OK, $edit->code, (string) ($edit->view ?? $edit->code));
        $this->assertAffordance($edit, 'doUpdateMailTemplate');

        return $edit;
    }

    #[Alps('doUpdateMailTemplate')]
    #[Depends('testSelectsCreatedTemplate')]
    public function testUpdatesMailTemplate(ResourceObject $edit): void
    {
        // Extract mailTemplateId from the rendered hidden input — onPost requires it.
        $view = (string) ($edit->view ?? '');
        $this->assertSame(
            1,
            preg_match('/name="mailTemplateId"[^>]*value="(\d+)"/i', $view, $idMatch),
            'mailTemplateId hidden input not found in rendered mail-template edit page',
        );
        $mailTemplateId = (int) $idMatch[1];

        // The HTML form renders mail_subject (not mailSubject) as the input name.
        $updated = $this->submit($edit, 'doUpdateMailTemplate', [
            'mailTemplateId' => (string) $mailTemplateId,
            'mail_subject' => self::$updatedSubject,
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateMailTemplate affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        // Follow Location when 303; otherwise check the inline response.
        $result = $updated->code === Code::SEE_OTHER
            ? $this->followLocation($updated)
            : $updated;

        $this->assertSame(Code::OK, $result->code, (string) ($result->view ?? $result->code));
        $this->assertStringContainsString(
            self::$updatedSubject,
            (string) ($result->view ?? ''),
            'Updated subject not found in rendered mail-template page after update',
        );
    }
}
