<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\RecommendedSort\Model\GetProductData;
use ArtM\RecommendedSort\Model\SortingJobFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Magento\Framework\View\Result\PageFactory;
use ArtM\RecommendedSort\Logger\Logger;
use ArtM\RecommendedSort\Helper\Data as SortHelper;

class Initiate extends Action
{
    protected $resultJsonFactory;
    protected $getProductData;
    protected $sortingJobFactory;
    protected $messageManager;
    protected $indexerFactory;
    protected $indexerCollectionFactory;
    protected $resultPageFactory;
    protected $logger;
    protected $sortHelper;

    public function __construct(
        Context $context,
        GetProductData $getProductData,
        JsonFactory $resultJsonFactory,
        SortingJobFactory $sortingJobFactory,
        ManagerInterface $messageManager,
        IndexerFactory $indexerFactory,
        CollectionFactory $indexerCollectionFactory,
        PageFactory $resultPageFactory,
        Logger $logger,
        SortHelper $sortHelper
    ) {
        parent::__construct($context);
        $this->getProductData = $getProductData;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sortingJobFactory = $sortingJobFactory;
        $this->messageManager = $messageManager;
        $this->indexerFactory = $indexerFactory;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->sortHelper = $sortHelper;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultJson = $this->resultJsonFactory->create();

        $id = $this->getRequest()->getParam('id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('ID parameter is missing.'));
            return $resultRedirect->setPath('artm_recommendedsort/recommendsortListing/processlisting');
        }

        // $sortingJobModel = $this->sortingJobFactory->create()->load($id);

        // if (!$sortingJobModel || !$sortingJobModel->getId()) {
        //     $this->messageManager->addErrorMessage(__('Sorting Job not found.'));
        //     return $resultRedirect->setPath('artm_recommendedsort/recommendsortListing/processlisting');
        // }
        
        $this->sortHelper->runCmdInBackground($id);

        // $sortingJobModel->setData('status', 'completed');
        // $sortingJobModel->save();

        $this->messageManager->addSuccessMessage(__("Initiated Successfully For => " . $id));

        return $resultRedirect->setPath('artm_recommendedsort/recommendsortListing/processlisting');
    }

}