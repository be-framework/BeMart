<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use Madapaja\TwigModule\Exception\TemplateNotFound;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use MyVendor\BeMart\Tests\Fake\Csrf\FakeCsrfToken;
use MyVendor\BeMart\Tests\Smoke\ResourceSmokeTest;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use Override;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_keys;
use function file_get_contents;
use function implode;
use function ksort;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_starts_with;

use const PREG_SET_ORDER;

/**
 * Every rendered POST form carries the session token.
 *
 * Templates fill the hidden `csrfToken` input from the Twig `csrf_token()` function, which
 * reads the bound {@see \BEAR\Csrf\CsrfTokenInterface}. The HTML test context binds
 * {@see FakeCsrfToken}. A POST form without the input is dead on submit in every armed
 * context, and any other value means a template still expects the resource to publish the
 * token — the pre-BEAR.Csrf shape, which rendered `value=""`.
 */
final class CsrfTokenRenderedTest extends TestCase
{
    public const ADMIN_ID = 'ad000000000000000000000000000001';
    public const CUSTOMER_ID = 'customer-001';

    public function testEveryRenderedPostFormCarriesTheSessionToken(): void
    {
        $forms = 0;
        $broken = [];
        foreach ($this->renderedPostForms() as $identity => $tokens) {
            foreach ($tokens as $index => $token) {
                $forms++;
                if ($token !== FakeCsrfToken::DEFAULT_TOKEN) {
                    $broken[] = sprintf('%s form#%d: %s', $identity, $index + 1, $token ?? '(no csrfToken input)');
                }
            }
        }

        $this->assertGreaterThan(0, $forms, 'No page rendered a POST form; the sweep is vacuous.');
        $this->assertSame(
            [],
            $broken,
            "POST forms rendered without the session token:\n" . implode("\n", $broken),
        );
    }

    public function testTemplatesDoNotReadCsrfTokenFromTheResourceBody(): void
    {
        $offenders = [];
        foreach ($this->twigFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match('/\{\{-?\s*csrfToken\b/', $contents) === 1) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Templates must fill csrfToken via csrf_token(), not from the resource body:\n" . implode("\n", $offenders),
        );
    }

    public function testTemplatesSubmittingViaFetchReferenceTheToken(): void
    {
        // Judged per <script> block, comments stripped: a hidden input elsewhere in the
        // page or a comment naming the token must not vouch for a fetch() that omits it.
        $offenders = [];
        foreach ($this->twigFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $contents, $scripts);
            foreach ($scripts[1] as $index => $script) {
                $code = (string) preg_replace(['/\{#.*?#\}/s', '~/\*.*?\*/~s', '~^\s*//.*$~m'], '', $script);
                if (preg_match('/\bfetch\(/', $code) !== 1 || preg_match('/\bmethod:\s*[\'"](POST|PUT|PATCH|DELETE)[\'"]/i', $code) !== 1) {
                    continue;
                }

                if (preg_match('/csrfToken/', $code) !== 1) {
                    $offenders[] = $file->getPathname() . ' <script> #' . ($index + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Scripts that submit with fetch() must send csrfToken like the forms do:\n" . implode("\n", $offenders),
        );
    }

    public function testTheTwigExtensionExposesCsrfToken(): void
    {
        $names = [];
        foreach ((new BeMartTwigExtension(new FakeCsrfToken()))->getFunctions() as $function) {
            $names[] = $function->getName();
        }

        $this->assertContains('csrf_token', $names);
    }

    /**
     * @return array<string, list<string|null>> page identity => csrfToken value of each POST form,
     *                                          null when the form has no csrfToken input
     */
    private function renderedPostForms(): array
    {
        /** @var array<string, ResourceInterface> $resources one per session shape */
        $resources = [];
        $rendered = [];

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

            preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);
            foreach ($forms as [, $attributes, $inner]) {
                if (preg_match('/\bmethod="post"/i', $attributes) !== 1) {
                    continue;
                }

                $rendered[$identity][] = preg_match('/<input\b[^>]*\bname="csrfToken"[^>]*\bvalue="([^"]*)"/', $inner, $value) === 1
                    ? $value[1]
                    : null;
            }
        }

        ksort($rendered);

        return $rendered;
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
