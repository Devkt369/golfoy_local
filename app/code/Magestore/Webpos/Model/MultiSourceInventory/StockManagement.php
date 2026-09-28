<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\MultiSourceInventory;

use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magestore\Webpos\Api\DataProvider\Session\GetSessionByIdInterface;

/**
 * Class StockManagement
 *
 * Used for api management stock
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockManagement implements \Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magestore\Webpos\Api\WebposManagementInterface
     */
    protected $webposManagement;

    /**
     * @var \Magestore\Webpos\Api\MultiSourceInventory\SourceManagementInterface
     */
    protected $sourceManagement;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magestore\Webpos\Api\Location\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var GetSessionByIdInterface
     */
    protected $getSessionById;

    /**
     * StockManagement constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magestore\Webpos\Api\WebposManagementInterface $webposManagement
     * @param \Magestore\Webpos\Api\MultiSourceInventory\SourceManagementInterface $sourceManagement
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magestore\Webpos\Api\Location\LocationRepositoryInterface $locationRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param GetSessionByIdInterface $getSessionById
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $coreRegistry,
        \Magestore\Webpos\Api\WebposManagementInterface $webposManagement,
        \Magestore\Webpos\Api\MultiSourceInventory\SourceManagementInterface $sourceManagement,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magestore\Webpos\Api\Location\LocationRepositoryInterface $locationRepository,
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        GetSessionByIdInterface $getSessionById
    ) {
        $this->objectManager = $objectManager;
        $this->request = $request;
        $this->coreRegistry = $coreRegistry;
        $this->webposManagement = $webposManagement;
        $this->sourceManagement = $sourceManagement;
        $this->productRepository = $productRepository;
        $this->locationRepository = $locationRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->resourceConnection = $resourceConnection;
        $this->getSessionById = $getSessionById;
    }

    /**
     * Get stock
     *
     * @return \Magento\InventoryApi\Api\Data\StockInterface|null
     */
    public function getStock()
    {
        $stockId = $this->getStockId();
        if ($stockId) {
            /** @var \Magento\InventoryApi\Api\StockRepositoryInterface $stockRepository */
            $stockRepository = $this->objectManager->create(
                \Magento\InventoryApi\Api\StockRepositoryInterface::class
            );
            $stock = $stockRepository->get($stockId);
            return $stock;
        }
        return null;
    }

    /**
     * Get stock id
     *
     * @return int|null
     */
    public function getStockId()
    {
        if ($this->webposManagement->isMSIEnable()) {
            try {
                $locationId = $this->request->getParam(
                    \Magestore\Webpos\Model\Checkout\PosOrder::PARAM_ORDER_LOCATION_ID
                );
                if (!$locationId) {
                    $sessionId = $this->request->getParam(
                        \Magestore\WebposIntegration\Controller\Rest\RequestProcessor::SESSION_PARAM_KEY
                    );
                    $sessionLogin = $this->getSessionById->execute($sessionId);
                    $locationId = $sessionLogin->getLocationId();
                }

            } catch (\Exception $e) {
                $locationId = $this->request->getParam(
                    \Magestore\Webpos\Model\Checkout\PosOrder::PARAM_ORDER_LOCATION_ID
                );
            }

            if (!$locationId) {
                return null;
            }
            if ($this->webposManagement->isWebposStandard()) {
                /** @var \Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface $defaultStockProvider */
                $defaultStockProvider = $this->objectManager->create(
                    \Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface::class
                );
                return $defaultStockProvider->getId();
            } else {
                $location = $this->locationRepository->getById($locationId);
                return $location->getStockId();
            }
        }
        return null;
    }

    /**
     * Get linked source code
     *
     * @param int $stockId
     * @return array|string[]
     */
    public function getLinkedSourceCodesByStockId($stockId)
    {
        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter('stock_id', $stockId)
            ->create();
        /** @var \Magento\InventoryApi\Api\GetStockSourceLinksInterface $getStockSourceLink */
        $getStockSourceLink = $this->objectManager->get(
            \Magento\InventoryApi\Api\GetStockSourceLinksInterface::class
        );
        $stockSourcesLink = $getStockSourceLink->execute($searchCriteria);
        $linkedSources = [];
        foreach ($stockSourcesLink->getItems() as $item) {
            $linkedSources[] = $item->getSourceCode();
        }
        return $linkedSources;
    }

    /**
     * Add custom sale
     *
     * @param int $stockId
     * @return \Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface
     */
    public function addCustomSaleToStock($stockId)
    {
        if (!$this->webposManagement->isMSIEnable()) {
            return $this;
        }

        try {
            $product = $this->productRepository->get(\Magestore\Webpos\Helper\Product\CustomSale::SKU);
            if ($product->getId()) {
                $sourceCodes = $this->getLinkedSourceCodesByStockId($stockId);
                if (empty($sourceCodes)) {
                    return $this;
                }
                $sourceItemsMap = $this->sourceManagement->getSourceItemsMap(
                    \Magestore\Webpos\Helper\Product\CustomSale::SKU,
                    $sourceCodes
                );
                foreach ($sourceCodes as $sourceCode) {
                    if (isset($sourceItemsMap[$sourceCode])) {
                        return $this;
                    }
                    $this->createSourceItem(
                        \Magestore\Webpos\Helper\Product\CustomSale::SKU,
                        $sourceCode,
                        0,
                        1
                    );
                }
            }
        } catch (\Exception $e) {
            return $this;
        }

        return $this;
    }

    /**
     * Get stock id
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return int|null
     */
    public function getStockIdFromOrder($order)
    {
        $locationId = $order->getPosLocationId();
        if ($locationId) {
            $location = $this->locationRepository->getById($locationId);
            if ($location->getLocationId()) {
                return $location->getStockId();
            }
            return null;
        }
        return null;
    }

    /**
     * Get stock id
     *
     * @param \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel
     * @return int|null|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStockIdBySalesChannel($salesChannel)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock_sales_channel');

        $select = $connection->select()
            ->from($tableName, 'stock_id')
            ->where(SalesChannelInterface::TYPE . ' = ?', $salesChannel->getType())
            ->where(SalesChannelInterface::CODE . ' = ?', $salesChannel->getCode());

        $stockId = $connection->fetchOne($select);
        $stockId = false === $stockId ? null : (int)$stockId;
        if (null === $stockId) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('No linked stock found'));
        }
        return $stockId;
    }

    /**
     * Get source item
     *
     * @param string $sku
     * @param string $sourceCode
     * @param int $quantity
     * @param int $status
     * @return void
     */
    public function createSourceItem($sku, $sourceCode, $quantity = 0, $status = 1)
    {
        /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItem */
        $sourceItem = $this->objectManager
            ->create(\Magento\InventoryApi\Api\Data\SourceItemInterface::class);
        $sourceItemData = [
            'sku' => $sku,
            'source_code' => $sourceCode,
            'quantity' => $quantity,
            'status' => $status
        ];
        $this->dataObjectHelper->populateWithArray(
            $sourceItem,
            $sourceItemData,
            \Magento\InventoryApi\Api\Data\SourceItemInterface::class
        );
        $sourceItemsForSave = [$sourceItem];
        /** @var \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemSave */
        $sourceItemSave = $this->objectManager->create(\Magento\InventoryApi\Api\SourceItemsSaveInterface::class);
        $sourceItemSave->execute($sourceItemsForSave);
    }
}
