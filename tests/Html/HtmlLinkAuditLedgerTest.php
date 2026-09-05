<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Dev\Html\HtmlLinkAuditLoggerInterface;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Smoke\ResourceSmokeTest;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Support\RecordingHtmlLinkAuditLogger;
use Madapaja\TwigModule\Exception\TemplateNotFound;
use Override;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

use function array_diff_key;
use function array_keys;
use function file_get_contents;
use function implode;
use function json_decode;
use function json_encode;
use function ksort;
use function sprintf;
use function str_starts_with;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Every #[Link] a page declares must be reachable in its rendered HTML, or
 * be recorded in the ledger with a classification. See docs/html-link-audit.md.
 *
 * @psalm-type Entry = array{reason: string, classification: string, note?: string}
 * @psalm-type Warning = array{rel: string, method: string, href: string, reason: string}
 */
final class HtmlLinkAuditLedgerTest extends TestCase
{
    private const LEDGER = __DIR__ . '/html-link-audit-ledger.json';
    private const CLASSIFICATIONS = ['resourceOnly', 'fail', 'targetOut'];
    public const ADMIN_ID = 'ad000000000000000000000000000001';

    public function testEveryAuditWarningIsClassified(): void
    {
        $ledger = $this->ledger();
        $observed = $this->observe();
        $this->assertNotSame([], $observed, 'The audit observed nothing; the HTML context is not rendering pages');

        $unclassified = array_diff_key($observed, $ledger);
        $this->assertSame([], $unclassified, "Unclassified HTML link audit warnings; add them to the ledger:\n" . $this->json($unclassified));

        $stale = array_diff_key($ledger, $observed);
        $this->assertSame([], array_keys($stale), "Ledger entries no longer observed; remove them:\n" . implode("\n", array_keys($stale)));

        foreach ($observed as $key => $warning) {
            $this->assertSame($ledger[$key]['reason'], $warning['reason'], sprintf('%s: reason changed', $key));
        }
    }

    public function testLedgerEntriesAreWellFormed(): void
    {
        foreach ($this->ledger() as $key => $entry) {
            $this->assertContains($entry['classification'], self::CLASSIFICATIONS, $key);
            if ($entry['classification'] !== 'targetOut') {
                continue;
            }

            $this->assertNotEmpty($entry['note'] ?? '', sprintf('%s: targetOut requires a note', $key));
        }
    }

    /** @return array<string, Entry> */
    private function ledger(): array
    {
        /** @var array<string, Entry> $ledger */
        $ledger = json_decode((string) file_get_contents(self::LEDGER), true, 512, JSON_THROW_ON_ERROR);

        return $ledger;
    }

    /** @return array<string, Warning> keyed by "<GET page uri> <rel>" */
    private function observe(): array
    {
        $logger = new RecordingHtmlLinkAuditLogger();
        /** @var array<string, ResourceInterface> $resources one per session shape */
        $resources = [];

        $observed = [];
        foreach (ResourceSmokeTest::resourceProvider() as $identity => [$method, $uri, $params, $code, $customerId]) {
            if ($method !== 'GET' || $code !== Code::OK || ! str_starts_with($uri, 'page://self/')) {
                continue;
            }

            $admin = str_starts_with($uri, 'page://self/admin');
            $sessionKey = ($admin ? 'admin' : 'customer') . ':' . ($customerId ?? '');
            $resources[$sessionKey] ??= $this->resource($logger, $admin, $customerId);
            try {
                $resources[$sessionKey]->get($uri, $params)->toString();
            } catch (TemplateNotFound) {
                // Redirect shells and file downloads: no HTML view to audit.
                continue;
            }

            foreach ($logger->drain() as $warning) {
                $observed[$identity . ' ' . $warning['rel']] = $warning;
            }
        }

        ksort($observed);

        return $observed;
    }

    private function resource(RecordingHtmlLinkAuditLogger $logger, bool $admin, string|null $customerId): ResourceInterface
    {
        $module = new class ($logger, $admin, $customerId) extends AbstractModule {
            public function __construct(
                private readonly RecordingHtmlLinkAuditLogger $logger,
                private readonly bool $admin,
                private readonly string|null $customerId,
            ) {
                parent::__construct();
            }

            #[Override]
            protected function configure(): void
            {
                $this->bind(HtmlLinkAuditLoggerInterface::class)->toInstance($this->logger);
                if ($this->admin) {
                    $this->bind(AdminSession::class)->toInstance(new FakeAdminSession(HtmlLinkAuditLedgerTest::ADMIN_ID));
                }

                if ($this->customerId !== null) {
                    $this->bind(CustomerSession::class)->toInstance(new FakeSession($this->customerId));
                }
            }
        };

        return HtmlTestInjector::getOverrideInstance($module)->getInstance(ResourceInterface::class);
    }

    /** @param array<string, mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
