<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Order\Email\Sender;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Translate\Inline\StateInterface;
use Magestore\ClickAndCollectApi\Api\GetPickUpInformationInterface;
use Magestore\ClickAndCollectApi\Api\GoogleMap\GetMapImageFromPositionInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;
use Magestore\ClickAndCollectFrontend\Model\Order\Email\Sender\OrderSender as OrderSenderFrontend;

/**
 * Order Email Sender
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderSender
{
    const TEMPLATE_STORE_OWNER_EMAIL_ID = 'sales_email_packed_order_template_click_and_collect';
    const TEMPLATE_NOTIFY_READY_PICK_DATE_EMAIL_ID = 'click_and_collect_notify_order_ready_pick_date';

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var GetPickUpInformationInterface
     */
    protected $getPickUpInformation;

    /**
     * @var GetMapImageFromPositionInterface
     */
    private $getMapImageFromPosition;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * OrderSender constructor.
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param GetPickUpInformationInterface $getPickUpInformation
     * @param LocationRepositoryInterface $locationRepository
     * @param GetMapImageFromPositionInterface $getMapImageFromPosition
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        GetPickUpInformationInterface $getPickUpInformation,
        LocationRepositoryInterface $locationRepository,
        GetMapImageFromPositionInterface $getMapImageFromPosition,
        TimezoneInterface $timezone
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->locationRepository = $locationRepository;
        $this->getPickUpInformation = $getPickUpInformation;
        $this->getMapImageFromPosition = $getMapImageFromPosition;
        $this->timezone = $timezone;
    }

    /**
     * Sends order email to the store owner.
     *
     * @param Order $order
     * @return bool
     */
    public function send(Order $order)
    {
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier(self::TEMPLATE_STORE_OWNER_EMAIL_ID);
        $this->transportBuilder->setTemplateOptions(
            ['area' => Area::AREA_FRONTEND, 'store' => $order->getStore()->getId()]
        );

        try {
            $locationId = $order->getLocationPickupId();
            $location = $this->locationRepository->getById($locationId);
            $information = $this->getStoreInformation($location);
            $mapInformation =   $this->getMapImageFromPosition->execute(
                (string) $information['lat'],
                (string) $information['lng']
            );
            $order->setMapImage(
                $mapInformation['image']
            );
            $order->setMapUrl(
                $mapInformation['url']
            );

            if (count($information)) {
                $order->setHasClickAndCollectInfo(true);
                $order->setClickAndCollectInfo(
                    new DataObject($information)
                );
            } else {
                $order->setHasClickAndCollectInfo(false);
                $order->setClickAndCollectInfo(null);
            }

            $this->transportBuilder->setTemplateVars(
                [
                    'order' => $order,
                    'order_data' => [
                        'customer_name' => $order->getCustomerName()
                    ],
                    'locationName' => $location->getName(),
                ]
            )->setFromByScope(
                $this->scopeConfig->getValue(
                    OrderSenderFrontend::XML_PATH_SALES_EMAIL_IDENTITY,
                    ScopeInterface::SCOPE_STORE,
                    $order->getStore()->getId()
                ),
                $order->getStore()->getId()
            )->addTo(
                $order->getCustomerEmail(),
                $order->getCustomerName()
            )->getTransport()->sendMessage();
        } catch (MailException $exception) {
            $this->inlineTranslation->resume();
            $this->logger->error($exception->getMessage());
            return false;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Sends order email to the store owner.
     *
     * @param Order $order
     * @return bool
     */
    public function sendNotifyReadyPickDate(OrderInterface $order)
    {
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier(self::TEMPLATE_NOTIFY_READY_PICK_DATE_EMAIL_ID);
        $this->transportBuilder->setTemplateOptions(
            ['area' => Area::AREA_FRONTEND, 'store' => $order->getStore()->getId()]
        );

        try {
            if (!$order->getReadyPickDate()) {
                return false;
            }
            $locationId = $order->getLocationPickupId();
            $location = $this->locationRepository->getById($locationId);
            $information = $this->getStoreInformation($location);
            $mapInformation =   $this->getMapImageFromPosition->execute(
                (string) $information['lat'],
                (string) $information['lng']
            );
            $order->setMapImage(
                $mapInformation['image']
            );
            $order->setMapUrl(
                $mapInformation['url']
            );

            if (count($information)) {
                $order->setHasClickAndCollectInfo(true);
                $order->setClickAndCollectInfo(
                    new DataObject($information)
                );
            } else {
                $order->setHasClickAndCollectInfo(false);
                $order->setClickAndCollectInfo(null);
            }

            $this->transportBuilder->setTemplateVars(
                [
                    'order' => $order,
                    'order_data' => [
                        'customer_name' => $order->getCustomerName()
                    ],
                    'locationName' => $location->getName(),
                    'ready_pick_date_formatted' => $this->getReadyPickDateFormatted($order, $location)
                ]
            )->setFromByScope(
                $this->scopeConfig->getValue(
                    OrderSenderFrontend::XML_PATH_SALES_EMAIL_IDENTITY,
                    ScopeInterface::SCOPE_STORE,
                    $order->getStore()->getId()
                ),
                $order->getStore()->getId()
            )->addTo(
                $order->getCustomerEmail(),
                $order->getCustomerName()
            )->getTransport()->sendMessage();
        } catch (MailException $exception) {
            $this->inlineTranslation->resume();
            $this->logger->error($exception->getMessage());
            return false;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Get Ready Pick Date Formatted
     *
     * @param Order $order
     * @param LocationInterface $location
     * @return string
     * @throws Exception
     */
    public function getReadyPickDateFormatted(Order $order, LocationInterface $location)
    {
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

    /**
     * Get store information
     *
     * @param \Magestore\Webpos\Api\Data\Location\LocationInterface $location
     * @return array
     */
    public function getStoreInformation($location)
    {
        if ($location->getLocationId()) {
            $locationAddress = [
                $location->getStreet(),
                $location->getCity(),
                $location->getRegion(),
                $location->getCountry(),
                $location->getPostcode()
            ];
            return [
                'store_name' => $location->getName(),
                'email' => $location->getEmail(),
                'phone' => $location->getTelephone(),
                'lat' => $location->getLatitude(),
                'lng' => $location->getLongitude(),
                'address' => implode(', ', array_filter($locationAddress))
            ];
        } else {
            return [];
        }
    }
}
