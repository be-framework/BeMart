<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function assert;
use function bin2hex;
use function getenv;
use function putenv;
use function random_bytes;

class FlowAdminTemplateLifecycleTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-template-lifecycle';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-template-csrf-token';

    private static InjectorInterface|null $injector = null;
    private static ExtendedPdoInterface|null $db = null;
    private static ResourceInterface|null $dbResource = null;
    private static string $templateCode;
    private static string|null $templateId = null;
    /** @var array<string, mixed>|null */
    private static array|null $previousSession = null;
    private static string|false $previousCsrfEnv = false;

    public static function setUpBeforeClass(): void
    {
        self::$templateCode = 'workflow-' . bin2hex(random_bytes(4));
        self::$previousSession = $_SESSION ?? null;
        self::$previousCsrfEnv = getenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR);
        $_SESSION = [
            HtmlAdminSessionAdapter::ADMIN_ID_KEY => self::ADMIN_ID,
            EccubeSharedCsrfTokenAdapter::SESSION_KEY => self::CSRF_TOKEN,
        ];
        putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=' . self::CSRF_TOKEN);

        self::$injector = Injector::getInstance('html-prod-hal-api-app');
        $db = self::$injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        self::$db = $db;
        self::$db->beginTransaction();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db instanceof ExtendedPdoInterface && self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        if (self::$previousSession === null) {
            unset($_SESSION);
        } else {
            $_SESSION = self::$previousSession;
        }

        if (self::$previousCsrfEnv === false) {
            putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR);
        } else {
            putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=' . self::$previousCsrfEnv);
        }

        self::$db = null;
        self::$dbResource = null;
        self::$injector = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        if (self::$dbResource instanceof ResourceInterface) {
            return self::$dbResource;
        }

        assert(self::$injector instanceof InjectorInterface);
        $resource = self::$injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        self::$dbResource = $resource;

        return $resource;
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
        $installed = $this->resource->post('page://self/admin/template/template-add', [
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

        $selected = $this->resource->put('page://self/admin/template/template-list', [
            'templateId' => $templateId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $selected->code);
        $this->assertSame($templateId, $this->bodyValue($selected, 'templateId'));

        return $selected;
    }

    #[Alps('doDownloadTemplate')]
    #[Depends('testSelectsTemplate')]
    public function testDownloadsTemplate(ResourceObject $response): ResourceObject
    {
        $templateId = self::$templateId;
        $this->assertIsString($templateId);

        $downloaded = $this->resource->post('page://self/admin/template/template-list', [
            'templateId' => $templateId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $downloaded->code);
        $this->assertSame('application/zip', $this->header($downloaded, 'Content-Type'));

        return $downloaded;
    }

    #[Alps('doDeleteTemplate')]
    #[Depends('testDownloadsTemplate')]
    public function testDeletesTemplate(ResourceObject $response): ResourceObject
    {
        $templateId = self::$templateId;
        $this->assertIsString($templateId);

        $deleted = $this->resource->delete('page://self/admin/template/template-list', [
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
