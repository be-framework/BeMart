<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use MyVendor\BeMart\Tests\Fake\Csrf\FakeCsrfToken;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

use function is_array;

/**
 * Follows the stories that read back what they wrote, on the SQL context inside a rolled-back transaction.
 *
 * Needs the MySQL that tests/Hypermedia uses; the tree is not compared, as the ids the database assigns change per run.
 */
final class PersistedLinkStoryTest extends TestCase
{
    private WorkflowDbSession|null $db = null;

    protected function setUp(): void
    {
        $this->db = WorkflowDbSession::startForAdmin(StoryModule::ADMIN_ID, FakeCsrfToken::DEFAULT_TOKEN);
    }

    protected function tearDown(): void
    {
        $this->db?->restore();
        $this->db = null;
    }

    /** @param array<string, mixed> $body */
    #[DataProviderExternal(LinkStories::class, 'persisted')]
    public function testFollows(string $uri, int $code, string $path, array $body): void
    {
        $this->assertNotNull($this->db);
        $ro = $this->db->resource()->get($uri);

        $this->assertSame($code, $ro->code);
        $this->assertSame($path, $ro->uri->path);
        $this->assertTrue(is_array($ro->body));
        foreach ($body as $key => $value) {
            $this->assertSame($value, $ro->body[$key] ?? null, $key);
        }
    }
}
