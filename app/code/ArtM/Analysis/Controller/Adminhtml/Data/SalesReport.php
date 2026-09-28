<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\Analysis\Controller\Adminhtml\Data;

class SalesReport extends \Magento\Backend\App\Action
{
	protected $resultPageFactory = false;
	protected $request;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->request = $request;
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Sales Report')));
		$request = $this->getRequest();
		$arr = [];
		if ($request->isPost()) {
            $formData = $request->getPostValue();
            if (isset($formData['startDate']) && isset($formData['endDate'])) {

                $startDate = $formData['startDate'];
                $endDate = $formData['endDate'];

				$arr['0'] = $formData['startDate'];
				$arr['1'] = $formData['endDate'];
				
				$block = $resultPage->getLayout()->getBlock('analysis.sales.report');
				$block->setData('formdata', $arr); 
            } else {
                echo "Please enter both start and end dates.";
            }
        }        
		return $resultPage;
	}
}