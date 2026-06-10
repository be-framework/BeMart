<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\Code;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\TestCase;

use function assert;
use function bin2hex;
use function random_bytes;

final class WorkflowDbSessionTest extends TestCase
{
    public function testResourceMutationUsesRollbackProtectedConnection(): void
    {
        $csrfToken = 'workflow-db-session-csrf';
        $email = 'workflow-db-session-' . bin2hex(random_bytes(4)) . '@example.test';
        $session = WorkflowDbSession::startWithCsrfToken($csrfToken);
        $db = $session->injector()->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);

        try {
            self::assertSame(0, $this->customerCount($db, $email));

            $registered = $session->resource()->post('page://self/entry', [
                'email' => $email,
                'password' => 'workflow-password-2026',
                'name01' => 'Workflow',
                'name02' => 'Rollback',
                'kana01' => 'ワークフロー',
                'kana02' => 'ロールバック',
                'phoneNumber' => '0312345678',
                'postalCode' => '1000001',
                'pref' => 13,
                'addr01' => '千代田区',
                'addr02' => '1-1',
                'csrfToken' => $csrfToken,
            ]);

            self::assertSame(Code::CREATED, $registered->code);
            self::assertSame(
                1,
                $this->customerCount($db, $email),
                'WorkflowDbSession must expose Resource writes through the same transaction it rolls back.',
            );
        } finally {
            $session->restore();
        }

        self::assertSame(
            0,
            $this->customerCount($db, $email),
            'WorkflowDbSession::restore() must roll back operational rows created through Resource transitions.',
        );
    }

    private function customerCount(ExtendedPdoInterface $db, string $email): int
    {
        return (int) $db->fetchValue(
            'SELECT COUNT(*) FROM dtb_customer WHERE email = :email',
            ['email' => $email],
        );
    }
}
