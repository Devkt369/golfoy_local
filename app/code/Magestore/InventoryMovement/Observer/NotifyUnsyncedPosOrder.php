<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magestore\InventoryMovement\Helper\Data as Helper;
use Magestore\Webpos\Model\Checkout\PosOrder;
use Psr\Log\LoggerInterface;
use Magestore\Webpos\Api\Checkout\PosOrderRepositoryInterface;

/**
 * Class NotifyUnsyncedPosOrder
 *
 * Use to send notification about unsynced pos order
 */
class NotifyUnsyncedPosOrder implements ObserverInterface
{
    const UNSYNCED_POS_ORDER_NOTIFICATION_EMAIL_TEMPLATE = 'unsynced_pos_order_notification_email';
    const UNSYNCED_POS_ORDER_VIEW_PATH = 'webposadmin/unconverted/view';

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var NotifierInterface
     */
    protected $notifier;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param Helper $helper
     * @param TransportBuilder $transportBuilder
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     * @param NotifierInterface $notifier
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Helper $helper,
        TransportBuilder $transportBuilder,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        NotifierInterface $notifier,
        ObjectManagerInterface $objectManager
    ) {
        $this->helper = $helper;
        $this->transportBuilder = $transportBuilder;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->objectManager = $objectManager;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isWebposEnabled()) {
            return $this;
        }
        /** @var PosOrder $posOrder */
        $posOrder = $observer->getEvent()->getPosOrder();
        if ($posOrder
            && $posOrder->getId()
            && ($posOrder->getStatus() == PosOrder::STATUS_FAILED
                || $posOrder->getStatus() == PosOrder::STATUS_PROCESSING)
            && $posOrder->getQueueingStatus() == PosOrder::QUEUEING_STATUS_FAIL
            && !$posOrder->getIsNotifyError()
        ) {
            $posOrderRepository = $this->objectManager->get(PosOrderRepositoryInterface::class);
            $this->sendNotificationEmail($posOrder);
            $this->sendAdminNotification($posOrder);
            $posOrder->setIsNotifyError(PosOrder::ERROR_NOTIFICATION_ALREADY_SENT);
            $posOrderRepository->save($posOrder);
        }
        return $this;
    }

    /**
     * Send Notification Email
     *
     * @param PosOrder $posOrder
     */
    public function sendNotificationEmail(PosOrder $posOrder)
    {
        $recipientEmail = $this->helper->getRecipientEmail();
        if (!$this->helper->isEnableEmailNotification() || empty($recipientEmail)) {
            return;
        }
        $url = $this->urlBuilder->getUrl(
            self::UNSYNCED_POS_ORDER_VIEW_PATH,
            [
                'id' => $posOrder->getId()
            ]
        );
        $templateVars = [
            'incrementId' => $posOrder->getIncrementId(),
            'url' => $url
        ];
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier(self::UNSYNCED_POS_ORDER_NOTIFICATION_EMAIL_TEMPLATE)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => Store::DEFAULT_STORE_ID
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope('general')
                ->addTo($recipientEmail)
                ->getTransport();
            if (count($this->helper->getAdditionalEmails())) {
                $transport->getMessage()->addCc($this->helper->getAdditionalEmails());
            }
            $transport->sendMessage();
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Send Admin Notification
     *
     * @param PosOrder $posOrder
     */
    public function sendAdminNotification(PosOrder $posOrder)
    {
        if (!$this->helper->isEnableAdminNotification()) {
            return;
        }
        $this->notifier->addCritical(
            __("Unsynchronized POS's Order"),
            __(
                "Order #%incrementId is unable to be synchronized from POS to Magento.",
                [
                    'incrementId' => $posOrder->getIncrementId()
                ]
            ),
            self::UNSYNCED_POS_ORDER_VIEW_PATH . '/id/' . $posOrder->getId()
        );
    }
}
