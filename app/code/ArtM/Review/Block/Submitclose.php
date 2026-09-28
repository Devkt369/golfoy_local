<?php
namespace ArtM\Review\Block;
class Submitclose extends \Magento\Framework\View\Element\Template
{
    /*
    public function __construct(    
        \Magento\Framework\View\Element\Template\Context $context,
        \ArtM\Review\Helper\Data $helper,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ){
        $this->_reviewFactory = $reviewFactory;
        $this->_ratingFactory = $ratingFactory;
        $this->_storeManager = $storeManager;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }*/
    
    public function submit_review(){
        return true;
    }
}