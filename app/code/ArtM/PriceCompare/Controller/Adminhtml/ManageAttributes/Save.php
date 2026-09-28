<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\ManageAttributes;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResourceConnection;

class Save extends Action
{
    protected $resultRedirectFactory;
    protected $dataPersistor;
    protected $resourceConnection;

    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        DataPersistorInterface $dataPersistor,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->dataPersistor = $dataPersistor;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pricecompare_attribute_selector');
            $connection->insert($table, [
                'website_id' => $data['website_id'],
                'attribute_name' => $data['attribute_name'],
                'selector' => $data['selector'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $this->messageManager->addSuccessMessage(__('Attribute selector saved.'));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }
}