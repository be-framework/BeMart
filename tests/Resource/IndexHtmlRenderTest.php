<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\HtmlTestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function array_diff;
use function array_filter;
use function array_values;
use function count;
use function dirname;
use function explode;
use function http_build_query;
use function implode;
use function is_dir;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the Top (goTop) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig. ALPS carries
 * no presentation, so the honest reference is EC-CUBE's own templates.
 *
 * EC-CUBE's `index.twig` `block main` is a purely-static main-visual
 * slider (three `asset()` image paths, no data binding), so this port is
 * the cleanest of the wave: there is no resource-body data to bind, the
 * main area is markup-identical, and the only residual is the
 * EC-CUBE-runtime-only <head> material (CSRF meta + jQuery $.ajaxSetup
 * script + shop-name title) shared by every page.
 *
 *   1. renders EC-CUBE's real `index.twig` + `default_frame.twig` through
 *      a standalone Twig env with EC-CUBE's API stubbed;
 *   2. renders BeMart's ported `Index.html.twig` via the `html` context;
 *   3. line-diffs the two (whitespace-collapsed);
 *   4. asserts every differing line is in {@see RESIDUAL_ALLOWLIST}.
 */
final class IndexHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes -------------------
        // EC-CUBE emits a live CSRF token; BeMart's html context has no
        // per-request CSRF widget, so the meta is rendered empty.
        '<meta name="eccube-csrf-token" content="">',
        // EC-CUBE's default_frame.twig has an inline <script> wiring the
        // CSRF token into jQuery $.ajaxSetup. BeMart's ported
        // base.html.twig omits it (no CSRF, no jQuery-coupled AJAX layer).
        '<script>',
        '$(function() {',
        '$.ajaxSetup({',
        "'headers': {",
        '}',
        '});',
        '});',
        '</script>',
        // EC-CUBE's <title> is "<shop_name> / <page>"; the top page has no
        // page title, so EC-CUBE emits just the shop name from BaseInfo.
        // BeMart's html context has no BaseInfo — different shop-name
        // source ("BeMart" vs the stub's "EC-CUBE").
        '<title>BeMart</title>',
        '<title>EC-CUBE</title>',
        // EC-CUBE injects meta.twig (description/keywords/OGP). BeMart has
        // no Page entity; the include renders nothing but the author meta.
        '<meta name="author" content="">',

        // --- top-page hypermedia supplement ----------------------------
        // EC-CUBE exposes these transitions through default layout Block
        // widgets. BeMart's Block layer is static-only today, so the Top
        // template renders the critical purchase-spine links directly.
        '<div class="ec-role">',
        '<div class="ec-grid2">',
        '<div class="ec-grid2__cell">',
        '<a class="ec-blockBtn--action" href="/products/list">商品一覧へ</a>',
        '<a class="ec-blockBtn--cancel" href="/cart">カートを見る</a>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlTestModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testTopPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTopPagePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        foreach ([
            'class="front_page"',
            '<div class="ec-sliderRole">',
            '<div class="main_visual">',
            '<div class="item slick-slide">',
            'assets/img/top/img_hero_pc01.jpg',
            'assets/img/top/img_hero_pc02.jpg',
            'assets/img/top/img_hero_pc03.jpg',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testTopPageRendersCriticalNavigationLinks(): void
    {
        $html = $this->resource->get('page://self/')->toString();

        $this->assertStringContainsString('href="/products/list"', $html);
        $this->assertStringContainsString('href="/cart"', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered top page against EC-CUBE's
     * own rendering. Every difference must be in the residual allowlist.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testTopHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/')->toString();
        $ecCube = $this->renderEcCubeTop();

        $beMartLines = $this->normalize($beMart);
        $ecCubeLines = $this->normalize($ecCube);

        $onlyInEcCube = array_values(array_diff($ecCubeLines, $beMartLines));
        $onlyInBeMart = array_values(array_diff($beMartLines, $ecCubeLines));

        $unexplained = array_values(array_filter(
            [...$onlyInEcCube, ...$onlyInBeMart],
            static fn (string $line): bool => ! self::isResidual($line),
        ));

        $this->assertSame(
            [],
            $unexplained,
            "BeMart's top-page HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // The top page is the cleanest port of the wave: no data binding,
        // residual is the shared EC-CUBE-runtime <head> material only.
        $this->assertLessThan(
            20,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        foreach (self::RESIDUAL_ALLOWLIST as $allowed) {
            if ($line === $allowed) {
                return true;
            }
        }

        foreach ([
            'eccube-csrf-token',
            '<title>',
            'meta name="author"',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real index.twig + default_frame.twig from the
     * gitignored clone, with EC-CUBE's Twig API stubbed.
     */
    private function renderEcCubeTop(): string
    {
        $ecCubeTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/default';
        if (! is_dir($ecCubeTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $twig = new Environment(new EcCubeStubLoader($ecCubeTemplates), [
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);
        $this->registerEcCubeStubs($twig);

        return $twig->render('index.twig', [
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
            ]),
            'eccube_config' => ['locale' => 'ja'],
            'Page' => new EcCubeStub([
                'meta_tags' => '',
                'description' => '',
                'author' => '',
                'keyword' => '',
                'meta_robots' => '',
            ]),
            'Layout' => new EcCubeStub([
                'Head' => null, 'BodyAfter' => null, 'Header' => [new EcCubeStub(['file_name' => 'logo'])],
                'ContentsTop' => null, 'SideLeft' => null, 'SideRight' => null,
                'MainTop' => null, 'MainBottom' => null, 'ContentsBottom' => null,
                'Footer' => [new EcCubeStub(['file_name' => 'footer'])], 'Drawer' => [0 => 'x'], 'CloseBodyBefore' => null,
                'ColumnNum' => 1,
            ]),
            'app' => new EcCubeStub(['session' => new EcCubeStub([
                'flashbag' => new EcCubeFlashBag(),
            ]), 'request' => new EcCubeStub(['_route' => 'homepage'])]),
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
    {
        $trans = static function (string $key, array $params = []): string {
            $messages = EcCubeStub::jaMessages();
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('nl2br', static fn (string $s): string => nl2br($s)));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));
    }

    /**
     * Collapse a rendered HTML document to a list of non-empty,
     * whitespace-trimmed lines for structural line-diffing.
     *
     * @return list<string>
     */
    private function normalize(string $html): array
    {
        $collapsed = (string) preg_replace('/[ \t]+/', ' ', $html);
        $lines = [];
        foreach (explode("\n", $collapsed) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
