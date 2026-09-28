<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Controller\Adminhtml\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Customer extends \Magento\Backend\App\Action
{
	protected $resultPageFactory = false;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig
	)
	{
		parent::__construct($context);
		$this->request = $request;
        $this->scopeConfig = $scopeConfig;
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
       
		$resultPage        = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Sales Performance')));
        return $resultPage;
	}

    public function getConfiguration(){
        $sfmcStatus = $this->scopeConfig->getValue('sfmc/module_status/status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $cronStatus = $this->scopeConfig->getValue('sfmc/module/cron', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $cronAfterMessage = $this->scopeConfig->getValue('sfmc/module/cron_after_message', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $batchOf = $this->scopeConfig->getValue('sfmc/module/batch_of', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $lastUpdate = $this->scopeConfig->getValue('sfmc/module/last_update', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

    }

}
?>
