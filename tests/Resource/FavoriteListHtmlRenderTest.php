<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\HtmlModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
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
use function in_array;
use function is_dir;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the favorites list (goFavoriteList) HTML
 * port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Mypage/favorite.twig` is a DATA page. The FavoriteList resource
 * requires AUTHN, so the `html` context's `SessionInterface` is rebound
 * to a fixture customer (alice). Per the Phase 3 ALPS audit, `Favorite`
 * was re-tagged `src-template`: the favorite row composes productName /
 * unitPrice, both carried by FavoriteEntity, so the body is sufficient
 * for the row content.
 */
final class FavoriteListHtmlRenderTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa.
     *
     * Phase 3 ENRICHMENT — the favorite-row product thumbnail is no
     * longer a residual: the `Favorite` ALPS descriptor + FavoriteEntity
     * gained `fileName` (the product main-image file name), so BeMart's
     * port emits the same `<img>` EC-CUBE does. Both sides are fed the
     * same image file name below, so the thumbnail line diffs to zero.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes (shared) ----------
        '<meta name="eccube-csrf-token" content="">',
        '<script>',
        '$(function() {',
        '$.ajaxSetup({',
        "'headers': {",
        '}',
        '});',
        '});',
        '</script>',
        '<title>BeMart / マイページ</title>',
        '<title>EC-CUBE / マイページ</title>',
        '<meta name="author" content="">',

        // --- favorite pager (not a paged view in BeMart) ----------------
        '<div class="ec-pagerRole">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlModule($meta);
        $session = new FakeSession(self::ALICE_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');

        // Seed one favorite for alice — fed identically to the EC-CUBE
        // side below. Resolve the singleton via the interface (the
        // concrete class is not separately scope-bound).
        $storage = $injector->getInstance(FavoriteStorageInterface::class);
        $storage->add(new FavoriteEntity(
            customerId: self::ALICE_ID,
            productCode: 'sample-001',
            productName: 'サンプル商品 A',
            unitPrice: 1200,
            fileName: 'sample-a.jpg',
        ));

        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testFavoriteRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testFavoritePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        foreach ([
            '<div class="ec-favoriteRole">',
            'class="ec-favoriteRole__itemList"',
            'class="ec-favoriteRole__item"',
            'class="ec-favoriteRole__itemTitle"',
            'class="ec-favoriteRole__itemPrice"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered favorites list against
     * EC-CUBE's own rendering of the same logical list.
     */
    public function testFavoriteHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/mypage/favorite-list')->toString();
        $ecCube = $this->renderEcCube();

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
            "BeMart's favorite HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // Phase 3 enrichment shrank the residual: the favorite-row
        // product thumbnail is now a real body field (`Favorite.fileName`)
        // and diffs to zero. The remaining ~12 lines are the EC-CUBE
        // <head> furniture + the ec-pagerRole node (BeMart's favorites
        // list is not a paged view).
        $this->assertLessThanOrEqual(
            13,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        if (in_array($line, self::RESIDUAL_ALLOWLIST, true)) {
            return true;
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

    private function renderEcCube(): string
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

        // The same logical favorite as BeMart's seed: sample-001 /
        // サンプル商品 A / ￥1,200 / image `sample-a.jpg`. EC-CUBE keys
        // the row by the surrogate Product.id and renders a price RANGE
        // (price02_inc_tax_min..max); BeMart's FavoriteEntity carries
        // productCode + a single unitPrice, so the row keys by
        // productCode and shows the single price. To keep the row's
        // TITLE / PRICE comparable, EC-CUBE is fed equal min/max prices
        // (so its `==` branch renders a single price) and the same id is
        // the productCode.
        //
        // Phase 3 enrichment — the product THUMBNAIL is now a real body
        // field (`Favorite.fileName`). EC-CUBE is fed the SAME image
        // file name (`main_list_image`) BeMart's FavoriteEntity carries,
        // so the `<img>` line diffs to zero on both sides.
        $favorite = new EcCubeStub([
            'Product' => new EcCubeStub([
                'id' => 'sample-001',
                'name' => 'サンプル商品 A',
                'main_list_image' => 'sample-a.jpg',
                'price02_inc_tax_min' => 1200,
                'price02_inc_tax_max' => 1200,
            ]),
        ]);

        return $twig->render('Mypage/favorite.twig', [
            'pagination' => new EcCubeStub(
                ['totalItemCount' => 1, 'paginationData' => []],
                [$favorite],
            ),
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
                'option_favorite_product' => true,
                'option_point' => false,
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
            'app' => new EcCubeStub([
                'session' => new EcCubeStub([
                    'flashbag' => new EcCubeFlashBag(),
                    'flashBag' => new EcCubeFlashBag(),
                ]),
                'request' => new EcCubeStub(['_route' => 'mypage_favorite']),
                // FavoriteListFetched does not carry the customer name
                // (its projection is customerId / favorites / count), so
                // the shared navi partial renders the welcome message
                // without a name. EC-CUBE's `app.user` name fields are
                // fed empty so both sides emit the same welcome <p>.
                'user' => new EcCubeStub(['name01' => '', 'name02' => '', 'point' => 0]),
            ]),
            'subtitle' => 'マイページ',
            'title' => 'マイページ',
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
        $twig->addFilter(new TwigFilter('nl2br', static fn (string $s): string => nl2br((string) $s)));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png'));

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
