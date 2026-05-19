<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory MailTemplate store, seeded with the two highest-traffic
 * templates from EC-CUBE's default install (order confirmation +
 * member registration thanks).
 *
 * Singleton-bound so a Becoming-chain's read sees its own write.
 */
final class FakeMailTemplateStorage implements MailTemplateStorageInterface
{
    public const SEED_ORDER_CONFIRM_ID = 1;
    public const SEED_REGISTER_THANKS_ID = 2;

    /** @var array<int, MailTemplateEntity> keyed by mailTemplateId */
    private array $byId = [];

    public function __construct()
    {
        $this->byId[self::SEED_ORDER_CONFIRM_ID] = new MailTemplateEntity(
            mailTemplateId: self::SEED_ORDER_CONFIRM_ID,
            mailTemplateName: '注文完了メール',
            fileName: 'Mail/order.twig',
            subject: 'ご注文ありがとうございます',
            body: "ご注文を承りました。\n注文番号: {{ orderNo }}",
            htmlBody: null,
        );
        $this->byId[self::SEED_REGISTER_THANKS_ID] = new MailTemplateEntity(
            mailTemplateId: self::SEED_REGISTER_THANKS_ID,
            mailTemplateName: '会員登録完了メール',
            fileName: 'Mail/entry.twig',
            subject: 'ご登録ありがとうございます',
            body: "会員登録が完了しました。\n{{ name }} 様",
            htmlBody: null,
        );
    }

    /** @return list<MailTemplateEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function findById(int $mailTemplateId): MailTemplateEntity|null
    {
        return $this->byId[$mailTemplateId] ?? null;
    }

    #[Override]
    public function update(MailTemplateEntity $entity): void
    {
        if (! isset($this->byId[$entity->mailTemplateId])) {
            throw new MailTemplateNotFoundException();
        }

        $this->byId[$entity->mailTemplateId] = $entity;
    }
}
