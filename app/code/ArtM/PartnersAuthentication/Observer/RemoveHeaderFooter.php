<?php

namespace ArtM\PartnersAuthentication\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;

class RemoveHeaderFooter implements ObserverInterface
{
    protected $customerSession;
    protected $storeManager;

    public function __construct(
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    public function execute(Observer $observer)
    {
        $layout = $observer->getData('layout');

        $websiteId = $this->storeManager->getWebsite()->getId();

        if (!$this->customerSession->isLoggedIn() && $websiteId == 5) {
            $layout->getUpdate()->addHandle('remove_header_footer_guest');
        }
    }
}
