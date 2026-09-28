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
class TierPrice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public $framework;
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;
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
    public function getTierPrices($websiteId)
    {
        $linkField = $this->framework->moduleIsEnabled('Magento_Enterprise') ? 'row_id' : 'entity_id';
        $connection = $this->getConnection();
        $sql = $connection->select();
        $tableCpetp = $this->getTable("catalog_product_entity_tier_price");
        $sql->from(["cpetp" => $tableCpetp], [$linkField, "all_groups", "customer_group_id", "value", "qty", "percentage_value"]);
        $sql->order(["cpetp.{$linkField}", "cpetp.customer_group_id", "cpetp.qty"]);
        $sql->where("cpetp.website_id=" . $websiteId . " OR cpetp.website_id=0");
        $result = $connection->fetchAll($sql);
        $tierPrices = [];
        foreach ($result as $tp) {
            if ($tp['all_groups'] == 1) {
                $tierPrices[$tp[$linkField]][32000][] = ["qty" => $tp['qty'], "value" => $tp['value'], "percent" => $tp['percentage_value']];
            } else {
                $tierPrices[$tp[$linkField]][$tp["customer_group_id"]][] = ["qty" => $tp['qty'], "value" => $tp['value'], "percent" => $tp['percentage_value']];
            }
        }
        return $tierPrices;
    }
}