<?php
/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magestore\ReportSuccess\Ui\Component\Columns;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Inventory\Model\Source;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Collection;
use Magestore\InventorySuccess\Model\Warehouse;
use Magestore\ReportSuccess\Api\ReportManagementInterface;
use Magestore\ReportSuccess\Model\Bookmark;
use Magestore\ReportSuccess\Ui\Component\ColumnFactory;

/**
 * Class Columns
 *
 * Used for render columns
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ColumnFactory|ColumnFactory
     */
    protected $columnFactory;

    /**
     * @var Bookmark
     */
    protected $bookmark;

    /**
     * @var ReportManagementInterface
     */
    protected $reportManagement;

    /**
     * Columns constructor.
     *
     * @param ContextInterface $context
     * @param ObjectManagerInterface $objectManager
     * @param ColumnFactory $columnFactory
     * @param ReportManagementInterface $reportManagement
     * @param Bookmark $bookmark
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        ObjectManagerInterface $objectManager,
        ColumnFactory $columnFactory,
        ReportManagementInterface $reportManagement,
        Bookmark $bookmark,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->objectManager = $objectManager;
        $this->columnFactory = $columnFactory;
        $this->bookmark = $bookmark;
        $this->reportManagement = $reportManagement;
    }

    /**
     * Prepare date
     *
     * @throws LocalizedException
     */
    public function prepare()
    {
        $locations = $this->bookmark->getLocations();
        if ($this->reportManagement->isMSIEnable()) {
            $warehouse = $this->objectManager->create(Source::class);
            $warehouseName = __('All Sources');
            $warehouseList = $this->getSourceList($locations);
        } else {
            $warehouse = $this->objectManager->create(Warehouse::class); // phpstan:ignore
            $warehouseName = __('All Warehouses');

            /** @var Collection $warehouseList */
            $warehouseList = $this->objectManager
                ->create(Collection::class); // phpstan:ignore
            $warehouseList->addFieldToFilter('warehouse_id', ['in' => $locations]);
            $warehouseList->setOrder(
                'warehouse_name',
                Collection::SORT_ORDER_ASC // phpstan:ignore
            );
        }
        if (in_array(' ', $locations)) {
            /** @var Warehouse $warehouse */
            $warehouse->setId('_');
            $warehouse->setWarehouseName($warehouseName);
            $column = $this->columnFactory->create($warehouse, $this->getContext());
            $column->prepare();
            $this->addComponent('loc_' . sha1($warehouse->getId()), $column);
        }

        foreach ($warehouseList as $warehouse) {
            $column = $this->columnFactory->create($warehouse, $this->getContext());
            $column->prepare();
            $warehouseId = $this->reportManagement->isMSIEnable() ? $warehouse->getSourceCode() : $warehouse->getId();
            $this->addComponent('loc_' . sha1($warehouseId), $column);
        }
        parent::prepare();
    }

    /**
     * Get source list by locations
     *
     * @param array $locations
     * @return mixed
     */
    public function getSourceList($locations)
    {
        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->objectManager->create(SortOrderBuilder::class);
        $sortOrder = $sortOrderBuilder->setField(SourceInterface::NAME)
            ->setDirection(SortOrder::SORT_ASC)
            ->create();
        /** @var SourceRepositoryInterface $sourceRepository */
        $sourceRepository = $this->objectManager->get(SourceRepositoryInterface::class);
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('source_code', $locations, 'in')
            ->setSortOrders([$sortOrder])
            ->create();
        $sourcesSearchResult = $sourceRepository->getList($searchCriteria);
        return $sourcesSearchResult->getItems();
    }
}
