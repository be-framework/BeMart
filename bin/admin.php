<?php

declare(strict_types=1);

use MyVendor\BeMart\Module\DatabaseUrl;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * BeMart admin maintenance CLI (dev utility).
 *
 * Operates directly on EC-CUBE's `dtb_member` via DATABASE_URL — handy when
 * 2FA locks you out of the admin and the management UI is unreachable.
 *
 *   composer admin -- list                  list admins
 *   composer admin -- reset-2fa <loginId>   clear 2FA (re-enroll QR next login)
 *   composer admin -- disable  <loginId>    soft delete (work_id=0)
 *   composer admin -- delete   <loginId>    hard delete the row
 */

const ADMIN_USAGE = <<<TXT
BeMart admin maintenance CLI

  composer admin -- list                  管理者一覧 (id / login_id / name / 2FA / 有効)
  composer admin -- reset-2fa <loginId>   2FAを解除（次回ログインでQRを再登録）
  composer admin -- disable   <loginId>   無効化 (work_id=0 ＝ 管理画面の削除と同じ)
  composer admin -- delete    <loginId>   行ごと削除

  直接実行: php bin/admin.php <action> [loginId]
  接続先は DATABASE_URL 環境変数を使用します。

TXT;

main($_SERVER['argv'] ?? []);

/** @param list<string> $argv */
function main(array $argv): void
{
    $action = $argv[1] ?? '';
    $loginId = $argv[2] ?? '';

    if ($action === '' || $action === 'help' || $action === '-h' || $action === '--help') {
        fwrite(STDOUT, ADMIN_USAGE);
        exit($action === '' ? 1 : 0);
    }

    $pdo = connect();

    switch ($action) {
        case 'list':
            listAdmins($pdo);
            break;
        case 'reset-2fa':
            resetTwoFactor($pdo, requireLoginId($loginId));
            break;
        case 'disable':
            disableAdmin($pdo, requireLoginId($loginId));
            break;
        case 'delete':
            deleteAdmin($pdo, requireLoginId($loginId));
            break;
        default:
            fail('不明なアクション: ' . $action);
    }
}

function connect(): PDO
{
    try {
        $db = DatabaseUrl::fromEnvironment();

        return new PDO($db->dsn, $db->user, $db->pass, $db->options);
    } catch (Throwable $e) {
        fwrite(STDERR, 'DB接続に失敗しました: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

function requireLoginId(string $loginId): string
{
    if ($loginId === '') {
        fail('loginId が必要です。');
    }

    return $loginId;
}

function listAdmins(PDO $pdo): void
{
    /** @var list<array<string, mixed>> $rows */
    $rows = $pdo->query(
        'SELECT id, login_id, name, work_id, two_factor_auth_enabled FROM dtb_member ORDER BY id',
    )->fetchAll();

    if ($rows === []) {
        fwrite(STDOUT, '管理者が登録されていません。' . PHP_EOL);

        return;
    }

    fwrite(STDOUT, sprintf("%-4s  %-22s  %-16s  %-4s  %s" . PHP_EOL, 'id', 'login_id', 'name', '2FA', 'work'));
    foreach ($rows as $row) {
        fwrite(STDOUT, sprintf(
            "%-4s  %-22s  %-16s  %-4s  %s" . PHP_EOL,
            (string) $row['id'],
            (string) $row['login_id'],
            (string) ($row['name'] ?? ''),
            ((int) $row['two_factor_auth_enabled'] === 1) ? 'on' : 'off',
            ((int) $row['work_id'] === 1) ? 'yes' : 'no',
        ));
    }
}

function resetTwoFactor(PDO $pdo, string $loginId): void
{
    $stmt = $pdo->prepare(
        'UPDATE dtb_member SET two_factor_auth_key = NULL, two_factor_auth_enabled = 0 WHERE login_id = :loginId',
    );
    $stmt->execute(['loginId' => $loginId]);
    report($stmt->rowCount(), $loginId, '2FAを解除しました（次回ログインでQRを再登録）');
}

function disableAdmin(PDO $pdo, string $loginId): void
{
    $stmt = $pdo->prepare('UPDATE dtb_member SET work_id = 0, update_date = NOW() WHERE login_id = :loginId');
    $stmt->execute(['loginId' => $loginId]);
    report($stmt->rowCount(), $loginId, '無効化しました (work_id=0)');
}

function deleteAdmin(PDO $pdo, string $loginId): void
{
    $stmt = $pdo->prepare('DELETE FROM dtb_member WHERE login_id = :loginId');
    $stmt->execute(['loginId' => $loginId]);
    report($stmt->rowCount(), $loginId, '削除しました');
}

function report(int $rows, string $loginId, string $done): void
{
    if ($rows === 0) {
        fwrite(STDERR, sprintf('該当する管理者が見つかりません: %s' . PHP_EOL, $loginId));
        exit(2);
    }

    fwrite(STDOUT, sprintf('%s: %s (%d 件)' . PHP_EOL, $loginId, $done, $rows));
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL . PHP_EOL . ADMIN_USAGE);
    exit(1);
}
