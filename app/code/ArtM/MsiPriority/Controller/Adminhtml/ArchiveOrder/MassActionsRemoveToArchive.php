<?php

namespace ArtM\MsiPriority\Controller\Adminhtml\ArchiveOrder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use ArtM\MsiPriority\Model\ArchiveOrdersFactory;
use ArtM\MsiPriority\Model\ResourceModel\ArchiveOrders\CollectionFactory;

class MassActionsRemoveToArchive extends Action implements HttpPostActionInterface
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ArchiveOrdersFactory
     */
    protected $archiveOrdersFactory;

    /**
     * MassActionsRemoveToArchive constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param ResultFactory $resultFactory
     * @param ArchiveOrdersFactory $archiveOrdersFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        ResultFactory $resultFactory,
        ArchiveOrdersFactory $archiveOrdersFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->resultFactory = $resultFactory;
        $this->archiveOrdersFactory = $archiveOrdersFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;

            foreach ($collection as $item) {
                $item->delete();
                $count++;
            }

            $this->messageManager->addSuccessMessage(__('%1 record(s) deleted.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error deleting records: %1', $e->getMessage()));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('msipriority/archiveorder/index');
    }
}
