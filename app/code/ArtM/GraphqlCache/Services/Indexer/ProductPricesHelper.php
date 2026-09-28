<?php

namespace ArtM\GraphqlCache\Services\Indexer;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\ProductTierPriceManagementInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ResourceConnection;

class ProductPricesHelper
{
    protected $collectionFactory;
    protected $configurableType;
    protected $productRepository;
    protected $groupRepository;
    protected $tierPriceFactory;
    protected $tierPriceManagement;
    protected $productFactory;
    protected $resourceConnection;

    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigurableType $configurableType,
        ProductRepositoryInterface $productRepository,
        GroupRepositoryInterface $groupRepository,
        ProductTierPriceInterfaceFactory $tierPriceFactory,
        ProductTierPriceManagementInterface $tierPriceManagement,
        ProductFactory $productFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configurableType = $configurableType;
        $this->productRepository = $productRepository;
        $this->groupRepository = $groupRepository;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->tierPriceManagement = $tierPriceManagement;
        $this->productFactory = $productFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Entry point: Fetch configurables and update their tier prices.
     */
    public function addConfigurableProductTierPrices()
    {
        $configurableProducts = $this->getConfigurableProducts();
        $totalCount = count($configurableProducts);
        echo "Total products: $totalCount\n";
        $processedCount = 0;

        foreach ($configurableProducts as $configurable) {
            $simpleProducts = $this->getAssociatedSimpleProducts($configurable);
            $lowestTierPrices = $this->getLowestTierPricesFromSimples($simpleProducts);

            if (!empty($lowestTierPrices)) {
                $this->updateConfigurableTierPrices($configurable, $lowestTierPrices);
            }

            $processedCount++;
            $percentage = ($processedCount / $totalCount) * 100;
            echo "\rProcessed $processedCount/$totalCount (" . number_format($percentage, 2) . "%)";
        }

        echo "\nDone!";
    }

    /**
     * Get configurable products (enabled, visible in catalog/search).
     */
    public function getConfigurableProducts()
    {
       return $this->collectionFactory->create()
            ->addAttributeToFilter('type_id', \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', Visibility::VISIBILITY_BOTH);
    }

    /**
     * Get simple products associated with a configurable product.
     */
    public function getAssociatedSimpleProducts(Product $configurable)
    {

        $simpleIds = $this->configurableType->getChildrenIds($configurable->getId())[0] ?? [];
        $simples = [];
        foreach ($simpleIds as $simpleId) {
            try {
                $simples[] = $this->productRepository->getById($simpleId, false, $configurable->getStoreId());
            } catch (\Exception $e) {
                // Log or skip
            }
        }
        return $simples;
    }
    public function getLowestTierPricesFromSimples(array $simpleProducts)
    {
        $lowestPrices = [];
        foreach ($simpleProducts as $simpleProduct) {
            if ($simpleProduct->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                continue;
            }
            
            $tierPrices = $simpleProduct->getData('tier_price');

            foreach ($tierPrices as $tierPrice) {
                $websiteId = (int)$tierPrice['website_id'];

                // Allow only global (0) or main website (1)
                if (!in_array($websiteId, [0, 1])) {
                    continue;
                }

                $groupId = (int)$tierPrice['cust_group'];
                $price = (float)$tierPrice['price'];
                $qty = (float)$tierPrice['price_qty'];

                if (!isset($lowestPrices[$groupId]) || $price < $lowestPrices[$groupId]['price']) {
                    $lowestPrices[$groupId] = [
                        'price' => $price,
                        'group_id' => ($groupId == 32000) ? 0 : $groupId,
                        'qty' => $qty,
                        'website_id' => $websiteId,
                        'product_id' => $simpleProduct->getId()
                    ];
                }
            }
        }
        return $lowestPrices;
    }


    /**
     * Undocumented function
     *
     * @param Product $configurable
     * @param array $lowestTierPrices
     * @return void
     */
    public function updateConfigurableTierPrices(Product $configurable, array $lowestTierPrices)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_entity_tier_price');
        $entityId = $configurable->getId();

        if (!$entityId) {
            // echo "Invalid product ID for SKU: " . $configurable->getSku() . "\n";
            return;
        }

        if (empty($lowestTierPrices)) {
            $connection->delete($table, ['entity_id = ?' => $entityId]);
            return;
        }

        foreach ($lowestTierPrices as $info) {
            $qty = $info['qty'];
            $websiteId = $info['website_id'] ?? 1;
            $price = $info['price'];
            $groupId = $info['group_id'];

            $select = $connection->select()
                ->from($table)
                ->where('entity_id = ?', $entityId)
                ->where('website_id = ?', $websiteId);

            if ($groupId == 0) {
                $select->where('all_groups = 1');
            } else {
                $select->where('customer_group_id = ?', $groupId);
            }

            $select->where('qty = ?', $qty);

            $existing = $connection->fetchRow($select);

            if ($existing) {
                if ($existing['value'] != $price || $existing['qty'] != $qty) {
                    $connection->update(
                        $table,
                        ['value' => $price, 'qty' => $qty],
                        ['value_id = ?' => $existing['value_id']]
                    );
                }
            } else {
                $allGroups = ($groupId == 0) ? 1 : 0;
                $connection->insert(
                    $table,
                    [
                        'entity_id' => $entityId,
                        'all_groups' => $allGroups,
                        'customer_group_id' => ($allGroups == 1) ? 0 : $groupId,
                        'qty' => $qty,
                        'value' => $price,
                        'website_id' => $websiteId,
                        'percentage_value' => null
                    ]
                );
            }
        }
    }

}
