<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Support\HtmlTestModule;
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
 * Phase 3 — fidelity check for the address book list
 * (goCustomerAddressList) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Mypage/delivery.twig` is a DATA page. The AddressList resource
 * requires AUTHN, so the `html` context's `CustomerSession` is rebound
 * to a fixture customer (alice).
 */
final class AddressListHtmlRenderTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa.
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
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $session = new FakeSession(self::ALICE_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');

        // The address row is supplied by the Ray.FakeQuery fixture
        // `address_list_by_customer.jsonl`; fake context does not mutate
        // query state during setup.

        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testAddressListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/address-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testAddressListPreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/mypage/address-list')->toString();

        foreach ([
            '<div class="ec-addressRole">',
            'class="ec-addressRole__actions"',
            '<div class="ec-addressList">',
            'class="ec-addressList__item"',
            'class="ec-addressList__address"',
            'class="ec-addressList__remove"',
            'href="/mypage/address?addressId=addr00000000000000000000000000a1"',
            'token-for-anchor=',
            'data-method="delete"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered address book against
     * EC-CUBE's own rendering of the same logical list.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testAddressListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/mypage/address-list')->toString();
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
            "BeMart's address-list HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // Phase 3 enrichment shrank the residual: the address line's
        // prefecture name is now a real body field (`prefName`) and
        // diffs to zero. The remaining ~11 lines are the EC-CUBE <head>
        // furniture only.
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
            'ec-addressList__remove',
            'token-for-anchor',
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

        // The same logical address as BeMart's seed. Phase 3 enrichment
        // — both sides now render the prefecture DISPLAY name: EC-CUBE's
        // `CustomerAddress.Pref` and BeMart's `prefName` are fed the same
        // value (東京都), so the address line diffs to zero. The
        // address-count cap is fed equal on both sides so the add-button
        // branch is taken.
        $address = new EcCubeStub([
            'id' => 'addr00000000000000000000000000a1',
            'name01' => '山田',
            'name02' => 'アリス',
            'postal_code' => '1500001',
            'Pref' => '東京都',
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phone_number' => '0312345678',
        ]);

        return $twig->render('Mypage/delivery.twig', [
            'Customer' => new EcCubeStub(['CustomerAddresses' => [$address]]),
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
                'option_favorite_product' => true,
                'option_point' => false,
            ]),
            'eccube_config' => ['locale' => 'ja', 'eccube_deliv_addr_max' => 20],
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
                'request' => new EcCubeStub(['_route' => 'mypage_delivery']),
                // AddressList does not carry the customer name; the navi
                // welcome message renders without one — EC-CUBE's
                // app.user names are fed empty so both sides match.
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
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png'));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
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
