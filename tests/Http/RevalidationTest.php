<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function preg_match;
use function shell_exec;
use function sprintf;
use function str_contains;
use function strpos;
use function substr;
use function trim;

/**
 * A client revalidating its copy over HTTP
 *
 * Every cacheable response goes out with an `ETag`, and `Bootstrap` answers `If-None-Match`
 * before routing - but nothing exercised the pair, so the app could stop emitting the validator
 * or stop checking it and every test would still pass while clients refetched whole pages.
 *
 * At the wire level on purpose: the header has to survive the responder, and a 304 has to carry
 * no body. The in-process suite cannot see either.
 *
 * Two mechanisms answer it, and this pins the contract rather than either one: `Bootstrap` asks
 * the ETag pool before routing - a 304 that runs no resource - and `DownloadResponder` compares
 * the ETag of the resource it just ran. Disabling only the first is invisible here, by design; the
 * cheap path is judged where its cost shows, in the cache log (`var/loop/verify-cache.php
 * help-revalidate`, which requires the `conditional_request` scope to close as a hit).
 */
final class RevalidationTest extends TestCase
{
    private const HOST = '127.0.0.1:8095';
    private const PATH = '/help/about';

    private static PhpServer|null $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$server ??= new PhpServer(self::HOST, __DIR__ . '/prod-json-index.php');
        self::$server->start();
    }

    /** @return array{status: int, headers: string, body: string} */
    private function get(string|null $ifNoneMatch = null): array
    {
        $curl = 'curl -s -i';
        if ($ifNoneMatch !== null) {
            $curl .= ' -H ' . escapeshellarg('If-None-Match: ' . $ifNoneMatch);
        }

        $raw = (string) shell_exec($curl . ' ' . escapeshellarg('http://' . self::HOST . self::PATH));
        $split = strpos($raw, "\r\n\r\n");
        $headers = $split === false ? $raw : substr($raw, 0, $split);
        $body = $split === false ? '' : trim(substr($raw, $split + 4));
        preg_match('/^HTTP\/[\d.]+ (\d{3})/', $headers, $m);

        return ['status' => (int) ($m[1] ?? 0), 'headers' => $headers, 'body' => $body];
    }

    private function etagOf(string $headers): string
    {
        foreach (explode("\r\n", $headers) as $line) {
            if (preg_match('/^ETag:\s*(.+)$/i', $line, $m) === 1) {
                return trim($m[1]);
            }
        }

        return '';
    }

    public function testTheResponseCarriesAValidatorTheClientCanReturn(): string
    {
        $response = $this->get();

        $this->assertSame(200, $response['status']);
        $etag = $this->etagOf($response['headers']);
        $this->assertNotSame('', $etag, 'without an ETag no client can revalidate: ' . $response['headers']);

        return $etag;
    }

    /** @depends testTheResponseCarriesAValidatorTheClientCanReturn */
    public function testReturningItIsAnsweredWithoutTheBody(string $etag): void
    {
        $response = $this->get($etag);

        $this->assertSame(304, $response['status'], 'the whole point of the validator: ' . $response['headers']);
        $this->assertSame('', $response['body'], 'a 304 that carries a body saves the client nothing');
    }

    public function testAValidatorTheServerNeverIssuedIsAnsweredInFull(): void
    {
        // A 304 here would leave the client showing whatever it already had - or nothing at all.
        $response = $this->get('"0000000000000000000000000000000000000000"');

        $this->assertSame(200, $response['status']);
        $this->assertTrue(str_contains($response['body'], '{'), 'the full representation, not an empty 304');
    }
}
