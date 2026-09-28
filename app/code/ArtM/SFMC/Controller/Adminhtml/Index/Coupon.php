<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Controller\Adminhtml\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use ArtM\SFMC\Helper\Data;

class Coupon extends \Magento\Backend\App\Action
{
	protected $resultPageFactory = false;
	protected $SfmcCouponSync;
	protected $data;
	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
		Data $data
	)
	{
		parent::__construct($context);
		$this->request = $request;
        $this->scopeConfig = $scopeConfig;
		$this->resultPageFactory = $resultPageFactory;
		$this->_data = $data;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Coupon Status')));
		$postData = $this->getRequest()->getPostValue();
		if ($postData && isset($postData['coupon-true']) && isset($postData['coupon-false'])) {
			$couponTrue = explode('||' , $postData['coupon-true']);
			$couponFalse = explode('||' , $postData['coupon-false']);
			if($this->_data->syncCouponList($couponTrue , $couponFalse)){
				$this->messageManager->addSuccess(__("The coupon statuses have been successfully updated in Salesforce Marketing Cloud"));
			}else{
				$this->messageManager->addError(__("There was an error syncing the coupon status to Salesforce Please check sfmc.log file"));
			}
			
		}
        return $resultPage;
	}
}
?>
