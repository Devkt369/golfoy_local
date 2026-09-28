<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Plugin\InventoryReservationsApi;

use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventorySales\Plugin\InventoryReservationsApi\PreventAppendReservationOnNotManageItemsInStockPlugin;
use Magestore\Webpos\Api\DataProvider\IsPlaceReservationOnPosInterface;

/**
 * Class DisabledPreventAppendReservationPlugin
 *
 * Disabled prevent append reservation if on POS
 */
class DisabledPreventAppendReservationPlugin
{
    /**
     * @var IsPlaceReservationOnPosInterface
     */
    protected $isPlaceReservationOnPos;

    /**
     * Constructor
     *
     * @param IsPlaceReservationOnPosInterface $isPlaceReservationOnPos
     */
    public function __construct(
        IsPlaceReservationOnPosInterface $isPlaceReservationOnPos
    ) {
        $this->isPlaceReservationOnPos = $isPlaceReservationOnPos;
    }

    /**
     * Disabled process if on POS
     *
     * @param PreventAppendReservationOnNotManageItemsInStockPlugin $appendReservationOnNotManageItemsInStockPlugin
     * @param \Closure $proceed
     * @param AppendReservationsInterface $subject
     * @param \Closure $pluginProceed
     * @param array $reservations
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAroundExecute(
        PreventAppendReservationOnNotManageItemsInStockPlugin $appendReservationOnNotManageItemsInStockPlugin,
        \Closure $proceed,
        AppendReservationsInterface $subject,
        \Closure $pluginProceed,
        array $reservations
    ) {
        if (!$this->isPlaceReservationOnPos->getIsPlaceReservationOnPos()) {
            $proceed($subject, $pluginProceed, $reservations);
        } else {
            $pluginProceed($reservations);
        }
    }
}
