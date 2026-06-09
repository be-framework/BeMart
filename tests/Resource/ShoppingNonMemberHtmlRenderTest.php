<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\NonMemberForm;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\WebFormModule\FormFactory;
use Twig\Environment;
use Twig\Markup;
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
use function is_string;
use function preg_replace;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * Phase 3 — fidelity check for the Shopping non-member
 * (goShoppingNonMember) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Shopping/nonmember.twig` is a FORM page. This port follows the
 * Ray.WebFormModule form-page recipe (see var/templates/README.md): the
 * NonMember resource exposes a real {@see NonMemberForm} (an
 * AbstractForm) as `body.form`. This test renders EC-CUBE's
 * `form_widget(form.name.name01)` calls through the SAME `NonMemberForm`
 * instance so the inputs diff to ZERO; `form_label` is stubbed to the
 * same `<label class="ec-label">` BeMart authors plainly. The residual
 * is the genuinely EC-CUBE-runtime-only `<head>` frame material + the
 * empty CSRF hidden value.
 */
final class ShoppingNonMemberHtmlRenderTest extends TestCase
{
    /** @var list<string> */
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
        '<title>BeMart / お客様情報の入力</title>',
        '<title>EC-CUBE / お客様情報の入力</title>',
        '<meta name="author" content="">',
        // --- form: CSRF hidden input ------------------------------------
        '<input type="hidden" name="csrfToken" value="">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testShoppingNonMemberRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/non-member');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testShoppingNonMemberPreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        foreach ([
            '<div class="ec-customerRole">',
            '<h1>お客様情報の入力</h1>',
            '<ul class="ec-progress">',
            '<div class="ec-borderedDefs">',
            'class="ec-halfInput"',
            'class="ec-zipInput"',
            'class="ec-telInput"',
            'class="ec-RegisterRole__actions"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /** The guest-info inputs are rendered by a real form library. */
    public function testShoppingNonMemberRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        $this->assertStringContainsString('name="name01"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="email_confirm"', $html);
        $this->assertStringContainsString('name="postalCode"', $html);
    }

    public function testRejectedPostRendersInlineErrorsAsHtml(): void
    {
        $ro = $this->resource->post('page://self/shopping/non-member', [
            'name01' => '',
            'name02' => '',
            'kana01' => '',
            'kana02' => '',
            'companyName' => '',
            'email' => '',
            'email_confirm' => '',
            'phoneNumber' => '',
            'postalCode' => '',
            'pref' => '',
            'addr01' => '',
            'addr02' => '',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $html);
        $this->assertStringContainsString('入力してください。', $html);
        $this->assertStringContainsString('name="email_confirm"', $html);
        $this->assertStringNotContainsString('Invalid parameter type', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testShoppingNonMemberHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/shopping/non-member')->toString();
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
            "BeMart's Shopping non-member HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            14,
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

        // The `form_label` stub renders `<label class="ec-label">…` —
        // the same plain label BeMart's port authors directly.
        if (str_starts_with($line, '<label class="ec-label">')) {
            return true;
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

        // EC-CUBE's `NonMemberType` nests fields under compound types;
        // each compound path resolves to the NonMemberForm leaf field
        // name, and the stubbed form_widget renders that field through
        // the real NonMemberForm.
        return $twig->render('Shopping/nonmember.twig', [
            'form' => new EcCubeStub([
                'name' => new EcCubeStub(['name01' => 'name01', 'name02' => 'name02']),
                'kana' => new EcCubeStub(['kana01' => 'kana01', 'kana02' => 'kana02']),
                'company_name' => 'companyName',
                'postal_code' => 'postalCode',
                'address' => new EcCubeStub([
                    'pref' => 'pref', 'addr01' => 'addr01', 'addr02' => 'addr02',
                ]),
                'phone_number' => 'phoneNumber',
                'email' => new EcCubeStub(['first' => 'email', 'second' => 'email_confirm']),
                'csrfToken' => '_csrfToken__',
            ]),
            'eccube_config' => ['locale' => 'ja'],
            'Page' => new EcCubeStub([
                'meta_tags' => '', 'description' => '', 'author' => '',
                'keyword' => '', 'meta_robots' => '',
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
            ]), 'request' => new EcCubeStub(['_route' => 'shopping_nonmember'])]),
            'subtitle' => 'お客様情報の入力',
            'title' => 'お客様情報の入力',
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
        $twig->addFilter(new TwigFilter('nl2br', static fn ($s): string => nl2br((string) $s), ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('filter', static fn ($it, $f): array => []));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        // FORM-PAGE recipe: `form_widget(form.name.name01)` delegates to
        // BeMart's real NonMemberForm so the inputs diff to ZERO.
        $form = (new FormFactory())->newInstance(NonMemberForm::class);
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form): Markup {
            if ($field === '_csrfToken__') {
                return new Markup('<input type="hidden" name="csrfToken" value="">', 'UTF-8');
            }

            if ($form instanceof NonMemberForm && is_string($field) && $field !== '') {
                return new Markup($form->input($field), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_label', static function ($f = '', $l = '', $o = []) use ($trans): Markup {
            $text = is_string($l) ? $trans($l) : '';

            return new Markup('<label class="ec-label">' . $text . '</label>', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
    }

    /** @return list<string> */
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
