<?php
/**
 * ArtM MsiPriority Module
 *
 * @category  ArtM
 * @package   ArtM_MsiPriority
 * @author    Dev
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace ArtM\MsiPriority\Controller\Adminhtml\ArchiveOrder;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\MsiPriority\Model\ArchiveOrdersFactory;

class AddToArchive extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var ArchiveOrdersFactory
     */
    protected $archiveOrdersFactory;

    /**
     * AddToArchive constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ArchiveOrdersFactory $archiveOrdersFactory
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ArchiveOrdersFactory $archiveOrdersFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
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
        $increment_id = $this->getRequest()->getParam('increment_id');
        $status = $this->getRequest()->getParam('status');
        $created_at = $this->getRequest()->getParam('created_at');
        $entity_id = $this->getRequest()->getParam('entity_id');
        // if (!$increment_id) {
        //     $this->messageManager->addErrorMessage(__('Order ID is required.'));
        //     return $resultRedirectFactory->setPath('msipriority/order/pending');
        // }
        $archiveOrder = $this->archiveOrdersFactory->create();
        // save order to archive
        try {
            $orderData = [
                'increment_id' => $increment_id,
                'order_status' => $status,
                'create_date' => $created_at,
                'order_id' => $entity_id,
            ];

            $archiveOrder->saveOrder($orderData);
            $this->messageManager->addSuccessMessage(__("Order %1 has been archived successfully.", $increment_id));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error loading order: %1', $e->getMessage()));
            return $resultRedirectFactory->setPath('msipriority/order/pending');
        }

        return $resultRedirectFactory->setPath('msipriority/order/pending');
    }
}