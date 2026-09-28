<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Checkout\PosOrder;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magestore\Webpos\Api\Checkout\PosOrderRepositoryInterface;
use Magestore\Webpos\Helper\PosOrder as PosOrderHelper;
use Magestore\Webpos\Model\Checkout\PosOrder;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

/**
 * Consumer message queue for converting pos order
 */
class Consumer
{
    const QUEUE_NAME = 'magestore_pos_process_order.convert.queue';
    const QUEUE_TOPIC_NAME = 'magestore_pos_process_order.convert.topic';
    const QUEUE_CONSUMER_NAME = 'magestore_pos_process_order.convert.consumer';

    /**
     * @var PosOrderRepositoryInterface
     */
    private $posOrderRepository;
    /**
     * @var PublisherInterface
     */
    private $publisher;
    /**
     * @var PosOrderHelper
     */
    private $posOrderHelper;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @param PosOrderRepositoryInterface $posOrderRepository
     * @param PublisherInterface $publisher
     * @param PosOrderHelper $posOrderHelper
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        PosOrderRepositoryInterface $posOrderRepository,
        PublisherInterface $publisher,
        PosOrderHelper $posOrderHelper,
        EventManagerInterface $eventManager
    ) {
        $this->posOrderRepository = $posOrderRepository;
        $this->publisher = $publisher;
        $this->posOrderHelper = $posOrderHelper;
        $this->eventManager = $eventManager;
    }

    /**
     * Process convert order
     *
     * @param string $incrementId
     * @throws \Exception
     */
    public function execute(string $incrementId)
    {
        try {
            // Log process load message to convert order
            $processLoadMessageToConvertLog = [
                'format' => 'process',
                'data' => [
                    'app' => 'pos_server',
                    'log_flow' => 'place_order',
                    'business_flow' => 'place_order',
                    'log_message' => sprintf('Load message from queue to convert order #%s', $incrementId),
                    'process_type' => 'system',
                    'process_name' => 'Load message from queue to convert order',
                    'process_input' => [
                        'increment_id' => $incrementId
                    ]
                ]
            ];
            $this->eventManager->dispatch('magestore_log', $processLoadMessageToConvertLog);

            $this->posOrderRepository->processConvertOrder($incrementId);
            // If the order is converted successfully
            // => The pos order will be moved to backup table
            // => Cannot get object pos order anymore
            // => The new message will not be added
            // Else, the new message will be added
            $this->addNextMessageQueue($incrementId);
        } catch (\Exception $e) {
            $this->addNextMessageQueue($incrementId);
        }
    }

    /**
     * Add a new message queue if the request fail
     *
     * @param string $incrementId
     * @return void
     */
    public function addNextMessageQueue(string $incrementId)
    {
        try {
            $posOrder = $this->posOrderRepository->get($incrementId);
        } catch (\Exception $e) {
            return;
        }

        $posOrderQueueStatusBefore = $posOrder->getQueueingStatus();
        $posOrderBefore = $posOrder->getData();

        $numberOfTrials = $posOrder->getNumberOfTrials() + 1;
        $posOrder->setNumberOfTrials($numberOfTrials);
        if ($numberOfTrials >= $this->posOrderHelper->getMaximumNumberOfTrials()) {
            // Mark pos order as fail and stop process convert
            $posOrder->setQueueingStatus(PosOrder::QUEUEING_STATUS_FAIL);
            $this->posOrderRepository->save($posOrder);

            if ($posOrderQueueStatusBefore !== $posOrder->getQueueingStatus()) {
                $this->eventManager->dispatch(
                    'magestore_log',
                    [
                        'format' => 'data_change',
                        'data' => [
                            'app' => 'pos_server',
                            'log_flow' => 'place_order',
                            'business_flow' => 'place_order',
                            'log_message' =>  sprintf(
                                'Change Unconverted Order #%s Queue Status : %s => %s ',
                                $posOrder->getIncrementId(),
                                $posOrderQueueStatusBefore,
                                $posOrder->getQueueingStatus()
                            ),
                            'data_type' => 'unconverted_order',
                            'data_before' => $posOrderBefore,
                            'data_after' => $posOrder->getData()
                        ]
                    ]
                );
            }

            return;
        }
        $this->publisher->publish(self::QUEUE_TOPIC_NAME, $incrementId);
        $this->posOrderRepository->save($posOrder);
    }
}
