<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;

/**
 * Storage-layer coverage for {@see MailTemplateStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see DeliveryStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminMailTemplateResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip /
 * NULL coalescing / unknown-id rejection on update.
 */
final class SqlMailTemplateStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertMailTemplate(['name' => '注文完了メール']);
        $secondId = $this->insertMailTemplate(['name' => '会員登録完了メール']);
        $thirdId = $this->insertMailTemplate(['name' => 'パスワード再発行メール']);

        $storage = $this->sql(MailTemplateStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(MailTemplateEntity::class, $rows);
        // ORDER BY id ASC.
        $this->assertSame($firstId, $rows[0]->mailTemplateId);
        $this->assertSame($secondId, $rows[1]->mailTemplateId);
        $this->assertSame($thirdId, $rows[2]->mailTemplateId);
        $this->assertSame('注文完了メール', $rows[0]->mailTemplateName);
        $this->assertSame('会員登録完了メール', $rows[1]->mailTemplateName);
        $this->assertSame('パスワード再発行メール', $rows[2]->mailTemplateName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(MailTemplateStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testFindByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertMailTemplate([
            'name' => '注文完了メール',
            'file_name' => 'Mail/order.twig',
            'mail_subject' => 'ご注文ありがとうございます',
        ]);

        $storage = $this->sql(MailTemplateStorageInterface::class);
        $entity = $storage->findById($id);

        $this->assertInstanceOf(MailTemplateEntity::class, $entity);
        $this->assertSame($id, $entity->mailTemplateId);
        $this->assertSame('注文完了メール', $entity->mailTemplateName);
        $this->assertSame('Mail/order.twig', $entity->fileName);
        $this->assertSame('ご注文ありがとうございます', $entity->subject);
    }

    public function testFindByIdCoercesNullableColumnsToEmptyString(): void
    {
        // name / file_name / mail_subject are all nullable in EC-CUBE
        // but MailTemplateEntity declares them non-null. The hydrator
        // coalesces NULL → '' so the projection shape stays stable
        // across externally-inserted rows.
        $id = $this->insertMailTemplate([
            'name' => null,
            'file_name' => null,
            'mail_subject' => null,
        ]);

        $storage = $this->sql(MailTemplateStorageInterface::class);
        $entity = $storage->findById($id);

        $this->assertInstanceOf(MailTemplateEntity::class, $entity);
        $this->assertSame('', $entity->mailTemplateName);
        $this->assertSame('', $entity->fileName);
        $this->assertSame('', $entity->subject);
    }

    public function testFindByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(MailTemplateStorageInterface::class);
        $this->assertNull($storage->findById(99999999));
    }

    public function testUpdateReplacesSubjectInPlace(): void
    {
        $id = $this->insertMailTemplate([
            'name' => '注文完了メール',
            'file_name' => 'Mail/order.twig',
            'mail_subject' => '旧件名',
        ]);

        $storage = $this->sql(MailTemplateStorageInterface::class);
        $storage->update(new MailTemplateEntity(
            mailTemplateId: $id,
            mailTemplateName: '注文完了メール',
            fileName: 'Mail/order.twig',
            subject: '【更新】ご注文ありがとうございます',
        ));

        $read = $storage->findById($id);
        $this->assertInstanceOf(MailTemplateEntity::class, $read);
        $this->assertSame('【更新】ご注文ありがとうございます', $read->subject);

        // Row count unchanged (UPDATE, not INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testUpdateDoesNotTouchFileName(): void
    {
        // file_name is fixed at create time and not editable
        // post-create — update() writes mail_subject only, even if the
        // incoming Entity carries a different fileName.
        $id = $this->insertMailTemplate([
            'file_name' => 'Mail/order.twig',
            'mail_subject' => '旧件名',
        ]);

        $storage = $this->sql(MailTemplateStorageInterface::class);
        $storage->update(new MailTemplateEntity(
            mailTemplateId: $id,
            mailTemplateName: 'whatever',
            fileName: 'Mail/HIJACKED.twig',
            subject: '新件名',
        ));

        // Raw column probe — file_name is the original, untouched.
        $stmt = $this->pdo->prepare(
            'SELECT file_name, mail_subject FROM dtb_mail_template WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame('Mail/order.twig', $row['file_name']);
        $this->assertSame('新件名', $row['mail_subject']);
    }

    public function testUpdatePreservesUntouchedColumns(): void
    {
        // creator_id / deletable / discriminator_type are NOT in the
        // narrowed contract — update() leaves them as the row carried.
        $id = $this->insertMailTemplate([
            'mail_subject' => '旧件名',
            'deletable' => 1,
        ]);

        $storage = $this->sql(MailTemplateStorageInterface::class);
        $storage->update(new MailTemplateEntity(
            mailTemplateId: $id,
            mailTemplateName: 'whatever',
            fileName: 'Mail/whatever.twig',
            subject: '新件名',
        ));

        $stmt = $this->pdo->prepare(
            'SELECT creator_id, deletable, discriminator_type '
            . 'FROM dtb_mail_template WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertNull($row['creator_id']);
        $this->assertSame(1, (int) $row['deletable']);
        $this->assertSame('mailtemplate', $row['discriminator_type']);
    }

    public function testUpdateRaisesNotFoundForUnknownId(): void
    {
        // Update-only contract — an unknown id is a 404, never an
        // INSERT. The Final's failure ladder maps this exception to
        // Code::NOT_FOUND.
        $storage = $this->sql(MailTemplateStorageInterface::class);

        $this->expectException(MailTemplateNotFoundException::class);
        $storage->update(new MailTemplateEntity(
            mailTemplateId: 99999999,
            mailTemplateName: 'ghost',
            fileName: 'Mail/ghost.twig',
            subject: 'ghost',
        ));
    }

    public function testUpdateUnknownIdDoesNotInsert(): void
    {
        $storage = $this->sql(MailTemplateStorageInterface::class);

        try {
            $storage->update(new MailTemplateEntity(
                mailTemplateId: 12345,
                mailTemplateName: 'ghost',
                fileName: 'Mail/ghost.twig',
                subject: 'ghost',
            ));
        } catch (MailTemplateNotFoundException) {
            // expected
        }

        // The failed update left no row behind.
        $this->assertSame([], $storage->list());
    }
}
