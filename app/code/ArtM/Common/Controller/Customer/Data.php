<?php

namespace ArtM\Common\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Mageplaza\RewardPoints\Helper\Account as AccountHelper;

class Data extends Action
{
    protected $resultJsonFactory;
    protected $customerSession;
    protected $accountHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        AccountHelper $accountHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession  = $customerSession;
        $this->accountHelper    = $accountHelper;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $request = $this->getRequest();

        $needAccount   = (bool) $request->getParam('account', false);
        $needLoggedIn  = (bool) $request->getParam('logged_in', false);
        $needPoints    = (bool) $request->getParam('points', false);
        $needGroup    = (bool) $request->getParam('group', false);

        // If no params set, return all data
        if (!$needAccount && !$needLoggedIn && !$needPoints && !$needGroup) {
            $needAccount = $needLoggedIn = $needPoints = $needGroup = true;
        }

        $response = [];
        $isLoggedIn = $this->customerSession->isLoggedIn();

        if ($needLoggedIn) {
            $response['logged_in'] = $isLoggedIn;
            if (!$isLoggedIn) {
                // If not logged in, return only logged_in status
                return $result->setData($response);
            }
        }

        if ($needAccount && $isLoggedIn) {
            $customer = $this->customerSession->getCustomer();
            $response['name'] = $customer && $customer->getFirstname() ? (string) $customer->getFirstname() : '';
        }

        if ($needPoints && $isLoggedIn) {
            $customerId = $this->customerSession->getCustomerId();
            $account = $customerId ? $this->accountHelper->getByCustomerId($customerId) : null;
            $response['balance'] = $account ? (int) $account->getBalance() : 0;
        }

        if ($needGroup && $this->customerSession->isLoggedIn()) {
            $response['group_id'] = (int) $customer->getGroupId();
        }

        return $result->setData($response);
    }
}