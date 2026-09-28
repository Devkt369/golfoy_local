<?php

namespace Magestore\ClickAndCollect\Plugin\Location;

use Magestore\Webpos\Api\Data\Location\LocationExtensionInterfaceFactory;
use Magento\Framework\Model\AbstractModel;
use Magestore\Webpos\Model\ResourceModel\Location\Location;
use Magestore\ClickAndCollectApi\Api\Data\LocationWebposAdditionalDataInterface;

/**
 * Model LoadDataForLocationWebpos
 */
class LoadDataForLocationWebpos
{
    /**
     * @var LocationExtensionInterfaceFactory
     */
    private $locationExtensionInterfaceFactory;

    /**
     * @param LocationExtensionInterfaceFactory $locationExtensionInterfaceFactory
     */
    public function __construct(
        LocationExtensionInterfaceFactory $locationExtensionInterfaceFactory
    ) {
        $this->locationExtensionInterfaceFactory = $locationExtensionInterfaceFactory;
    }

    /**
     * Load and add Pickup Location information to Quote Address.
     *
     * @param Location $subject
     * @param Location $result
     * @param AbstractModel $entity
     *
     * @return Location
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoad(Location $subject, Location $result, AbstractModel $entity): Location
    {
        if (!$entity->getId()) {
            return $result;
        }

        if (!$entity->getExtensionAttributes()) {
            $entity->setExtensionAttributes($this->locationExtensionInterfaceFactory->create());
        }

        $entity->getExtensionAttributes()->setIsAllowPickup(
            $entity->getData(LocationWebposAdditionalDataInterface::IS_ALLOW_PICKUP)
        );
        $entity->getExtensionAttributes()->setScheduleId(
            $entity->getData(LocationWebposAdditionalDataInterface::SCHEDULE_ID)
        );

        return $result;
    }
}
