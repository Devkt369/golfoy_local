<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Model\ResourceModel;

/**
 * Class WeeeTax
 * @package Wyomind\DataFeedManager\Model\ResourceModel
 */
class WeeeTax extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('weee_tax', 'value_id');
    }

    /**
     * @param $websiteId
     * @return array
     */
    public function getAll($websiteId)
    {
        $attributeSelect = $this->getConnection()->select();
        $attributeSelect->from(
            ['eavTable' => $this->getTable('eav_attribute')],
            ['eavTable.attribute_code']
        )->joinInner(
            ['weeeTax' => $this->getTable('weee_tax')],
            'weeeTax.attribute_id = eavTable.attribute_id',
            ['weeeTax.entity_id', 'weeeTax.value as weee_value', 'weeeTax.country', 'weeeTax.state']
        )->joinLeft(
            ['region' => $this->getTable('directory_country_region')],
            'weeeTax.state = region.region_id',
            ['region.code as region_code']
        )->where("weeeTax.website_id in (0," . $websiteId . ")");


        $values = $this->getConnection()->fetchAll($attributeSelect);

        return $values;
    }
}
