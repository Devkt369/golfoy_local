<?php

namespace ArtM\Dob\Block\Adminhtml\Customerlist;

use Magento\Backend\Block\Template;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class CustomerList extends Template
{
    protected $customerCollectionFactory;

    public function __construct(
        Template\Context $context,
        CollectionFactory $customerCollectionFactory,
        array $data = []
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getCustomerData()
    {
        
//        $currentDate = new DateTime();
        $currentDate = date("Y-m-d");
//        $dateRangeStart = $currentDate->modify('-5 days')->format('Y-m-d');
//        $dateRangeEnd = $currentDate->modify('+25 days')->format('Y-m-d');
        
        $dateRangeStart = date("Y-m-d", strtotime('-5 days', strtotime($currentDate)));
        $dateRangeEnd = date("Y-m-d", strtotime('+20 days', strtotime($currentDate)));

         $customerCollection = $this->customerCollectionFactory->create();
        /*->addFieldToFilter('dob', ['lteq' => $dateRangeStart ])
        ->addFieldToFilter('dob', ['gteq' => $dateRangeEnd ]); */
        $customerCollection->addFieldToFilter(
            'dob',
            [
                'lteq' => $dateRangeStart,
                'gte' => $dateRangeEnd
            ]
        );
//        $customerCollection->addFieldToFilter('dob', ['from' => $dateRangeStart, 'to' => $dateRangeEnd]);

        /*
        $startDate = date("Y-m-d H:i:s"); // start date (today's date)
        $dateBefore5Days = date("Y-m-d", strtotime('-5 days', strtotime($startDate)));
        $dateAfter20Days = date("Y-m-d", strtotime('+20 days', strtotime($startDate)));

//echo "Start Date: " . $startDate . "<br>";
//echo "Date before 5 days: " . $dateBefore5Days . "<br>";
//echo "Date after 20 days: " . $dateAfter20Days . "<br>";

        
        $currentMonth = date('m');
        $prevMonth = date('m', strtotime('-1 month'));
        $nextMonth = date('m', strtotime('+1 month'));
    
        $customerCollection = $this->customerCollectionFactory->create();
//        $customerCollection->addAttributeToSelect(['name', 'dob']);
        $customerCollection->addAttributeToFilter('dob', array('from'=>$dateBefore5Days, 'to'=>$dateAfter20Days));
//    
//        $customerCollection->getSelect()
//            ->where("DATE_FORMAT(dob, '%m') = ?", $currentMonth)
//            ->orWhere("DATE_FORMAT(dob, '%m') = ?", $nextMonth)
//            ->orWhere("DATE_FORMAT(dob, '%m') = ?", $prevMonth);
//            
//            
//        $customerCollection->getSelect()->order("dob DESC");
           
    */
        return $customerCollection;
    }
    
    
}
