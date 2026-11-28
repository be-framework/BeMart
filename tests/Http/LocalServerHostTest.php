<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use MyVendor\BeMart\Tests\Support\LocalServerHost;
use PHPUnit\Framework\TestCase;

use function array_unique;
use function count;
use function range;
use function stream_socket_server;

final class LocalServerHostTest extends TestCase
{
    public function testEachOwnerGetsItsOwnFreeHostAndKeepsIt(): void
    {
        $hosts = [];
        foreach (range(1, 50) as $n) {
            $hosts[] = LocalServerHost::for(self::class . $n)->host;
        }

        $this->assertCount(count($hosts), array_unique($hosts));
        $this->assertSame($hosts[0], LocalServerHost::for(self::class . '1')->host);
        $server = stream_socket_server('tcp://' . $hosts[0]);
        $this->assertNotFalse($server, 'the host is free for a server to bind');
    }
}
