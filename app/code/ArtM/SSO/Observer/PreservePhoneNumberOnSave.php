<?php

namespace ArtM\SSO\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerFactory;
use Psr\Log\LoggerInterface;

class PreservePhoneNumberOnSave implements ObserverInterface
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CustomerFactory $customerFactory,
        LoggerInterface $logger
    ) {
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
    }

    /**
     * Preserve phone_number attribute when customer is saved via model save.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            if (!$customer || !$customer->getId()) {
                return;
            }

            $phoneNumber = $customer->getData('phone_number');
            if (empty($phoneNumber)) {
                $existingCustomer = $this->customerFactory->create()->load($customer->getId());
                $existingPhone = $existingCustomer->getData('phone_number');

                if (!empty($existingPhone)) {
                    $customer->setData('phone_number', $existingPhone);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('ArtM_SSO PreservePhoneNumberOnSave: ' . $e->getMessage());
        }
    }
}
