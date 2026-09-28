<?php

namespace ArtM\CustomProductDesign\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Session\SessionManagerInterface;
use ArtM\CustomProductDesign\Model\UserdataFactory;
use Magento\Customer\Model\Session as CustomerSession;

use function GuzzleHttp\json_encode;

class AddToCartObserver implements ObserverInterface
{
    protected $userdataFactory;
    protected $session;
    protected $customerSession;

    public function __construct(
        UserdataFactory $userdataFactory,
        SessionManagerInterface $session,
        CustomerSession $customerSession
    ) {
        $this->userdataFactory = $userdataFactory;
        $this->session = $session;
        $this->customerSession = $customerSession;
    }

    public function execute(Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/add_to_cart.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        
        
        /** @var \Magento\Quote\Model\Quote\Item $item */
        $item = $observer->getEvent()->getQuoteItem();
        
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        
        $productId = $product->getId();
        $customerId = null;
        $sessionId = $this->session->getSessionId();
        $logger->info('current='.$sessionId);
        $logger->info('current='.$sessionId);
        // if ($this->customerSession->isLoggedIn()) {
        //     $customerId = $this->customerSession->getCustomerId();
        // }
        
        $collection = $this->userdataFactory->create()->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('session_id', $sessionId)
            ->addFieldToFilter('attached_to_cart', 0);

        $logger->info('Userdata collection size = ' . $collection->getSize());
        
        if ($collection->getSize() > 0) {
            $previousData = $item->getCustomDesignUserdataIds() ?? '';

            foreach ($collection as $userdataItem) {
                $data = [
                    'id'              => $userdataItem->getData('id'),
                    'product_id'      => $userdataItem->getData('product_id'),
                    'customer_id'     => $userdataItem->getData('customer_id'),
                    'session_id'      => $userdataItem->getData('session_id'),
                    'img_path'        => $userdataItem->getData('img_path'),
                    'additional_data' => $userdataItem->getData('additional_data'),
                ];

                $json = json_encode($data);
                $previousData .= $json;

                if(!$product->getData('enable_bulk_ordering')){
                    $userdataItem->setData('attached_to_cart', 1);
                }                
                $userdataItem->save();
            }
            $item->setCustomDesignUserdataIds($previousData);
        }

    }
}
