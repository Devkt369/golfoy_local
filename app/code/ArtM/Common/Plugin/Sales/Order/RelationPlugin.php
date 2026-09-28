<?php

namespace ArtM\Common\Plugin\Sales\Order;

use Magento\Framework\Model\AbstractModel;
use Magento\Tax\Model\ResourceModel\Sales\Order\Relation;
use Magento\Sales\Model\Order;

class RelationPlugin
{
    public function aroundProcessRelation(
        Relation $subject,
        callable $proceed,
        AbstractModel $object
    ) {
        if ($object instanceof Order) {
            $incrementId = $object->getData('increment_id');
            if (stripos($incrementId, 'POS') !== false) {
                return; 
            }
        }
        return $proceed($object);
    }
}