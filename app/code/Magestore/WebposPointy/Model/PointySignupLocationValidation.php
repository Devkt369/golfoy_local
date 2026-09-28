<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use Magestore\Webpos\Model\Location\Location;
use Magestore\WebposPointy\Api\PointySignupLocationValidationInterface;

/**
 * Pointy signup location: validation data
 */
class PointySignupLocationValidation implements PointySignupLocationValidationInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Location $location): array
    {
        $validationResult = [
            'error' => false,
            'messages' => []
        ];
        if (!$location->getId()) {
            $validationResult['error'] = true;
            $validationResult['messages'][] = 'Location does not exist!';
            return $validationResult;
        }

        if ($location->getPointyEnable()) {
            $validationResult['error'] = true;
            $validationResult['messages'][] = 'Location has already signed up!';
            return $validationResult;
        }

        if (!$location->getLatitude() || !$location->getLongitude() || !$location->getStreet()) {
            $validationResult['error'] = true;
            $validationResult['messages'][] = 'Location does not have enough address information to signup. '
                . 'Latitude, longitude and street address are required.';
        }

        if (!$location->getScheduleId()) {
            $validationResult['error'] = true;
            $validationResult['messages'][] = 'Location must have opening time to signup Pointy.';
        }

        return $validationResult;
    }
}
