<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Koriym\FileUpload\FileUpload;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\Stree\FormatterRegistry;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeRenderer;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function count;
use function dirname;
use function file_put_contents;
use function getenv;
use function json_decode;
use function json_encode;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Follows each story in-process and compares its semantic log tree with tests/Story/golden.
 *
 * `UPDATE_GOLDEN=1 vendor/bin/phpunit tests/Story` records the trees; read their diff before committing.
 */
final class LinkStoryTest extends TestCase
{
    /** Values made anew on each run or checkout, written as one numbered placeholder per distinct value. */
    private const array VOLATILE = [
        '/INQ-[0-9a-f]+\.[0-9]+/' => 'INQ', // inquiry ticket ids
        '/(?<=Interface)[0-9a-f]{8}Null\d+/' => 'Null', // classes Ray.MediaQuery generates for null query ports
    ];

    private ResourceInterface $resource;
    private SemanticLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new SemanticLogger();
        $module = new StoryModule($this->logger, new TestModule(new Meta('MyVendor\\BeMart', 'test')));
        $this->resource = (new Injector($module, dirname(__DIR__, 2) . '/var/tmp/story'))->getInstance(ResourceInterface::class);
    }

    /** @param array<string, mixed> $body */
    #[DataProviderExternal(LinkStories::class, 'followable')]
    public function testFollows(string $uri, int $code, string $path, array $body): void
    {
        $this->assertEnd($this->resource->get($uri), $code, $path, $body);
        $this->assertTree();
    }

    /** @param class-string<\Throwable> $exception */
    #[DataProviderExternal(LinkStories::class, 'unfollowable')]
    public function testRefuses(string $uri, string $exception): void
    {
        $this->expectException($exception);

        $this->resource->get($uri);
    }

    /** @param array<string, string> $query */
    #[DataProviderExternal(LinkStories::class, 'uploads')]
    public function testUploads(string $uri, array $query, string $file, int $code, string $path): void
    {
        $this->assertEnd($this->resource->post($uri, $query + ['file' => FileUpload::fromFile($file)]), $code, $path, []);
    }

    /** @param array<string, mixed> $body */
    private function assertEnd(ResourceObject $ro, int $code, string $path, array $body): void
    {
        $this->assertSame($code, $ro->code);
        $this->assertSame($path, $ro->uri->path);
        $this->assertIsArray($ro->body);
        foreach ($body as $key => $value) {
            $this->assertSame($value, $ro->body[$key] ?? null, $key);
        }
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
        // LogJson::render() leaves out nested context values that stree prints, so render the JSON form stree reads.
        $log = json_decode((string) json_encode($this->logger->flush()), true);
        $this->assertIsArray($log);
        $tree = $this->stabilize($renderer->renderTree($log));
        $golden = sprintf('%s/golden/%s.stree.txt', __DIR__, trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $this->dataName())), '-'));
        if (getenv('UPDATE_GOLDEN') === '1') {
            file_put_contents($golden, $tree);
        }

        $this->assertFileExists($golden, 'Record the tree with UPDATE_GOLDEN=1 and read it before committing.');
        $this->assertStringEqualsFile($golden, $tree);
    }

    private function stabilize(string $tree): string
    {
        $tree = str_replace(dirname(__DIR__, 2), '<root>', $tree);
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
