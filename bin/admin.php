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

    // Validate before any side effect: a bad action or missing loginId must
    // fail before a DB connection is opened.
    if (! in_array($action, ['list', 'reset-2fa', 'disable', 'delete'], true)) {
        fail('不明なアクション: ' . $action);
    }

    if ($action !== 'list' && $loginId === '') {
        fail('loginId が必要です。');
    }

    $pdo = connect();

    switch ($action) {
        case 'list':
            listAdmins($pdo);
            break;
        case 'reset-2fa':
            resetTwoFactor($pdo, $loginId);
            break;
        case 'disable':
            disableAdmin($pdo, $loginId);
            break;
        case 'delete':
            deleteAdmin($pdo, $loginId);
            break;
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

function adminExists(PDO $pdo, string $loginId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM dtb_member WHERE login_id = :loginId LIMIT 1');
    $stmt->execute(['loginId' => $loginId]);

    return (bool) $stmt->fetchColumn();
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
    if (! adminExists($pdo, $loginId)) {
        notFound($loginId);
    }

    $stmt = $pdo->prepare(
        'UPDATE dtb_member SET two_factor_auth_key = NULL, two_factor_auth_enabled = 0 WHERE login_id = :loginId',
    );
    $stmt->execute(['loginId' => $loginId]);
    // rowCount() is 0 when the admin exists but is already reset; existence is
    // checked above, so that is "already done", not "not found".
    $done = $stmt->rowCount() > 0 ? '2FAを解除しました（次回ログインでQRを再登録）' : '既に2FAは未設定です';
    fwrite(STDOUT, sprintf('%s: %s' . PHP_EOL, $loginId, $done));
}

function disableAdmin(PDO $pdo, string $loginId): void
{
    if (! adminExists($pdo, $loginId)) {
        notFound($loginId);
    }

    $stmt = $pdo->prepare('UPDATE dtb_member SET work_id = 0, update_date = NOW() WHERE login_id = :loginId');
    $stmt->execute(['loginId' => $loginId]);
    fwrite(STDOUT, sprintf('%s: 無効化しました (work_id=0)' . PHP_EOL, $loginId));
}

function deleteAdmin(PDO $pdo, string $loginId): void
{
    $stmt = $pdo->prepare('DELETE FROM dtb_member WHERE login_id = :loginId');
    $stmt->execute(['loginId' => $loginId]);
    // DELETE affects 0 rows only when nothing matched — unambiguously not found.
    if ($stmt->rowCount() === 0) {
        notFound($loginId);
    }

    fwrite(STDOUT, sprintf('%s: 削除しました' . PHP_EOL, $loginId));
}

function notFound(string $loginId): never
{
    fwrite(STDERR, sprintf('該当する管理者が見つかりません: %s' . PHP_EOL, $loginId));
    exit(2);
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL . PHP_EOL . ADMIN_USAGE);
    exit(1);
}
