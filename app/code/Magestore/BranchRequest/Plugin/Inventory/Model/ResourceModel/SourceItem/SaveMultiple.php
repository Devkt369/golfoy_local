<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\BranchRequest\Plugin\Inventory\Model\ResourceModel\SourceItem;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Class BranchRequest SaveMultiple
 */
class SaveMultiple
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * SaveMultiple constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param RequestInterface $request
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        RequestInterface $request
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->request = $request;
    }

    /**
     * Build column sql part
     *
     * @param array $columns
     * @return string
     */
    private function buildColumnsSqlPart(array $columns): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedColumns = array_map([$connection, 'quoteIdentifier'], $columns);
        $sql = implode(', ', $processedColumns);
        return $sql;
    }

    /**
     * Build sql query for values
     *
     * @param SourceItemInterface[] $sourceItems
     * @return string
     */
    private function buildValuesSqlPart(array $sourceItems): string
    {
        $sql = rtrim(str_repeat('(?, ?, ?, ?, ?), ', count($sourceItems)), ', ');
        return $sql;
    }

    /**
     * Get Sql bind data
     *
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function getSqlBindData(array $sourceItems): array
    {
        $bind = [];
        $sources = $this->request->getParam('sources', []);
        $product = $this->request->getParam('product', []);
        $assignedSources =
            isset($sources['assigned_sources'])
            && is_array($sources['assigned_sources'])
                ? $sources['assigned_sources']
                : [];
        $assignedSourcesArr = [];
        foreach ($assignedSources as $assignedSource) {
            $assignedSourcesArr[$assignedSource['source_code']] = $assignedSource['racket'];
        }
        foreach ($sourceItems as $sourceItem) {
            $bind[] = $sourceItem->getSourceCode();
            $bind[] = $sourceItem->getSku();
            $bind[] = $sourceItem->getQuantity();
            $bind[] = $sourceItem->getStatus();
            $bind[] = isset($assignedSourcesArr[$sourceItem->getSourceCode()]) ?
                $assignedSourcesArr[$sourceItem->getSourceCode()]
                : ((isset($product['racket']) && $product['racket']) ? $product['racket'] : '');
        }
        return $bind;
    }

    /**
     * Build sql query for on duplicate event
     *
     * @param array $fields
     * @return string
     */
    private function buildOnDuplicateSqlPart(array $fields): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedFields = [];
        foreach ($fields as $field) {
            $processedFields[] = sprintf('%1$s = VALUES(%1$s)', $connection->quoteIdentifier($field));
        }
        $sql = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $processedFields);
        return $sql;
    }

    /**
     * Aroud execute function
     *
     * @param SourceItemResourceModel\SaveMultiple $saveMultiple
     * @param callable $proceed
     * @param array $sourceItems
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        \Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple $saveMultiple,
        callable $proceed,
        array $sourceItems
    ) {
        if (!count($sourceItems)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);

        $columnsSql = $this->buildColumnsSqlPart(
            [
                SourceItemInterface::SOURCE_CODE,
                SourceItemInterface::SKU,
                SourceItemInterface::QUANTITY,
                SourceItemInterface::STATUS,
                'racket'
            ]
        );
        $valuesSql = $this->buildValuesSqlPart($sourceItems);
        $onDuplicateSql = $this->buildOnDuplicateSqlPart(
            [
                SourceItemInterface::QUANTITY,
                SourceItemInterface::STATUS,
                'racket'
            ]
        );
        $bind = $this->getSqlBindData($sourceItems);

        $insertSql = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s %s',
            $tableName,
            $columnsSql,
            $valuesSql,
            $onDuplicateSql
        );
        $connection->query($insertSql, $bind);
    }
}
