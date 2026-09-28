<?php
/**
 * Copyright © 2024 ArtM. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ArtM\QueryChatBox\Block\ChatBox;

use ArtM\QueryChatBox\Model\QueryChatBoxFactory;
/**
 * Class ChatBox
 * @package ArtM\QueryChatBox\Block
 */
class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * query model
     *
     * @var QueryChatBoxFactory
     */
    protected $queryChatBoxFactory;

    /**
     * ChatBox constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        QueryChatBoxFactory $queryChatBoxFactory,
    ) {
        $this->_queryChatBoxFactory = $queryChatBoxFactory;
        parent::__construct($context);
    }

    public function getQueryList($cEmail){
        $queryChatBox = $this->_queryChatBoxFactory->create();
        $collection = $queryChatBox->getCollection()
        ->addFieldToFilter('customer_email', ['eq' => $cEmail]);
        return $collection;        
    }
}