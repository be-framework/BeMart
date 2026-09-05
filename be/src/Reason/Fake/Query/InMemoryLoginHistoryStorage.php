<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\Result\LoginFailureCount;
use Override;

use function array_map;
use function array_reverse;
use function array_slice;
use function date;
use function dirname;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function session_status;
use function strtotime;
use function time;
use function usort;

use const DATE_ATOM;
use const JSON_THROW_ON_ERROR;
use const PHP_SESSION_ACTIVE;

/**
 * Fake login-attempt audit log — read, append AND throttle counter.
 *
 * Implements the same two-counter rule as the SQL gate: the strict
 * count is per client+loginId and resets on that client's own success,
 * the loose account count is per loginId across all clients and resets
 * on any success.
 *
 * Ray.FakeQuery fixtures are static: append() against a JSONL corpus
 * does not change what the next read sees, and a throttle whose counter
 * never moves is not a throttle. So Fake contexts get a real (if
 * volatile) store, exactly as the browser cart does
 * ({@see SessionCartStorage}) — appended attempts show up in the
 * 管理画面ログイン履歴 grid and drive the failure count.
 *
 * Rows live in the PHP session when one is active, so a browser demo can
 * actually lock itself out across requests; otherwise they live on this
 * instance, which keeps each test's counter to itself. The sample
 * attempts the grid has always shown are seeded from the JSON corpus,
 * and being months old they sit outside every counting window.
 *
 * The store, not the caller, timestamps a row — standing in for the
 * database's NOW().
 */
final class InMemoryLoginHistoryStorage implements LoginHistoryStorageInterface, LoginAttemptGateInterface
{
    private const SESSION_KEY = 'bemart_fake_login_history';

    /** @var list<array{timestamp: string, loginId: string, success: bool, clientIp: string}>|null */
    private array|null $rows = null;

    private readonly string $seedFile;

    public function __construct(string|null $seedFile = null)
    {
        $this->seedFile = $seedFile ?? dirname(__DIR__, 4) . '/var/fake/login_history.json';
    }

    /** @return list<LoginHistoryEntity> */
    #[Override]
    public function list(int $limit = 50): array
    {
        $rows = array_reverse($this->rows());
        // Stable sort keeps the reverse-insertion order for equal
        // timestamps, matching the SQL `create_date DESC, id DESC`.
        usort($rows, static fn (array $a, array $b): int => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));

        return array_map(
            static fn (array $row): LoginHistoryEntity => new LoginHistoryEntity(
                $row['timestamp'],
                $row['loginId'],
                $row['success'],
                $row['clientIp'],
            ),
            array_slice($rows, 0, $limit),
        );
    }

    #[Override]
    public function append(string $loginId, bool $success, string $clientIp): void
    {
        $rows = $this->rows();
        $rows[] = [
            'timestamp' => date(DATE_ATOM),
            'loginId' => $loginId,
            'success' => $success,
            'clientIp' => $clientIp,
        ];

        $this->writeRows($rows);
    }

    #[Override]
    public function failuresSinceLastSuccess(string $loginId, string $clientIp, int $windowMinutes): LoginFailureCount
    {
        return $this->countSinceLastSuccess($loginId, $clientIp, $windowMinutes);
    }

    #[Override]
    public function accountFailuresSinceLastSuccess(string $loginId, int $windowMinutes): LoginFailureCount
    {
        return $this->countSinceLastSuccess($loginId, null, $windowMinutes);
    }

    /**
     * Count failures for `$loginId` (from `$clientIp` when given) since
     * the newest matching success — the same two-counter rule as the SQL
     * gate: the per-client counter resets on that client's own success,
     * the per-account counter on any success for the loginId.
     */
    private function countSinceLastSuccess(string $loginId, string|null $clientIp, int $windowMinutes): LoginFailureCount
    {
        $since = time() - ($windowMinutes * 60);
        $failures = 0;
        foreach ($this->rows() as $row) {
            if (
                $row['loginId'] !== $loginId
                || ($clientIp !== null && $row['clientIp'] !== $clientIp)
                || strtotime($row['timestamp']) < $since
            ) {
                continue;
            }

            $failures = $row['success'] ? 0 : $failures + 1;
        }

        return new LoginFailureCount($failures);
    }

    /** @return list<array{timestamp: string, loginId: string, success: bool, clientIp: string}> */
    private function rows(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            /** @var mixed $rows */
            $rows = $_SESSION[self::SESSION_KEY] ?? null;
            if (! is_array($rows)) {
                $rows = $this->seedRows();
                $_SESSION[self::SESSION_KEY] = $rows;
            }

            /** @var list<array{timestamp: string, loginId: string, success: bool, clientIp: string}> $rows */
            return $rows;
        }

        return $this->rows ??= $this->seedRows();
    }

    /** @param list<array{timestamp: string, loginId: string, success: bool, clientIp: string}> $rows */
    private function writeRows(array $rows): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = $rows;

            return;
        }

        $this->rows = $rows;
    }

    /** @return list<array{timestamp: string, loginId: string, success: bool, clientIp: string}> */
    private function seedRows(): array
    {
        $json = file_get_contents($this->seedFile);
        /** @var mixed $decoded */
        $decoded = $json === false ? [] : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $rows = [];
        foreach (is_array($decoded) ? $decoded : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'timestamp' => is_string($row['timestamp'] ?? null) ? $row['timestamp'] : date(DATE_ATOM),
                'loginId' => is_string($row['loginId'] ?? null) ? $row['loginId'] : '',
                'success' => is_bool($row['success'] ?? null) ? $row['success'] : false,
                'clientIp' => is_string($row['clientIp'] ?? null) ? $row['clientIp'] : '',
            ];
        }

        return $rows;
    }
}
