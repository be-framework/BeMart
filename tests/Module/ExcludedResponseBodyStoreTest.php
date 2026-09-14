<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\AppMeta\Meta;
use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceInterface;
use FilesystemIterator;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Module\AppParamsFilter;
use MyVendor\BeMart\Module\ExcludedResponseBodyStore;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;

use function dirname;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function sys_get_temp_dir;
use function uniqid;

use const JSON_THROW_ON_ERROR;

/**
 * Proves {@see ExcludedResponseBodyStore} never persists a `page://` response body — not
 * asserted against the class in isolation, since the risk this closes is specifically what
 * happens with a real FileBodyStore against BeMart's own resources, wired the same way
 * ObserveModule wires its invoker and body store (not against ObserveModule itself; buildGraph()
 * below duplicates that wiring in an anonymous module so it can inject a controllable temp
 * directory and logger). Assertions read the log itself for whether a `body_ref` was recorded
 * (the property ExcludedResponseBodyStore actually controls) and separately scan every file the
 * run actually wrote for the exact secret value, so a passing "no leak" result also survives a
 * nested `app://` call the page wraps writing its own unrelated file into the same directory.
 *
 * @psalm-suppress MixedArrayAccess,MixedArgument The canonical JSON tree view is untyped by design.
 */
final class ExcludedResponseBodyStoreTest extends TestCase
{
    private string|null $bodyDir = null;

    protected function tearDown(): void
    {
        try {
            if ($this->bodyDir !== null) {
                FileBodyStore::clearDirectory($this->bodyDir);
                self::assertTrue(rmdir($this->bodyDir), 'temp body directory must be removable after clearing');
            }
        } finally {
            $this->bodyDir = null;
            unset(
                $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
                $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
                $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
            );
        }
    }

    /** @return array{0: ResourceInterface, 1: SemanticLoggerInterface, 2: Injector, 3: string} */
    private function buildGraph(): array
    {
        $bodyDir = sys_get_temp_dir() . '/' . uniqid('bear-es-excluded-response-body-', true);
        FileBodyStore::clearDirectory($bodyDir);
        $this->bodyDir = $bodyDir;
        $logger = new SemanticLogger();

        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));

        $wrapper = new class ($bodyDir, $logger, $base) extends AbstractModule {
            private const string ORIGINAL_INVOKER = 'original_invoker';

            public function __construct(
                private readonly string $bodyDir,
                private readonly SemanticLoggerInterface $logger,
                AbstractModule $module,
            ) {
                parent::__construct($module);
            }

            protected function configure(): void
            {
                $this->rename(InvokerInterface::class, self::ORIGINAL_INVOKER);
                $this->bind(ParamsFilterInterface::class)->annotatedWith(Filtered::class)
                    ->to(AppParamsFilter::class);
                $this->bind(InvokerInterface::class)
                    ->toConstructor(SemanticLogInvoker::class, [
                        'invoker' => self::ORIGINAL_INVOKER,
                        'recordedMethods' => Recorded::class,
                        'paramsFilter' => Filtered::class,
                    ])
                    ->in(Scope::SINGLETON);
                $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
                    ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
                $this->bind(BodyStoreInterface::class)
                    ->toInstance(new ExcludedResponseBodyStore(new FileBodyStore($this->bodyDir)));
                $this->install(new EventSourcingModule());
                $this->bind(SemanticLoggerInterface::class)->toInstance($this->logger);
                $this->bind(BecomingInterface::class)->to(Becoming::class);
            }
        };

        $injector = new Injector($wrapper, dirname(__DIR__, 2) . '/var/tmp/test');

        return [$injector->getInstance(ResourceInterface::class), $logger, $injector, $bodyDir];
    }

    /** @return array<string, mixed> */
    private static function flushToTree(SemanticLoggerInterface $logger): array
    {
        /** @var array<string, mixed> */
        return json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Every file actually written by this run, concatenated, for a secret-absence scan. */
    private static function allWrittenBytes(string $dir): string
    {
        $bytes = '';
        foreach (new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS) as $file) {
            if ($file->isFile()) {
                $bytes .= (string) file_get_contents($file->getPathname());
            }
        }

        return $bytes;
    }

    public function testResetPageResponseIsNeverWrittenToDisk(): void
    {
        [$resource, $logger, , $bodyDir] = $this->buildGraph();
        $sentinel = 'sentinel-reset-key-' . uniqid();

        $ro = $resource->get('page://self/reset', ['resetKey' => $sentinel]);

        $this->assertSame(200, $ro->code, 'fixture must actually reach Reset::onGet');
        $this->assertSame(
            $sentinel,
            $ro->body['resetKey'],
            'the resource still returns resetKey to the real client — only body storage is skipped',
        );

        $tree = self::flushToTree($logger);
        $this->assertArrayNotHasKey(
            'body_ref',
            $tree['open'][0]['close']['context'],
            'a page:// response must never record a body_ref',
        );
        $this->assertStringNotContainsString(
            $sentinel,
            self::allWrittenBytes($bodyDir),
            'resetKey must not appear in any file this run wrote',
        );
    }

    public function testAdminTwoFactorAuthSetPageResponseIsNeverWrittenToDisk(): void
    {
        [$resource, $logger, $injector, $bodyDir] = $this->buildGraph();
        $sentinel = 'sentinel-auth-key-' . uniqid();

        $injector->getInstance(HtmlAdminLoginChallengeAdapter::class)
            ->startSetup('admin-id-001', 'admin-login-001', $sentinel);

        $ro = $resource->get('page://self/admin/two-factor-auth-set');

        $this->assertSame(200, $ro->code, 'fixture must actually reach TwoFactorAuthSet::onGet');
        $this->assertSame(
            $sentinel,
            $ro->body['authKey'],
            'the resource still returns authKey to the real client — only body storage is skipped',
        );

        $tree = self::flushToTree($logger);
        $this->assertArrayNotHasKey(
            'body_ref',
            $tree['open'][0]['close']['context'],
            'a page:// response must never record a body_ref',
        );
        $this->assertStringNotContainsString(
            $sentinel,
            self::allWrittenBytes($bodyDir),
            'authKey must not appear in any file this run wrote, including one a form embeds independent of the body array',
        );
    }

    public function testNestedAppResourceUnderneathAPageIsStillWrittenToDisk(): void
    {
        // Positive control: proves the page:// skip does not also silently stop the nested
        // app:// data resource a page wraps from being stored.
        [$resource, $logger, , $bodyDir] = $this->buildGraph();

        $ro = $resource->get('page://self/products');

        $this->assertSame(200, $ro->code);

        $tree = self::flushToTree($logger);
        $this->assertArrayNotHasKey(
            'body_ref',
            $tree['open'][0]['close']['context'],
            'the outer page:// response must not record a body_ref',
        );
        $this->assertArrayHasKey(
            'body_ref',
            $tree['open'][0]['open'][0]['close']['context'],
            'the nested app:// response must still record a body_ref',
        );
        $this->assertStringNotContainsString(
            'csrfToken',
            self::allWrittenBytes($bodyDir),
            'the page-level csrfToken must not be among the bytes actually written to disk',
        );
    }
}
