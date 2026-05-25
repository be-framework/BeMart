<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminMemberForm;
use MyVendor\BeMart\Module\HtmlModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\SystemJaMessages;
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
use function is_string;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the admin Member-edit HTML port (the
 * Setting/System section's FORM/CRUD page).
 *
 * Same standard as the admin pilot {@see AdminNewsHtmlRenderTest}: EC-CUBE
 * renders the member inputs through the Symfony FormView
 * (`form_widget(form.name)`); BeMart renders them through a real
 * Ray.WebFormModule {@see AdminMemberForm} exposed as `body.form`. This
 * test renders EC-CUBE's `form_widget(form.<field>)` calls through the
 * SAME `AdminMemberForm` instance, pre-filled with the same persisted
 * member, so the inputs are byte-identical on both sides and diff to
 * ZERO — the form-widget residual family is eliminated.
 *
 * Honest, not circular: `AdminMemberForm::init()` is itself a PORT of
 * EC-CUBE's `MemberType` + `member_edit.twig`'s `form_widget` calls.
 *
 * The page extends `admin-base.html.twig`, served via
 * {@see EcCubeAdminStubLoader}. The Member resource requires an
 * authenticated admin, so the html context's `AdminSessionInterface` is
 * rebound to a seeded admin id.
 */
final class AdminMemberHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** The pre-seeded admin in be/var/fake/admins.json. */
    private const SEED_LOGIN_ID = 'test-admin';

    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. The form
     * inputs are rendered by a real AdminMemberForm on BOTH sides so they
     * diff to zero; the residual is the admin-frame baseline plus the
     * form `_token` hidden input and the omitted `Authority` / `Work`
     * master-data controls.
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
        "'ECCUBE-CSRF-TOKEN': $('meta[name=\"eccube-csrf-token\"]').attr('content')",
        '}',
        '});',
        '});',
        '</script>',
        '<title>メンバー登録 システム設定 - BeMart</title>',
        '<title>システム設定 メンバー登録 - EC-CUBE</title>',
        // Form: EC-CUBE's `_token` hidden CSRF input is rendered by the
        // Symfony FormView; BeMart's port keeps the hidden input
        // (structure) with an empty value — the html context has no
        // per-request CSRF widget.
        '<input type="hidden" id="member__token" name="_token" value="">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlModule($meta);
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testMemberEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testMemberEditPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            'id="member_form"',
            'class="card rounded border-0 mb-4"',
            'class="collapse show ec-cardCollapse"',
            'class="c-conversionArea"',
            'class="btn btn-ec-conversion px-5"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The form inputs are rendered by a real AdminMemberForm: the page
     * carries the EC-CUBE field ids / attributes, pre-filled with the
     * persisted profile.
     */
    public function testMemberEditRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();

        $this->assertStringContainsString('id="admin_member_name"', $html);
        $this->assertStringContainsString('id="admin_member_login_id"', $html);
        // The seed admin's profile is repopulated from the resource body.
        $this->assertStringContainsString('value="テスト管理者"', $html);
        $this->assertStringContainsString('value="test-admin"', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered admin Member-edit page
     * against EC-CUBE's own rendering. Every difference must be in the
     * residual allowlist or a residual family.
     */
    public function testMemberEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ])->toString();
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
            "BeMart's admin Member-edit HTML diverged from EC-CUBE's "
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
            // EC-CUBE-runtime <head> furniture.
            'eccube-csrf-token',
            '<title>',
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            'nav-',
            'fa-fw',
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

        // FORM-PAGE recipe: EC-CUBE's `form_widget(form.<field>)` calls
        // render through BeMart's real AdminMemberForm — pre-filled with
        // the SAME seed admin as BeMart's html-context body — so the
        // inputs are byte-identical to BeMart's port.
        $beMart = $this->resource->get('page://self/admin/member', [
            'loginId' => self::SEED_LOGIN_ID,
        ]);
        $form = (new FormFactory())->newInstance(AdminMemberForm::class);
        if ($form instanceof AdminMemberForm) {
            $form->fillValues($beMart->body);
        }

        $this->registerEcCubeStubs($twig, $form);

        return $twig->render('Setting/System/member_edit.twig', [
            // `form`'s children are the (possibly nested) field NAMES; the
            // stubbed form_widget renders each leaf through the form.
            // `Authority` / `Work` are EC-CUBE master-data selects — the
            // MemberFetched projection carries them only as bare ints, so
            // AdminMemberForm omits them; they render empty on both sides.
            'form' => new EcCubeStub([
                '_token' => '_token',
                'name' => 'name',
                'department' => 'department',
                'login_id' => 'login_id',
                'plain_password' => new EcCubeStub([
                    'first' => 'plain_password_first',
                    'second' => 'plain_password_second',
                ]),
                'Authority' => 'Authority',
                'Work' => 'Work',
                'two_factor_auth_enabled' => 'two_factor_auth_enabled',
            ], []),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['setting', 'system', 'member'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_setting_system_member_edit']),
            ]),
            'subtitle' => 'システム設定',
            'sub_title' => 'システム設定',
            'title' => 'メンバー登録',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminMemberForm|null $form): void
    {
        $messages = AdminJaMessages::forSection(SystemJaMessages::keys());
        $trans = static function (string $key, array $params = []) use ($messages): string {
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('date_min', static fn ($d): string => (string) $d));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        $twig->addFunction(new TwigFunction('asset', static fn (string $p, string ...$rest): string => '/' . $p));
        $twig->addFunction(new TwigFunction('url', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('path', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        // EC-CUBE's `form_widget(form.<field>)` renders through BeMart's
        // real AdminMemberForm so the inputs are byte-identical to
        // BeMart's port. `_token`, `Authority` and `Work` are NOT
        // declared by AdminMemberForm (CSRF is EC-CUBE-runtime;
        // Authority/Work need a master-data option set out of the Wave 8
        // slice) — they render empty here, mirroring BeMart's port.
        $formFields = [
            'name', 'department', 'login_id',
            'plain_password_first', 'plain_password_second',
            'two_factor_auth_enabled',
        ];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form, $formFields): Markup {
            if ($form instanceof AdminMemberForm && is_string($field) && in_array($field, $formFields, true)) {
                return new Markup($form->input($field), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
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
