<?php

namespace ArtM\SFMC\Observer\Frontend\Order;

use Magento\Framework\Event\ObserverInterface;
use ArtM\SFMC\Helper\Data as SFMCHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Psr\Log\LoggerInterface;

class CreateAfter implements ObserverInterface
{
    protected $cookieManager;
    protected $logger;
    protected $SFMCHelper;
    protected $cookieMetadataFactory;

    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        LoggerInterface $logger,
        SFMCHelper $SFMCHelper
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->logger = $logger;
        $this->SFMCHelper = $SFMCHelper;
    }

    public function execute(Observer $observer)
    {
        try {
            $utmSource    = $this->cookieManager->getCookie('utm_source') ?? '';
            $utmMedium    = $this->cookieManager->getCookie('utm_medium') ?? '';
            $utmCampaign  = $this->cookieManager->getCookie('utm_campaign') ?? '';
            $utmId        = $this->cookieManager->getCookie('utm_id') ?? '';
            $utmTerm      = $this->cookieManager->getCookie('utm_term') ?? '';
            $sfmcId       = $this->cookieManager->getCookie('sfmc_id') ?? '';

            if($utmSource || $utmMedium || $utmCampaign || $utmId || $utmTerm || $sfmcId){
                
                $trackingData = [];
                $order = $observer->getOrder();
                $orderId = $order->getId();
                $GrandTotal = $order->getGrandTotal();
                $orderItems = $this->getOrderedItems($order);
                if($order->getCustomerId()){
                    $sfmcId = $order->getCustomerId();
                }
                $trackingData = [
                    'utm_source'    => $utmSource,
                    'utm_campaign'  => $utmCampaign,
                    'utm_id'        => $utmId,
                    'utm_term'      => $utmTerm,
                    'utm_medium'    => $utmMedium,
                    'sfmc_id'       => $sfmcId,
                    'order_no'      => $orderId,
                    'ordered_Items' => $orderItems,
                    'order_value'   => $GrandTotal
                ]; 
                $this->SFMCHelper->SyncTrackingOrder($trackingData);
            }
            $this->deleteCookies();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    public function getOrderedItems($order) {
        $productData = [];
        $orderItems = $order->getAllVisibleItems();
        foreach ($orderItems as $item) {
            try {
                $productName = str_replace("'", "", $item->getName());
                $productSku = $item->getSku();
                $productPrice = $item->getData('price_incl_tax');
                $qty = $item->getQtyOrdered();
            } catch (\Exception $e) {
                $productName = 'Not Found';
                $productSku = 'Not Found';
                $qty = 'Not Found';
                $productPrice = 'Not Found';
            }
            $productData[] = [
                'name' => $productName,
                'sku' => $productSku,
                'qty' => $qty,
                'price' => $productPrice
            ];
        }
        return json_encode($productData);
    }

    public function deleteCookies(){
        $cookieKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'sfmc_id', 'utm_term'];
        foreach ($cookieKeys as $cookieKey) {
            $cookieValue = $this->cookieManager->getCookie($cookieKey) ?? '';
            if ($cookieValue) {
                $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                $metadata->setPath('/'); 
                $this->cookieManager->deleteCookie($cookieKey, $metadata);
            }
        }
    }
}
