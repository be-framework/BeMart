<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use JsonException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;

use function array_map;
use function array_values;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class ProductFactory
{
    public function factory(
        string|null $productCode,
        string|null $productName,
        int|string $price02,
        int|string|null $stock,
        int|string|null $productStatusId,
        string|null $descriptionDetail,
        string|null $searchWord,
        string|null $note,
        string|null $imageFileName,
        string|null $categoryNamesJson,
        string|null $tagNamesJson,
        string|null $classNamesJson,
    ): ProductEntity {
        return new ProductEntity(
            productCode: (string) $productCode,
            productName: (string) $productName,
            price02: (int) $price02,
            stock: $stock === null ? null : (int) $stock,
            productStatus: $productStatusId === null ? ProductEntity::STATUS_VISIBLE : (int) $productStatusId,
            description: $descriptionDetail,
            searchWord: $searchWord,
            note: $note,
            imagePath: $imageFileName === null || $imageFileName === '' ? null : 'save_image/' . $imageFileName,
            categoryNames: $this->stringList($categoryNamesJson),
            tagNames: $this->stringList($tagNamesJson),
            classNames: $this->stringList($classNamesJson),
        );
    }

    /** @return list<string> */
    private function stringList(string|null $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        return array_map(static fn (mixed $value): string => (string) $value, array_values($decoded));
    }
}
