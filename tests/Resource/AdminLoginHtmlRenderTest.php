<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Form\AdminLoginForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\TopJaMessages;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
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
 * Phase 3 — fidelity check for the admin login HTML port (the top-level
 * wave's login-context FORM page).
 *
 * Same standard as the storefront form-page pilot {@see LoginHtmlRenderTest}
 * and the admin form pilot {@see AdminNewsHtmlRenderTest}: EC-CUBE renders
 * the login inputs through the Symfony FormView (`form_widget(form.loginId)`);
 * BeMart renders them through a real Ray.WebFormModule
 * {@see AdminLoginForm} exposed as `body.form`. This test renders
 * EC-CUBE's `form_widget(form.<field>)` calls through the SAME
 * `AdminLoginForm`, so the inputs are byte-identical and diff to ZERO.
 *
 * The login page extends the admin LOGIN frame (`admin-login-base.html.twig`,
 * a port of EC-CUBE's `login_frame.twig`) — the small unauthenticated
 * frame, NOT the standard sidebar+header `default_frame`. The admin Login
 * resource (`Page/Admin/Login.php`) is anonymous-accessible (the login
 * page is public), so no `AdminSession` rebind is needed.
 */
final class AdminLoginHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. The form
     * inputs are rendered by a real AdminLoginForm on BOTH sides, so they
     * diff to zero; the residual is the small login-frame baseline.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // <title>: EC-CUBE's login_frame is `<admin.login|trans> -
        // <BaseInfo.shop_name>`; only the shop name differs.
        '<title>ログイン - BeMart</title>',
        '<title>ログイン - EC-CUBE</title>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testLoginRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/login');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<body id="login-page"', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testLoginRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/login')->toString();

        $this->assertStringContainsString('id="loginId"', $html);
        $this->assertStringContainsString('name="loginId"', $html);
        $this->assertStringContainsString('value="test-admin"', $html);
        $this->assertStringContainsString('id="admin_login_password"', $html);
        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('value="admin-test-password-2026"', $html);
        // Slice 9: path('admin_login') now resolves through canonical Resource path.
        $this->assertStringContainsString('action="/admin/login"', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testLoginHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/login')->toString();
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
            "BeMart's admin login HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            20,
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
            '<title>',
            // The hidden CSRF input: EC-CUBE renders a live per-request
            // `csrfcsrfToken('authenticate')` value, BeMart renders the
            // CsrfToken reference. The token VALUE can never
            // match across the two runtimes — the line is residual by
            // its `csrfToken` field name regardless of the value.
            'name="csrfToken"',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    private function renderEcCube(): string
    {
        $adminTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/admin';
        if (! is_dir($adminTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $twig = new Environment(new EcCubeAdminStubLoader($adminTemplates), [
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);

        // EC-CUBE's `form_widget(form.<field>)` renders through BeMart's
        // real AdminLoginForm — the same form the BeMart port renders —
        // so the inputs are byte-identical and diff to zero.
        $form = (new FormFactory())->newInstance(AdminLoginForm::class);
        if ($form instanceof AdminLoginForm) {
            $form->fillValues([
                'loginId' => 'test-admin',
                'password' => 'admin-test-password-2026',
            ]);
        }
        $this->registerEcCubeStubs($twig, $form instanceof AdminLoginForm ? $form : null);

        return $twig->render('login.twig', [
            'form' => new EcCubeStub([
                'loginId' => 'loginId',
                'password' => 'password',
            ]),
            'error' => null,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => ['locale' => 'ja'],
            'app' => new EcCubeStub([
                'request' => new EcCubeStub(['_route' => 'admin_login']),
            ]),
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminLoginForm|null $form): void
    {
        $messages = AdminJaMessages::forSection(TopJaMessages::keys());
        $trans = static function (string $key, array $params = []) use ($messages): string {
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('nl2br', static fn ($v): string => (string) $v));

        $twig->addFunction(new TwigFunction('trans', $trans));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));

        // EC-CUBE's `form_widget(form.<field>)` renders through the real
        // AdminLoginForm so the inputs are byte-identical to BeMart's
        // port. The first arg is the field name. Returns Twig\Markup so
        // the markup is not double-escaped.
        $formFields = ['loginId', 'password'];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form, $formFields): Markup {
            if ($form instanceof AdminLoginForm && is_string($field) && in_array($field, $formFields, true)) {
                return new Markup($form->input($field), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => ''));
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
