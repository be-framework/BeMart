<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use RuntimeException;

use function array_map;
use function fclose;
use function in_array;
use function sprintf;
use function stream_socket_get_name;
use function stream_socket_server;

/**
 * A loopback host:port that no other process listens on, one per owner for the whole process
 *
 * The built-in server reports a port it failed to bind as started, so a fixed port shared with
 * another run sends the tests to that run's server.
 */
final class LocalServerHost
{
    private const ATTEMPTS = 10;

    /** @var array<string, self> */
    private static array $taken = [];

    private function __construct(
        public readonly string $host,
    ) {
    }

    public static function for(string $owner): self
    {
        if (isset(self::$taken[$owner])) {
            return self::$taken[$owner];
        }

        $inUse = array_map(static fn (self $taken): string => $taken->host, self::$taken);
        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            // Port 0 lets the OS pick a free port; it is released for the server to bind right after
            $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
            if ($socket === false) {
                throw new RuntimeException($error);
            }

            $host = stream_socket_get_name($socket, false);
            fclose($socket);
            if ($host === false) {
                throw new RuntimeException('The loopback socket has no name');
            }

            // The OS may hand a released port out again, and another owner's server may not be up yet
            if (! in_array($host, $inUse, true)) {
                return self::$taken[$owner] = new self($host);
            }
        }

        throw new RuntimeException(sprintf('No loopback port for %s that this process has not given out already', $owner));
    }
}
