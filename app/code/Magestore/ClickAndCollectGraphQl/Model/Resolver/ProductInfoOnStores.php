<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationsByProductSkuInterface;
use Magestore\ClickAndCollectApi\Api\SortLocationsAlgorithmByDistanceInterface;
use Magestore\Webpos\Api\GetLatLngFromAddressInterface;

/**
 * ProductInfoOnStores resolver, used for GraphQL request processing.
 */
class ProductInfoOnStores implements ResolverInterface
{
    /**
     * @var GetLocationsByProductSkuInterface
     */
    protected $getLocationsByProductSku;

    /**
     * @var GetProductSalableQtyInterface
     */
    protected $getProductSalableQty;

    /**
     * @var IsProductSalableInterface
     */
    protected $isProductSalable;

    /**
     * @var SortLocationsAlgorithmByDistanceInterface
     */
    private $sortLocationsAlgorithmByDistance;

    /**
     * @var GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var Product
     */
    private $resourceProduct;

    /**
     * ProductInfoOnStores constructor.
     * @param GetLocationsByProductSkuInterface $getLocationsByProductSku
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsProductSalableInterface $isProductSalable
     * @param SortLocationsAlgorithmByDistanceInterface $sortLocationsAlgorithmByDistance
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param Product $resourceProduct
     */
    public function __construct(
        GetLocationsByProductSkuInterface $getLocationsByProductSku,
        GetProductSalableQtyInterface $getProductSalableQty,
        IsProductSalableInterface $isProductSalable,
        SortLocationsAlgorithmByDistanceInterface $sortLocationsAlgorithmByDistance,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        Product $resourceProduct
    ) {
        $this->getLocationsByProductSku = $getLocationsByProductSku;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->isProductSalable = $isProductSalable;
        $this->sortLocationsAlgorithmByDistance = $sortLocationsAlgorithmByDistance;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->resourceProduct = $resourceProduct;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $productSku = $args['productSku'];

        $productId = $this->resourceProduct->getIdBySku($productSku);

        if (!$productId) {
            throw new GraphQlInputException(__('Could not find a product with SKU "%1"', $productSku));
        }

        $locations = $this->getLocationsByProductSku->execute($productSku);

        if (!count($locations)) {
            throw new GraphQlInputException(
                __('Product with SKU "%1" does not assign to any source or not allow pickup at any store', $productSku)
            );
        }

        if (isset($args['currentAddress']) && !empty($args['currentAddress'])) {
            $currentLatLngAddress = $this->getLatLngFromAddress->execute($args['currentAddress']);
            $locations = $this->sortLocationsAlgorithmByDistance->execute($currentLatLngAddress, $locations);
        }

        $items = [];
        foreach ($locations as $location) {
            $saleableQty = $this->getProductSalableQty->execute($productSku, $location->getStockId());
            $isSalable = $this->isProductSalable->execute($productSku, $location->getStockId());
            $formatLocation = $location->getData();
            $formatLocation['model'] = $location;
            $items[] = [
                'location' => $formatLocation,
                'saleable_qty' => $saleableQty,
                'stock_status' => $isSalable
            ];
        }

        return [
            'items' => $items
        ];
    }
}
