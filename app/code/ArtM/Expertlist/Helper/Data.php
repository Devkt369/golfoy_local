<?php

namespace ArtM\Expertlist\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_getexpert;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \ArtM\Expertlist\Model\ExpertModelFactory $getexpert
    ) {
        $this->_getexpert = $getexpert;
    }

    public function getRow($expertName)
    {
        $getexpertModel = $this->_getexpert->create();
        $getexpertCollection = $getexpertModel->getCollection()->addFieldToFilter('expert_name', $expertName);

        $data = [];
        foreach ($getexpertCollection as $item) {
            $data[] = $item->getData();
        }

        return $data;
    }
}
