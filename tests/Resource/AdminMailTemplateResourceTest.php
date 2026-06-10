<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 8 — doUpdateMailTemplate resource coverage.
 */
final class AdminMailTemplateResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private Injector $injector;
    private MailTemplateStorageInterface $storage;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        $this->storage = $this->injector->getInstance(MailTemplateStorageInterface::class);
    }

    public function testOnPostHappyPathUpdatesSubject(): void
    {
        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => 1,
            'mailSubject' => '【更新】ご注文ありがとうございます',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('【更新】ご注文ありがとうございます', $ro->body['mailSubject']);
        $this->assertTrue($ro->body['changed']);
        // fileName and mailTemplateName are preserved from the seed.
        $this->assertSame('Mail/order.twig', $ro->body['fileName']);
        $this->assertSame('注文完了メール', $ro->body['mailTemplateName']);

        // Persistence read-back belongs to the SQL suite. Fake context is
        // static Ray.FakeQuery fixtures and does not mutate query state.
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        $seed = $this->storage->item(2);
        $this->assertNotNull($seed);

        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => $seed->mailTemplateId,
            'mailSubject' => $seed->subject,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostUnknownIdReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException::class);

        $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => 999,
            'mailSubject' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostEmptySubjectReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => 1,
            'mailSubject' => '   ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => 1,
            'mailSubject' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnDeleteSurfaceReturnsTemplateIdentity(): void
    {
        $ro = $this->resource->delete('page://self/admin/mail-template', [
            'mailTemplateId' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doDeleteMailTemplate', $ro->body['transitionId']);
        $this->assertSame(1, $ro->body['mailTemplateId']);
        $this->assertSame('Mail/order.twig', $ro->body['fileName']);
    }

    public function testOnDeleteUnknownTemplateReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/mail-template', [
            'mailTemplateId' => 999,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
