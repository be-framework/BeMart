<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use MyVendor\BeMart\Tests\Fake\Csrf\FakeCsrfToken;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\DataProviderExternal;

/**
 * Follows the stories that read back what they wrote, on the SQL context inside a rolled-back transaction.
 *
 * Needs the MySQL that tests/Hypermedia uses; the tree is not compared, as the ids the database assigns change per run.
 */
final class PersistedLinkStoryTest extends AbstractLinkStoryCase
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
        $this->assertEnd($this->db->resource()->get($uri), $code, $path, $body);
    }
}
