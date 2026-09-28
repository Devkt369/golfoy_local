<?php

namespace ArtM\ReservedProducts\Controller\Adminhtml\Management;

use ArtM\ReservedProducts\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use ArtM\ReservedProducts\Helper\Data;

class Delete extends Action
{
    protected $logger;
    protected $resourceConnection;
    protected $messageManager;
    protected $orderRepository;
    protected $helper;    

    public function __construct(
        Context $context,
        Logger $logger,
        Data $helper,
        ResourceConnection $resourceConnection,
        ManagerInterface $messageManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository

    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->resourceConnection = $resourceConnection;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('reservation_id');
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('inventory_reservation');
            $select = $connection->select()->from($tableName)->where('reservation_id = ?', $id);
            $recordData = $connection->fetchRow($select);
            $data = json_decode($recordData['metadata']);
            $incrementId = $data->object_increment_id;
            $successResponse = $this->helper->getOrderDetailsByIncrementId($incrementId);
            $this->logger->info("This order id has cancelled ".$incrementId);
            if ($successResponse) {
                $this->logger->info("Order has cancelled ".$incrementId);
                $this->messageManager->addSuccessMessage(__("This ID has change  $id deleted successfully. Order id is ".$incrementId));
            }else{
                $this->messageManager->addErrorMessage(__("Something went wrong ".$incrementId));
                $this->logger->info("Please Check Order id not reserved".$incrementId);
            }

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error("Error deleting record with ID $id: " . $e->getMessage());
            $this->messageManager->addErrorMessage(__("Error deleting record. Record not found."));
        } catch (\Exception $e) {
            $this->logger->error("Error deleting record with ID $id: " . $e->getMessage());
            $this->messageManager->addErrorMessage(__("Error deleting record. Please check the logs."));
        }
        return $this->_redirect('*/*/index');
    }
}
