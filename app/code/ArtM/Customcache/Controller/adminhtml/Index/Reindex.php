<?php

namespace ArtM\Customcache\Controller\Adminhtml\Index;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

class Reindex extends \Magento\Backend\App\Action
{
    protected $resourceConnection;
	protected $resultPageFactory = false;
    protected $indexerFactory;
    protected $indexerCollectionFactory;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
        IndexerFactory $indexerFactory,
        CollectionFactory $indexerCollectionFactory,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ResourceConnection $resourceConnection,
	)
	{
		parent::__construct($context);
        $this->indexerFactory = $indexerFactory;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
		$this->resultPageFactory = $resultPageFactory;
        $this->resourceConnection = $resourceConnection;
	}

	public function execute()
	{
        $indexerCode = 'webpos_search_product';
        $indexer = $this->indexerFactory->create();
        $indexer->load($indexerCode);
        $indexer->invalidate();
        $indexer->reindexAll();
        $this->messageManager->addSuccess(__('Pos indexer reindexed'));
        return $this->_redirect('indexer/indexer/list/');   
	}
}
?>
