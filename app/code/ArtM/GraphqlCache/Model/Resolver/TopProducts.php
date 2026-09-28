<?php
namespace ArtM\GraphqlCache\Model\Resolver;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class TopProducts implements ResolverInterface
{
    private $collectionFactory;
    private $storeManager;
    private $urlInterface;
    protected $priceHelper;

    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface,
        Data $priceHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->urlInterface = $urlInterface;
        $this->priceHelper = $priceHelper;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['product_ids']) || !is_array($args['product_ids'])) {
            return [];
        }

        $productIds = array_filter($args['product_ids'], 'is_numeric');

        if (empty($productIds)) {
            return [];
        }

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['entity_id','name', 'price', 'special_price', 'url_key']);
        $collection->addIdFilter($productIds);
        $collection->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED);
        $collection->setPageSize(count($productIds));

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        $products = [];
        foreach ($collection as $product) {
            $products[$product->getId()] = [
                'id'    => $product->getId(),
                'name'  => $product->getName(),
                'price' => $this->priceHelper->currency(
                    $product->getData('special_price')
                        ? $product->getData('special_price')
                        : $product->getData('price'),
                    true,
                    false
                ),
                'url'   => $baseUrl . $product->getUrlKey() . '.html'
            ];
        }

        $orderedProducts = [];
        foreach ($productIds as $id) {
            if (isset($products[$id])) {
                $orderedProducts[] = $products[$id];
            }
        }

        return $orderedProducts;

    }
}
