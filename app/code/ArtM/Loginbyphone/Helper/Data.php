<?php
namespace ArtM\Loginbyphone\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $customerRepository;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\AddressRepositoryInterface $customeraddressRepository
    )
    {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_storeManager = $storeManager;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customeraddressRepository = $customeraddressRepository;
    }
    
        
        
    public function CustomerExist( $id ){
        try {
            $customer = $this->_customerRepositoryInterface->getById($id);
            $customerstoreid = $customer->getWebsiteID();
            
            $storeId = $this->_storeManager->getStore()->getId();
            if($storeId == $customerstoreid){
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }  
        return false;
    }    
        
    public function getCustomeridwithaddress(string $phone, $prefix = ''){
        // Add customer attribute filter
        $phone = $prefix.$phone;
        $customerFilter[] = $this->filterBuilder
            ->setField('telephone')
            ->setConditionType('eq')
            ->setValue($phone)
            ->create();

        // Build search criteria
        $searchCriteriaBuilder = $this->searchCriteriaBuilder->addFilters($customerFilter);
        $searchCriteria = $searchCriteriaBuilder->create();

        // Retrieve customer collection.
        $collection = $this->customeraddressRepository->getList($searchCriteria);
//        echo "Count is: ".$collection->getTotalCount()."<br>";
        if ($collection->getTotalCount() > 0) {
            $accounts = $collection->getItems();
            foreach ($accounts as $item) {
//                echo $item->getId()."<br>";
                $curr_cust_id = $item->getCustomerId();

                if( $this->CustomerExist($curr_cust_id)){
//                    echo "customer found with this id:".$curr_cust_id."<br>";
                    return $curr_cust_id;
                }else{
//                    echo "customer not found with this id. curr_cust_id: ".$curr_cust_id." phone: ".$phone;
                }
            }
        }else{
//            echo "address collection not found";
            return false;
        }
        return false;
    }
}