<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowTestSession;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function assert;
use function bin2hex;
use function random_bytes;

class FlowAdminSystemOperationTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-system-operation';

    private const CSRF_TOKEN = 'workflow-system-csrf-token';
    private const ADMIN_PASSWORD = 'admin-test-password-2026';
    private const MEMBER_PASSWORD = 'workflow-member-password-2026';

    private static InjectorInterface|null $injector = null;
    private static ExtendedPdoInterface|null $db = null;
    private static ResourceInterface|null $dbResource = null;
    private static string $adminLoginId;
    private static string $memberLoginId;
    private static string $twoFactorAuthSecret;
    private static WorkflowTestSession|null $session = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$adminLoginId = 'workflow-admin-' . $suffix;
        self::$memberLoginId = 'workflow-member-' . $suffix;
        self::$session = WorkflowTestSession::fromCurrent();
        self::$session->assumeAdminLoggedIn('ad000000000000000000000000000001', self::CSRF_TOKEN);

        self::$injector = Injector::getInstance('html-prod-hal-api-app');
        $db = self::$injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        self::$db = $db;
        self::$db->beginTransaction();

        $resource = self::$injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        self::$dbResource = $resource;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db instanceof ExtendedPdoInterface && self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        self::$session?->restore();

        self::$db = null;
        self::$dbResource = null;
        self::$injector = null;
        self::$session = null;

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

    #[Alps('goAdminLogin')]
    public function testAdminLoginForm(): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/member', [
            'loginId' => self::$adminLoginId,
            'password' => self::ADMIN_PASSWORD,
            'name' => 'Workflow System Admin',
            'authority' => 0,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$adminLoginId, $this->bodyValue($created, 'loginId'));

        $response = $this->resource->get('page://self/admin/login');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doAdminLogin')]
    #[Depends('testAdminLoginForm')]
    public function testAdminLogsIn(ResourceObject $response): ResourceObject
    {
        $loggedIn = $this->resource->post('page://self/admin/login', [
            'loginId' => self::$adminLoginId,
            'password' => self::ADMIN_PASSWORD,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $loggedIn->code);
        $this->assertSame(self::$adminLoginId, $this->bodyValue($loggedIn, 'loginId'));
        assert(self::$session instanceof WorkflowTestSession);
        self::$session->setAdminId((string) $this->bodyValue($loggedIn, 'adminId'));

        return $loggedIn;
    }

    #[Alps('goAdminTop')]
    #[Depends('testAdminLogsIn')]
    public function testAdminTop(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goAdminTop');
    }

    #[Alps('goMemberList')]
    #[Depends('testAdminTop')]
    public function testMemberList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goMemberList');
    }

    #[Alps('doCreateMember')]
    #[Depends('testMemberList')]
    public function testCreatesMember(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post('page://self/admin/member', [
            'loginId' => self::$memberLoginId,
            'password' => self::MEMBER_PASSWORD,
            'name' => 'Workflow Member',
            'authority' => 1,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$memberLoginId, $this->bodyValue($created, 'loginId'));

        return $created;
    }

    #[Alps('goMember')]
    #[Depends('testCreatesMember')]
    public function testMember(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goMember', ['loginId' => self::$memberLoginId]);
    }

    #[Alps('doUpdateMember')]
    #[Depends('testMember')]
    public function testUpdatesMember(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/member', [
            'loginId' => self::$memberLoginId,
            'name' => 'Workflow Member Updated',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$memberLoginId, $this->bodyValue($updated, 'loginId'));

        return $updated;
    }

    #[Alps('doUpdateAuthorityRole')]
    #[Depends('testUpdatesMember')]
    public function testUpdatesAuthorityRole(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => self::$memberLoginId,
            'authority' => 0,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(self::$memberLoginId, $this->bodyValue($updated, 'loginId'));

        return $updated;
    }

    #[Alps('goLoginHistoryList')]
    #[Depends('testUpdatesAuthorityRole')]
    public function testLoginHistoryList(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goLoginHistoryList');
    }

    #[Alps('goSecurity')]
    #[Depends('testLoginHistoryList')]
    public function testSecurity(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goSecurity');
    }

    #[Alps('doUpdateSecurity')]
    #[Depends('testSecurity')]
    public function testUpdatesSecurity(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->put('page://self/admin/security', [
            'adminAllowHosts' => '',
            'adminDenyHosts' => '',
            'frontAllowHosts' => '',
            'frontDenyHosts' => '',
            'trustedHosts' => '^localhost$',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('doUpdateSecurity', $this->bodyValue($updated, 'transitionId'));

        return $updated;
    }

    #[Alps('goTwoFactorAuthSet')]
    #[Depends('testUpdatesSecurity')]
    public function testTwoFactorAuthSet(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTwoFactorAuthSet');
    }

    #[Alps('doSetTwoFactorAuth')]
    #[Depends('testTwoFactorAuthSet')]
    public function testSetsTwoFactorAuth(ResourceObject $response): ResourceObject
    {
        assert(self::$injector instanceof InjectorInterface);
        $twoFactorAuth = self::$injector->getInstance(TwoFactorAuthInterface::class);
        assert($twoFactorAuth instanceof TwoFactorAuthInterface);
        self::$twoFactorAuthSecret = $twoFactorAuth->generateSecret();
        $configured = $this->resource->put('page://self/admin/two-factor-auth-set', [
            'loginId' => self::$adminLoginId,
            'authKey' => self::$twoFactorAuthSecret,
            'deviceToken' => $twoFactorAuth->generateDeviceToken(self::$twoFactorAuthSecret),
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $configured->code);
        $this->assertSame('doSetTwoFactorAuth', $this->bodyValue($configured, 'transitionId'));
        $this->assertSame(self::$adminLoginId, $this->bodyValue($configured, 'loginId'));

        return $configured;
    }

    #[Alps('goTwoFactorAuth')]
    #[Depends('testSetsTwoFactorAuth')]
    public function testTwoFactorAuth(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goTwoFactorAuth');
    }

    #[Alps('doVerifyTwoFactorAuth')]
    #[Depends('testTwoFactorAuth')]
    public function testVerifiesTwoFactorAuth(ResourceObject $response): ResourceObject
    {
        assert(self::$injector instanceof InjectorInterface);
        $twoFactorAuth = self::$injector->getInstance(TwoFactorAuthInterface::class);
        assert($twoFactorAuth instanceof TwoFactorAuthInterface);
        $verified = $this->resource->post('page://self/admin/two-factor-auth', [
            'loginId' => self::$adminLoginId,
            'deviceToken' => $twoFactorAuth->generateDeviceToken(self::$twoFactorAuthSecret),
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $verified->code);
        $this->assertSame('doVerifyTwoFactorAuth', $this->bodyValue($verified, 'transitionId'));
        $this->assertSame(self::$adminLoginId, $this->bodyValue($verified, 'loginId'));

        return $verified;
    }

    #[Alps('goContentCache')]
    #[Depends('testAdminTop')]
    public function testContentCache(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goContentCache');
    }

    #[Alps('doClearCache')]
    #[Depends('testContentCache')]
    public function testClearsCache(ResourceObject $response): ResourceObject
    {
        $cleared = $this->resource->put('page://self/admin/content/cache', [
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $cleared->code);
        $this->assertSame('doClearCache', $this->bodyValue($cleared, 'transitionId'));

        return $cleared;
    }

    #[Alps('goMaintenance')]
    #[Depends('testClearsCache')]
    public function testMaintenance(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goMaintenance');
    }

    #[Alps('doToggleMaintenance')]
    #[Depends('testMaintenance')]
    public function testTogglesMaintenance(ResourceObject $response): ResourceObject
    {
        $toggled = $this->resource->put('page://self/admin/content/maintenance', [
            'enabled' => false,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $toggled->code);
        $this->assertSame('doToggleMaintenance', $this->bodyValue($toggled, 'transitionId'));

        return $toggled;
    }

    #[Alps('goSystemInfo')]
    #[Depends('testTogglesMaintenance')]
    public function testSystemInfo(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goSystemInfo');
    }

    #[Alps('doAdminLogout')]
    #[Depends('testSystemInfo')]
    public function testAdminLogsOut(ResourceObject $response): void
    {
        $loggedOut = $this->resource->post('page://self/admin/logout', [
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $loggedOut->code);
    }
}
