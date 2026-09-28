<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Checkout;

use Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Webapi\Rest\Request;
use Magestore\Webpos\Api\Data\Checkout\PosOrderInterface;
use Magestore\Webpos\Helper\Data;
use Magestore\Webpos\Log\Logger;
use Magestore\Webpos\Model\ResourceModel\Checkout\PosOrder as PosOrderResource;
use Magestore\Webpos\Model\ResourceModel\Checkout\PosOrder\Collection;
use Magestore\WebposIntegration\Controller\Rest\RequestProcessor;
use Magestore\Webpos\Api\LogHandle\ProcessLogExceptionInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class PosOrder
 *
 * Pos order model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PosOrder extends AbstractModel implements PosOrderInterface
{
    const STATUS_PENDING = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_FAILED = 4;

    const PARAM_ORDER_POS_ID = 'new_order_pos_id';
    const PARAM_ORDER_LOCATION_ID = 'new_order_location_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'pos_order';

    /**
     * @var string
     */
    protected $_eventObject = 'pos_order';

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * PosOrder constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param PosOrderResource $resource
     * @param Collection $resourceCollection
     * @param Logger $logger
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PosOrderResource $resource,
        Collection $resourceCollection,
        Logger $logger,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init(PosOrderResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getPosOrderId(): ?int
    {
        return (int)$this->getData(self::ID);
    }

    /**
     * @inheritdoc
     */
    public function setPosOrderId(?int $id): void
    {
        $this->setData(self::ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?int
    {
        return (int)$this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?int $status): void
    {
        $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData(): ?string
    {
        return $this->getData(self::ADDITIONAL_DATA);
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalData(?string $additionalData): void
    {
        $this->setData(self::ADDITIONAL_DATA, $additionalData);
    }

    /**
     * @inheritdoc
     */
    public function getIncrementId(): ?string
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setIncrementId(?string $incrementId): void
    {
        $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * @inheritdoc
     */
    public function getSessionId(): ?string
    {
        return $this->getData(self::SESSION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setSessionId(?string $sessionId): void
    {
        $this->setData(self::SESSION_ID, $sessionId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(?int $storeId): void
    {
        $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getLocationId(): ?int
    {
        return (int)$this->getData(self::LOCATION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLocationId(?int $locationId): void
    {
        $this->setData(self::LOCATION_ID, $locationId);
    }

    /**
     * @inheritdoc
     */
    public function getPosId(): ?int
    {
        return (int)$this->getData(self::POS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setPosId(?int $posId): void
    {
        $this->setData(self::POS_ID, $posId);
    }

    /**
     * @inheritdoc
     */
    public function getPosStaffId(): ?int
    {
        return (int)$this->getData(self::POS_STAFF_ID);
    }

    /**
     * @inheritdoc
     */
    public function setPosStaffId(?int $posStaffId): void
    {
        $this->setData(self::POS_STAFF_ID, $posStaffId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderCreatedTime(): ?string
    {
        return $this->getData(self::ORDER_CREATED_TIME);
    }

    /**
     * @inheritdoc
     */
    public function setOrderCreatedTime(?string $orderCreatedTime): void
    {
        $this->setData(self::ORDER_CREATED_TIME, $orderCreatedTime);
    }

    /**
     * @inheritdoc
     */
    public function getParams(): ?string
    {
        return $this->getData(self::PARAMS);
    }

    /**
     * @inheritdoc
     */
    public function setParams(?string $params): void
    {
        $this->setData(self::PARAMS, $params);
    }

    /**
     * @inheritdoc
     */
    public function getQueueingStatus(): ?string
    {
        return $this->getData(self::QUEUEING_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setQueueingStatus(?string $queueingStatus): void
    {
        $this->setData(self::QUEUEING_STATUS, $queueingStatus);
    }

    /**
     * @inheritdoc
     */
    public function getNumberOfTrials(): ?int
    {
        return (int)$this->getData(self::NUMBER_OF_TRIALS);
    }

    /**
     * @inheritdoc
     */
    public function setNumberOfTrials(?int $numberOfTrials): void
    {
        $this->setData(self::NUMBER_OF_TRIALS, $numberOfTrials);
    }

    /**
     * @inheritdoc
     */
    public function getIsNotifyError(): ?int
    {
        return (int)$this->getData(self::IS_NOTIFY_ERROR);
    }

    /**
     * @inheritdoc
     */
    public function setIsNotifyError(?int $isNotifyError): void
    {
        $this->setData(self::IS_NOTIFY_ERROR, $isNotifyError);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes()
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(
        \Magestore\Webpos\Api\Data\Checkout\PosOrderExtensionInterface $extensionAttributes
    ) {
        return $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }

    /**
     * Save request place order
     *
     * @param Request $request
     */
    public function saveRequest(Request $request)
    {
        $params = $request->getBodyParams();

        $existedRequest = clone $this;
        $existedRequest->load($params['order']['increment_id'], 'increment_id');
        if ($existedRequest->getId()) {
            return;
        }

        $data = [
            'status' => self::STATUS_PENDING,
            'increment_id' => $params['order']['increment_id'],
            'session_id' => $request->getParam(
                RequestProcessor::SESSION_PARAM_KEY
            ),
            'store_id' => $this->helper->getCurrentStoreView()->getId(),
            'location_id' => $params['order']['pos_location_id'],
            'pos_staff_id' => $params['order']['pos_staff_id'],
            'pos_id' => $params['order']['pos_id'],
            'order_created_time' => $params['order']['created_at'],
            'params' => json_encode($params),
        ];

        $this->setData($data);

        // Log process create unconverted order
        $processCreateUnconvertedOrderLog = [
            'format' => 'process',
            'data' => [
                'app' => 'pos_server',
                'log_flow' => 'place_order',
                'business_flow' => 'place_order',
                'log_message' => 'Create unconverted order #' . $data['increment_id'],
                'process_type' => 'system',
                'process_name' => 'create unconverted order',
                'process_input' => $data
            ]
        ];
        $this->_eventManager->dispatch('magestore_log', $processCreateUnconvertedOrderLog);

        try {
            $unconvertedPosOrder = $this->save();
            $this->_eventManager->dispatch(
                'magestore_log',
                [
                    'format' => 'data_change',
                    'data' => [
                        'app' => 'pos_server',
                        'log_flow' => 'place_order',
                        'business_flow' => 'place_order',
                        'log_message' =>  sprintf('Saved unconverted POS order : #%s', $data['increment_id']),
                        'data_type' => 'unconverted_order',
                        'data_before' => [],
                        'data_after' => $unconvertedPosOrder->getData()
                    ]
                ]
            );
        } catch (Exception $e) {
            /* @var ProcessLogExceptionInterface $loggerException */
            $loggerException = ObjectManager::getInstance()->get(ProcessLogExceptionInterface::class);
            $loggerException->execute(
                'place_order',
                'place_order',
                sprintf('Can not save unconverted POS order : #%s', $data['increment_id']),
                'critical',
                $e
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function afterCommitCallback()
    {
        parent::afterCommitCallback();

        if ($this->getId() && $this->getStatus() == self::STATUS_COMPLETED) {
            $this->_resource->processBackupOfWebposOrder($this->getIncrementId());
        }

        return $this;
    }
}
