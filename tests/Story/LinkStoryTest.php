<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\AppMeta\Meta;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\Resource\ResourceInterface;
use Koriym\FileUpload\FileUpload;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\Stree\FormatterRegistry;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeRenderer;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Ray\Di\Injector;

use function count;
use function dirname;
use function file_put_contents;
use function getenv;
use function glob;
use function implode;
use function is_dir;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strtolower;
use function trim;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Follows each story in-process and compares its semantic log tree, and the events extracted from it, with
 * tests/Story/golden.
 *
 * `UPDATE_GOLDEN=1 vendor/bin/phpunit tests/Story` records the trees; read their diff before committing.
 */
final class LinkStoryTest extends AbstractLinkStoryCase
{
    /**
     * Values made anew on each run or checkout, written as one numbered placeholder per distinct value
     *
     * Neither the schemas nor the stories can fix them: a schema's x-volatile does not reach the request URI a value
     * also appears in, and the code that makes them takes no generator to inject.
     */
    private const array VOLATILE = [
        '/INQ-[0-9a-f]+\.[0-9]+/' => 'INQ', // ContactSubmitted makes the ticket id with uniqid()
        '/(?<=orderNo: |orderNo=)[0-9a-f]{32}/' => 'OrderNo', // OrderNoProvider draws the number of a placed order at random
        '/(?<=orderDate: |paymentDate: )\d{4}-\d\d-\d\dT\S+/' => 'Now', // a placed order is stamped with the clock
    ];

    /** The line a throw sits on moves with any edit above it in that file; the file it names stays */
    private const string SOURCE_LINE = '/(\bline: )\d+$/m';

    /** The class generated for a null binding; the interface before it names the binding */
    private const string NULL_BINDING = '/(?<=Interface)[0-9a-f]{8}Null\d+/';

    private ResourceInterface $resource;
    private SemanticLogger $logger;

    /** Each run weaves the null bindings under new class names, so the files of earlier runs would only pile up */
    public static function setUpBeforeClass(): void
    {
        foreach ((array) glob(dirname(__DIR__, 2) . '/var/tmp/story*/*') as $file) {
            is_string($file) && is_file($file) && unlink($file);
        }
    }

    protected function setUp(): void
    {
        $this->signIn(null);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'followable')]
    public function testFollows(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->assertStory($uri, $code, $path, $body, $flow);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'forMembers')]
    public function testFollowsAsMember(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->signIn(StoryModule::MEMBER_ID);
        $this->assertStory($uri, $code, $path, $body, $flow);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'forBuyers')]
    public function testFollowsAsBuyer(string $uri, int $code, string $path, array $body, string|array $flow = []): void
    {
        $this->signIn(StoryModule::BUYER_ID);
        $this->assertStory($uri, $code, $path, $body, $flow);
    }

    /**
     * @param class-string<\Throwable> $exception
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'unfollowable')]
    public function testRefuses(string $uri, string $exception, string|array $flow): void
    {
        $this->assertFlow($uri, $flow);
        $this->expectException($exception);

        $this->resource->get($uri);
    }

    /**
     * @param array<string, string> $query
     * @param string|list<string> $flow
     */
    #[DataProviderExternal(LinkStories::class, 'uploads')]
    public function testUploads(string $uri, array $query, string $file, int $code, string $path, string|array $flow): void
    {
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->post($uri, $query + ['file' => FileUpload::fromFile($file)]), $code, $path, []);
    }

    private function signIn(string|null $customerId): void
    {
        $this->logger = new SemanticLogger();
        $module = new StoryModule($this->logger, new TestModule(new Meta('MyVendor\\BeMart', 'test')), $customerId);
        $tmp = dirname(__DIR__, 2) . '/var/tmp/story' . ($customerId === null ? '' : '-' . $customerId);
        // Ray.Di falls back to sys_get_temp_dir(), shared by every checkout, when the dir is missing
        is_dir($tmp) || mkdir($tmp, 0777, true);
        $this->resource = (new Injector($module, $tmp))->getInstance(ResourceInterface::class);
    }

    /**
     * @param array<string, mixed> $body
     * @param string|list<string> $flow
     */
    private function assertStory(string $uri, int $code, string $path, array $body, string|array $flow): void
    {
        $this->assertFlow($uri, $flow);
        $this->assertEnd($this->resource->get($uri), $code, $path, $body);
        $this->assertTree();
    }

    private function assertTree(): void
    {
        $vendor = dirname(__DIR__, 2) . '/vendor';
        $renderer = new TreeRenderer(new RenderConfig(
            true,
            0.0,
            0,
            formatters: FormatterRegistry::fromInstalledPackages($vendor . '/composer/installed.json'),
            snapshot: true,
            schemaDir: $vendor . '/bear/event-sourcing/docs/schemas',
        ));
        $logJson = $this->logger->flush();
        // LogJson::render() leaves out nested context values that stree prints, so render the JSON form stree reads.
        $log = json_decode((string) json_encode($logJson), true);
        $this->assertIsArray($log);
        $tree = $this->stabilize($renderer->renderTree($log) . $this->events($logJson));
        $golden = sprintf('%s/golden/%s.stree.txt', __DIR__, trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $this->dataName())), '-'));
        if (getenv('UPDATE_GOLDEN') === '1') {
            file_put_contents($golden, $tree);
        }

        $this->assertFileExists($golden, 'Record the tree with UPDATE_GOLDEN=1 and read it before committing.');
        $this->assertStringEqualsFile($golden, $tree);
    }

    /** The requests bear/event-sourcing extracts as events, the ones a replay runs again */
    private function events(LogJson $log): string
    {
        $lines = [];
        foreach ((new SemanticLogExtractor())->extract($log) as $event) {
            $lines[] = sprintf('%s %s %s', $event->method, $event->uri, json_encode($event->params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        return "\n\nevents:\n" . ($lines === [] ? '(none)' : implode("\n", $lines));
    }

    private function stabilize(string $tree): string
    {
        $tree = str_replace(dirname(__DIR__, 2), '<root>', $tree);
        $tree = (string) preg_replace(self::SOURCE_LINE, '$1[line]', $tree);
        $tree = (string) preg_replace(self::NULL_BINDING, '<Null>', $tree);
        foreach (self::VOLATILE as $pattern => $name) {
            $seen = [];
            $tree = (string) preg_replace_callback($pattern, static function (array $match) use (&$seen, $name): string {
                $seen[$match[0]] ??= sprintf('<%s-%d>', $name, count($seen) + 1);

                return $seen[$match[0]];
            }, $tree);
        }

        return $tree;
    }
}
