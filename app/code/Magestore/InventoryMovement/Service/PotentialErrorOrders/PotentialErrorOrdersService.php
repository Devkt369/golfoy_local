<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Service\PotentialErrorOrders;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magestore\InventoryMovement\Model\ResourceModel\OrderPotentialErrorFactory;
use Magestore\InventoryMovement\Helper\Data as Helper;
use Psr\Log\LoggerInterface;
use Magestore\InventoryMovement\Model\ResourceModel\OrderPotentialError\CollectionFactory;

/**
 * Service for potential error orders
 */
class PotentialErrorOrdersService
{
    const POTENTIAL_ERROR_ORDERS_NOTIFICATION_EMAIL_TEMPLATE = 'potential_error_orders_notification_email';
    const POTENTIAL_ERROR_ORDERS_URL_PATH = 'inventorymovement/orderspotentialerror/index';

    /**
     * @var OrderPotentialErrorFactory
     */
    protected $orderPotentialErrorFactory;

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
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var NotifierInterface
     */
    protected $notifier;

    /**
     * PotentialErrorOrdersService constructor.
     * @param OrderPotentialErrorFactory $orderPotentialErrorFactory
     * @param Helper $helper
     * @param TransportBuilder $transportBuilder
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     * @param CollectionFactory $collectionFactory
     * @param NotifierInterface $notifier
     */
    public function __construct(
        OrderPotentialErrorFactory $orderPotentialErrorFactory,
        Helper $helper,
        TransportBuilder $transportBuilder,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        NotifierInterface $notifier
    ) {
        $this->orderPotentialErrorFactory = $orderPotentialErrorFactory;
        $this->helper = $helper;
        $this->transportBuilder = $transportBuilder;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->notifier = $notifier;
    }

    /**
     * Aggregate data
     *
     * @throws LocalizedException
     */
    public function aggregate()
    {
        $hasNewData = $this->orderPotentialErrorFactory->create()->aggregate();
        if ($hasNewData) {
            $this->sendNotifications();
        }
    }

    /**
     * Send Notifications
     */
    public function sendNotifications()
    {
        $this->sendNotificationEmail();
        $this->sendAdminNotification();
    }

    /**
     * Send Notification Email
     */
    public function sendNotificationEmail()
    {
        $recipientEmail = $this->helper->getRecipientEmail();
        if (!$this->helper->isEnableEmailNotification() || empty($recipientEmail)) {
            return;
        }
        $ordersCount = $this->getTotalPotentialErrorOrders();
        $url = $this->urlBuilder->getUrl(self::POTENTIAL_ERROR_ORDERS_URL_PATH);
        $templateVars = [
            'ordersCount' => $ordersCount,
            'url' => $url
        ];
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier(self::POTENTIAL_ERROR_ORDERS_NOTIFICATION_EMAIL_TEMPLATE)
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
     * Get Total Potential Error Orders
     *
     * @return int
     */
    public function getTotalPotentialErrorOrders()
    {
        return $this->collectionFactory->create()->getSize();
    }

    /**
     * Send Admin Notification
     */
    public function sendAdminNotification()
    {
        if (!$this->helper->isEnableAdminNotification()) {
            return;
        }
        $ordersCount = $this->getTotalPotentialErrorOrders();
        $this->notifier->addCritical(
            __("Potential error orders detected!"),
            __(
                "There are %ordersCount potential error orders.",
                [
                    'ordersCount' => $ordersCount,
                ]
            ),
            self::POTENTIAL_ERROR_ORDERS_URL_PATH
        );
    }
}
