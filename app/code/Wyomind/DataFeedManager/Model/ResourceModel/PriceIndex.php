<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Model\ResourceModel;

/**
 * Class TierPrice
 * @package Wyomind\DataFeedManager\Model\ResourceModel
 */
class PriceIndex extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public $framework;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        /** @delegation off */
        $connectionName = null
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context, $connectionName);
    }
    public function _construct()
    {
        $this->_init('datafeedmanager_feeds', 'id');
    }
    /**
     * @param $websiteId
     * @return array
     * @throws \Exception
     */
    public function getPrices($websiteId, $entityId)
    {
        $connection = $this->getConnection();
        $sql = $connection->select();
        $tableCpip = $this->getTable("catalog_product_index_price");
        $sql->from(["cpip" => $tableCpip], ["*"]);
        $sql->where("cpip.entity_id=" . $entityId . " AND website_id=" . $websiteId);
        $result = $connection->fetchAll($sql);
        $prices = [];
        foreach ($result as $tp) {
            $prices[$tp['customer_group_id']] = ["price" => $tp['price'], "final_price" => $tp['final_price'], "min_price" => $tp['min_price'], "max_price" => $tp['max_price'], "tier_price" => $tp['tier_price']];
        }
        return $prices;
    }
}