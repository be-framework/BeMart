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
            (string) $productCode,
            (string) $productName,
            (int) $price02,
            $stock === null ? null : (int) $stock,
            $productStatusId === null ? ProductEntity::STATUS_VISIBLE : (int) $productStatusId,
            $descriptionDetail,
            $searchWord,
            $note,
            $this->resolveImagePath($imageFileName),
            $this->stringList($categoryNamesJson),
            $this->stringList($tagNamesJson),
            $this->stringList($classNamesJson),
        );
    }

    /**
     * Resolve a stored image filename to a web path.
     *
     * Bare upload filenames are served from the `save_image/` upload
     * dir. Values that are already full asset/absolute/remote paths (e.g. the
     * seeded `assets/idea-store/...` catalog images) are used as-is.
     */
    private function resolveImagePath(string|null $imageFileName): string|null
    {
        if ($imageFileName === null || $imageFileName === '') {
            return null;
        }

        // Already absolute or remote → as-is.
        if (str_starts_with($imageFileName, '/') || str_starts_with($imageFileName, 'http')) {
            return $imageFileName;
        }

        // Full asset paths (the seeded catalog) become root-absolute.
        if (str_starts_with($imageFileName, 'assets/')) {
            return '/' . $imageFileName;
        }

        // Bare upload filenames are served from the upload dir.
        return '/save_image/' . $imageFileName;
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
