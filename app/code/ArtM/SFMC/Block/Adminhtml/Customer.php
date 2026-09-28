<?php

namespace ArtM\SFMC\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\CustomerFactory;

class Customer extends Template
{
    protected $customerFactory;

    public function __construct(
        Template\Context $context,
        CustomerFactory $customerFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerFactory = $customerFactory;
    }

    public function getCustomerData()
    {
        $customerId = 2414; 
        $this->_logger->info("Customer ID: $customerId");
        $customer = $this->customerFactory->create()->load($customerId);
        if ($customer->getId()) {
            return $customer->getData();
        } else {
            return false; 
        }
    }
}
