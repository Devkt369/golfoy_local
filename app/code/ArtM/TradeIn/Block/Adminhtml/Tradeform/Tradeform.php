<?php

namespace ArtM\TradeIn\Block\Adminhtml\Tradeform;

use Magento\Backend\Block\Template;
use ArtM\TradeIn\Model\TradeInFactory;

class Tradeform extends Template
{
    protected $tradeInFactory;

    public function __construct(
        Template\Context $context,
        TradeInFactory $tradeInFactory,
        array $data = []
    ) {
        $this->tradeInFactory = $tradeInFactory;
        parent::__construct($context, $data);
    }

    public function getTradeInData($page_firstpage='1', $result_per_page='20', $cust_id = '', $data= '')
    {
        if( count($data) > 0 ){
            $searchEmail = $data['searchEmail'];
            $searchAdId = $data['searchAdId'];
        }else{
            
            $searchEmail = '';
            $searchAdId = '';
        }
        $collection = $this->tradeInFactory->create()->getCollection();
        if( $cust_id != ''){
            $collection->addFieldToFilter('cust_id', $cust_id);
        }
        if( $searchEmail != ''){
//            $collection->addFieldToFilter('email', $searchEmail);
            $collection->addFieldToFilter('email', ['like' => '%' . $searchEmail . '%']);
        }
        if( $searchAdId != ''){
            $collection->addFieldToFilter('ad_id', ['like' => '%' . $searchAdId . '%']);
//            $collection->addFieldToFilter('ad_id', $searchAdId);
            
        }
        if(isset($result_per_page)){
//            $currentPage = $this->getRequest()->getParam('currpage') ?: 1;
//            $offset = ($currentPage - 1) * $result_per_page;
            
            $collection->setPageSize($result_per_page)
                ->setCurPage($page_firstpage)
                ->setOrder('tradein_id', 'DESC');
        }
       
        return $collection;
    }

    public function getTradeIncount(){

    }
}
