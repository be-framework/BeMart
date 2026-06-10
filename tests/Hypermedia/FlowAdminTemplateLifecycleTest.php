<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Dev\Http\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function random_bytes;

class FlowAdminTemplateLifecycleTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-template-lifecycle';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-template-csrf-token';

    private static string $templateCode;
    private static string|null $templateId = null;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$templateCode = 'workflow-' . bin2hex(random_bytes(4));
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

        return self::$dbSession->resource();
    }

    #[Alps('goTemplateList')]
    public function testTemplateList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/template/template-list');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('goTemplateInstall')]
    #[Depends('testTemplateList')]
    public function testTemplateInstall(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTemplateInstall');
    }

    #[Alps('doInstallTemplate')]
    #[Depends('testTemplateInstall')]
    public function testInstallsTemplate(ResourceObject $response): ResourceObject
    {
        $installed = $this->resource->post($this->linkHref($response, 'doInstallTemplate'), [
            'templateCode' => self::$templateCode,
            'templateName' => 'Workflow Template',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $installed->code);
        $templateId = $this->bodyValue($installed, 'templateId');
        $this->assertIsString($templateId);
        self::$templateId = $templateId;

        return $installed;
    }

    #[Alps('doSelectTemplate')]
    #[Depends('testInstallsTemplate')]
    public function testSelectsTemplate(ResourceObject $response): ResourceObject
    {
        $templateId = self::$templateId;
        $this->assertIsString($templateId);

        $selected = $this->resource->put($this->linkHref($response, 'doSelectTemplate'), [
            'templateId' => $templateId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $selected->code);
        $this->assertSame($templateId, $this->bodyValue($selected, 'templateId'));

        return $this->followLocation($selected);
    }

    #[Alps('doDownloadTemplate')]
    #[Depends('testSelectsTemplate')]
    public function testDownloadsTemplate(ResourceObject $response): ResourceObject
    {
        $templateId = self::$templateId;
        $this->assertIsString($templateId);

        $downloaded = $this->resource->post($this->linkHref($response, 'doDownloadTemplate'), [
            'templateId' => $templateId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $downloaded->code);
        $this->assertSame('application/zip', $this->header($downloaded, 'Content-Type'));

        return $response;
    }

    #[Alps('doDeleteTemplate')]
    #[Depends('testDownloadsTemplate')]
    public function testDeletesTemplate(ResourceObject $response): ResourceObject
    {
        $templateId = self::$templateId;
        $this->assertIsString($templateId);

        $deleted = $this->resource->delete($this->linkHref($response, 'doDeleteTemplate'), [
            'templateId' => $templateId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame($templateId, $this->bodyValue($deleted, 'templateId'));

        return $deleted;
    }

    #[Alps('goTemplateList')]
    #[Depends('testDeletesTemplate')]
    public function testReturnsToTemplateList(ResourceObject $response): void
    {
        $this->follow($response, 'goTemplateList');
    }
}
