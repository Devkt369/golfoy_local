<?php

namespace ArtM\Dob\Block\Index;

use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Dbtest extends \Magento\Framework\View\Element\Template
{
    protected $customerSession;
    protected $customerRepository;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);

    }
    
    public function getCustomerId()
    {
        
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomerId();
        }
        return false;
    }

    public function getCustomerDob()
    {
        $customerId = $this->getCustomerId();
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getDob();
        }
        return false;
    }

}
