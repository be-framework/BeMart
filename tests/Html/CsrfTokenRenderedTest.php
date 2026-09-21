<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use Madapaja\TwigModule\Exception\TemplateNotFound;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\NullCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Smoke\ResourceSmokeTest;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use Override;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_diff_key;
use function array_keys;
use function file_get_contents;
use function implode;
use function json_decode;
use function ksort;
use function preg_match_all;
use function str_contains;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

/**
 * A rendered CSRF field must carry a token.
 *
 * `EccubeSharedCsrfTokenAdapter::isValid()` rejects an empty string, so a form
 * whose hidden `csrfToken` renders empty answers 403 on submit. Templates write
 * `{{ csrfToken|default('') }}`, which degrades to `value=""` when the resource
 * never published the field — that is how the admin ブロック and 税率 inline
 * create forms stayed dead while per-page tests asserted only the field name.
 *
 * The bound adapter returns a fixed non-empty token, so an empty value can only
 * mean the resource body lacks `csrfToken`. #139 closed the ledger: every page
 * publishes the field from the `CsrfTokenInterface` port now, including the
 * three pages (`admin/category/category-list`, `admin/product/csv-category`,
 * `admin/product/csv-class-name`) that used to fill the field from the
 * `csrf_token()` Twig function instead — a helper that reads `$_SESSION`
 * directly and so rendered non-empty while the resource published nothing,
 * invisible to this sweep. The ledger stays as a regression guard: a page
 * whose resource stops publishing the field, or a template that reaches for
 * `csrf_token()` again, fails here instead of at submit time in production.
 *
 * `reason` values a re-opened entry may use:
 *
 *   nullByDesign  the resource sets `csrfToken => null` on purpose, waiting for
 *                 the EC-CUBE EventListener that mirrors the Symfony token into
 *                 the session (see EccubeSharedCsrfTokenAdapter's docblock).
 *   notPublished  the resource simply never publishes the field.
 *
 * @psalm-type Entry = array{fields: int, reason: string}
 */
final class CsrfTokenRenderedTest extends TestCase
{
    private const LEDGER = __DIR__ . '/csrf-empty-token-ledger.json';
    private const REASONS = ['nullByDesign', 'notPublished'];
    public const ADMIN_ID = 'ad000000000000000000000000000001';
    public const CUSTOMER_ID = 'customer-001';

    public function testEveryPageWithAnEmptyCsrfFieldIsRecorded(): void
    {
        $observed = $this->observe();
        $ledger = $this->ledger();

        $unrecorded = array_diff_key($observed, $ledger);
        $this->assertSame(
            [],
            array_keys($unrecorded),
            "Pages newly rendering an empty csrfToken field; publish csrfToken from the resource:\n"
            . implode("\n", array_keys($unrecorded)),
        );

        $stale = array_diff_key($ledger, $observed);
        $this->assertSame(
            [],
            array_keys($stale),
            "Ledger entries no longer observed; remove them:\n" . implode("\n", array_keys($stale)),
        );
    }

    /**
     * The regex sweep in {@see testEveryPageWithAnEmptyCsrfFieldIsRecorded} only catches an
     * *empty* rendered field; a template that fills it via the `csrf_token()` (or
     * `csrf_token_for_anchor()`) Twig function instead of the resource-published value renders
     * non-empty and slips past that sweep entirely (that was the #139 blindspot for
     * `admin/category/category-list`, `admin/product/csv-category`,
     * `admin/product/csv-class-name`). Guard the closed ledger directly, two ways:
     *
     *  1. Structurally: {@see \MyVendor\BeMart\Module\BeMartTwigExtension} must not register a
     *     Twig function whose name starts with `csrf_token` at all — this catches any future
     *     $_SESSION-reading variant by shape, not by name list.
     *  2. Textually: a regex sweep over every template source, as a second line of defence in
     *     case a call site is ever wired through a differently-named Twig registration.
     */
    public function testNoTemplateFallsBackToTheSessionReadingCsrfHelper(): void
    {
        $extension = new BeMartTwigExtension();
        $functionNames = [];
        foreach ($extension->getFunctions() as $function) {
            $functionNames[] = $function->getName();
        }

        foreach ($functionNames as $name) {
            $this->assertFalse(
                str_starts_with($name, 'csrf_token'),
                "BeMartTwigExtension exposes a \$_SESSION-reading csrf_token* Twig function ('{$name}'); " .
                'templates must use the resource-published csrfToken value instead.',
            );
        }

        $offenders = [];
        foreach ($this->twigFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match('/\bcsrf_token\w*\s*\(/', $contents) === 1) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Template(s) fill csrfToken via the \$_SESSION-reading Twig helper instead of the resource-published value:\n"
            . implode("\n", $offenders),
        );
    }

    public function testLedgerEntriesAreWellFormed(): void
    {
        $ledger = $this->ledger();

        // Validate first, assert closed-state last: if a failing assertSame([], $ledger) ran
        // *before* this loop, a reopened entry would never get its reason checked - exactly the
        // silent-masking bug this ordering avoids. The final assertSame always fires (0 or more
        // loop iterations don't affect it), so this PR's closed-ledger claim stays a real,
        // non-vacuous assertion instead of "no entries, so no assertions ran".
        foreach ($ledger as $key => $entry) {
            $this->assertContains($entry['reason'], self::REASONS, $key);
        }

        $this->assertSame([], $ledger, 'The csrf-empty-token ledger reopened; entries above were still checked for a valid reason.');
    }

    /** @return array<string, Entry> */
    private function ledger(): array
    {
        /** @var array<string, Entry> $ledger */
        $ledger = json_decode((string) file_get_contents(self::LEDGER), true, 512, JSON_THROW_ON_ERROR);

        return $ledger;
    }

    /** @return array<string, int> page identity => empty field count */
    private function observe(): array
    {
        /** @var array<string, ResourceInterface> $resources one per session shape */
        $resources = [];
        $observed = [];

        foreach (ResourceSmokeTest::resourceProvider() as $identity => [$method, $uri, $params, $code, $customerId]) {
            if ($method !== 'GET' || $code !== Code::OK || ! str_starts_with($uri, 'page://self/')) {
                continue;
            }

            $admin = str_starts_with($uri, 'page://self/admin');
            $sessionKey = ($admin ? 'admin' : 'customer') . ':' . ($customerId ?? '');
            $resources[$sessionKey] ??= $this->resource($admin, $customerId);
            try {
                $html = $resources[$sessionKey]->get($uri, $params)->toString();
            } catch (TemplateNotFound) {
                // Redirect shells and file downloads have no HTML view.
                continue;
            }

            $empty = preg_match_all('/name="csrfToken"\s+value=""/', $html);
            if ($empty === 0 || $empty === false) {
                continue;
            }

            $observed[$identity] = $empty;
        }

        ksort($observed);

        return $observed;
    }

    private function resource(bool $admin, string|null $customerId): ResourceInterface
    {
        $module = new class ($admin, $customerId) extends AbstractModule {
            public function __construct(
                private readonly bool $admin,
                private readonly string|null $customerId,
            ) {
                parent::__construct();
            }

            #[Override]
            protected function configure(): void
            {
                $this->bind(CsrfTokenInterface::class)->toInstance(new NullCsrfToken());
                $this->bind(AdminSession::class)->toInstance(new FakeAdminSession($this->admin ? CsrfTokenRenderedTest::ADMIN_ID : null));
                $this->bind(CustomerSession::class)->toInstance(new FakeSession($this->customerId ?? CsrfTokenRenderedTest::CUSTOMER_ID));
            }
        };

        return HtmlTestInjector::getOverrideInstance($module)->getInstance(ResourceInterface::class);
    }

    /** @return list<SplFileInfo> */
    private function twigFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../var/templates'),
        );
        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() === 'twig') {
                $files[] = $file;
            }
        }

        return $files;
    }
}
