<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Model\ResourceModel\Customer\Indexer;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;

/**
 * Customers collection for customer_grid indexer
 */
class Collection extends \Magento\Customer\Model\ResourceModel\Customer\Collection
{
    /**
     * @inheritdoc
     */
    protected function beforeAddLoadedItem(DataObject $item)
    {
        if (!$item->getBillingTelephone()) {
            $objectManager = ObjectManager::getInstance();
            $eavConfig = $objectManager->get(Config::class);
            $customerId = $item->getId();

            $customerSelect = $this->getConnection()->select()
                ->from(
                    ['e' => $this->getMainTable()],
                    []
                );

            /** Join with attribute name */
            $customerEntityId = $eavConfig->getEntityType(Customer::ENTITY)->getId();
            $customerSelect->join(
                ['ea' => $this->getTable('eav_attribute')],
                "ea.entity_type_id = $customerEntityId AND ea.attribute_code = 'customer_telephone'",
                []
            );
            $customerSelect->join(
                ['cev' => $this->getTable('customer_entity_varchar')],
                "cev.entity_id = e.entity_id AND cev.attribute_id = ea.attribute_id",
                [
                    'billingTelephone' => 'value'
                ]
            );
            $customerSelect->where("e.entity_id = ?", $customerId);
            $data = $this->getConnection()->fetchAll($customerSelect);
            if (count($data)) {
                $item->setBillingTelephone($data[0]['billingTelephone']);
            }
        }
        return $item;
    }
}
