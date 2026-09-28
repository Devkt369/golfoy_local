<?php
namespace ArtM\SSO\Controller\Forms;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use ArtM\SSO\Helper\Data;
class CheckEmail extends Action
{
    protected $resultJsonFactory;
    protected $customerRepository;
    protected $helperData;
    protected $customerSession;
    protected $customerModel;
    protected $storeMangerFactory;
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeMangerFactory,
        Customer $customerModel,
        Session $customerSession,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerRepository = $customerRepository;
        $this->customerModel = $customerModel;
        $this->customerSession = $customerSession;
        $this->helperData = $helperData;
        $this->storeMangerFactory = $storeMangerFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $storeID = $this->storeMangerFactory->getStore()->getId();
        $websiteID = $this->storeMangerFactory->getStore($storeID)->getWebsiteId();
        $email = $this->getRequest()->getParam('email');
        $firstName = $this->getRequest()->getParam('fname');
        $lastName = $this->getRequest()->getParam('lname');
       // echo 'controller-'.$email; die;

        if (!$email) {
            return $result->setData(['error' => 'Email parameter is missing']);
        }

        try {
            if($this->helperData->isEmailRegistered($email)){
               $customer = $this->customerModel->setWebsiteId(1)->loadByEmail($email);
               $this->customerSession->setCustomerAsLoggedIn($customer);
                return $result->setData(['exists' => true]);
            }else {
                setcookie('user_email', $email, time() + 300, '/');
                $postData = array(
                    'firstName'=>$firstName,
                    'lastName'=>$lastName,
                    'email'=>$email

                );

                $this->helperData->createAccount($postData,$websiteID);
                return $result->setData(['exists' => false]);
            }
            
        } catch (NoSuchEntityException $e) {
           
        }
    }
}