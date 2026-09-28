<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\ClickAndCollectFrontend\Block\Checkout;

use Magento\Framework\View\Element\Template;

/**
 * Class TimePreparePickup
 *
 * Use for modify the layout
 */
class TimePreparePickup extends Template
{
    const XML_PATH_CLICK_AND_COLLECT_TIME_TO_PREPARE_PICKUP = 'click_and_collect/general/time_to_prepare_pickup';
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * TimePreparePickup constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }
    
    /**
     * GetTimePreparePickup
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getClickAndCollectTimePreparePickup()
    {
        $storeId = $this->storeManager->getStore()->getId();
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
        $timePreparePickup = $this->scopeConfig->getValue(
            self::XML_PATH_CLICK_AND_COLLECT_TIME_TO_PREPARE_PICKUP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        return $timePreparePickup;
    }
}
