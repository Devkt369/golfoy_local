<?php

namespace ArtM\MsiPriority\Controller\Adminhtml\ArchiveOrder;

use Magento\Backend\App\Action;
use ArtM\MsiPriority\Helper\Data;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Ui\Component\MassAction\Filter;
use ArtM\MsiPriority\Model\ArchiveOrdersFactory;
use ArtM\MsiPriority\Model\ResourceModel\ArchiveOrders\CollectionFactory;

class MassActionsSync extends Action implements HttpPostActionInterface
{
    /**
     * @var Data
     */
    protected $vinculumInventoryHelper;

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
    protected $resultFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ArchiveOrdersFactory
     */
    protected $archiveOrdersFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * MassActions constructor.
     *
     * @param Context $context
     * @param Data $vinculumInventoryHelper
     * @param LocationRepositoryInterface $locationRepository
     * @param Filter $filter
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        Context $context,
        Data $vinculumInventoryHelper,
        LocationRepositoryInterface $locationRepository,
        Filter $filter,
        ResultFactory $resultFactory,
        OrderCollectionFactory $orderCollectionFactory,
        ArchiveOrdersFactory $archiveOrdersFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->vinculumInventoryHelper = $vinculumInventoryHelper;
        $this->locationRepository = $locationRepository;
        $this->filter = $filter;
        $this->resultFactory = $resultFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->archiveOrdersFactory = $archiveOrdersFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!$this->vinculumInventoryHelper->isEnabled()) {
            $this->messageManager->addErrorMessage(__('Vinculum Inventory Sync is disabled.'));

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setPath('msipriority/archiveorder/index');

        }

        $collection = $this->filter->getCollection($this->collectionFactory->create());

        foreach ($collection as $orderArchive) {
            try {

                if($orderArchive['order_id'] !== null){

                    $order = $this->orderCollectionFactory->create()
                        ->addFieldToFilter('entity_id', $orderArchive['order_id'])
                        ->getFirstItem();

                    if ($order) {
                    //    /////////////////////////
                        $orderLocationId = $order->getData('pos_location_id');
                        if (!$orderLocationId) {
                            $this->messageManager->addErrorMessage(__('Location ID is missing for the order.'));

                            if (isset($params['namespace']) && $params['namespace'] === 'msipriority_archive_order_grid') {
                                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                                ->setPath('msipriority/archiveorder/index');
                            }
                            
                            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('msipriority/order/pending');
                        }
                        $locationModel = $this->locationRepository->getById($orderLocationId);

                        $location = "";
                        if($locationModel['name'] == "Chandimandir Golf Course"){
                            $location = "SPT";
                        }elseif($locationModel['name'] == "CGC"){
                            $location = "CGC";
                        }else{
                            $location = "W01";
                        }

                        if($order->getData('pos_id') > 0){
                            $orderItems = $order->getAllItems();
                            $remarks = "pos order";

                            foreach ($orderItems as $item) {
                                if ($item['product_type'] === 'simple') {
                                    $inventoryList = [
                                        "skuCode"     => $item['sku'],
                                        "qty"         => (float)$item['qty_ordered'],
                                        "bin"         => "Saleable",
                                        "lottable_01" => "",
                                        "lottable_02" => "",
                                        "lottable_03" => "",
                                        "lottable_04" => "",
                                        "lottable_05" => "",
                                        "lottable_06" => "",
                                        "reason"      => "MISC",
                                        "mode"        => "Decrease",
                                        "location"    => $location,
                                        "operationOn" => "Free Qty",
                                        "remarks"     => $remarks
                                    ];
                                }
                                $posorderid = $order->getIncrementId();
                                
                                $this->vinculumInventoryHelper->callUpdateInventoryApi([$inventoryList], $posorderid);
                                $archiveOrdersModel = $this->archiveOrdersFactory->create()->load($orderArchive['id']);
                                try{
                                    if ($archiveOrdersModel->getOrderId()) {
                                        $archiveOrdersModel->delete();
                                    }
                                    } catch (\Exception $e) {
                                        $this->messageManager->addErrorMessage(__("Error while deleting record from archive."));
                                    }
                                }

                        }
                    //    /////////////////////////
                    }
                }else {
                    $this->messageManager->addErrorMessage(__('Order cannot be synced as it does not have invoices or shipments.'));
                }
                
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Failed to sync order: %1', $e->getMessage()));
            }
        }
        $this->messageManager->addSuccessMessage(__('%1 Order synced successfully.', $collection->getSize()));
        
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('msipriority/archiveorder/index');
    }
}
