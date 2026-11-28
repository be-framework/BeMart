<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\MethodNotAllowedException;
use BEAR\Resource\Exception\ResourceNotFoundException;
use BEAR\Resource\ResourceInterface;
use DOMDocument;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Smoke\ResourceSmokeTest;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use Madapaja\TwigModule\Exception\TemplateNotFound;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Throwable;

use function array_key_exists;
use function array_keys;
use function dirname;
use function is_file;
use function is_string;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function parse_str;
use function parse_url;
use function ksort;
use function sprintf;
use function str_starts_with;
use function trim;
use function urldecode;

/**
 * Every same-origin `<a href>` of a rendered page must reach a resource that answers a GET.
 *
 * Renders the pages the HTML link audit renders (the GET 200 rows of the resource smoke table) and requests
 * each link target: a target the router does not know (404) or that has no `onGet` (405) is a dead link.
 * Any other outcome, an exception a live page raises included, means the route exists.
 */
final class HtmlDeadLinkTest extends TestCase
{
    /**
     * Targets that are not meant to be fetched with GET.
     *
     * @var array<string, string> href path => reason
     */
    private const NOT_FETCHED_WITH_GET = [];

    public function testNoLinkOfARenderedPageIsDead(): void
    {
        /** @var array<string, ResourceInterface> $resources one per session shape */
        $resources = [];
        /** @var array<string, string> $outcomes target => "dead (404|405)" or "live", fetched once */
        $outcomes = [];
        /** @var array<string, string> $dead "<page> -> <target>" => reason */
        $dead = [];
        foreach (ResourceSmokeTest::resourceProvider() as [$method, $uri, $params, $code, $customerId]) {
            if ($method !== 'GET' || $code !== Code::OK || ! str_starts_with($uri, 'page://self/')) {
                continue;
            }

            $admin = str_starts_with($uri, 'page://self/admin');
            $sessionKey = ($admin ? 'admin' : 'customer') . ':' . ($customerId ?? '');
            $resources[$sessionKey] ??= $this->resource($admin, $customerId);
            try {
                $html = $resources[$sessionKey]->get($uri, $params)->toString();
            } catch (TemplateNotFound) {
                continue; // a redirect shell or a download: no HTML view
            }

            foreach ($this->hrefs($html) as $target) {
                $outcomes[$sessionKey . ' ' . $target] ??= $this->outcome($resources[$sessionKey], $target);
                if ($outcomes[$sessionKey . ' ' . $target] !== 'live') {
                    $dead[sprintf('%s -> %s', $uri, $target)] = $outcomes[$sessionKey . ' ' . $target];
                }
            }
        }

        ksort($dead);

        $this->assertSame([], $dead, 'Dead links: fix the href, or list the target in NOT_FETCHED_WITH_GET with a reason.');
    }

    /** @return list<string> same-origin hrefs, query kept, fragment dropped */
    private function hrefs(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $targets = [];
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            if ($href === '' || $href[0] !== '/' || str_starts_with($href, '//')) {
                continue;
            }

            $path = parse_url($href, PHP_URL_PATH);
            $query = parse_url($href, PHP_URL_QUERY);
            if (! is_string($path) || is_file(dirname(__DIR__, 2) . '/public' . urldecode($path))) {
                continue; // a static file
            }

            if (array_key_exists($path, self::NOT_FETCHED_WITH_GET)) {
                continue;
            }

            $targets[$path . (is_string($query) ? '?' . $query : '')] = true;
        }

        return array_keys($targets);
    }

    private function outcome(ResourceInterface $resource, string $target): string
    {
        $path = (string) parse_url($target, PHP_URL_PATH);
        parse_str((string) parse_url($target, PHP_URL_QUERY), $query);
        try {
            $resource->get('page://self' . $path, $query);
        } catch (ResourceNotFoundException) {
            return 'dead (404)';
        } catch (MethodNotAllowedException) {
            return 'dead (405)';
        } catch (Throwable) {
            return 'live';
        }

        return 'live';
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

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance(new FakeAdminSession($this->admin ? HtmlLinkAuditLedgerTest::ADMIN_ID : null));
                $this->bind(CustomerSession::class)->toInstance(new FakeSession($this->customerId ?? HtmlLinkAuditLedgerTest::CUSTOMER_ID));
            }
        };

        return HtmlTestInjector::getOverrideInstance($module)->getInstance(ResourceInterface::class);
    }
}
