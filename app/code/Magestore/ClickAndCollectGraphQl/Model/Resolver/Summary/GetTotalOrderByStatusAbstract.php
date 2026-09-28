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
use Magento\Framework\Webapi\Request;
use Magestore\ClickAndCollectApi\Api\Order\GetTotalClickAndCollectOrderByLocationStatusInterface;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\AbstractAuthorizeResolver;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;

/**
 * GetTotalPending field resolver, used for GraphQL request processing.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class GetTotalOrderByStatusAbstract extends AbstractAuthorizeResolver implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::get_summary';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::click_and_collect';

    const FILTER_STATUSES = [];

    /**
     * @var GetTotalClickAndCollectOrderByLocationStatusInterface
     */
    protected $getTotalClickAndCollectOrderByLocationStatus;

    /**
     * GetTotalOrderByStatusAbstract constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param GetTotalClickAndCollectOrderByLocationStatusInterface $getTotalClickAndCollectOrderByLocationStatus
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        GetTotalClickAndCollectOrderByLocationStatusInterface $getTotalClickAndCollectOrderByLocationStatus
    ) {
        parent::__construct($authorization, $staffManagement, $request);
        $this->getTotalClickAndCollectOrderByLocationStatus = $getTotalClickAndCollectOrderByLocationStatus;
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

        return [
            'total' => $this->getTotalClickAndCollectOrderByLocationStatus->execute(
                $args['locationId'],
                static::FILTER_STATUSES
            )
        ];
    }
}
