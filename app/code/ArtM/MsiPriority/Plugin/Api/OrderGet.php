<?php

namespace ArtM\MsiPriority\Plugin\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemExtension;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use ArtM\MsiPriority\Plugin\OrderRepositoryPlugin;
use Mageplaza\RewardPoints\Model\Order;
use Mageplaza\RewardPoints\Model\OrderFactory;
use Mageplaza\RewardPoints\Model\OrderItem;
use Mageplaza\RewardPoints\Model\OrderItemFactory;

class OrderGet
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderItemFactory
     */
    protected $orderItemFactory;

    /** @var OrderExtensionFactory */
    protected $orderExtensionFactory;

    /** @var OrderItemExtensionFactory */
    protected $orderItemExtensionFactory;

    /**
     * Undocumented variable
     *
     * @var OrderRepositoryPlugin
     */
    protected $orderRepositoryPlugin;

    /**
     * OrderGet constructor.
     *
     * @param OrderFactory $orderFactory
     * @param OrderItemFactory $orderItemFactory
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param OrderItemExtensionFactory $orderItemExtensionFactory
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderItemFactory $orderItemFactory,
        OrderExtensionFactory $orderExtensionFactory,
        OrderItemExtensionFactory $orderItemExtensionFactory,
        OrderRepositoryPlugin $orderRepositoryPlugin
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
        $this->orderRepositoryPlugin = $orderRepositoryPlugin;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $resultOrder
     *
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $resultOrder
    ) {
        $resultOrder = $this->getOrderSource($resultOrder);
        $resultOrder = $this->getOrderItemStock($resultOrder);

        return $resultOrder;
    }

    /**
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    protected function getOrderSource(OrderInterface $order)
    {
        return $order;
    }

    /**
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    protected function getOrderItemStock(OrderInterface $order)
    {
        $orderItems = $order->getItems();
        if (null !== $orderItems) {
            /** @var OrderItemInterface $orderItem */
            foreach ($orderItems as $orderItem) {
                // if($orderItem->getProductType() == "bundle"){
                //     $orderItem->setSku($orderItem->getProduct()->getSku());
                // }
                $sourceCode = $this->orderRepositoryPlugin->getAvailableSourceCode($orderItem->getSku());
                $extensionAttributes = $orderItem->getExtensionAttributes();

                /** @var OrderItemExtension $orderItemExtension */
                $orderItemExtension = $extensionAttributes ? $extensionAttributes
                    : $this->orderItemExtensionFactory->create();

                $orderItemExtension = $orderItemExtension->setSourceCode($sourceCode);
                $orderItem->setExtensionAttributes($orderItemExtension);
            }
        }

        return $order;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param Collection $resultOrder
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        Collection $resultOrder
    ) {
        /** @var OrderInterface $order */
        foreach ($resultOrder->getItems() as $order) {
            $this->afterGet($subject, $order);
        }

        return $resultOrder;
    }

}
