<?php
declare(strict_types=1);

namespace ArtM\GraphqlCache\Model\Resolver\Category;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class PageLayout implements ResolverInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function resolve(
        $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['id'])) {
            return null;
        }

        try {
            $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
            $category = $this->categoryRepository->get($value['id'], $storeId);
            return $category->getPageLayout();
        } catch (\Exception $e) {
            return null;
        }
    }
}
