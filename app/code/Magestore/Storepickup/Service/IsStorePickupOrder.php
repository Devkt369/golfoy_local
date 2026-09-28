<?php

namespace Magestore\Storepickup\Service;

/**
 * Class IsStorePickupOrder used for data provider
 */
class IsStorePickupOrder
{
    /**
     * @var bool
     */
    private $storePickup;

    /**
     * Set Is Store PickUp Order
     *
     * @param bool $storePickup
     */
    public function setIsStorePickUpOrder($storePickup)
    {
        $this->storePickup = $storePickup;
    }

    /**
     * Get Is Store PickUp Order
     *
     * @return bool
     */
    public function isStorePickUpOrder()
    {
        return $this->storePickup;
    }
}
