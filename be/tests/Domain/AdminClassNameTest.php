<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ClassNameLabelFormatException;
use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminClassNameListFetched;
use MyVendor\BeMart\Be\Final\ClassNameCreated;
use MyVendor\BeMart\Be\Final\ClassNameDeleted;
use MyVendor\BeMart\Be\Final\ClassNameUpdated;
use MyVendor\BeMart\Be\Input\CreateClassNameInput;
use MyVendor\BeMart\Be\Input\DeleteClassNameInput;
use MyVendor\BeMart\Be\Input\GetAdminClassNameListInput;
use MyVendor\BeMart\Be\Input\UpdateClassNameInput;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeClassNameStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;

/**
 * Wave 7 — admin catalog ClassName transitions (domain layer).
 */
final class AdminClassNameTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private FakeClassNameStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakeClassNameStorage();
        $this->bindAs(self::TEST_ADMIN_ID);
    }

    private function bindAs(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeClassNameStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(ClassNameStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeClassNameStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    private function seed(string $label): string
    {
        $final = ($this->becoming)(new CreateClassNameInput(classNameLabel: $label));
        assert($final instanceof ClassNameCreated);

        return $final->classNameId;
    }

    public function testCreateHappyPathPersistsAxis(): void
    {
        $final = ($this->becoming)(new CreateClassNameInput(classNameLabel: 'Color'));

        $this->assertInstanceOf(ClassNameCreated::class, $final);
        $this->assertSame('Color', $final->name);
        $this->assertNotEmpty($final->classNameId);
        $this->assertNotNull($this->storage->getById($final->classNameId));
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new CreateClassNameInput(classNameLabel: 'Color'));
    }

    public function testCreateRejectsEmptyLabel(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new CreateClassNameInput(classNameLabel: ''));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                ClassNameLabelFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testListReturnsEveryAxis(): void
    {
        $this->seed('Color');
        $this->seed('Size');

        $final = ($this->becoming)(new GetAdminClassNameListInput());

        $this->assertInstanceOf(AdminClassNameListFetched::class, $final);
        $this->assertSame(2, $final->count);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminClassNameListInput());
    }

    public function testUpdateRenamesAxis(): void
    {
        $id = $this->seed('Color');

        $final = ($this->becoming)(new UpdateClassNameInput(
            classNameId: $id,
            classNameLabel: 'Colour',
        ));

        $this->assertInstanceOf(ClassNameUpdated::class, $final);
        $this->assertSame('Colour', $final->name);
    }

    public function testUpdateNullLabelKeepsExisting(): void
    {
        $id = $this->seed('Color');

        $final = ($this->becoming)(new UpdateClassNameInput(
            classNameId: $id,
            classNameLabel: null,
        ));

        $this->assertInstanceOf(ClassNameUpdated::class, $final);
        $this->assertSame('Color', $final->name);
    }

    public function testUpdateRejectsUnknownId(): void
    {
        $this->expectException(ClassNameNotFoundException::class);
        ($this->becoming)(new UpdateClassNameInput(
            classNameId: 'nonexistent-zzz',
            classNameLabel: 'X',
        ));
    }

    public function testUpdateRejectsAnonymousAdmin(): void
    {
        $id = $this->seed('Color');
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateClassNameInput(classNameId: $id, classNameLabel: 'X'));
    }

    public function testDeleteRemovesRow(): void
    {
        $id = $this->seed('Color');

        $final = ($this->becoming)(new DeleteClassNameInput(classNameId: $id));

        $this->assertInstanceOf(ClassNameDeleted::class, $final);
        $this->assertSame($id, $final->classNameId);
        $this->assertNull($this->storage->getById($id));
    }

    public function testDeleteRejectsUnknownId(): void
    {
        $this->expectException(ClassNameNotFoundException::class);
        ($this->becoming)(new DeleteClassNameInput(classNameId: 'nonexistent-zzz'));
    }

    public function testDeleteRejectsAnonymousAdmin(): void
    {
        $id = $this->seed('Color');
        $this->bindAs(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new DeleteClassNameInput(classNameId: $id));
    }
}
