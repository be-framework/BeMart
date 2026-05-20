<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\HtmlModule;
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
use function is_string;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the Login (goLogin) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * The Login page is the first form-bearing port of the wave. EC-CUBE's
 * `Mypage/login.twig` renders its inputs through the Symfony FormView
 * (`form_widget(form.login_email)` etc.). BeMart's Login resource body
 * carries the field NAMES (`fields: [email, password, csrfToken]`), not
 * a FormView, so the `<input>` widgets are authored plainly in the port.
 * The EC-CUBE-side `form_widget` calls are stubbed to a deterministic
 * marker; the input lines therefore differ on each side and are an
 * enumerated residual FAMILY ("Symfony FormView runtime"). What this
 * test proves is that the `ec-*` skeleton — the page header, the
 * `ec-off2Grid` / `ec-login` / `ec-grid2` wrappers, the action button,
 * the forgot/signup links — is ported verbatim.
 *
 *   1. renders EC-CUBE's real `Mypage/login.twig` + `default_frame.twig`;
 *   2. renders BeMart's ported `Login.html.twig` via the `html` context;
 *   3. line-diffs the two (whitespace-collapsed);
 *   4. asserts every differing line is in {@see RESIDUAL_ALLOWLIST} or a
 *      structurally-explained residual family.
 */
final class LoginHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable.
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
        '<title>BeMart / ログイン</title>',
        '<title>EC-CUBE / ログイン</title>',
        '<meta name="author" content="">',

        // --- login form: Symfony FormView widget inputs -----------------
        // EC-CUBE's form_widget(form.login_email / login_pass) renders the
        // <input> through the Symfony FormView with FormView-derived id /
        // name (`login_mypage_login_email`, `login_mypage[login_email]`).
        // BeMart's resource body carries the field NAMES, not a FormView,
        // so the port authors the inputs plainly with the bare EC-CUBE
        // field name. Same two inputs, FormView-runtime attributes only.
        // (Stubbed marker on the EC-CUBE side; bare <input> on BeMart's.)
        '<input type="text" id="login_email" name="login_email" style="ime-mode: disabled;" placeholder="メールアドレス" autofocus="autofocus">',
        '<input type="password" id="login_pass" name="login_pass" placeholder="パスワード">',
        '[form_widget:form.login_email]',
        '[form_widget:form.login_pass]',
        // EC-CUBE's hidden _csrf_token carries a live csrf_token('authenticate')
        // value; BeMart's html context has no CSRF widget so the value is
        // empty. Same hidden input, different (empty) value.
        '<input type="hidden" name="_csrf_token" value="">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testLoginPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/login');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testLoginPagePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/login')->toString();

        foreach ([
            '<div class="ec-role">',
            '<div class="ec-pageHeader">',
            '<div class="ec-off2Grid">',
            'class="ec-off2Grid__cell"',
            '<form name="login_mypage" id="login_mypage"',
            '<div class="ec-login">',
            'class="ec-login__icon"',
            'class="ec-login__input"',
            '<div class="ec-grid2">',
            'class="ec-grid2__cell"',
            'class="ec-blockBtn--cancel"',
            'class="ec-login__link"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered login page against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or an explained residual family.
     */
    public function testLoginHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/login')->toString();
        $ecCube = $this->renderEcCubeLogin();

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
            "BeMart's login HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // The skeleton matches; residual is the shared <head> material +
        // the 2 Symfony-FormView inputs + the CSRF hidden value.
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
            'form_widget:',          // Symfony FormView widget marker
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real Mypage/login.twig + default_frame.twig
     * from the gitignored clone, with EC-CUBE's Twig API stubbed.
     */
    private function renderEcCubeLogin(): string
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

        return $twig->render('Mypage/login.twig', [
            // login_email / login_pass are FormView children; the stubbed
            // form_widget renders a deterministic marker for each.
            'form' => new EcCubeStub([
                'login_email' => 'form.login_email',
                'login_pass' => 'form.login_pass',
                'login_memory' => 'form.login_memory',
            ]),
            'error' => null,
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
                // option_remember_me null/false -> EC-CUBE OMITS the
                // remember-me checkbox; BeMart's port omits it too, so it
                // contributes nothing to the diff.
                'option_remember_me' => null,
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
                'Head' => null, 'BodyAfter' => null, 'Header' => [0 => 'x'],
                'ContentsTop' => null, 'SideLeft' => null, 'SideRight' => null,
                'MainTop' => null, 'MainBottom' => null, 'ContentsBottom' => null,
                'Footer' => [0 => 'x'], 'Drawer' => [0 => 'x'], 'CloseBodyBefore' => null,
                'ColumnNum' => 1,
            ]),
            'app' => new EcCubeStub(['session' => new EcCubeStub([
                'flashbag' => new EcCubeFlashBag(),
                'flashBag' => new EcCubeFlashBag(),
            ]), 'request' => new EcCubeStub(['_route' => 'mypage_login'])]),
            'subtitle' => 'ログイン',
            'title' => 'ログイン',
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
        $twig->addFunction(new TwigFunction('asset', static fn (string $p): string => '/' . $p));
        $twig->addFunction(new TwigFunction('url', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('path', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        // EC-CUBE's storefront templates render inputs through the
        // Symfony FormView. BeMart's resource body carries field names,
        // not a FormView, so these helpers are stubbed to deterministic
        // markers; the resulting lines are an enumerated residual family
        // ("Symfony FormView runtime"). See RESIDUAL_ALLOWLIST.
        $twig->addFunction(new TwigFunction('form_widget', static fn ($f = '', $o = []): string => '[form_widget:' . (is_string($f) ? $f : 'field') . ']'));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => '[form_label:' . (is_string($l) ? $l : 'label') . ']'));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => '[form_row]'));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
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
