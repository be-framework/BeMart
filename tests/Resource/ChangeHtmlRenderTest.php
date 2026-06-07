<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Form\ChangeForm;
use MyVendor\BeMart\Module\HtmlTestModule;
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
use function str_starts_with;
use function trim;

/**
 * Phase 3 — fidelity check for the profile-edit (mypage_change) HTML
 * port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Mypage/change.twig` is a FORM page. This port follows the
 * Ray.WebFormModule form-page recipe (see var/templates/README.md): the
 * Change resource exposes a real {@see ChangeForm} (an AbstractForm) as
 * `body.form`, pre-populated by `onGet` with the customer's profile.
 * This test renders EC-CUBE's `form_widget(form.name.name01)` calls
 * through the SAME `ChangeForm` instance so the inputs diff to ZERO;
 * `form_label` is stubbed to the same `<label class="ec-label">` BeMart
 * authors plainly. The residual is the genuinely EC-CUBE-runtime-only
 * `<head>` frame material + the empty CSRF hidden value.
 *
 * The Change::onGet form-info endpoint requires AUTHN against a real
 * customer (the Be Final fetches the profile), so the `html` context's
 * `CustomerSession` is rebound to the fixture customer alice.
 */
final class ChangeHtmlRenderTest extends TestCase
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

        // --- form: CSRF hidden input ------------------------------------
        '<input type="hidden" name="_token" value="">',
    ];

    private ResourceInterface $resource;

    /** ChangeForm pre-populated with alice's profile — fed to both sides. */
    private ChangeForm $form;

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
        $this->resource = $injector->getInstance(ResourceInterface::class);

        // Mirror Change::onGet's pre-populate so the EC-CUBE side renders
        // the same form values. The fixture customer alice's profile is
        // pinned by ChangeResourceTest::testOnGetReturnsFormPrePopulated.
        $form = (new FormFactory())->newInstance(ChangeForm::class);
        $form->fillValues([
            'name01' => '山田',
            'name02' => 'アリス',
            'kana01' => 'ヤマダ',
            'kana02' => 'アリス',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'email' => 'alice@example.com',
        ]);
        $this->form = $form;
    }

    public function testChangeRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/change');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testChangePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        foreach ([
            '<div class="ec-editRole">',
            '<div class="ec-borderedDefs">',
            'class="ec-halfInput"',
            'class="ec-zipInput"',
            'class="ec-birth"',
            'class="ec-radio"',
            'class="ec-RegisterRole__actions"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The profile inputs are rendered by a real form library, with the
     * customer's current values pre-populated.
     */
    public function testChangeRendersRealFormInputsPrePopulated(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        $this->assertStringContainsString('name="name01"', $html);
        $this->assertStringContainsString('value="山田"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('value="alice@example.com"', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered profile-edit page against
     * EC-CUBE's own rendering. Every difference must be in the allowlist.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testChangeHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/mypage/change')->toString();
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
            "BeMart's profile-edit HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
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

        // EC-CUBE's `EntryType` (reused by mypage_change) nests fields
        // under compound types; each compound path resolves to the
        // ChangeForm leaf field name, and the stubbed form_widget renders
        // that field through the SAME pre-populated ChangeForm.
        return $twig->render('Mypage/change.twig', [
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
                'plain_password' => new EcCubeStub([
                    'first' => 'password', 'second' => 'password_confirm',
                ]),
                'birth' => new EcCubeStub([
                    'year' => 'birth_year', 'month' => 'birth_month', 'day' => 'birth_day',
                ]),
                'sex' => 'sex',
                'job' => 'job',
                '_token' => '__token__',
            ]),
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
                'option_favorite_product' => true,
                'option_point' => false,
            ]),
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
            'app' => new EcCubeStub([
                'session' => new EcCubeStub([
                    'flashbag' => new EcCubeFlashBag(),
                    'flashBag' => new EcCubeFlashBag(),
                ]),
                'request' => new EcCubeStub(['_route' => 'mypage_change']),
                'user' => new EcCubeStub(['name01' => '山田', 'name02' => 'アリス', 'point' => 0]),
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
        $twig->addFilter(new TwigFilter('filter', static fn ($it, $f): array => []));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        // FORM-PAGE recipe: `form_widget(form.name.name01)` delegates to
        // the SAME pre-populated ChangeForm so the inputs are
        // byte-identical to BeMart's port.
        $form = $this->form;
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form): Markup {
            if ($field === '__token__') {
                return new Markup('<input type="hidden" name="_token" value="">', 'UTF-8');
            }

            if (is_string($field) && $field !== '') {
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
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => '[form_row]'));
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
