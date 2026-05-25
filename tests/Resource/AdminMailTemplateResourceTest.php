<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeMailTemplateStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
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
    private FakeMailTemplateStorage $storage;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        $this->storage = $this->injector->getInstance(FakeMailTemplateStorage::class);
    }

    public function testOnPostHappyPathUpdatesSubject(): void
    {
        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => FakeMailTemplateStorage::SEED_ORDER_CONFIRM_ID,
            'mailSubject' => '【更新】ご注文ありがとうございます',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('【更新】ご注文ありがとうございます', $ro->body['mailSubject']);
        $this->assertTrue($ro->body['changed']);
        // fileName and mailTemplateName are preserved from the seed.
        $this->assertSame('Mail/order.twig', $ro->body['fileName']);
        $this->assertSame('注文完了メール', $ro->body['mailTemplateName']);

        $persisted = $this->storage->findById(FakeMailTemplateStorage::SEED_ORDER_CONFIRM_ID);
        $this->assertNotNull($persisted);
        $this->assertSame('【更新】ご注文ありがとうございます', $persisted->subject);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        $seed = $this->storage->findById(FakeMailTemplateStorage::SEED_REGISTER_THANKS_ID);
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
        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => 999,
            'mailSubject' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostEmptySubjectReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => FakeMailTemplateStorage::SEED_ORDER_CONFIRM_ID,
            'mailSubject' => '   ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => FakeMailTemplateStorage::SEED_ORDER_CONFIRM_ID,
            'mailSubject' => 'whatever',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => FakeMailTemplateStorage::SEED_ORDER_CONFIRM_ID,
            'mailSubject' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
