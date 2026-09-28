<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\Summary;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magestore\ClickAndCollectApi\Api\Order\GetTotalDeliveredThisWeekByLocationInterface;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\AbstractAuthorizeResolver;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\PickupPackage\DeliverOrderPickupPackage;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;
use Magento\Framework\Webapi\Request;

/**
 * GetTotalNeedToPackToday field resolver, used for GraphQL request processing.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetTotalDeliveredThisWeek extends AbstractAuthorizeResolver implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::delivery_package';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::delivery_package';

    /**
     * @var GetTotalDeliveredThisWeekByLocationInterface
     */
    protected $getTotalDeliveredThisWeekByLocation;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * GetTotalNeedToPackToday constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param GetTotalDeliveredThisWeekByLocationInterface $getTotalDeliveredThisWeekByLocation
     * @param LocationRepositoryInterface $locationRepository
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        GetTotalDeliveredThisWeekByLocationInterface $getTotalDeliveredThisWeekByLocation,
        LocationRepositoryInterface $locationRepository,
        TimezoneInterface $localeDate
    ) {
        parent::__construct($authorization, $staffManagement, $request);
        $this->getTotalDeliveredThisWeekByLocation = $getTotalDeliveredThisWeekByLocation;
        $this->locationRepository = $locationRepository;
        $this->localeDate = $localeDate;
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
        parent::resolve($field, $context, $info, $value, $args);

        $locationId = $args['locationId'];
        $location = $this->locationRepository->getById($locationId);
        $storeLocationId = $location->getStoreId();
        $currentDate = $this->localeDate->scopeDate($storeLocationId, null, true)->format('Y-m-d');
        $from = date('Y-m-d', strtotime('monday this week', strtotime($currentDate)));
        return [
            'total' => $this->getTotalDeliveredThisWeekByLocation->execute(
                $locationId,
                $from
            )
        ];
    }
}
