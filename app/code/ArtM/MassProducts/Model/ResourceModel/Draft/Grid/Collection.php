<?php
namespace ArtM\MassProducts\Model\ResourceModel\Draft\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        EventManagerInterface $eventManager,
        $mainTable = 'artm_massproducts_draft',
        $resourceModel = \ArtM\MassProducts\Model\ResourceModel\Draft::class
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
        $this->addFilterToMap('sku', 'main_table.sku');
        $this->addFilterToMap('attribute_set', 'main_table.attribute_set');
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('operation_type', 'main_table.operation_type');
        return $this;
    }
}
