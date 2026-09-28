<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\Orders;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Webapi\Request;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\AbstractAuthorizeResolver;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;
use Psr\Log\LoggerInterface;

/**
 * Save order ready pickup date resolver
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveOrderReadyPickDateResolver extends AbstractAuthorizeResolver implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::set_ready_pick_date';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::set_ready_pick_date';

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * SaveOrderReadyPickDateResolver constructor.
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezone
     * @param LocationRepositoryInterface $locationRepository
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        TimezoneInterface $timezone,
        LocationRepositoryInterface $locationRepository
    ) {
        parent::__construct($authorization, $staffManagement, $request);
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->locationRepository = $locationRepository;
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

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
        try {
            $order = $this->orderRepository->get($args['input']['order_id']);
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        try {
            $order->setReadyPickDate($args['input']['ready_pick_date']);
            $order->setCustomerNoteNotify(1);
            $order->setCustomerNote($this->getCustomerNote($order));
            $this->orderRepository->save($order);
            $result = true;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $result = false;
        }

        return ['result' => $result];
    }

    /**
     * Get Customer Note
     *
     * @param OrderInterface $order
     * @return \Magento\Framework\Phrase
     */
    public function getCustomerNote(OrderInterface $order)
    {
        return __(
            "Your order will be ready to be collected on %1.",
            $this->getReadyPickDateFormatted($order)
        );
    }

    /**
     * Get Ready Pick Date Formatted
     *
     * @param OrderInterface $order
     * @return string
     * @throws \Exception
     */
    public function getReadyPickDateFormatted(OrderInterface $order)
    {
        $locationId = $order->getLocationPickupId();
        $location = $this->locationRepository->getById($locationId);
        if ($location->getStoreId()) {
            $storeId = $location->getStoreId();
        } else {
            $storeId = null;
        }
        /* No convert timezone because it have date only */
        $readyPickDate = new \DateTime($order->getReadyPickDate());
        $formatter = new \IntlDateFormatter(
            null,
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE
        );
        /* Only has date so it always Location Time zone */
        return $formatter->format($readyPickDate) . ' ('
            . $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $storeId) . ')';
    }
}
