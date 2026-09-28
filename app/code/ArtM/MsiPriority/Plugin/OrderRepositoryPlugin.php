<?php

namespace ArtM\MsiPriority\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;

class OrderRepositoryPlugin
{
    /**
     * OrderExtensionFactory
     *
     * @var OrderExtensionFactory
     */
    protected $orderExtensionFactory;

    /**
     * OrderExtensionFactory
     *
     * @var OrderExtensionInterface
     */
    protected $OrderExtensionInterface;
    /**
     * @var [type]
     */
    protected $itemExtensionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    protected SourceItemRepositoryInterface $sourceItems;

    /**
     * Undocumented function
     *
     * @param OrderItemExtensionFactory $itemExtensionFactory
     */

     /**
      * Add item source and pos
      *
      * @param OrderItemExtensionFactory $itemExtensionFactory
      * @param SourceItemRepositoryInterface $sourceItems
      * @param SearchCriteriaBuilder $searchCriteriaBuilder
      * @param OrderExtensionFactory $orderExtensionFactory
      */
    public function __construct(
        OrderItemExtensionFactory $itemExtensionFactory,
        SourceItemRepositoryInterface $sourceItems,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderExtensionFactory $orderExtensionFactory
    ) {
        $this->itemExtensionFactory = $itemExtensionFactory;
        $this->sourceItems = $sourceItems;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderExtensionFactory = $orderExtensionFactory;
    }

    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        foreach ($order->getItems() as $item) {
            $sourceCode = $this->getAvailableSourceCode($item->getSku());
            $extensionAttributes = $item->getExtensionAttributes();
            if (!$extensionAttributes) {
                $extensionAttributes = $this->itemExtensionFactory->create();
            }

            $extensionAttributes->setSourceCode($sourceCode);
            $item->setExtensionAttributes($extensionAttributes);
        }
        $orderExtensionAttributes = $order->getExtensionAttributes();
        $orderExtensionAttributes = $orderExtensionAttributes ? $orderExtensionAttributes : $this->orderExtensionFactory->create();
        if($order->getPosId() > 0){
            $orderExtensionAttribute = $orderExtensionAttributes->setPosOrder(1);
            $order->setExtensionAttributes($orderExtensionAttribute);
        }else{
            $orderExtensionAttribute = $orderExtensionAttributes->setPosOrder(0);
            $order->setExtensionAttributes($orderExtensionAttribute);
        }

        return $order;
    }

    public function getAvailableSourceCode($productSku){

        $sourceCode = [];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $productSku)
            ->create();

        $sourceItemData = $this->sourceItems->getList($searchCriteria);
        foreach ($sourceItemData->getItems() as $sourceItem){
            if($sourceItem['status'] != 0){
                $sourceCode[$sourceItem->getSourceCode()] = $sourceItem->getQuantity();
            }
        }
        if (!empty($sourceCode['G5'])) {
            return 'G5';
        } elseif (!empty($sourceCode['CGC'])) {
            return 'CGC';
        } else {
            arsort($sourceCode);
            foreach ($sourceCode as $code => $qty) {
                if ($qty > 0) {
                    return $code;
                }
            }
            return 0;
        }
    }
}
