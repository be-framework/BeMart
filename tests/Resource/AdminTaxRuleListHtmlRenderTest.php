<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminTaxRuleForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ShopJaMessages;
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
 * Phase 3 — fidelity check for the admin Tax-rule list HTML port (the
 * Setting/Shop section's `Setting/Shop/tax_rule.twig` LIST + inline-FORM
 * page).
 *
 * Same standard as the admin pilot {@see AdminNewsHtmlRenderTest}:
 * EC-CUBE renders the inline-create inputs through the Symfony FormView
 * (`form_widget(form.tax_rate)`); BeMart renders them through a real
 * Ray.WebFormModule {@see AdminTaxRuleForm} exposed as `body.form`. This
 * test renders EC-CUBE's `form_widget` calls through the SAME form
 * instance so the `tax_rate` / `apply_date` inputs diff to ZERO.
 *
 * The TaxRuleStorageInterface starts empty, so the `{% for TaxRule %}` loop
 * is skipped on both sides; the test focuses on the page skeleton +
 * the inline-create form row.
 */
final class AdminTaxRuleListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var list<string> */
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
        '<title>税率設定 店舗設定 - BeMart</title>',
        '<title>店舗設定 税率設定 - EC-CUBE</title>',
        // Form: EC-CUBE's `_token` hidden CSRF input is rendered by the
        // Symfony FormView; BeMart's port keeps the hidden input
        // (structure) with an empty value — the html context has no
        // per-request CSRF widget.
        '<input type="hidden" id="tax_rule__token" name="_token" value="">',
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

    public function testTaxRuleListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTaxRuleListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-pageTitle">',
            'class="c-primaryCol"',
            'id="tax_rule_item_new"',
            'id="form1"',
            'class="table tabel-sm"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testTaxRuleListRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        $this->assertStringContainsString('id="tax_rule_tax_rate"', $html);
        $this->assertStringContainsString('id="tax_rule_apply_date"', $html);
    }

    public function testTaxRuleListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();
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
            "BeMart's admin Tax-rule list HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
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

        $form = (new FormFactory())->newInstance(AdminTaxRuleForm::class);

        $this->registerEcCubeStubs($twig, $form instanceof AdminTaxRuleForm ? $form : null);

        // The TaxRuleStorageInterface starts empty; feed EC-CUBE the same empty
        // list so the per-row `{% for %}` loop is skipped on both sides.
        return $twig->render('Setting/Shop/tax_rule.twig', [
            'TaxRules' => [],
            'forms' => [],
            'errors' => [],
            'form' => new EcCubeStub([
                '_token' => '_token',
                'tax_rate' => 'tax_rate',
                'rounding_type' => 'rounding_type',
                'apply_date' => 'apply_date',
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
            'menus' => ['setting', 'shop', 'shop_tax'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'id' => self::TEST_ADMIN_ID,
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_setting_shop_tax']),
            ]),
            'subtitle' => '店舗設定',
            'sub_title' => '店舗設定',
            'title' => '税率設定',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminTaxRuleForm|null $form): void
    {
        $messages = AdminJaMessages::forSection(ShopJaMessages::keys());
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
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        // EC-CUBE's `form_widget(form.<field>)` renders through BeMart's
        // real AdminTaxRuleForm so the inline-create inputs are
        // byte-identical to BeMart's port. `_token` (CSRF runtime) and
        // `rounding_type` (mtb_rounding_type master select, no option set
        // in the projection) are NOT declared by AdminTaxRuleForm — they
        // render empty here, mirroring BeMart's port.
        $formFields = ['tax_rate', 'apply_date'];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form, $formFields): Markup {
            if ($form instanceof AdminTaxRuleForm && is_string($field) && in_array($field, $formFields, true)) {
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
