<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\RecommendedSort\Model\GetProductData;
use ArtM\RecommendedSort\Model\SortingJobFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use ArtM\RecommendedSort\Helper\Data as HelperData;
use ArtM\RecommendedSort\Logger\SortingLogLogger;

class UpdateData extends Action
{
    protected $resultJsonFactory;
    protected $getProductData;
    protected $sortingJobFactory;
    protected $messageManager;
    protected $indexerCollectionFactory;
    protected $helperData;
    protected $sortingLogLogger;

    public function __construct(
        Context $context,
        GetProductData $getProductData,
        JsonFactory $resultJsonFactory,
        SortingJobFactory $sortingJobFactory,
        ManagerInterface $messageManager,
        CollectionFactory $indexerCollectionFactory,
        HelperData $helperData,
        SortingLogLogger $sortingLogLogger
    ) {
        parent::__construct($context);
        $this->getProductData = $getProductData;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sortingJobFactory = $sortingJobFactory;
        $this->messageManager = $messageManager;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->helperData = $helperData;
        $this->sortingLogLogger = $sortingLogLogger;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $categories = $this->getRequest()->getParam('cat_ids') ?? '';
        $filters = $this->getRequest()->getParam('filters') ?? [];
        if(!$filters && !$categories){
            return $result->setData(['error' => false,'message' => "No input found",]);
        }
        $sortingJobFactoryModel = $this->sortingJobFactory->create();

        date_default_timezone_set("Asia/Kolkata");  
        $currentDate = date('Y-m-d H:i:s' );

        $sortingJobFactoryModel->addData([
                                            "filters" => json_encode($filters),
                                            "categories" => $categories,
                                            "started_at" => $currentDate,
                                            "status" => "initiated",
                                            "error_message" => false,
                                            "output_log" => true
                                        ]);

        try {
            $sortingJobFactoryModel->save();

            $filters = $filters;
            $filters = array_map(function($item) {
                $item = str_replace('_', ' ', $item);
                return ucwords($item);
            }, $filters);

            $this->sortingLogLogger->info("batch id : " . $sortingJobFactoryModel->getId(). " created with filters ". implode(', ', $filters) );
            $this->helperData->runCmdInBackground($sortingJobFactoryModel->getId());

        } catch (\Exception $e) {
            return $result->setData(['error' => true,'message' => "Failed",]);
        }

        return $result->setData([
            'error' => false,
            'message' => "Success",
        ]);
    }
}
