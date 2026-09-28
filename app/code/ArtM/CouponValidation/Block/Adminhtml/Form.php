<?php

namespace ArtM\CouponValidation\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class Form extends Template
{
    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        RuleCollectionFactory $ruleCollectionFactory,
        array $data = []
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get active sales rules
     *
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    public function getRules()
    {
        return $this->ruleCollectionFactory
            ->create()
            ->addFieldToFilter('is_active', 1)
            ->setOrder('name', 'ASC');
    }

    /**
     * Form submit URL
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('artm_couponvalidation/index/save');
    }
}