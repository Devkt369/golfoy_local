<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Magento\CatalogRule\Model\ResourceModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Rule
 * @package Wyomind\DataFeedManager\Magento\CatalogRule\Model\ResourceModel
 */
class Rule extends \Magento\CatalogRule\Model\ResourceModel\Rule
{




    /**
     * Retrieve product prices by catalog rule for specific date, website and customer group
     * Collect data with  product Id => price pairs
     *
     * @param \DateTimeInterface $date
     * @param int $websiteId
     * @param int $customerGroupId
     * @param array $productIds
     * @return array
     */
    public function getRuleId(\DateTimeInterface $date, $websiteId, $customerGroupId, $productId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('catalogrule_product'), ['product_id', 'rule_id'])
            ->joinInner($this->getTable('catalogrule'), "".$this->getTable('catalogrule_product').".rule_id=".$this->getTable('catalogrule').".rule_id  
              AND (( from_date >= '" . $date->format('Y-m-d') . "' AND to_date < '" . $date->format('Y-m-d') . "') 
           OR (from_date >='" . $date->format('Y-m-d') . "' AND ISNULl(to_date))
           OR (ISNULl(from_date) AND to_date < '" . $date->format('Y-m-d') . "')
           OR (ISNULL(from_date) AND ISNULL(to_date)))", ["stop_rules_processing"])
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('product_id =(?)', $productId)
            ->where('is_active =(?)', 1)
            ->order(["".$this->getTable('catalogrule').".sort_order DESC", " ".$this->getTable('catalogrule').".rule_id ASC"]);
        $result = $connection->fetchAll($select);

        $ruleId = "";
        foreach ($result as $rule) {
            if ($rule["stop_rules_processing"] == 1) {
                return $rule["rule_id"];
            } else {
                $ruleId = $rule["rule_id"];
            }
        };
        return $ruleId;
    }
}
