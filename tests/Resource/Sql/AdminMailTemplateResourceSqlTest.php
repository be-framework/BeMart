<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function str_contains;

/**
 * SQL-backed hypermedia coverage for the admin MailTemplate POST
 * endpoint — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminMailTemplateResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/mail-template`), same body-shape
 * assertions, same AUTHN / AUTHZ / CSRF / 404 branches. The only
 * difference is the storage binding (MailTemplateStorageInterface →
 * SqlMailTemplateStorage) layered via the base class's
 * sqlOverrideModule; persistence is against the real dtb_mail_template
 * table.
 *
 * The Fake-backed sibling relies on MailTemplateStorageInterface's two
 * built-in seed rows (SEED_ORDER_CONFIRM_ID = 1,
 * SEED_REGISTER_THANKS_ID = 2). The SQL side has no built-in seed —
 * dtb_mail_template is empty in the structure-only dump — so each test
 * seeds its own row(s) via {@see SqlFixturesTrait::insertMailTemplate}
 * and addresses them by the AUTO_INCREMENT id the fixture returns.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminMailTemplateResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

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
                $this->bind(AdminSessionInterface::class)
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

    public function testOnPostHappyPathUpdatesSubject(): void
    {
        $id = $this->insertMailTemplate([
            'name' => '注文完了メール',
            'file_name' => 'Mail/order.twig',
            'mail_subject' => 'ご注文ありがとうございます',
        ]);

        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => $id,
            'mailSubject' => '【更新】ご注文ありがとうございます',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('【更新】ご注文ありがとうございます', $ro->body['mailSubject']);
        $this->assertTrue($ro->body['changed']);
        // fileName and mailTemplateName are preserved from the seed row.
        $this->assertSame('Mail/order.twig', $ro->body['fileName']);
        $this->assertSame('注文完了メール', $ro->body['mailTemplateName']);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        $id = $this->insertMailTemplate([
            'mail_subject' => 'ご登録ありがとうございます',
        ]);

        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => $id,
            'mailSubject' => 'ご登録ありがとうございます',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostUnknownIdReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => 99999999,
            'mailSubject' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostEmptySubjectReturns400(): void
    {
        $id = $this->insertMailTemplate();

        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => $id,
            'mailSubject' => '   ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $id = $this->insertMailTemplate();

        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => $id,
            'mailSubject' => 'whatever',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $id = $this->insertMailTemplate();
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => $id,
            'mailSubject' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
