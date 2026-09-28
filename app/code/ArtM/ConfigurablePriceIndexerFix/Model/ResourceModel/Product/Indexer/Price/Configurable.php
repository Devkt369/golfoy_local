<?php
declare(strict_types=1);

namespace ArtM\ConfigurablePriceIndexerFix\Model\ResourceModel\Product\Indexer\Price;

use Magento\Catalog\Model\Indexer\Product\Price\TableMaintainer;
use Magento\Catalog\Model\ResourceModel\Product\BaseSelectProcessorInterface;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\BasePriceModifier;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\IndexTableStructure;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\IndexTableStructureFactory;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\Query\BaseFinalPrice;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Indexer\Price\OptionsIndexerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\DimensionalIndexerInterface;
use Zend_Db_Expr;

/**
 * Configurable product price indexer with custom option min/max price fix.
 *
 * This class replaces Magento's core implementation because the relevant
 * methods in the original class are private and cannot be overridden safely.
 */
class Configurable implements DimensionalIndexerInterface
{
    /**
     * @var BaseFinalPrice
     */
    private $baseFinalPrice;

    /**
     * @var IndexTableStructureFactory
     */
    private $indexTableStructureFactory;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var bool
     */
    private $fullReindexAction;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var AdapterInterface|null
     */
    private $connection;

    /**
     * @var BasePriceModifier
     */
    private $basePriceModifier;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var BaseSelectProcessorInterface
     */
    private $baseSelectProcessor;

    /**
     * @var OptionsIndexerInterface
     */
    private $optionsIndexer;

    /**
     * @param BaseFinalPrice $baseFinalPrice
     * @param IndexTableStructureFactory $indexTableStructureFactory
     * @param TableMaintainer $tableMaintainer
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resource
     * @param BasePriceModifier $basePriceModifier
     * @param bool $fullReindexAction
     * @param string $connectionName
     * @param ScopeConfigInterface|null $scopeConfig
     * @param BaseSelectProcessorInterface|null $baseSelectProcessor
     * @param OptionsIndexerInterface|null $optionsIndexer
     */
    public function __construct(
        BaseFinalPrice $baseFinalPrice,
        IndexTableStructureFactory $indexTableStructureFactory,
        TableMaintainer $tableMaintainer,
        MetadataPool $metadataPool,
        ResourceConnection $resource,
        BasePriceModifier $basePriceModifier,
        $fullReindexAction = false,
        $connectionName = 'indexer',
        ?ScopeConfigInterface $scopeConfig = null,
        ?BaseSelectProcessorInterface $baseSelectProcessor = null,
        ?OptionsIndexerInterface $optionsIndexer = null
    ) {
        $this->baseFinalPrice = $baseFinalPrice;
        $this->indexTableStructureFactory = $indexTableStructureFactory;
        $this->tableMaintainer = $tableMaintainer;
        $this->metadataPool = $metadataPool;
        $this->resource = $resource;
        $this->basePriceModifier = $basePriceModifier;
        $this->fullReindexAction = (bool) $fullReindexAction;
        $this->connectionName = (string) $connectionName;
        $this->scopeConfig = $scopeConfig ?: ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->baseSelectProcessor = $baseSelectProcessor
            ?: ObjectManager::getInstance()->get(BaseSelectProcessorInterface::class);
        $this->optionsIndexer = $optionsIndexer
            ?: ObjectManager::getInstance()->get(OptionsIndexerInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function executeByDimensions(array $dimensions, \Traversable $entityIds)
    {
        $entityIds = iterator_to_array($entityIds);

        $this->tableMaintainer->createMainTmpTable($dimensions);

        $temporaryPriceTable = $this->indexTableStructureFactory->create([
            'tableName' => $this->tableMaintainer->getMainTmpTable($dimensions),
            'entityField' => 'entity_id',
            'customerGroupField' => 'customer_group_id',
            'websiteField' => 'website_id',
            'taxClassField' => 'tax_class_id',
            'originalPriceField' => 'price',
            'finalPriceField' => 'final_price',
            'minPriceField' => 'min_price',
            'maxPriceField' => 'max_price',
            'tierPriceField' => 'tier_price',
        ]);

        $select = $this->baseFinalPrice->getQuery(
            $dimensions,
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
            $entityIds
        );

        $this->tableMaintainer->insertFromSelect(
            $select,
            $temporaryPriceTable->getTableName(),
            [
                'entity_id',
                'customer_group_id',
                'website_id',
                'tax_class_id',
                'price',
                'final_price',
                'min_price',
                'max_price',
                'tier_price',
            ]
        );

        $this->basePriceModifier->modifyPrice($temporaryPriceTable, $entityIds);
        $this->applyConfigurableOption($temporaryPriceTable, $dimensions, $entityIds);
    }

    /**
     * Apply configurable option prices to the temporary index table.
     *
     * @param IndexTableStructure $temporaryPriceTable
     * @param array $dimensions
     * @param array $entityIds
     * @return $this
     */
    private function applyConfigurableOption(
        IndexTableStructure $temporaryPriceTable,
        array $dimensions,
        array $entityIds
    ) {
        $temporaryOptionsTableName = 'catalog_product_index_price_cfg_opt_temp';

        $this->getConnection()->createTemporaryTableLike(
            $temporaryOptionsTableName,
            $this->getTable('catalog_product_index_price_cfg_opt_tmp'),
            true
        );

        $indexTableName = $this->getMainTable($dimensions);
        $this->optionsIndexer->execute($indexTableName, $temporaryOptionsTableName, $entityIds);
        $this->updateTemporaryTable($temporaryPriceTable->getTableName(), $temporaryOptionsTableName);

        $this->getConnection()->delete($temporaryOptionsTableName);

        return $this;
    }

    /**
     * Update temp table values using option-indexed min/max prices directly.
     *
     * This avoids Magento's default formula:
     * i.min_price - i.price + io.min_price
     * i.max_price - i.price + io.max_price
     */
    private function updateTemporaryTable(string $temporaryPriceTableName, string $temporaryOptionsTableName): void
    {
        $table = ['i' => $temporaryPriceTableName];

        $selectForCrossUpdate = $this->getConnection()->select()->join(
            ['io' => $temporaryOptionsTableName],
            'i.entity_id = io.entity_id AND i.customer_group_id = io.customer_group_id'
            . ' AND i.website_id = io.website_id',
            []
        );

        $selectForCrossUpdate->columns([
            'min_price' => new Zend_Db_Expr('io.min_price'),
            'max_price' => new \Zend_Db_Expr('i.max_price - i.price + io.max_price'),
            'tier_price' => 'io.tier_price',
        ]);

        $query = $selectForCrossUpdate->crossUpdateFromSelect($table);
        $this->getConnection()->query($query);
    }

    /**
     * Get main index table name.
     */
    private function getMainTable(array $dimensions): string
    {
        if ($this->fullReindexAction) {
            return $this->tableMaintainer->getMainReplicaTable($dimensions);
        }

        return $this->tableMaintainer->getMainTableByDimensions($dimensions);
    }

    /**
     * Get DB connection.
     */
    private function getConnection(): AdapterInterface
    {
        if ($this->connection === null) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }

        return $this->connection;
    }

    /**
     * Resolve table name for the current connection.
     */
    private function getTable(string $tableName): string
    {
        return $this->resource->getTableName($tableName, $this->connectionName);
    }
}
