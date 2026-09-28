<?php
/**
 * ArtM MsiPriority Module
 *
 * @category  ArtM
 * @package   ArtM_MsiPriority
 * @author    Dev
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace ArtM\MsiPriority\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\MsiPriority\Helper\Data;// as VinculumnInventoryHelper;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magento\Sales\Model\Order;
use ArtM\MsiPriority\Model\ArchiveOrdersFactory;

class SyncToVinculum extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    

    /**
     * @var Data
     */
    protected $vinculumInventoryHelper;

    /**
     * @var ArchiveOrdersFactory
     */
    protected $archiveOrdersFactory;


    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        Data $vinculumInventoryHelper,
        LocationRepositoryInterface $locationRepository,
        ArchiveOrdersFactory $archiveOrdersFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->vinculumInventoryHelper = $vinculumInventoryHelper;
        $this->locationRepository = $locationRepository;
        $this->archiveOrdersFactory = $archiveOrdersFactory;
    }

    /**
     * Execute method for controller
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getParam('entity_id');
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Order ID is required.'));

            if($this->getRequest()->getParam('from_archive')) {
                return $resultRedirectFactory->setPath('msipriority/archiveorder/index');
            }
           
            return $resultRedirectFactory->setPath('msipriority/order/pending');
        }

        if (!$this->vinculumInventoryHelper->isEnabled()) {
            $this->messageManager->addErrorMessage(__('Vinculum Inventory Sync is disabled.'));

            if($this->getRequest()->getParam('from_archive')) {
                return $resultRedirectFactory->setPath('msipriority/archiveorder/index');
            }
           
            return $resultRedirectFactory->setPath('msipriority/order/pending');
        }

        $order = $this->_objectManager->create(Order::class)->load($orderId);
        if (!$order->getId()) {
            $this->messageManager->addErrorMessage(__('Order not found.'));

            if($this->getRequest()->getParam('from_archive')) {
                return $resultRedirectFactory->setPath('msipriority/archiveorder/index');
            }
            
            return $resultRedirectFactory->setPath('msipriority/order/pending');
        }

        if($order->hasInvoices() && $order->hasShipments()){
           
            $orderLocationId = $order->getData('pos_location_id');
            if (!$orderLocationId) {
                $this->messageManager->addErrorMessage(__('Location ID is missing for the order.'));

                if($this->getRequest()->getParam('from_archive')) {
                    return $resultRedirectFactory->setPath('msipriority/archiveorder/index');
                }

                return $resultRedirectFactory->setPath('msipriority/order/pending');
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
                $inventoryList = [];
                foreach ($orderItems as $item) {
                    // echo count($item);
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
                    $inventoryList3[] = $inventoryList;
                    $this->vinculumInventoryHelper->callUpdateInventoryApi([$inventoryList], $posorderid);

                    if( $this->getRequest()->getParam('from_archive') ){
                        try {
                            $collection = $this->archiveOrdersFactory->create()
                                ->getCollection()
                                ->addFieldToFilter('order_id', $order->getId());
                            foreach ($collection as $archiveOrder) {
                                $archiveOrder->delete();
                            }

                        } catch (\Exception $e) {
                            $this->messageManager->addErrorMessage(
                                __("Error while deleting record(s) from archive.")
                            );
                        }
                    }
                }
            }
            $this->messageManager->addSuccessMessage(__('Order synced successfully.'));
        }else{
            $this->messageManager->addErrorMessage(__('Order is not eligible for sync.'));
        }
        
        // print_r($inventoryList3);die;
        if($this->getRequest()->getParam('from_archive')) {
            return $resultRedirectFactory->setPath('msipriority/archiveorder/index');
        }

        return $resultRedirectFactory->setPath('msipriority/order/pending');
    }
}