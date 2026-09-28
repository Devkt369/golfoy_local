<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink;

use Magento\Framework\App\ResourceConnection;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink as LocationSpecialDayLinkResourceModel;
use Magestore\ClickAndCollect\Model\LocationSpecialDayLink;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterface;

/**
 * Implementation of Location Special Day Link save multiple operation for specific db layer
 *
 * Save Multiple used here for performance efficient purposes over single save operation
 */
class SaveMultiple
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Multiple save LocationSpecialDayLink
     *
     * @param LocationSpecialDayLinkInterface[] $links
     * @return void
     */
    public function execute(array $links)
    {
        if (!count($links)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(
            LocationSpecialDayLinkResourceModel::TABLE_NAME_LOCATION_SPECIAL_DAY
        );

        $columnsSql = $this->buildColumnsSqlPart([
            LocationSpecialDayLink::LOCATION_ID,
            LocationSpecialDayLink::SPECIALDAY_ID
        ]);
        $valuesSql = $this->buildValuesSqlPart($links);
        $onDuplicateSql = $this->buildOnDuplicateSqlPart([
            LocationSpecialDayLink::LOCATION_ID,
            LocationSpecialDayLink::SPECIALDAY_ID
        ]);
        $bind = $this->getSqlBindData($links);

        $insertSql = sprintf(
            'INSERT INTO %s (%s) VALUES %s %s',
            $tableName,
            $columnsSql,
            $valuesSql,
            $onDuplicateSql
        );
        $connection->query($insertSql, $bind);
    }

    /**
     * Build Columns Sql Part
     *
     * @param array $columns
     * @return string
     */
    private function buildColumnsSqlPart(array $columns): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedColumns = array_map([$connection, 'quoteIdentifier'], $columns);
        return implode(', ', $processedColumns);
    }

    /**
     * Build Values Sql Part
     *
     * @param LocationSpecialDayLinkInterface[] $links
     * @return string
     */
    private function buildValuesSqlPart(array $links): string
    {
        $sql = rtrim(str_repeat('(?, ?), ', count($links)), ', ');
        return $sql;
    }

    /**
     * Get Sql Bind Data
     *
     * @param LocationSpecialDayLinkInterface[] $links
     * @return array
     */
    private function getSqlBindData(array $links): array
    {
        $bind = [];
        foreach ($links as $link) {
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $bind = array_merge($bind, [
                $link->getLocationId(),
                $link->getSpecialDayId()
            ]);
        }
        return $bind;
    }

    /**
     * Build On Duplicate Sql Part
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
        return 'ON DUPLICATE KEY UPDATE ' . implode(', ', $processedFields);
    }
}
