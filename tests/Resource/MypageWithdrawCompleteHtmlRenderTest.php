<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function dirname;
use function implode;
use function is_dir;
use function nl2br;
use function number_format;
use function str_replace;

/**
 * Phase 3 — functional / semantic render check for the withdrawal-complete
 * page (goMypageWithdrawComplete).
 *
 * The template is a clean-room IdeaStore implementation — not a port of
 * EC-CUBE markup. Tests verify:
 *
 *   L1  Required fields / data output: HTTP 200, HTML document shape,
 *       IdeaStore base layout present, page title, heading hierarchy,
 *       ALPS transition ID available, no account navigation (the customer
 *       is already withdrawn / logged out).
 *
 *   L2  Hypermedia contract: the goTop link ( href="/" ) is present and
 *       reachable as a plain anchor.
 *
 * EC-CUBE parity (exact markup comparison) is archived below under
 * @group ec-cube-parity-archived.
 */
final class MypageWithdrawCompleteHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ------------------------------------------------------------------ L1

    public function testWithdrawCompletePageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/withdraw-complete');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testWithdrawCompletePageUsesIdeaStoreBaseLayout(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-complete')->toString();

        // IdeaStore layout landmark elements
        $this->assertStringContainsString('idea-store', $html);
        $this->assertStringContainsString('<main>', $html);
        $this->assertStringContainsString('idea-store.css', $html);
    }

    public function testWithdrawCompletePageTitleContainsExpectedLabel(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-complete')->toString();

        $this->assertStringContainsString('退会', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
    }

    public function testWithdrawCompletePageRendersCompletionHeading(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-complete')->toString();

        // At least one heading-level element must signal completion
        $this->assertMatchesRegularExpression('/<h[12][^>]*>[^<]*退会[^<]*<\/h[12]>/u', $html);
    }

    public function testWithdrawCompletePageHasNoAccountNavigation(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-complete')->toString();

        // The customer is logged out — no account nav should appear
        $this->assertStringNotContainsString('idea-account-nav', $html);
        $this->assertStringNotContainsString('/mypage/favorite-list', $html);
        $this->assertStringNotContainsString('/mypage/change', $html);
    }

    public function testWithdrawCompletePageHasNoForm(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-complete')->toString();

        // Completion page: no POST form elements.
        // (The IdeaStore layout header includes a GET search form; that is expected.)
        $this->assertStringNotContainsString('method="post"', $html);
    }

    // ------------------------------------------------------------------ L2

    public function testWithdrawCompletePageProvidesGoTopLink(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-complete')->toString();

        // ALPS #[Link rel=goTop href=page://self/] maps to href="/"
        $this->assertMatchesRegularExpression('/<a\s[^>]*href=["\']\/["\'][^>]*>/', $html);
    }

    // ------------------------------------------------------------------ EC-CUBE parity (archived)

    /**
     * EC-CUBE exact-rendering comparison — archived.
     *
     * The template is now a clean-room IdeaStore implementation and no
     * longer tracks EC-CUBE markup. These tests are retained as dead code
     * for traceability; they are skipped permanently.
     *
     * @group ec-cube-parity-archived
     */
    public function testWithdrawCompletePagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity archived: template rebuilt as clean-room IdeaStore design.'
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testWithdrawCompleteHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE rendering comparison archived: template rebuilt as clean-room IdeaStore design.'
        );

        // Dead code kept for traceability — never executed.
        $beMart = $this->resource->get('page://self/mypage/withdraw-complete')->toString();
        $ecCube = $this->renderEcCube();
        $this->assertSame($beMart, $ecCube);
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

        return $twig->render('Mypage/withdraw_complete.twig', [
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
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
            ]), 'request' => new EcCubeStub(['_route' => 'mypage_withdraw_complete'])]),
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
        $twig->addFilter(new TwigFilter('nl2br', static fn ($s): string => nl2br((string) $s), ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));
    }
}
