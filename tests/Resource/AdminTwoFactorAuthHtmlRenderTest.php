<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use MyVendor\BeMart\Module\HtmlModule;
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
 * Phase 3 — fidelity check for the admin 2段階認証 (challenge) HTML port
 * (a top-level wave login-context FORM page).
 *
 * Same standard as the admin form pilot {@see AdminNewsHtmlRenderTest}:
 * EC-CUBE renders the token input through the Symfony FormView
 * (`form_widget(form.device_token)`); BeMart renders it through a real
 * Ray.WebFormModule {@see AdminTwoFactorAuthForm} exposed as `body.form`.
 * This test renders EC-CUBE's `form_widget(...)` through the SAME form so
 * the input diffs to ZERO.
 *
 * The page extends the admin LOGIN frame (`admin-login-base.html.twig`,
 * a port of EC-CUBE's `login_frame.twig`). The resource
 * (`Page/Admin/TwoFactorAuth.php`) is anonymous-accessible (login
 * context), so no `AdminSessionInterface` rebind is needed.
 */
final class AdminTwoFactorAuthHtmlRenderTest extends TestCase
{
    /**
     * The token input is rendered by a real AdminTwoFactorAuthForm on
     * BOTH sides (diffs to zero); the residual is the small login-frame
     * baseline + the form `_token` hidden CSRF input.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // EC-CUBE's login_frame <title> is `admin.login|trans` —
        // BeMart's port uses the page's own 2段階認証 title; both are
        // a `<title>` residual.
        '<title>2段階認証 - BeMart</title>',
        '<title>ログイン - EC-CUBE</title>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlModule($meta);
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testTwoFactorAuthRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/two-factor-auth');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<body id="login-page"', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTwoFactorAuthRendersRealFormInput(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth')->toString();

        $this->assertStringContainsString('id="admin_two_factor_auth_device_token"', $html);
        $this->assertStringContainsString('name="device_token"', $html);
        $this->assertStringContainsString('action="/admin_two_factor_auth"', $html);
    }

    public function testTwoFactorAuthHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/two-factor-auth')->toString();
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
            "BeMart's admin 2FA HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
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
            // Form: EC-CUBE's hidden `_token` CSRF input.
            'name="_token"',
            'csrf_token',
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

        $form = (new FormFactory())->newInstance(AdminTwoFactorAuthForm::class);
        $this->registerEcCubeStubs($twig, $form instanceof AdminTwoFactorAuthForm ? $form : null);

        return $twig->render('two_factor_auth.twig', [
            'form' => new EcCubeStub([
                '_token' => '_token',
                'device_token' => 'device_token',
            ]),
            'error' => null,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => ['locale' => 'ja'],
            'app' => new EcCubeStub([
                'request' => new EcCubeStub(['_route' => 'admin_two_factor_auth']),
            ]),
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminTwoFactorAuthForm|null $form): void
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

        $twig->addFunction(new TwigFunction('trans', $trans));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));

        $formFields = ['device_token', 'auth_key'];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form, $formFields): Markup {
            if ($form instanceof AdminTwoFactorAuthForm && is_string($field) && in_array($field, $formFields, true)) {
                return new Markup($form->input($field), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
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
