<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use BEAR\AppMeta\Meta;
use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\ResourceInterface;
use FilesystemIterator;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Module\ObserveModule;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function dirname;
use function file_get_contents;
use function is_dir;
use function json_decode;
use function json_encode;
use function rmdir;
use function str_starts_with;
use function substr;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * Proves issue #134: a body {@see ObserveModule} wrote in one session is still readable after a
 * later injector creation — the same shape as a second `php bin/observe.php` process starting
 * while the log from an earlier one is still retained. Before the fix,
 * `FileBodyStore::clearDirectory()` wiped the single shared `es-bodies` directory on every
 * `ObserveModule::configure()` run, so an earlier session's `body_ref` stopped resolving (or, if
 * the two sessions' sequence numbers collided, silently pointed at the wrong bytes) the moment a
 * second session's injector was built.
 *
 * Uses the real {@see ObserveModule} (not a duplicated wiring) since the fix lives in its
 * generation-key/retention logic; only the logger and the resource-object/etag pools are
 * overridden, matching how `check.php`'s own diagnostics keep a pool inside one process
 * (ArrayAdapter) instead of depending on — or polluting — the on-disk pool a real CLI run uses.
 *
 * @psalm-suppress MixedArrayAccess,MixedArgument The canonical JSON tree view is untyped by design.
 */
final class ObserveModuleBodyRetentionTest extends TestCase
{
    private const string CONTEXT = 'test-observe-body-retention';

    protected function tearDown(): void
    {
        self::removeTree(dirname(__DIR__, 2) . '/var/log/' . self::CONTEXT);
        self::removeTree(dirname(__DIR__, 2) . '/var/tmp/' . self::CONTEXT);
    }

    private function buildSession(SemanticLoggerInterface $logger): ResourceInterface
    {
        $meta = new Meta('MyVendor\\BeMart', self::CONTEXT);
        $base = new TestModule($meta);
        $observeModule = new ObserveModule($meta, $base);

        $wrapper = new class ($logger, $observeModule) extends AbstractModule {
            public function __construct(
                private readonly SemanticLoggerInterface $logger,
                AbstractModule $module,
            ) {
                parent::__construct($module);
            }

            protected function configure(): void
            {
                $this->bind(SemanticLoggerInterface::class)->toInstance($this->logger);
                // TestModule's DevModule chain wraps BecomingInterface in DevBecoming, which
                // flushes the semantic logger after every becoming — the same conflict
                // ObserveModule's own docblock keeps it out of `dev` for. Undo it here so a
                // resource that runs a Be transition (e.g. page://self/product) still closes
                // its resource_request scope instead of finding it already flushed out from
                // under it.
                $this->bind(BecomingInterface::class)->to(Becoming::class);
                // Fresh, process-local pools per session: DevPoolProvider's on-disk
                // FilesystemAdapter is what makes a real CLI run's second request a hit —
                // exactly what an isolated test must not depend on, or leave behind.
                $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)
                    ->toInstance(new ArrayAdapter());
                $this->bind(AdapterInterface::class)->annotatedWith(EtagPool::class)
                    ->toInstance(new ArrayAdapter());
            }
        };

        $injector = new Injector($wrapper, dirname(__DIR__, 2) . '/var/tmp/test');

        return $injector->getInstance(ResourceInterface::class);
    }

    /** @return array<string, mixed> */
    private static function flushToTree(SemanticLoggerInterface $logger): array
    {
        /** @var array<string, mixed> */
        return json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private static function bodyPath(string $bodyRef): string
    {
        return str_starts_with($bodyRef, 'file://') ? substr($bodyRef, 7) : $bodyRef;
    }

    private static function removeTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            /** @var FilesystemIterator $file phpcs and psalm both accept the SPL leaf type here */
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }

    public function testBodyWrittenInOneInjectorGenerationSurvivesALaterInjectorCreation(): void
    {
        $logger1 = new SemanticLogger();
        $resource1 = $this->buildSession($logger1);

        $ro1 = $resource1->get('page://self/products');
        $this->assertSame(200, $ro1->code, 'fixture must actually reach the products page');

        $tree1 = self::flushToTree($logger1);
        $bodyRef1 = $tree1['open'][0]['open'][0]['close']['context']['body_ref'] ?? null;
        $this->assertIsString($bodyRef1, 'the nested app:// call must record a body_ref');
        $bodyPath1 = self::bodyPath($bodyRef1);
        $this->assertFileExists($bodyPath1, 'the body this session just wrote must exist right after writing it');
        $bytesBefore = (string) file_get_contents($bodyPath1);

        // A later injector creation: a brand new ObserveModule::configure() run, the same shape
        // as a second `php bin/observe.php` process starting. Same context, so the same
        // logDir/es-bodies root — the exact scenario issue #134 broke: FileBodyStore's
        // clearDirectory() wiped every earlier session's bodies out from under this second run.
        //
        // This session's own request records no body at all (page://self/product resolves its
        // data through a direct MediaQuery, no nested app:// call — see
        // ExcludedResponseBodyStore's own contract for why the outer page:// response itself
        // never gets a body_ref), so nothing in this session can recreate session 1's file by
        // coincidence: if it is gone after this session, this session's own clearing broke it.
        $logger2 = new SemanticLogger();
        $resource2 = $this->buildSession($logger2);

        $ro2 = $resource2->get('page://self/product', ['productCode' => 'sample-001']);
        $this->assertSame(200, $ro2->code, 'second session fixture must actually reach the product page');
        $tree2 = self::flushToTree($logger2);
        $this->assertArrayNotHasKey(
            'body_ref',
            $tree2['open'][0]['close']['context'],
            'fixture precondition: this session must itself write zero bodies, so a survival check '
            . 'right after it cannot pass by the second session coincidentally recreating the same file',
        );

        $this->assertFileExists(
            $bodyPath1,
            "the first session's body_ref must still resolve after a later injector creation that wrote no "
            . 'bodies of its own (issue #134)',
        );
        $this->assertSame(
            $bytesBefore,
            (string) file_get_contents($bodyPath1),
            "the first session's body content must be unchanged",
        );

        // A third session that does write a body must land in its own generation, never reusing
        // (and so overwriting) the first session's numbered file.
        $logger3 = new SemanticLogger();
        $resource3 = $this->buildSession($logger3);

        $ro3 = $resource3->get('page://self/products');
        $this->assertSame(200, $ro3->code, 'third session fixture must also reach the products page');
        $tree3 = self::flushToTree($logger3);
        $bodyRef3 = $tree3['open'][0]['open'][0]['close']['context']['body_ref'] ?? null;
        $this->assertIsString($bodyRef3, 'the third session must also record its own body_ref');
        $bodyPath3 = self::bodyPath($bodyRef3);
        $this->assertNotSame($bodyPath1, $bodyPath3, 'each body-writing session must use its own generation');

        $this->assertFileExists($bodyPath1, "the first session's body_ref must still resolve after a third session");
        $this->assertSame(
            $bytesBefore,
            (string) file_get_contents($bodyPath1),
            "the first session's body content must remain unchanged after a third session",
        );
    }
}
