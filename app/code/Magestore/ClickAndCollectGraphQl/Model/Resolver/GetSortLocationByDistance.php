<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationsOrderedByDistanceInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;
use Magestore\Webpos\Api\Data\Location\LocationInterface;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class GetSortLocationByDistance implements ResolverInterface
{
    /**
     * @var GetLocationsOrderedByDistanceInterface
     */
    protected $getLocationsOrderedByDistance;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressInterfaceFactory;

    /**
     * GetSortLocationByDistance constructor.
     *
     * @param GetLocationsOrderedByDistanceInterface $getLocationsOrderedByDistance
     * @param AddressInterfaceFactory $addressInterfaceFactory
     */
    public function __construct(
        GetLocationsOrderedByDistanceInterface $getLocationsOrderedByDistance,
        AddressInterfaceFactory $addressInterfaceFactory
    ) {
        $this->getLocationsOrderedByDistance = $getLocationsOrderedByDistance;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $stockId = 0;
        if (isset($args['stockId']) && $args['stockId'] < 1) {
            throw new GraphQlInputException(__('stockId value must be greater than 0.'));
        } elseif (isset($args['stockId'])) {
            $stockId = $args['stockId'];
        }

        if ($args['radius'] < 1) {
            throw new GraphQlInputException(__('radius value must be greater than 0.'));
        }

        if (!$args['address']) {
            throw new GraphQlInputException(__('Address must not be empty.'));
        }

        $result = $this->getLocationsOrderedByDistance->execute($args['address'], $args['radius'], $stockId);
        $locations = $result['sort_location'];

        return [
            'total_count' => count($locations),
            'items' => $this->formatLocationsArray($locations),
            'address_latitude' => $result['address_lat'],
            'address_longitude' => $result['address_lng']
        ];
    }

    /**
     * Format location models for graphql schema
     *
     * @param LocationInterface[] $locations
     * @return array
     */
    public function formatLocationsArray(array $locations)
    {
        $locationsArray = [];
        foreach ($locations as $location) {
            $locationsArray[$location->getLocationId()] = $location->getData();
            $locationsArray[$location->getLocationId()]['model'] = $location;
        }
        return $locationsArray;
    }
}
