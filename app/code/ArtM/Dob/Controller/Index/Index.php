<?php

namespace ArtM\Dob\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Index extends Action
{
    protected $customerSession;
    protected $customerRepository;
    protected $_resultJsonFactory;
    protected $request;
    protected $resultPageFactory;

    public function __construct
    (
        Context $context, 
        PageFactory $resultPageFactory,
        RequestInterface $request,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        JsonFactory $_resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $_resultJsonFactory;
        $this->request = $request;   
        $this->resultPageFactory = $resultPageFactory; 
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;    
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $result = $this->_resultJsonFactory->create();
        
        $formData = $this->request->getPost();

        $year = 1;
        
        if (strlen($formData['year']) == '4'){
            $year = 4;
        }
       

        if(checkdate($formData['month'], $formData['day'], $formData['year'] )){
            
            $dob = $formData['dob_customer'];
    
            $save = $this->saveCustomerDob($dob);
    
            if($save){
                $result->setData(['result' => "True"]);
            }else{
                $result->setData(['result' => 'False']);
            }

            return $result;
        }
        return $result->setData(['result' => 'False']);

    }

    public function getCustomerId()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomerId();
        }
        return false;
    }

    public function saveCustomerDob($dob)
    {
       $customerId = $this->getCustomerId();
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            $customer->setDob($dob);
            $customer->setCustomAttribute('sfmcsync', 0);
            $this->customerRepository->save($customer);
            return true;
        }
        return false;
    }
}
