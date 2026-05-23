<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\ProductFactory;
use Override;

final class SqlProductQuery implements ProductQueryInterface
{
    private ProductFactory $factory;

    public function __construct(private readonly InternalDbQueryInterface $db)
    {
        $this->factory = new ProductFactory();
    }

    #[Override]
    public function item(string $productCode): ProductEntity|null
    {
        $row = $this->db->product_get(productCode: $productCode);
        return $row === null ? null : $this->hydrate($row);
    }

    /** @return list<ProductEntity> */
    #[Override]
    public function listAll(int $limit, int $offset = 0): array
    {
        return array_map($this->hydrate(...), $this->db->product_list(limit: $limit, offset: $offset));
    }

    /** @return list<ProductEntity> */
    #[Override]
    public function search(?string $nameKeyword, int $limit = 50): array
    {
        return array_map($this->hydrate(...), $this->db->product_search(nameKeyword: $nameKeyword, limit: $limit));
    }

    /** @return list<ProductEntity> */
    #[Override]
    public function listForExport(): array
    {
        return array_map($this->hydrate(...), $this->db->product_export());
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ProductEntity
    {
        return $this->factory->factory(
            $row['product_code'] === null ? null : (string) $row['product_code'],
            $row['product_name'] === null ? null : (string) $row['product_name'],
            $row['price02'],
            $row['stock'],
            $row['product_status_id'],
            $row['description_detail'] === null ? null : (string) $row['description_detail'],
            $row['search_word'] === null ? null : (string) $row['search_word'],
            $row['note'] === null ? null : (string) $row['note'],
            $row['image_file_name'] === null ? null : (string) $row['image_file_name'],
            $row['category_names_json'] === null ? null : (string) $row['category_names_json'],
            $row['tag_names_json'] === null ? null : (string) $row['tag_names_json'],
            $row['class_names_json'] === null ? null : (string) $row['class_names_json'],
        );
    }
}
