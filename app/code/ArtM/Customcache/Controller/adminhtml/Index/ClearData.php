<?php

namespace ArtM\Customcache\Controller\Adminhtml\Index;

use Magento\Framework\App\ResourceConnection;
use ArtM\Common\Helper\Data;

class ClearData extends \Magento\Backend\App\Action
{
    protected $resourceConnection;
    protected $helperData;
	protected $resultPageFactory = false;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ResourceConnection $resourceConnection,
        Data $helperData
	)
	{
		parent::__construct($context);
        $this->helperData = $helperData;
		$this->resultPageFactory = $resultPageFactory;
        $this->resourceConnection = $resourceConnection;
	}

	public function execute()
	{
            
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Cache Clear')));

        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('artm_custom_cache');
        $sql = "TRUNCATE TABLE " . $tableName;

        // $tableName2 = $connection->getTableName('artm_product_cache');
        // $sql2 = "TRUNCATE TABLE " . $tableName2;

        try {
            $connection->query($sql);
            // $connection->query($sql2);
            $this->helperData->getCategoryById();
            $this->messageManager->addSuccess(__('Your Menu Cache has Refreshed'));
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }

        return $this->_redirect('adminhtml/cache/index/');
        
	}
}
?>
