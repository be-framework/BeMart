<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\AppMeta\Meta;
use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\NullBodyStore;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\SemanticLogExtractorInterface;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;

use function dirname;
use function json_decode;
use function json_encode;

/**
 * Proves bear/event-sourcing extracts BeMart's boundary writes as replayable events, wired the
 * same way ObserveModule decorates InvokerInterface (rename + SemanticLogInvoker), minus the
 * file-based body store and cache-log sink ObserveModule uses for CLI observation: this test
 * binds a bare SemanticLogger and flushes it itself, per the bear-observe skill's guidance for
 * tests that read the log in-process instead of from var/log.
 *
 * RecordedMethods::WITH_READS is bound for #[Recorded] so GET is recorded into the log (making
 * the nested page->app read visible), while #[Extracted] is left at its library default
 * (state-changing only) — this is what actually proves the extractor enforces its own
 * write-only policy independent of what the invoker chose to record.
 *
 * No app-side redaction here: bear/event-sourcing's SemanticLogInvoker filters params by
 * default (SensitiveParamsFilter, unbound #[Filtered] falls back to it), so csrfToken never
 * reaches an extracted event's params — proven below against the real Checkout resource, not
 * asserted in the abstract. Checkout's own params (preOrderId + csrfToken) never include a
 * credential-shaped key, so the request stays `replayable: true` and is extracted normally.
 */
final class EventSourcingExtractionTest extends TestCase
{
    private const string PRE_ORDER_ID = 'aaaa00000000000000000000000000000000aaaa';
    private const string UNKNOWN_PRE_ORDER_ID = 'eeee00000000000000000000000000000000eeee';

    /** @return array{0: ResourceInterface, 1: SemanticLogExtractorInterface, 2: SemanticLoggerInterface} */
    private function buildGraph(string|null $customerId): array
    {
        $logger = new SemanticLogger();

        // Constructor-wrapping (not override()): rename() only rewires the module passed as
        // this module's own $module argument (AbstractModule::$lastModule), so $base must be
        // the wrapped module, matching how ObserveModule itself is chained in a real context.
        //
        // BecomingInterface and SemanticLoggerInterface are rebound back to their plain BeModule
        // targets to undo TestModule's DevModule layer: DevBecoming flushes the logger after
        // every becoming, which would empty this test's shared logger mid-checkout — the same
        // conflict that keeps ObserveModule's own context chain out of `dev`.
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));

        $wrapper = new class ($logger, $customerId, $base) extends AbstractModule {
            private const string ORIGINAL_INVOKER = 'original_invoker';

            public function __construct(
                private readonly SemanticLoggerInterface $logger,
                private readonly string|null $customerId,
                AbstractModule $module,
            ) {
                parent::__construct($module);
            }

            protected function configure(): void
            {
                $this->rename(InvokerInterface::class, self::ORIGINAL_INVOKER);
                $this->bind(InvokerInterface::class)
                    ->toConstructor(SemanticLogInvoker::class, [
                        'invoker' => self::ORIGINAL_INVOKER,
                        'recordedMethods' => Recorded::class,
                    ])
                    ->in(Scope::SINGLETON);
                $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
                    ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
                $this->bind(BodyStoreInterface::class)->to(NullBodyStore::class);
                $this->install(new EventSourcingModule());
                $this->bind(SemanticLoggerInterface::class)->toInstance($this->logger);
                $this->bind(BecomingInterface::class)->to(Becoming::class);
                $this->bind(CustomerSession::class)->toInstance(new FakeSession($this->customerId));
            }
        };

        $injector = new Injector($wrapper, dirname(__DIR__, 2) . '/var/tmp/test');

        return [
            $injector->getInstance(ResourceInterface::class),
            $injector->getInstance(SemanticLogExtractorInterface::class),
            $logger,
        ];
    }

    public function testSuccessfulRootPostExtractsExactlyOneEvent(): void
    {
        [$resource, $extractor, $logger] = $this->buildGraph('customer-001');

        $ro = $resource->post('page://self/shopping/checkout', [
            'preOrderId' => self::PRE_ORDER_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(201, $ro->code, 'fixture must actually succeed for this test to mean anything');
        $this->assertSame('customer-001', $ro->body['customerId']);

        $log = $logger->flush();
        $rawOpen = $log->toTreeArray()['open'] ?? [];
        $this->assertCount(1, $rawOpen, 'a single external request must have exactly one root scope');
        $rootChildren = $rawOpen[0]['open'] ?? [];
        $this->assertNotEmpty(
            $rootChildren,
            'checkout must compose nested Be becoming/being scopes for the single-event '
            . 'assertion below to prove they are excluded, not merely absent',
        );

        $events = $extractor->extract($log);
        $this->assertCount(1, $events, 'root POST plus nested Be transitions must extract to exactly one event');

        $event = [...$events][0];
        $this->assertSame('POST', $event->method);
        $this->assertSame('page://self/shopping/checkout', $event->uri);
        $this->assertSame(self::PRE_ORDER_ID, $event->params['preOrderId']);
        // The bundled resource bridge records code/body_ref/durationMs on close, never an
        // inline body (README: "Events extracted from a bridge log therefore always carry a
        // null result"). With NullBodyStore there is no body_ref either, so result is null —
        // this is documented library behavior, not a wiring gap; the response body itself is
        // already proven above via $ro->body.
        $this->assertNull($event->result);
        $this->assertArrayNotHasKey(
            'csrfToken',
            $event->params,
            'RedactingSemanticLogger must scrub csrfToken before it reaches an extracted event',
        );

        // Re-extracting the same log must reproduce the same id (deterministic identity).
        $again = $extractor->extract($log);
        $this->assertSame($event->id, ([...$again][0])->id);
    }

    public function testFailedRootPostExtractsNoEvents(): void
    {
        [$resource, $extractor, $logger] = $this->buildGraph('customer-001');

        try {
            $resource->post('page://self/shopping/checkout', [
                'preOrderId' => self::UNKNOWN_PRE_ORDER_ID,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
            $this->fail('expected PreOrderNotFoundException for an unknown preOrderId');
        } catch (PreOrderNotFoundException) {
            // expected: SemanticLogInvoker still records a failed resource_response before rethrow.
        }

        $events = $extractor->extract($logger->flush());
        $this->assertCount(0, $events, 'a request that failed (code >= 400) must not become an event');
    }

    public function testReadOnlyRequestExtractsNoEventsEvenThoughItIsRecorded(): void
    {
        [$resource, $extractor, $logger] = $this->buildGraph(null);

        $ro = $resource->get('page://self/products');
        $this->assertSame(200, $ro->code);

        $log = $logger->flush();
        $rawOpen = $log->toTreeArray()['open'] ?? [];
        $this->assertGreaterThanOrEqual(
            1,
            count($rawOpen),
            'GET must still be recorded into the log under RecordedMethods::WITH_READS',
        );

        $events = $extractor->extract($log);
        $this->assertCount(
            0,
            $events,
            'extraction defaults to state-changing methods only, independent of what was recorded',
        );
    }

    public function testCredentialBearingSuccessStaysInTheLogButIsExcludedFromEvents(): void
    {
        // Admin login's params (loginId + password + csrfToken) carry a genuine domain
        // credential, not just transport metadata: SensitiveParamsFilter marks the request
        // non-replayable, and the extractor must exclude it even though it succeeded — the
        // library's own contract this test pins against BeMart's real Admin\Login resource.
        [$resource, $extractor, $logger] = $this->buildGraph(null);

        $ro = $resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'local-dev-admin-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(200, $ro->code, 'fixture must actually succeed for this test to mean anything');

        $log = $logger->flush();
        $tree = json_decode(json_encode($log->toTreeArray()), true);
        $this->assertArrayNotHasKey('password', $tree['open'][0]['context']['params']);
        $this->assertFalse($tree['open'][0]['context']['replayable']);

        $events = $extractor->extract($log);
        $this->assertCount(
            0,
            $events,
            'a successful request with a redacted credential must not become an event, even '
            . 'though it is still visible in the log for audit purposes',
        );
    }
}
