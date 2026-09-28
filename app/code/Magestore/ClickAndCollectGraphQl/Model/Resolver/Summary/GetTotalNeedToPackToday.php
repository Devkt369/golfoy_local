<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\Summary;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Webapi\Request;
use Magestore\ClickAndCollectApi\Api\Order\GetTotalNeedToPackInterface;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\AbstractAuthorizeResolver;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;

/**
 * GetTotalNeedToPackToday field resolver, used for GraphQL request processing.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetTotalNeedToPackToday extends AbstractAuthorizeResolver implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::get_summary';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::click_and_collect';

    /**
     * @var GetTotalNeedToPackInterface
     */
    protected $getTotalNeedToPackInterface;

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
     * @param GetTotalNeedToPackInterface $getTotalNeedToPackInterface
     * @param LocationRepositoryInterface $locationRepository
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        GetTotalNeedToPackInterface $getTotalNeedToPackInterface,
        LocationRepositoryInterface $locationRepository,
        TimezoneInterface $localeDate
    ) {
        parent::__construct($authorization, $staffManagement, $request);
        $this->getTotalNeedToPackInterface = $getTotalNeedToPackInterface;
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
        $currentDate = $this->localeDate->scopeDate($storeLocationId, null, true)
            ->format('Y-m-d');

        return [
            'total' => $this->getTotalNeedToPackInterface->execute(
                $locationId,
                $currentDate,
                $currentDate
            )
        ];
    }
}
