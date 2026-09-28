<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Quote\AddressCollection;

use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection;
use Magestore\ClickAndCollectApi\Api\Data\LocationAdditionalDataInterface;

/**
 * Plugin for getting pickup location's information
 */
class GetPickupLocationInformationPlugin
{
    /**
     * @var AddressExtensionInterfaceFactory
     */
    private $addressExtensionInterfaceFactory;

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory
     * @param ResourceConnection $connection
     */
    public function __construct(
        AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory,
        ResourceConnection $connection
    ) {
        $this->addressExtensionInterfaceFactory = $addressExtensionInterfaceFactory;
        $this->connection = $connection;
    }

    /**
     * Load information about Pickup Location Code to collection of Quote Address.
     *
     * @param Collection $collection
     * @param \Closure $proceed
     * @param bool $printQuery
     * @param bool $logQuery
     *
     * @return Collection
     */
    public function aroundLoadWithFilter(
        Collection $collection,
        \Closure $proceed,
        bool $printQuery,
        bool $logQuery
    ): Collection {
        if ($collection->isLoaded()) {
            return $proceed($printQuery, $logQuery);
        }

        $result = $proceed($printQuery, $logQuery);

        foreach ($collection as $address) {
            $this->processAddress($address);
        }

        return $result;
    }

    /**
     * Process address entity.
     *
     * @param Address $address
     *
     * @return void
     */
    private function processAddress(Address $address): void
    {
        $hasDataChanges = $address->hasDataChanges();
        if ($address->getData(LocationAdditionalDataInterface::LOCATION_PICKUP_ID)) {
            $this->addPickupLocationToExtensionAttributes($address);
        }
        $address->setDataChanges($hasDataChanges);
    }

    /**
     * Add Loaded Pickup Location to Extension Attributes.
     *
     * @param Address $item
     *
     * @return void
     */
    private function addPickupLocationToExtensionAttributes(Address $item): void
    {
        if (!$item->getExtensionAttributes()) {
            $item->setExtensionAttributes($this->addressExtensionInterfaceFactory->create());
        }

        $item->getExtensionAttributes()->setLocationPickupId(
            $item->getData(LocationAdditionalDataInterface::LOCATION_PICKUP_ID)
        );

        $item->getExtensionAttributes()->setPickupDateTime(
            $item->getData(LocationAdditionalDataInterface::PICKUP_DATE_TIME)
        );

        $item->getExtensionAttributes()->setPickupDateOnly(
            $item->getData(LocationAdditionalDataInterface::PICKUP_DATE_ONLY)
        );

        $item->unsetData(LocationAdditionalDataInterface::LOCATION_PICKUP_ID);
        $item->unsetData(LocationAdditionalDataInterface::PICKUP_DATE_TIME);
        $item->unsetData(LocationAdditionalDataInterface::PICKUP_DATE_ONLY);
    }
}
