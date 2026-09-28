<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Stocktaking\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magestore\Stocktaking\Api\Data\StocktakingInterface;
use Magestore\Stocktaking\Api\Db\QueryProcessorInterface;
use Magestore\Stocktaking\Api\Data\StocktakingItemInterface;

/**
 * Class Stocktaking
 *
 * Used for stocktaking resource model
 */
class Stocktaking extends AbstractDb
{
    /**
     * Main table primary key field name
     *
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var QueryProcessorInterface
     */
    protected $queryProcessor;

    /**
     * Resource Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ms_stocktaking', StocktakingInterface::ID);
    }

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param QueryProcessorInterface $queryProcessor
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        QueryProcessorInterface $queryProcessor,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->queryProcessor = $queryProcessor;
    }

    /**
     * Move To Archive
     *
     * @param StocktakingInterface $stocktaking
     * @param Select $stocktakingItemsSelect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function moveToArchive(StocktakingInterface $stocktaking, Select $stocktakingItemsSelect)
    {
        $this->queryProcessor->start('moveStocktakingToArchive');
        // Move stocktaking to stocktaking archive table
        $this->queryProcessor->addQuery(
            [
                'type' => QueryProcessorInterface::QUERY_TYPE_INSERT,
                'values' => $stocktaking->getData(),
                'table' => $this->getTable('ms_stocktaking_archive')
            ],
            'moveStocktakingToArchive'
        );

        // Move stocktaking items to stocktaking archive item table

        $countSelect = $this->getConnection()->select()
            ->from(
                $stocktakingItemsSelect,
                ['count' => new \Zend_Db_Expr('COUNT(*)')]
            );
        $totalItems = $this->getConnection()->fetchOne($countSelect);

        $pageSize = 300;
        $totalPage = ceil($totalItems / $pageSize);
        for ($i = 1; $i <= $totalPage; $i++) {
            $insertSelect = $this->getConnection()->select()
                ->from($stocktakingItemsSelect)
                ->limitPage($i, $pageSize);
            $this->queryProcessor->addQuery(
                [
                    'type' => QueryProcessorInterface::QUERY_TYPE_INSERT,
                    'values' => $this->getConnection()->fetchAll($insertSelect),
                    'table' => $this->getTable('ms_stocktaking_archive_item')
                ],
                'moveStocktakingToArchive'
            );
        }

        // Delete stocktaking and it's items
        $this->queryProcessor->addQuery(
            [
                'type' => QueryProcessorInterface::QUERY_TYPE_DELETE,
                'condition' => [StocktakingInterface::ID . ' = ?' => $stocktaking->getId()],
                'table' => $this->getMainTable()
            ],
            'moveStocktakingToArchive'
        );
        $this->queryProcessor->process('moveStocktakingToArchive');
    }

    /**
     * Get Stocktaking Items To Move To Archive Select
     *
     * @param StocktakingInterface $stocktaking
     * @param Select $currentItemsSelect
     * @param Select|null $uncountedProductSelect
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getStocktakingItemsToMoveToArchiveSelect(
        StocktakingInterface $stocktaking,
        Select $currentItemsSelect,
        Select $uncountedProductSelect = null
    ) {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(
                $currentItemsSelect,
                [
                    StocktakingItemInterface::STOCKTAKING_ID,
                    StocktakingItemInterface::PRODUCT_ID,
                    StocktakingItemInterface::PRODUCT_NAME,
                    StocktakingItemInterface::PRODUCT_SKU,
                    StocktakingItemInterface::QTY_IN_SOURCE,
                    StocktakingItemInterface::COUNTED_QTY,
                    StocktakingItemInterface::DIFFERENCE_REASON
                ]
            );

        if ($stocktaking->getStocktakingType() == StocktakingInterface::STOCKTAKING_TYPE_FULL
            && $uncountedProductSelect
        ) {
            $uncountedItemsSelect = $connection->select()
                ->from(
                    $uncountedProductSelect,
                    [
                        StocktakingItemInterface::STOCKTAKING_ID => new \Zend_Db_Expr($stocktaking->getId()),
                        StocktakingItemInterface::PRODUCT_ID => 'entity_id',
                        StocktakingItemInterface::PRODUCT_NAME => 'name',
                        StocktakingItemInterface::PRODUCT_SKU => 'sku',
                        StocktakingItemInterface::QTY_IN_SOURCE => 'qty_in_source',
                        StocktakingItemInterface::COUNTED_QTY => new \Zend_Db_Expr(0),
                        StocktakingItemInterface::DIFFERENCE_REASON => new \Zend_Db_Expr('NULL')
                    ]
                )->where('qty_in_source > ?', 0);
            $select = $connection->select()
                ->union(
                    [$select, $uncountedItemsSelect],
                    Select::SQL_UNION_ALL
                );
        }
        return $select;
    }

    /**
     * Add Uncounted Products To Stocktaking
     *
     * @param StocktakingInterface $stocktaking
     * @param Select $uncountedProductSelect
     */
    public function addUncountedProductsToStocktaking(
        StocktakingInterface $stocktaking,
        Select $uncountedProductSelect
    ) {
        $connection = $this->getConnection();
        $uncountedItemsSelect = $connection->select()
            ->from(
                $uncountedProductSelect,
                [
                    StocktakingItemInterface::STOCKTAKING_ID => new \Zend_Db_Expr($stocktaking->getId()),
                    StocktakingItemInterface::PRODUCT_ID => 'entity_id',
                    StocktakingItemInterface::PRODUCT_NAME => 'name',
                    StocktakingItemInterface::PRODUCT_SKU => 'sku',
                    StocktakingItemInterface::QTY_IN_SOURCE => 'qty_in_source',
                    StocktakingItemInterface::COUNTED_QTY => new \Zend_Db_Expr(0),
                    StocktakingItemInterface::DIFFERENCE_REASON => new \Zend_Db_Expr('NULL')
                ]
            )->where('qty_in_source > ?', 0);

        $this->queryProcessor->start('addUncountedProductToStocktaking');

        $countSelect = $this->getConnection()->select()
            ->from(
                $uncountedItemsSelect,
                ['count' => new \Zend_Db_Expr('COUNT(*)')]
            );
        $totalItems = $this->getConnection()->fetchOne($countSelect);

        $pageSize = 300;
        $totalPage = ceil($totalItems / $pageSize);
        for ($i = 1; $i <= $totalPage; $i++) {
            $insertSelect = $this->getConnection()->select()
                ->from($uncountedItemsSelect)
                ->limitPage($i, $pageSize);
            $this->queryProcessor->addQuery(
                [
                    'type' => QueryProcessorInterface::QUERY_TYPE_INSERT,
                    'values' => $this->getConnection()->fetchAll($insertSelect),
                    'table' => $this->getTable('ms_stocktaking_item')
                ],
                'addUncountedProductToStocktaking'
            );
        }

        $this->queryProcessor->process('addUncountedProductToStocktaking');
    }
}
