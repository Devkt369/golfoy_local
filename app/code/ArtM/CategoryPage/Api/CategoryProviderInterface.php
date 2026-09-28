<?php
namespace ArtM\CategoryPage\Api;

interface CategoryProviderInterface
{
    public function getMainCategories(?int $storeId = null, bool $includeDisabled = false): array;

    public function getShowcasePayload(int $categoryId, ?int $storeId = null, bool $includeDisabled = false): array;

    public function clearCacheByCategoryId(int $categoryId): void;
}
