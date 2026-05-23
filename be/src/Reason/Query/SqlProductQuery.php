<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use Override;

use function str_replace;

final class SqlProductQuery implements ProductQueryInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function item(string $productCode): ProductEntity|null
    {
        $row = $this->db->row('product_get', ['productCode' => $productCode]);
        return $row === null ? null : $this->hydrate($row);
    }

    /** @return list<ProductEntity> */
    #[Override]
    public function listAll(int $limit, int $offset = 0): array
    {
        return array_map($this->hydrate(...), $this->db->rows('product_list', ['limit' => $limit, 'offset' => $offset]));
    }

    /** @return list<ProductEntity> */
    #[Override]
    public function search(?string $nameKeyword, int $limit = 50): array
    {
        if ($nameKeyword === null || $nameKeyword === '') {
            return $this->listAll($limit, 0);
        }
        return array_map($this->hydrate(...), $this->db->rows('product_search', ['keyword' => '%' . $this->escapeLike($nameKeyword) . '%', 'limit' => $limit]));
    }

    /** @return list<ProductEntity> */
    #[Override]
    public function listForExport(): array
    {
        return array_map($this->hydrate(...), $this->db->rows('product_export'));
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ProductEntity
    {
        $productId = (int) $row['product_id'];
        return new ProductEntity(
            productCode: (string) $row['product_code'],
            productName: (string) $row['product_name'],
            price02: (int) $row['price02'],
            stock: $row['stock'] === null ? null : (int) $row['stock'],
            productStatus: $row['product_status_id'] === null ? ProductEntity::STATUS_VISIBLE : (int) $row['product_status_id'],
            description: $row['description_detail'] === null ? null : (string) $row['description_detail'],
            searchWord: $row['search_word'] === null ? null : (string) $row['search_word'],
            note: $row['note'] === null ? null : (string) $row['note'],
            imagePath: $this->imagePath($productId),
            categoryNames: $this->stringColumn('product_categories', ['productId' => $productId], 'category_name'),
            tagNames: $this->stringColumn('product_tags', ['productId' => $productId], 'name'),
            classNames: $this->stringColumn('product_class_names', ['productId' => $productId], 'name'),
        );
    }

    private function imagePath(int $productId): string|null
    {
        $row = $this->db->row('product_image', ['productId' => $productId]);
        return $row === null ? null : 'save_image/' . (string) $row['file_name'];
    }

    /**
     * @param array<string, mixed> $values
     * @return list<string>
     */
    private function stringColumn(string $queryId, array $values, string $column): array
    {
        $out = [];
        foreach ($this->db->rows($queryId, $values) as $row) {
            $out[] = (string) $row[$column];
        }

        return $out;
    }

    private function escapeLike(string $keyword): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);
    }
}
