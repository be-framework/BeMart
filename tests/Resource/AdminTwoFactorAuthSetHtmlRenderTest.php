<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\TopJaMessages;
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
use function rawurlencode;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the admin 2段階認証 デバイス登録 HTML port
 * (a top-level wave login-context FORM page).
 *
 * Same standard as {@see AdminTwoFactorAuthHtmlRenderTest}: EC-CUBE
 * renders the inputs through the Symfony FormView; BeMart renders them
 * through a real {@see AdminTwoFactorAuthForm} exposed as `body.form`.
 * The page also has a QR-code `{% block javascript %}` that builds an
 * `otpauth://` URI from `authKey` / `shopName` / `memberName` body
 * placeholders (the TwoFactorAuthSet resource is a thin renderer — no
 * Be 2FA projection; see its doc). The EC-CUBE side is fed the same
 * logical values so the URI diffs to zero.
 *
 * The page extends the admin LOGIN frame (`admin-login-base.html.twig`).
 * The resource (`Page/Admin/TwoFactorAuthSet.php`) is anonymous-
 * accessible (login context).
 */
final class AdminTwoFactorAuthSetHtmlRenderTest extends TestCase
{
    /** @var list<string> */
    private const RESIDUAL_ALLOWLIST = [
        '<title>2段階認証 - BeMart</title>',
        '<title>ログイン - EC-CUBE</title>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testTwoFactorAuthSetRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/two-factor-auth-set');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<body id="login-page"', $html);
        $this->assertStringContainsString('id="qrcode"', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTwoFactorAuthSetRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/two-factor-auth-set')->toString();

        $this->assertStringContainsString('id="admin_two_factor_auth_device_token"', $html);
        $this->assertStringContainsString('id="admin_two_factor_auth_auth_key"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('action="/admin/two-factor-auth-set?_method=put"', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testTwoFactorAuthSetHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/two-factor-auth-set')->toString();
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
            "BeMart's admin 2FA-set HTML diverged from EC-CUBE's beyond "
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
            'name="csrfToken"',
            'csrfcsrfToken',
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

        // Feed EC-CUBE the SAME logical QR-code data as BeMart's
        // thin-renderer body: shopName 'BeMart', empty memberName, empty
        // authKey — so the `otpauth://` URI diffs to zero.
        return $twig->render('two_factor_auth_set.twig', [
            'form' => new EcCubeStub([
                'csrfToken' => 'csrfToken',
                'deviceToken' => 'deviceToken',
                'authKey' => 'authKey',
            ]),
            'error' => null,
            'authKey' => '',
            'Member' => new EcCubeStub(['name' => '']),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'BeMart']),
            'eccube_config' => ['locale' => 'ja'],
            'app' => new EcCubeStub([
                'request' => new EcCubeStub(['_route' => 'admin_two_factor_auth_set']),
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
        // EC-CUBE's `url_encode` is Twig's built-in; the port uses the
        // same built-in. Registered explicitly so a single concatenated
        // `url_encode` on the BeMart side and the per-fragment
        // `url_encode` on the EC-CUBE side resolve identically.
        $twig->addFilter(new TwigFilter('url_encode', static fn ($v): string => rawurlencode((string) $v)));

        $twig->addFunction(new TwigFunction('trans', $trans));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));

        $formFields = ['deviceToken', 'authKey'];
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
