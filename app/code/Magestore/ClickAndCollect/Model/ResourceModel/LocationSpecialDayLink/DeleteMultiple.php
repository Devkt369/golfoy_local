<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink;

use Magento\Framework\App\ResourceConnection;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink as LocationSpecialDayLinkResourceModel;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterface;

/**
 * Implementation of Location Special Day Link delete multiple operation for specific db layer
 *
 * Delete Multiple used here for performance efficient purposes over single delete operation
 */
class DeleteMultiple
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
     * Multiple delete location special day links
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

        $whereSql = $this->buildWhereSqlPart($links);
        $connection->delete($tableName, $whereSql);
    }

    /**
     * Build WHERE part of the delete SQL query
     *
     * @param LocationSpecialDayLinkInterface[] $links
     * @return string
     */
    private function buildWhereSqlPart(array $links): string
    {
        $connection = $this->resourceConnection->getConnection();

        $condition = [];

        foreach ($links as $link) {
            $locationCondition = $connection->quoteInto(
                LocationSpecialDayLinkInterface::LOCATION_ID . ' = ?',
                $link->getLocationId()
            );
            $specialDayCodeCondition = $connection->quoteInto(
                LocationSpecialDayLinkInterface::SPECIALDAY_ID . ' = ?',
                $link->getSpecialDayId()
            );
            $condition[] = '(' . $locationCondition . ' AND ' . $specialDayCodeCondition . ')';
        }

        return implode(' OR ', $condition);
    }
}
