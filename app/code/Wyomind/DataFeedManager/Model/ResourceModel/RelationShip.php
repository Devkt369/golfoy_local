<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Model\ResourceModel;

/**
 * Class RelationShip
 * @package Wyomind\DataFeedManager\Model\ResourceModel
 */
class RelationShip extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('datafeedmanager_feeds', 'id');
    }

    /**
     * @return array
     */
    public function getRelationShip()
    {
        $connection = $this->getConnection();

        $tableCpsl = $this->getTable('catalog_product_super_link');
        $tableCpsa = $this->getTable('catalog_product_super_attribute');
        $tableCpsal = $this->getTable("catalog_product_super_attribute_label");

        $sql = $connection->select();
        $sql->from(["cpsl" => $tableCpsl], ["parent_id", "product_id"]);
        $sql->joinleft(["cpsa" => $tableCpsa], "cpsa.product_id = cpsl.parent_id", ["attribute_id"]);
        $sql->joinleft(["cpsal" => $tableCpsal], "cpsal.product_super_attribute_id = cpsa.product_super_attribute_id", ["relationship" => "GROUP_CONCAT(DISTINCT cpsal.value SEPARATOR '>>>')"]);
        $sql->order(["cpsl.parent_id", "cpsl.product_id"]);
        $sql->group(['cpsl.parent_id', 'cpsl.product_id']);

        $relationShip = $connection->fetchAll($sql);
        $productRelationShip = [];
        foreach ($relationShip as $rs) {
            $productRelationShip[$rs['product_id']] = $rs['relationship'];
            $productRelationShip[$rs['parent_id']] = $rs['relationship'];
        }
        return $productRelationShip;
    }
}
