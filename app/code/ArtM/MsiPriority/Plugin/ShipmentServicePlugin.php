<?php

namespace ArtM\MsiPriority\Plugin;

use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Service\ShipmentService;

class ShipmentServicePlugin
{
    public function beforeSubmitShipment(
        ShipmentService $subject,
        ShipmentInterface $shipment,
        ShipmentCreationArgumentsInterface $arguments = null
    ) {
        if ($arguments && $arguments->getExtensionAttributes() && $arguments->getExtensionAttributes()->getShipBy()) {
            $shipBy = $arguments->getExtensionAttributes()->getVinForceShipment();
            $shipment->setData('vin_force_shipment', $shipBy);
        }
        return [$shipment, $arguments];
    }
}
