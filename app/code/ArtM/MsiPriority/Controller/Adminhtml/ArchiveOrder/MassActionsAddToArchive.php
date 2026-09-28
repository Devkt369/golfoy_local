<?php

namespace ArtM\MsiPriority\Controller\Adminhtml\ArchiveOrder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use ArtM\MsiPriority\Model\ArchiveOrdersFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as PosOrderCollectionFactory;
use ArtM\MsiPriority\Ui\DataProvider\PosOrder\Listing\CollectionFactory as OrderCollectionFactory;


class MassActionsAddToArchive extends Action implements HttpPostActionInterface
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
     * @var ArchiveOrdersFactory
     */
    protected $archiveOrdersFactory;

    /**
     * @var PosOrderCollectionFactory
     */
    protected $posOrderCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;
    
    public function __construct(
        Context $context,
        Filter $filter,
        ResultFactory $resultFactory,
        ArchiveOrdersFactory $archiveOrdersFactory,
        OrderCollectionFactory $orderCollectionFactory,
        PosOrderCollectionFactory $posOrderCollectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->resultFactory = $resultFactory;
        $this->archiveOrdersFactory = $archiveOrdersFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->posOrderCollectionFactory = $posOrderCollectionFactory;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->posOrderCollectionFactory->create());
        
        foreach ($collection as $archiveOrder) {
            try {
                $orderData = [
                    'increment_id' => $archiveOrder->getIncrementId(),
                    'order_status' => $archiveOrder->getStatus(),
                    'create_date' => $archiveOrder->getCreatedAt(),
                    'order_id' => $archiveOrder->getEntityId(),
                ];
                $this->archiveOrdersFactory->create()->saveOrder($orderData);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error archiving order %1: %2', $archiveOrder->getIncrementId(), $e->getMessage()));
            }
            
        }
        $this->messageManager->addSuccessMessage(__('%1 Order(s) have been archived successfully.', $collection->getSize()));

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('msipriority/order/pending');
    }
}
