<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException;
use MyVendor\BeMart\Be\Exception\MasterRowNotFoundException;
use MyVendor\BeMart\Be\Exception\MasterTypeFormatException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\SortNoMoved;
use MyVendor\BeMart\Be\Final\VisibleToggled;
use MyVendor\BeMart\Be\Input\SortNoMoveInput;
use MyVendor\BeMart\Be\Input\ToggleVisibleInput;
use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeClassCategoryStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeDeliveryStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeNewsStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakePaymentMethodAdminStorage;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeTagStorage;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 ALPS-audit remediation — the two generic admin-list
 * transitions doSortNoMove / doToggleVisible (domain layer).
 *
 * The masters are all bound as Fake singletons by AppModule, so this
 * test seeds rows directly through those singletons and only overrides
 * the admin session. AdminMasterRegistry routes the abstract transition
 * to the right storage keyed by `masterType`.
 */
final class AdminMasterListTransitionsTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private FakePaymentMethodAdminStorage $payments;
    private FakeDeliveryStorage $deliveries;
    private FakeTagStorage $tags;
    private FakeClassCategoryStorage $classCategories;
    private FakeNewsStorage $news;

    protected function setUp(): void
    {
        $this->payments = new FakePaymentMethodAdminStorage();
        $this->deliveries = new FakeDeliveryStorage();
        $this->tags = new FakeTagStorage();
        $this->classCategories = new FakeClassCategoryStorage();
        $this->news = new FakeNewsStorage();
        $this->bindAs(self::TEST_ADMIN_ID);
    }

    /**
     * Bind both the storage interface and its concrete Fake class to
     * the SAME instance — the AdminMasterRegistry resolves the
     * interface while the test introspects the concrete class, and a
     * linked `to()->in(Singleton)` binding would hand the two lookups
     * separate objects (the Ray.Di gotcha documented in AppModule).
     */
    private function bindAs(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class (
            $session,
            $this->payments,
            $this->deliveries,
            $this->tags,
            $this->classCategories,
            $this->news,
        ) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakePaymentMethodAdminStorage $payments,
                private readonly FakeDeliveryStorage $deliveries,
                private readonly FakeTagStorage $tags,
                private readonly FakeClassCategoryStorage $classCategories,
                private readonly FakeNewsStorage $news,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(PaymentMethodAdminStorageInterface::class)->toInstance($this->payments);
                $this->bind(FakePaymentMethodAdminStorage::class)->toInstance($this->payments);
                $this->bind(DeliveryStorageInterface::class)->toInstance($this->deliveries);
                $this->bind(FakeDeliveryStorage::class)->toInstance($this->deliveries);
                $this->bind(TagStorageInterface::class)->toInstance($this->tags);
                $this->bind(FakeTagStorage::class)->toInstance($this->tags);
                $this->bind(ClassCategoryStorageInterface::class)->toInstance($this->classCategories);
                $this->bind(FakeClassCategoryStorage::class)->toInstance($this->classCategories);
                $this->bind(NewsStorageInterface::class)->toInstance($this->news);
                $this->bind(FakeNewsStorage::class)->toInstance($this->news);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    // ---- doSortNoMove --------------------------------------------------

    public function testSortNoMoveReordersPaymentMaster(): void
    {
        $payments = $this->payments;
        $payments->put(new PaymentMethodAdminEntity('1', '代金引換', 0, null, null, true));

        $final = ($this->becoming)(new SortNoMoveInput(
            masterType: 'payment',
            rowId: '1',
            sortNo: 7,
        ));

        $this->assertInstanceOf(SortNoMoved::class, $final);
        $this->assertSame('payment', $final->masterType);
        $this->assertSame('1', $final->rowId);
        $this->assertSame(7, $final->sortNo);
        $this->assertSame(7, $payments->sortNoOf('1'));
    }

    public function testSortNoMoveReordersTagMaster(): void
    {
        $tags = $this->tags;
        $tags->put(new TagEntity('t1', 'New'));

        $final = ($this->becoming)(new SortNoMoveInput(
            masterType: 'tag',
            rowId: 't1',
            sortNo: 3,
        ));

        $this->assertInstanceOf(SortNoMoved::class, $final);
        $this->assertSame(3, $tags->sortNoOf('t1'));
    }

    public function testSortNoMoveIsIdempotent(): void
    {
        $deliveries = $this->deliveries;
        $deliveries->put(new DeliveryEntity('d1', '宅配便', true));

        $first = ($this->becoming)(new SortNoMoveInput('delivery', 'd1', 5));
        $second = ($this->becoming)(new SortNoMoveInput('delivery', 'd1', 5));

        $this->assertSame(5, $first->sortNo);
        $this->assertSame(5, $second->sortNo);
        $this->assertSame(5, $deliveries->sortNoOf('d1'));
    }

    public function testSortNoMoveRejectsUnknownRow(): void
    {
        $this->expectException(MasterRowNotFoundException::class);
        ($this->becoming)(new SortNoMoveInput('tag', 'no-such-row', 1));
    }

    public function testSortNoMoveRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $tags = $this->tags;
        $tags->put(new TagEntity('t1', 'New'));

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new SortNoMoveInput('tag', 't1', 1));
    }

    public function testSortNoMoveRejectsUnknownMasterType(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new SortNoMoveInput('bogusMaster', 'x', 1));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                MasterTypeFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testSortNoMoveRejectsMasterWithoutSortNoColumn(): void
    {
        // `news` is a known master (passes the Semantic) but dtb_news
        // has no sort_no column — the registry rejects the operation.
        $news = $this->news;
        $news->put(new NewsEntity('n1', 'Hi', null, null, '2026-01-01T00:00:00+09:00', false));

        $this->expectException(MasterOperationNotSupportedException::class);
        ($this->becoming)(new SortNoMoveInput('news', 'n1', 1));
    }

    public function testSortNoMoveRejectsOutOfRangeSortNo(): void
    {
        $tags = $this->tags;
        $tags->put(new TagEntity('t1', 'New'));

        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new SortNoMoveInput('tag', 't1', 10000));
    }

    // ---- doToggleVisible ----------------------------------------------

    public function testToggleVisibleFlipsPaymentVisibility(): void
    {
        $payments = $this->payments;
        $payments->put(new PaymentMethodAdminEntity('1', '代金引換', 0, null, null, true));

        $final = ($this->becoming)(new ToggleVisibleInput(
            masterType: 'payment',
            rowId: '1',
            visible: false,
        ));

        $this->assertInstanceOf(VisibleToggled::class, $final);
        $this->assertSame('payment', $final->masterType);
        $this->assertFalse($final->visible);
        // `visible` is projected onto the entity — read it back.
        $row = $payments->getById('1');
        $this->assertNotNull($row);
        $this->assertFalse($row->visible);
    }

    public function testToggleVisibleFlipsClassCategoryVisibility(): void
    {
        $classCategories = $this->classCategories;
        $classCategories->put(new ClassCategoryEntity('cc1', 'axis1', '赤'));

        $final = ($this->becoming)(new ToggleVisibleInput('classCategory', 'cc1', false));

        $this->assertInstanceOf(VisibleToggled::class, $final);
        $this->assertFalse($classCategories->visibleOf('cc1'));
    }

    public function testToggleVisibleFlipsNewsVisibility(): void
    {
        $news = $this->news;
        $news->put(new NewsEntity('n1', 'Hi', null, null, '2026-01-01T00:00:00+09:00', false));

        $final = ($this->becoming)(new ToggleVisibleInput('news', 'n1', false));

        $this->assertInstanceOf(VisibleToggled::class, $final);
        $this->assertFalse($news->visibleOf('n1'));
    }

    public function testToggleVisibleIsIdempotent(): void
    {
        $deliveries = $this->deliveries;
        $deliveries->put(new DeliveryEntity('d1', '宅配便', true));

        $first = ($this->becoming)(new ToggleVisibleInput('delivery', 'd1', false));
        $second = ($this->becoming)(new ToggleVisibleInput('delivery', 'd1', false));

        $this->assertFalse($first->visible);
        $this->assertFalse($second->visible);
        $row = $deliveries->getById('d1');
        $this->assertNotNull($row);
        $this->assertFalse($row->visible);
    }

    public function testToggleVisibleRejectsUnknownRow(): void
    {
        $this->expectException(MasterRowNotFoundException::class);
        ($this->becoming)(new ToggleVisibleInput('delivery', 'no-such-row', true));
    }

    public function testToggleVisibleRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $deliveries = $this->deliveries;
        $deliveries->put(new DeliveryEntity('d1', '宅配便', true));

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ToggleVisibleInput('delivery', 'd1', false));
    }

    public function testToggleVisibleRejectsMasterWithoutVisibleColumn(): void
    {
        // `tag` is a known master but dtb_tag has no visible column.
        $tags = $this->tags;
        $tags->put(new TagEntity('t1', 'New'));

        $this->expectException(MasterOperationNotSupportedException::class);
        ($this->becoming)(new ToggleVisibleInput('tag', 't1', false));
    }

    public function testToggleVisibleRejectsUnknownMasterType(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new ToggleVisibleInput('bogusMaster', 'x', false));
    }
}
