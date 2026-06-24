<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use FilesystemIterator;
use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function escapeshellarg;
use function file_get_contents;
use function implode;
use function preg_match;
use function preg_replace;
use function preg_split;
use function shell_exec;
use function sort;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Walks EVERY storefront/admin GET page through the real SQL HTTP stack and
 * asserts none of them CRASHES.
 *
 * This is the "did you actually walk all ~90 screens?" gate. A green curated
 * suite is not the same as every page rendering — and a schema/template/route
 * change can 500 a page that no targeted test happens to drive. So this test
 * enumerates every Page resource with an onGet (the public URL IS the BEAR
 * resource path — see CanonicalResourceRouter), drives each in the right auth
 * state (anonymous / logged-in member / admin) against the real eccubedb_test
 * stack, and fails loudly on any 500 or leaked framework error / raw weaved
 * class string (the CustomerDeliveryEdit class of bug).
 *
 * Scope honesty: this proves no page CRASHES, NOT that every page shows the
 * correct VALUE. "valid shape but wrong value" bugs (e.g. a member confirm
 * rendering "- - 様") are a 200 and belong to targeted semantic tests +
 * the schema contracts, not here.
 */
final class HttpSqlAllPagesRenderTest extends TestCase
{
    private const HOST = '127.0.0.1:18244';
    private const ADMIN_ID = '1';
    private const MEMBER_EMAIL = 'login-test@example.com';
    private const MEMBER_PASSWORD = 'local-dev-member-password';

    /** Extra params handed to every route so detail/edit pages render instead of 400ing on a missing id. */
    private const PARAM_BAG = 'productCode=sample-001&orderNo=&customerId=4&categoryId=1&category_id=1'
        . '&classNameId=1&classCategoryId=1&blockId=2&layoutId=2&newsId=1&pageId=1&id=1&addressId=1'
        . '&pluginCode=Sample&deliveryId=1&paymentId=1&taxRuleId=1&tagId=1&memberId=1&mailTemplateId=1'
        . '&productId=1&resetKey=x&secretKey=x';

    private static PhpServer|null $server = null;
    private static string $memberJar = '';
    private static string $anonJar = '';

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();

        self::$memberJar = (string) tempnam(sys_get_temp_dir(), 'bemart-allpages-member-');
        self::$anonJar = (string) tempnam(sys_get_temp_dir(), 'bemart-allpages-anon-');

        // Log the member in and put an item in each cart so member-area and
        // checkout pages render their populated state rather than redirecting.
        self::login(self::$memberJar);
        foreach ([self::$memberJar, self::$anonJar] as $jar) {
            self::addToCart($jar);
        }
    }

    public function testEveryGetPageRendersWithoutCrashing(): void
    {
        $routes = $this->getRoutes();
        self::assertGreaterThan(80, count($routes), 'route enumeration looks too small');

        $crashes = [];
        $statuses = [];
        foreach ($routes as $url) {
            $auth = $this->authFor($url);
            $jar = $auth === 'anon' ? self::$anonJar : self::$memberJar;
            $response = $this->get($url, $jar, $auth === 'admin');

            $statuses[$response['status']] = ($statuses[$response['status']] ?? 0) + 1;

            if ($response['status'] === 500 || $this->looksLikeCrash($response['body'])) {
                $crashes[] = sprintf('[%s] %s -> %d', $auth, $url, $response['status']);
            }
        }

        self::assertSame(
            [],
            $crashes,
            sprintf(
                "Page(s) crashed (500 or leaked a framework error / raw class string):\n  %s\n(status spread: %s)",
                implode("\n  ", $crashes),
                implode(' ', array_map(static fn ($k, $v): string => "{$k}:{$v}", array_keys($statuses), $statuses)),
            ),
        );
    }

    private function looksLikeCrash(string $body): bool
    {
        return preg_match(
            '/Fatal error|Uncaught|Internal Server Error|Stack trace|::__|_\d{6,}::|Allowed memory|Call to a member|Typed property|Service Unavailable/',
            $body,
        ) === 1;
    }

    private function authFor(string $url): string
    {
        if (str_starts_with($url, '/admin')) {
            return 'admin';
        }

        if (str_starts_with($url, '/mypage')) {
            return 'member';
        }

        return 'anon';
    }

    /**
     * Every public GET URL = the BEAR resource path of a Page resource with an
     * onGet (CanonicalResourceRouter maps URL -> page://self<path>). Derive each
     * from its file location, kebab-casing the directories and class name.
     *
     * @return list<string>
     */
    private function getRoutes(): array
    {
        $base = __DIR__ . '/../../src/Resource/Page';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        );
        $routes = [];
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            if (! str_contains($contents, 'function onGet')) {
                continue;
            }

            $rel = substr($file->getPathname(), strlen($base) + 1, -4); // strip base + ".php"
            $segments = preg_split('#/#', $rel) ?: [];
            $kebab = array_map($this->kebab(...), $segments);

            // Index is the directory's own URL (Page/Index -> "/", Admin/Index -> "/admin").
            if (($kebab[count($kebab) - 1] ?? '') === 'index') {
                unset($kebab[count($kebab) - 1]);
            }

            $url = '/' . implode('/', $kebab);
            $routes[] = $url;
        }

        $routes = array_values(array_unique($routes));
        sort($routes);

        return $routes;
    }

    private function kebab(string $name): string
    {
        return strtolower((string) preg_replace('/(?<!^)(?=[A-Z])/', '-', $name));
    }

    /** @return array{status: int, body: string} */
    private function get(string $url, string $jar, bool $admin): array
    {
        $sep = str_contains($url, '?') ? '&' : '?';
        $curl = sprintf(
            'curl -s -i -G -b %s -c %s %s %s',
            escapeshellarg($jar),
            escapeshellarg($jar),
            $admin ? '-H ' . escapeshellarg('X-BeMart-Test-Admin-Id: ' . self::ADMIN_ID) : '',
            escapeshellarg('http://' . self::HOST . $url . $sep . self::PARAM_BAG),
        );
        $raw = (string) shell_exec($curl);

        $status = preg_match('#HTTP/\S+ (\d{3})#', $raw, $m) === 1 ? (int) $m[1] : 0;
        $parts = preg_split("/\r?\n\r?\n/", $raw, 2) ?: [];

        return ['status' => $status, 'body' => $parts[1] ?? ''];
    }

    private static function login(string $jar): void
    {
        $form = (string) shell_exec(sprintf(
            'curl -s -b %s -c %s %s',
            escapeshellarg($jar),
            escapeshellarg($jar),
            escapeshellarg('http://' . self::HOST . '/login'),
        ));
        $token = preg_match('/name="csrfToken" value="([^"]*)"/', $form, $m) === 1 ? $m[1] : '';
        shell_exec(sprintf(
            'curl -s -b %s -c %s -X POST %s %s %s %s %s',
            escapeshellarg($jar),
            escapeshellarg($jar),
            '--data-urlencode ' . escapeshellarg('email=' . self::MEMBER_EMAIL),
            '--data-urlencode ' . escapeshellarg('password=' . self::MEMBER_PASSWORD),
            '--data-urlencode ' . escapeshellarg('mode=login'),
            '--data-urlencode ' . escapeshellarg('csrfToken=' . $token),
            escapeshellarg('http://' . self::HOST . '/login'),
        ));
    }

    private static function addToCart(string $jar): void
    {
        $page = (string) shell_exec(sprintf(
            'curl -s -b %s -c %s %s',
            escapeshellarg($jar),
            escapeshellarg($jar),
            escapeshellarg('http://' . self::HOST . '/product?productCode=sample-001'),
        ));
        $token = preg_match('/name="csrfToken" value="([^"]*)"/', $page, $m) === 1 ? $m[1] : '';
        shell_exec(sprintf(
            'curl -s -b %s -c %s -X POST %s %s %s %s %s',
            escapeshellarg($jar),
            escapeshellarg($jar),
            '--data-urlencode ' . escapeshellarg('productCode=sample-001'),
            '--data-urlencode ' . escapeshellarg('quantity=1'),
            '--data-urlencode ' . escapeshellarg('operation=add'),
            '--data-urlencode ' . escapeshellarg('csrfToken=' . $token),
            escapeshellarg('http://' . self::HOST . '/cart/item'),
        ));
    }
}
