<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Model\DistanceProvider\GoogleMap;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterfaceFactory;
use Magestore\Webpos\Api\GetLatLngFromAddressInterface;

/**
 * @inheritdoc
 */
class GetLatLngFromAddress implements GetLatLngFromAddressInterface
{
    /**
     * @var array
     */
    private $latLngCache = [];

    /**
     * @var LatLngInterface
     */
    private $latLngInterfaceFactory;

    /**
     * @var GetGeoCodesForAddress
     */
    private $getGeoCodesForAddress;

    /**
     * GetLatLngFromAddress constructor.
     *
     * @param LatLngInterfaceFactory $latLngInterfaceFactory
     * @param GetGeoCodesForAddress|null $getGeoCodesForAddress
     */
    public function __construct(
        LatLngInterfaceFactory $latLngInterfaceFactory,
        GetGeoCodesForAddress $getGeoCodesForAddress
    ) {
        $this->latLngInterfaceFactory = $latLngInterfaceFactory;
        $this->getGeoCodesForAddress = $getGeoCodesForAddress;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $address): LatLngInterface
    {
        if (!isset($this->latLngCache[$address])) {
            $res = $this->getGeoCodesForAddress->execute($address);
            $location = $res['results'][0]['geometry']['location'];
            $this->latLngCache[$address] = $this->latLngInterfaceFactory->create([
                'lat' => (float)$location['lat'],
                'lng' => (float)$location['lng'],
            ]);
        }

        return $this->latLngCache[$address];
    }
}
