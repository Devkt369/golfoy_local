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
use ArtM\RecommendedSort\Helper\Data as HelperData;

class InitiateAll extends Action
{
    protected $resultJsonFactory;
    protected $getProductData;
    protected $sortingJobFactory;
    protected $messageManager;
    protected $indexerFactory;
    protected $indexerCollectionFactory;
    protected $resultPageFactory;
    protected $logger;
    protected $helperData;

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
        HelperData $helperData
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
        $this->helperData = $helperData;
    }

    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();

        $result = $this->resultJsonFactory->create();
        // $id = $this->getRequest()->getParam('id');
        // $categories = $this->getRequest()->getParam('cat_ids');
        // $filters = $this->getRequest()->getParam('filters');
        // $sortingJobFactoryModel = $this->sortingJobFactory->create();

        // date_default_timezone_set("Asia/Kolkata");  
        // $currentDate = date('Y-m-d H:i:s' );

        // $sortingJobFactoryModel->addData([
        //                                     "filters" => json_encode($filters),
        //                                     "categories" => $categories,
        //                                     "started_at" => $currentDate,
        //                                     "status" => "initiated",
        //                                     "error_message" => false,
        //                                     "output_log" => true
        //                                 ]);
        // $sortingJobFactoryModel->save();

        try {
            $this->helperData->runIndexerInBackground('recommended_sort_all');
        } catch (\Exception $e) {
            return $result->setData(['error' => true,'message' => "Failed",]);
        }
        $this->messageManager->addSuccessMessage(__("All Initiated Successfully "));
        return $resultRedirectFactory->setPath('artm_recommendedsort/recommendsortListing/processlisting');
    }
}