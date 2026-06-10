<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminCalendarForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin 定休日カレンダー Setting/Shop Tier-2 page.
 *
 * The resource is a thin GET renderer for the EC-CUBE holiday-calendar
 * editor. BeMart has no calendar master transition/storage in this
 * wave, so the page exposes a renderer seed body shape only. The AUTHZ
 * guard rejects anonymous admins with 403.
 */
final class AdminCalendarResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsCalendarForm(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/calendar');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminCalendarForm::class, $ro->body['form']);
        $this->assertCount(1, $ro->body['calendars']);
        $this->assertSame('元日', $ro->body['calendars'][0]['title']);
        $this->assertInstanceOf(AdminCalendarForm::class, $ro->body['calendars'][0]['form']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/calendar');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPostUpdateCalendarSurface(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->post('page://self/admin/calendar', [
            'operation' => 'update',
            'calendarId' => 1,
            'title' => '元日',
            'holiday' => '2026-01-01',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doUpdateCalendar', $ro->body['transitionId']);
        $this->assertSame('元日', $ro->body['title']);
    }

    public function testOnPostCreateCalendarHolidaySurface(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->post('page://self/admin/calendar', [
            'operation' => 'create',
            'title' => '建国記念の日',
            'holiday' => '2026-02-11',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('doCreateCalendarHoliday', $ro->body['transitionId']);
    }

    public function testOnDeleteCalendarHolidaySurface(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->delete('page://self/admin/calendar', [
            'calendarId' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doDeleteCalendarHoliday', $ro->body['transitionId']);
        $this->assertSame(1, $ro->body['calendarId']);
    }

}
