<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PurchaseOrderSuccess\Ui\DataProvider\PurchaseOrder\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magestore\PurchaseOrderSuccess\Model\ResourceModel\PurchaseOrder\CollectionFactory;

/**
 * Class PurchaseOrder
 * @package Magestore\PurchaseOrderSuccess\Ui\DataProvider\PurchaseOrder\Form
 */
class PurchaseOrder extends AbstractDataProvider
{
    /**
     * @var PoolInterface
     */
    protected $pool;
    protected $_coreRegistry;

    /**
     * Warehouse constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PoolInterface $pool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PoolInterface $pool,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $_coreRegistry,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool = $pool;
        $this->collection = $collectionFactory->create();
        $this->_coreRegistry = $_coreRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $purchaseOrder = $this->_coreRegistry->registry('current_purchase_order');
        if($purchaseOrder && $purchaseOrder->getId()){
            $this->data[$purchaseOrder->getId()] = $purchaseOrder->getData();
        }
        
        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->data = $modifier->modifyData($this->data);
        }

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

}