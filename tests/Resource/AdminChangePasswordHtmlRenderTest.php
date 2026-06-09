<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminChangePasswordForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\TopJaMessages;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
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
use function is_object;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the admin password-change HTML port (a
 * top-level wave FORM/CRUD page).
 *
 * Same standard as the admin form pilot {@see AdminNewsHtmlRenderTest}:
 * EC-CUBE renders the inputs through the Symfony FormView
 * (`form_widget(form.current_password)` etc.); BeMart renders them
 * through a real Ray.WebFormModule {@see AdminChangePasswordForm} exposed
 * as `body.form`. This test renders EC-CUBE's `form_widget(...)` calls
 * through the SAME form so the inputs diff to ZERO.
 *
 * EC-CUBE's `change_password` field is a `RepeatedType`; this test maps
 * `form.change_password.first|second` (EC-CUBE FormView access) to the
 * flat `change_password_first|second` fields the AbstractForm declares.
 *
 * The page extends `admin-base.html.twig` and its resource
 * (`Page/Admin/ChangePassword.php`) is admin-only, so the html context's
 * `AdminSession` is rebound to a seeded admin id.
 */
final class AdminChangePasswordHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /**
     * The form inputs are rendered by a real AdminChangePasswordForm on
     * BOTH sides, so they diff to zero; the residual is the admin-frame
     * baseline + the form `csrfToken` hidden CSRF input.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        '<meta name="eccube-csrf-token" content="">',
        '<script>',
        '$(function() {',
        '$.ajaxSetup({',
        "'headers': {",
        "'ECCUBE-CSRF-TOKEN': $('meta[name=\"eccube-csrf-token\"]').attr('content')",
        '}',
        '});',
        '});',
        '</script>',
        '<title>パスワード変更 - BeMart</title>',
        '<title>パスワード変更 - EC-CUBE</title>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testChangePasswordRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/change-password');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testChangePasswordRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString('id="admin_change_password_current_password"', $html);
        $this->assertStringContainsString('id="admin_change_password_change_password_first"', $html);
        $this->assertStringContainsString('id="admin_change_password_change_password_second"', $html);
        $this->assertStringContainsString('type="password"', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testChangePasswordHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/change-password')->toString();
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
            "BeMart's admin password-change HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            40,
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
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // Form: EC-CUBE's hidden `csrfToken` CSRF input. BeMart keeps the
            // hidden input (structure) with an empty value.
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

        $form = (new FormFactory())->newInstance(AdminChangePasswordForm::class);
        $this->registerEcCubeStubs($twig, $form instanceof AdminChangePasswordForm ? $form : null);

        // EC-CUBE accesses `form.change_password.first` — a RepeatedType
        // child. The `change_password` stub child carries a nested
        // `first` / `second` whose values are the flat AbstractForm field
        // names; the stubbed `form_widget` delegates by that name.
        return $twig->render('change_password.twig', [
            'form' => new EcCubeStub([
                'csrfToken' => 'csrfToken',
                'current_password' => 'current_password',
                'change_password' => new EcCubeStub([
                    'first' => 'change_password_first',
                    'second' => 'change_password_second',
                ]),
            ]),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => [],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_change_password']),
            ]),
            'subtitle' => '',
            'sub_title' => '',
            'title' => 'パスワード変更',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminChangePasswordForm|null $form): void
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
        $twig->addFilter(new TwigFilter('date_min', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        // EC-CUBE's `form_widget(form.<field>, opts)` renders through the
        // real AdminChangePasswordForm. The first arg is the field name
        // (a string for current_password / a RepeatedType child for
        // change_password.first|second — both resolve to the flat field
        // name via the EcCubeStub above). Fields not declared by the form
        // (`csrfToken`) render empty, mirroring the BeMart port.
        $formFields = ['current_password', 'change_password_first', 'change_password_second'];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form, $formFields): Markup {
            $name = is_object($field) ? (string) $field : $field;
            if ($form instanceof AdminChangePasswordForm && in_array($name, $formFields, true)) {
                return new Markup($form->input($name), 'UTF-8');
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
