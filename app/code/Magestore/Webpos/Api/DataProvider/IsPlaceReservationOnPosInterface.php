<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\Webpos\Api\DataProvider;

interface IsPlaceReservationOnPosInterface
{
    const IS_PLACE_RESERVATION_POS_POS = 'is_place_reservation_on_pos';

    /**
     * Set is place reservation on POS to mark
     *
     * @param bool $isPlaceReservationOnPos
     * @return void
     */
    public function setIsPlaceReservationOnPos(bool $isPlaceReservationOnPos);

    /**
     * Get is place reservation on POS
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsPlaceReservationOnPos(): bool;
}
