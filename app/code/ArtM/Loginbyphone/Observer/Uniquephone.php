<?php
namespace ArtM\Loginbyphone\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\AlreadyExistsException;

class Uniquephone implements ObserverInterface
{

    protected $_customerCollectionFactory;
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->_customerCollectionFactory = $customerCollectionFactory;
    }


    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {   
        $customer = $observer->getCustomer();
        $customerCollection= $this->_customerCollectionFactory->create();
        $customerCollection->addAttributeToSelect('*');
        $customerCollection->addAttribuTeToFilter('phone_number',$customer->getData('phone_number'));
        
        if ($customer->getId()) {   
            $customerCollection->addAttribuTeToFilter('entity_id',array('neq' => (int)$customer->getId()));   
        }  
        if($customer->isObjectNew() ){ 
         if($customerCollection->getSize()>0){         
             throw new \Magento\Framework\Exception\LocalizedException(__('A customer with the same Phone Number already exists.'));
         }
        }

    }

}