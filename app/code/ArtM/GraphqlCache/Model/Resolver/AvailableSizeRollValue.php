<?php
namespace ArtM\GraphqlCache\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProducts\CollectionFactory;

class AvailableSizeRollValue implements ResolverInterface
{
    private $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        // die('asdfsd');
        $productId = $args['productId'] ?? null;
        if (!$productId) {
            return null; // Or throw an exception if required
        }
        
        /** @var \ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProducts\Collection $collection */
        $collection = $this->collectionFactory->create();
        $item = $collection->addFieldToFilter('product_id', $productId)
                    ->setPageSize(1)
                    ->getFirstItem();

        return $item->getData(); // Return the whole item data or a specific field
    }
}
