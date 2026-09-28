<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types = 1);

namespace Magestore\Webpos\Model\DataProvider;

use Magento\Framework\DataObject;
use Magestore\Webpos\Api\DataProvider\IsPlaceReservationOnPosInterface;

/**
 * Class IsPlaceReservationOnPos
 *
 * Data Provider to get is place reservation on POS
 */
class IsPlaceReservationOnPos extends DataObject implements IsPlaceReservationOnPosInterface
{
    /**
     * Set is place reservation on POS to mark
     *
     * @param bool $isPlaceReservationOnPos
     * @return void
     */
    public function setIsPlaceReservationOnPos(bool $isPlaceReservationOnPos)
    {
        $this->setData(self::IS_PLACE_RESERVATION_POS_POS, $isPlaceReservationOnPos);
    }

    /**
     * Get is place reservation on POS
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsPlaceReservationOnPos(): bool
    {
        if ($this->_getData(self::IS_PLACE_RESERVATION_POS_POS)) {
            return true;
        } else {
            return false;
        }
    }
}
