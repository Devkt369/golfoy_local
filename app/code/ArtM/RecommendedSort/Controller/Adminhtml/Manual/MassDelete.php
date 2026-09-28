<?php
/**
 * ArtM Recommended Sort Extension
 *
 * @package ArtM\RecommendedSort
 * */

namespace ArtM\RecommendedSort\Controller\Adminhtml\Manual;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\RecommendedSort\Logger\Logger;
use ArtM\RecommendedSort\Model\SortingCategoryProductsFactory;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Ui\Component\MassAction\Filter;
use ArtM\RecommendedSort\Logger\SortingLogLogger;

class MassDelete extends Action implements HttpPostActionInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var SortingCategoryProductsFactory
     */
    protected $sortingCategoryProductsFactory;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var ResultFactory
     */
    protected $resultRedirect;

    /**
     * @var Redirect
     */
    protected $resultRedirectFactory;
    
    /**
     * MassDelete constructor.
     *
     * @param Context $context
     * @param Logger $logger
     * @param SortingCategoryProductsFactory $sortingCategoryProductsFactory
     * @param LocationRepositoryInterface $locationRepository
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        Logger $logger,
        SortingCategoryProductsFactory $sortingCategoryProductsFactory,
        LocationRepositoryInterface $locationRepository,
        Filter $filter,
        SortingLogLogger $sortingLogLogger
    ) {
        $this->logger = $logger;
        $this->sortingCategoryProductsFactory = $sortingCategoryProductsFactory;
        $this->locationRepository = $locationRepository;
        $this->filter = $filter;
        $this->sortingLogLogger = $sortingLogLogger;
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->sortingCategoryProductsFactory->create()->getCollection());
        if (!$collection->getSize()) {
            $this->messageManager->addErrorMessage(__('No items selected.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('artm_recommendedsort/manual/index');
        }
        try {
            foreach ($collection as $item) {
                $item->delete();
                $this->sortingLogLogger->info("manual Points row deleted for product id ".$item->getProductId()." and category id ".$item->getCategoryId());
            }
            $this->messageManager->addSuccessMessage(__('Selected products have been deleted.'));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the products.'));
        }
        
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('artm_recommendedsort/manual/index');
    }
}
