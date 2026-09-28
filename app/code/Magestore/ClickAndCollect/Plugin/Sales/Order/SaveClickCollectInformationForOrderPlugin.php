<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Sales\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;

/**
 * Save Click and Collect information
 */
class SaveClickCollectInformationForOrderPlugin
{
    /**
     * Save Order to Pickup Location relation when saving the order.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderInterface $entity
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        OrderRepositoryInterface $orderRepository,
        OrderInterface $entity
    ) {
        $extensionAttributes = $entity->getExtensionAttributes();
        if ($extensionAttributes) {
            $pickupLocationId = $extensionAttributes->getLocationPickupId();
            if ($pickupLocationId) {
                $entity->setLocationPickupId($pickupLocationId);
            }
            if ($pickupLocationId && $extensionAttributes->getPickupDateTime()) {
                $entity->setPickupDateTime($extensionAttributes->getPickupDateTime());
            }
            if ($pickupLocationId && $extensionAttributes->getPickupDateOnly()) {
                $entity->setPickupDateOnly($extensionAttributes->getPickupDateOnly());
            }

            if ($pickupLocationId && !$extensionAttributes->getPickupStatus()) {
                $entity->setPickupStatus(OrderPickupStatusResolverInterface::STATUS_PENDING);
            }
        }
        return [$entity];
    }
}
