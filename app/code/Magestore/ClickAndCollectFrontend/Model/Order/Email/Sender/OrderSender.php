<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Model\Order\Email\Sender;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magestore\ClickAndCollectApi\Api\GetPickUpInformationInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Order\IsEnoughQtyInStockInterface;

/**
 * @inheritDoc
 */
class OrderSender
{
    const TEMPLATE_STORE_OWNER_EMAIL_ID = 'sales_email_order_store_owner_template_click_and_collect';

    const XML_PATH_SALES_EMAIL_IDENTITY = "trans_email/ident_sales";

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
     * @var IsEnoughQtyInStockInterface
     */
    protected $isEnoughQtyInStock;

    /**
     * OrderSender constructor.
     *
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param GetPickUpInformationInterface $getPickUpInformation
     * @param LocationRepositoryInterface $locationRepository
     * @param IsEnoughQtyInStockInterface $isEnoughQtyInStock
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        GetPickUpInformationInterface $getPickUpInformation,
        LocationRepositoryInterface $locationRepository,
        IsEnoughQtyInStockInterface $isEnoughQtyInStock
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->locationRepository = $locationRepository;
        $this->getPickUpInformation = $getPickUpInformation;
        $this->isEnoughQtyInStock = $isEnoughQtyInStock;
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
            $order->setPickTime($this->getPickUpInformation->execute($order, true));
            $this->transportBuilder->setTemplateVars(
                [
                    'order' => $order
                ]
            );
            $locationId = $order->getLocationPickupId();
            $location = $this->locationRepository->getById($locationId);
            if ($location->getLocationId() && $location->getEmail()) {
                $order->setisOutOfStock(!$this->isEnoughQtyInStock->execute($order, $location));
                $this->transportBuilder->setFromByScope(
                    $this->scopeConfig->getValue(
                        self::XML_PATH_SALES_EMAIL_IDENTITY,
                        ScopeInterface::SCOPE_STORE,
                        $order->getStore()->getId()
                    ),
                    $order->getStore()->getId()
                );
                $this->transportBuilder->addTo(
                    $location->getEmail(),
                    $location->getName()
                );
                $transport = $this->transportBuilder->getTransport();
                $transport->sendMessage();
            }
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
}
