<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Form\ResetForm;
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
use function trim;

/**
 * Functional HTML render test for Reset (doResetPassword).
 *
 * The template was rebuilt from scratch in the IdeaStore design language
 * (var/templates/Page/Reset.html.twig); it no longer carries EC-CUBE markup.
 * The EC-CUBE parity comparison test is archived below.
 *
 * L1 checks: required fields present in the rendered output.
 * L2 checks: form action / method match the Resource #[Link] contract
 *   (page://self/reset, POST), the resetKey hidden field is carried, and
 *   the login link (rel=goLogin) is present.
 */
final class ResetHtmlRenderTest extends TestCase
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
        '<title>BeMart / パスワード再発行(再設定)</title>',
        '<title>EC-CUBE / パスワード再発行(再設定)</title>',
        '<meta name="author" content="">',

        // --- reset form: CSRF hidden input ------------------------------
        // EC-CUBE's hidden csrfToken carries a live form CSRF token; BeMart's
        // html context has no CSRF widget, so the value is empty.
        '<input type="hidden" name="csrfToken" value="">',

        // --- reset form: resetKey hidden input (BeMart-only) ------------
        // EC-CUBE carries the reset key in the URL path / session; BeMart's
        // Reset resource carries it in a hidden form field (ResetForm is
        // keyed by `resetKey`). BeMart-side only — no EC-CUBE counterpart.
        '<input type="hidden" name="resetKey" value="">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testResetPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/reset');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<main>', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L1/L2 functional check: the reset form exposes the required ALPS
     * data-contract fields, the correct action/method transition, and the
     * login link matches the Resource #[Link] rel=goLogin contract.
     */
    public function testResetPagePreservesSemanticStructure(): void
    {
        $html = $this->resource->get('page://self/reset')->toString();

        // L2 — ALPS doResetPassword transition: form posts to /reset
        $this->assertStringContainsString('method="post"', $html, 'form method must be POST');
        $this->assertStringContainsString('action="/reset"', $html, 'form action must be /reset');

        // L2 — goLogin link present (Resource #[Link] rel=goLogin, href=page://self/login)
        $this->assertStringContainsString('href="/login"', $html, 'goLogin link to /login required');
        $this->assertStringContainsString('rel="goLogin"', $html, 'goLogin rel attribute required');

        // L2 — submit button present
        $this->assertStringContainsString('type="submit"', $html, 'submit button required');
    }

    /**
     * L1 — all required form fields are rendered by the real ResetForm.
     */
    public function testResetPageRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/reset')->toString();

        // L1 — email renderer field (ResetForm declares it for display fidelity)
        $this->assertStringContainsString('name="email"', $html, 'email input required');

        // L1 — password fields (ResetPasswordInput contract fields)
        $this->assertStringContainsString('name="password"', $html, 'password input required');
        $this->assertStringContainsString('type="password"', $html, 'password input type must be password');
        $this->assertStringContainsString('name="password_confirm"', $html, 'password_confirm input required');

        // L1 — resetKey carried in a hidden field for the POST
        $this->assertStringContainsString('name="resetKey"', $html, 'resetKey hidden input required');
    }

    /**
     * EC-CUBE markup parity test — archived; superseded by functional checks
     * (license cleanup). The template was rebuilt in the IdeaStore design
     * language and no longer shares EC-CUBE DOM structure.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testResetHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped('EC-CUBE markup parity retired; superseded by functional checks (license cleanup).');
        $beMart = $this->resource->get('page://self/reset')->toString();
        $ecCube = $this->renderEcCubeReset();

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
            "BeMart's reset HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
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

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real Forgot/reset.twig + default_frame.twig
     * from the gitignored clone, with EC-CUBE's Twig API stubbed.
     *
     * `form_widget(form.<field>)` delegates to the real {@see ResetForm}
     * so the inputs are byte-identical to BeMart's port. EC-CUBE's
     * `PasswordResetType` nests the new password under a
     * `RepeatedPasswordType` (`form.password.first` / `.second`); the
     * `form` stub resolves each compound path to the ResetForm leaf
     * field name.
     */
    private function renderEcCubeReset(): string
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

        return $twig->render('Forgot/reset.twig', [
            'form' => new EcCubeStub([
                'email' => 'email',
                'password' => new EcCubeStub([
                    'first' => 'password', 'second' => 'password_confirm',
                ]),
                'csrfToken' => '_csrfToken__',
            ]),
            'error' => null,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_password_min_len' => 8,
                'eccube_password_max_len' => 32,
            ],
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
            ]), 'request' => new EcCubeStub(['_route' => 'forgot_reset'])]),
            'subtitle' => 'パスワード再発行(再設定)',
            'title' => 'パスワード再発行(再設定)',
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
        $twig->addFilter(new TwigFilter('filter', static fn ($it, $f): array => []));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        // FORM-PAGE recipe: `form_widget(form.<field>)` delegates to
        // BeMart's real ResetForm so the inputs are byte-identical.
        $resetForm = (new FormFactory())->newInstance(ResetForm::class);
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($resetForm): Markup {
            if ($field === '_csrfToken__') {
                return new Markup('<input type="hidden" name="csrfToken" value="">', 'UTF-8');
            }

            if ($resetForm instanceof ResetForm && is_string($field) && $field !== '') {
                return new Markup($resetForm->input($field), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        // `form_label` renders the same `<label class="ec-label">` BeMart
        // authors plainly — the label IS a port, so it diffs to zero.
        $twig->addFunction(new TwigFunction('form_label', static function ($f = '', $l = '', $o = []) use ($trans): Markup {
            $text = is_string($l) ? $trans($l) : '';

            return new Markup('<label class="ec-label">' . $text . '</label>', 'UTF-8');
        }));
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
