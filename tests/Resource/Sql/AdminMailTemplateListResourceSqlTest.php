<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

use function array_column;

/**
 * SQL-backed hypermedia coverage for the admin MailTemplate GET
 * endpoint — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminMailTemplateListResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/mail-template`), same body-shape
 * assertions, same AUTHZ branch. The only difference is the storage
 * binding (MailTemplateStorageInterface → SqlMailTemplateStorage)
 * layered via the base class's sqlOverrideModule; the list is read
 * from the real dtb_mail_template table.
 *
 * The Fake-backed sibling relies on FakeMailTemplateStorage's two
 * built-in seed rows. The SQL side has no built-in seed — each test
 * seeds its own rows via {@see SqlFixturesTrait::insertMailTemplate}.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings.
 */
final class AdminMailTemplateListResourceSqlTest extends AbstractResourceSqlTestCase
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
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnGetReturnsSeededTemplates(): void
    {
        $orderId = $this->insertMailTemplate([
            'name' => '注文完了メール',
            'file_name' => 'Mail/order.twig',
            'mail_subject' => 'ご注文ありがとうございます',
        ]);
        $registerId = $this->insertMailTemplate([
            'name' => '会員登録完了メール',
            'file_name' => 'Mail/entry.twig',
            'mail_subject' => 'ご登録ありがとうございます',
        ]);

        $ro = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);

        $ids = array_column($ro->body['mailTemplates'], 'mailTemplateId');
        $this->assertContains($orderId, $ids);
        $this->assertContains($registerId, $ids);

        // Shape check — required projection fields are present.
        foreach ($ro->body['mailTemplates'] as $row) {
            $this->assertArrayHasKey('mailTemplateId', $row);
            $this->assertArrayHasKey('mailTemplateName', $row);
            $this->assertArrayHasKey('fileName', $row);
            $this->assertArrayHasKey('mailSubject', $row);
        }
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
