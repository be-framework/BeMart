<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use Override;
use PDO;

/**
 * Real PDO-backed MailTemplate storage — Phase 2b.
 *
 * Mirrors {@see FakeMailTemplateStorage} against the live EC-CUBE 4.3
 * schema (`dtb_mail_template`). After the 厳密移植 narrowing
 * (MailTemplate Phase A), the 4-field MailTemplateEntity projection
 * (mailTemplateId / mailTemplateName / fileName / subject) lines up
 * 1:1 with the modeled dtb_mail_template columns (id / name /
 * file_name / mail_subject) — there are no body columns to touch:
 * dtb_mail_template has none. EC-CUBE 4.3 stores mail bodies as Twig
 * files on disk and `file_name` is the path; the body is therefore
 * never a database concern.
 *
 * Scope (Wave 8 + Wave 9 — same as MailTemplateStorageInterface):
 *   - list()                              → every template, id ASC
 *   - findById(int $mailTemplateId)       → one template or null
 *   - update(MailTemplateEntity $entity)  → replace mail_subject only
 *
 * dtb_mail_template has five more columns the BeMart slice does NOT
 * project: creator_id, create_date, update_date, deletable,
 * discriminator_type. The migration scope only covers UPDATE of the
 * subject (no create-new-template flow — that would need to set
 * file_name + write the on-disk Twig file), so the INSERT path is
 * deliberately absent: there is no MailTemplateIdGenerator and `update`
 * raises {@see MailTemplateNotFoundException} on an unknown id rather
 * than inserting.
 *
 * `update()` writes `mail_subject` (+ `update_date = NOW()`) and does
 * NOT touch `file_name` — the file path is fixed at create time and
 * not editable post-create (the Entity doc states this). `creator_id`
 * is left untouched on UPDATE (a Doctrine Blameable concern; the
 * BeMart slice has no member identity to write), and `deletable` /
 * `discriminator_type` are likewise preserved — UPDATE only sets the
 * two columns the contract narrows to.
 *
 * Coercions:
 *   - `name` / `file_name` / `mail_subject` are all nullable in
 *     EC-CUBE but non-null on MailTemplateEntity. The hydrator
 *     coalesces NULL → '' so the projection's non-null shape is
 *     preserved across externally-inserted rows.
 *   - `id` is `int unsigned`; MailTemplateEntity::mailTemplateId is
 *     `int` — a direct `(int)` cast on read, bound as int on write.
 *
 * List ordering: `ORDER BY id ASC` — same parity convention as
 * SqlBlockStorage / SqlPageStorage / SqlDeliveryStorage.
 *
 * DI is intentionally NOT wired in production (FakeMailTemplateStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlMailTemplateStorage implements MailTemplateStorageInterface
{
    private const SELECT_COLUMNS = 'id, name, file_name, mail_subject';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<MailTemplateEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_mail_template '
            . 'ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function findById(int $mailTemplateId): MailTemplateEntity|null
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_mail_template '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $mailTemplateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function update(MailTemplateEntity $entity): void
    {
        // Update-only contract — an unknown id is a 404, never an
        // INSERT. Probe first so the miss is surfaced as the domain
        // exception the Final's failure ladder expects.
        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_mail_template WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $entity->mailTemplateId]);
        if ($existsStmt->fetchColumn() === false) {
            throw new MailTemplateNotFoundException();
        }

        // Only mail_subject is narrowed into the contract. file_name is
        // fixed at create time (not editable post-create); creator_id /
        // deletable / discriminator_type are preserved untouched.
        $sql = 'UPDATE dtb_mail_template SET '
            . 'mail_subject = :subject, '
            . 'update_date = NOW() '
            . 'WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $entity->mailTemplateId,
            ':subject' => $entity->subject,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): MailTemplateEntity
    {
        return new MailTemplateEntity(
            mailTemplateId: (int) $row['id'],
            // name / file_name / mail_subject are nullable in EC-CUBE
            // but non-null on MailTemplateEntity — coalesce NULL → ''
            // so the projection shape stays stable across externally-
            // inserted rows.
            mailTemplateName: $row['name'] === null ? '' : (string) $row['name'],
            fileName: $row['file_name'] === null ? '' : (string) $row['file_name'],
            subject: $row['mail_subject'] === null ? '' : (string) $row['mail_subject'],
        );
    }
}
