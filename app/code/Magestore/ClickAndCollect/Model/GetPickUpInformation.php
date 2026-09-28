<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use DateTime;
use IntlDateFormatter;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magestore\ClickAndCollectApi\Api\GetPickUpInformationInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;

/**
 * Class GetPickUpInformation
 *
 * Get information of pickup order
 */
class GetPickUpInformation implements GetPickUpInformationInterface
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * GetPickUpInformation constructor.
     *
     * @param TimezoneInterface $timezone
     * @param LocationRepositoryInterface $locationRepository
     */
    public function __construct(
        TimezoneInterface $timezone,
        LocationRepositoryInterface $locationRepository
    ) {
        $this->timezone = $timezone;
        $this->locationRepository = $locationRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(Order $order, bool $isConvertLocationTimezone = false): string
    {
        if (!$isConvertLocationTimezone) {
            $storeId = null;
        } else {
            $locationPickUpId = $order->getLocationPickupId();
            $location = $this->locationRepository->getById($locationPickUpId);
            if ($location->getStoreId()) {
                $storeId = $location->getStoreId();
            } else {
                $storeId = null;
            }
        }
        $timezone = $this->timezone->getConfigTimezone(
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($order->getPickupDateTime()) {
            $timeToPick = $this->timezone->formatDateTime(
                new DateTime($order->getPickupDateTime()),
                IntlDateFormatter::SHORT,
                true,
                null,
                $timezone
            ) . ' (' . $timezone . ')';
        } elseif ($order->getPickupDateOnly()) {
            /* No convert timezone because it have time only */
            $timeToPick = new DateTime($order->getPickupDateOnly());
            $formatter = new IntlDateFormatter(
                null,
                IntlDateFormatter::SHORT,
                IntlDateFormatter::NONE
            );
            /* Only has date so it always Location Time zone */
            $timeToPick = $formatter->format($timeToPick) . ' ('
                . $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $storeId) . ')';
        } else {
            $timeToPick = '';
        }
        if ($timeToPick) {
            return $timeToPick;
        } else {
            return '';
        }
    }
}
