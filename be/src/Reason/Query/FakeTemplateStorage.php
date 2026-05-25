<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use Override;

/**
 * In-memory Template store (Wave 9). Seeded with EC-CUBE's stock
 * default templates per device type.
 */
final class FakeTemplateStorage implements TemplateStorageInterface
{
    /** @var list<TemplateEntity> */
    private array $rows;

    public function __construct()
    {
        $this->rows = [
            new TemplateEntity('tp-default-pc', 'デフォルト (PC)', 10),
            new TemplateEntity('tp-default-sp', 'デフォルト (スマホ)', 2),
        ];
    }

    /** @return list<TemplateEntity> */
    #[Override]
    public function list(): array
    {
        return $this->rows;
    }
}
