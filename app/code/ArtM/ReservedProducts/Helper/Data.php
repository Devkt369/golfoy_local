<?php
namespace ArtM\ReservedProducts\Helper;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use ArtM\ReservedProducts\Logger\Logger;

class Data extends AbstractHelper
{
    protected $orderRepository;    

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        ResourceConnection $resourceConnection,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    public function getOrderDetailsByIncrementId($incrementId)
    {
        try {
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            $order->setState('new');
            $order->save();
            $orderId = $order->getEntityId();
            $this->orderManagement->cancel($orderId);
            return true;

        } catch (NoSuchEntityException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

}
