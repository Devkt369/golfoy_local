<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\DataProvider\InventoryTransfer\Form;

use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Delivery\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Class BranchRequest DeliveryHistory
 */
class DeliveryHistory extends AbstractDataProvider
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * ReceivingHistory construct
     *
     * @param mixed $name
     * @param mixed $primaryFieldName
     * @param mixed $requestFieldName
     * @param \Magento\Framework\App\RequestInterface $request
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\App\RequestInterface $request,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->collection = $collectionFactory->create();
        $this->prepareCollection();
    }

    /**
     * Prepare Collection
     *
     * @return void
     */
    public function prepareCollection()
    {
        $inventorytransfer_id = $this->request->getParam('inventorytransfer_id');
        if ($inventorytransfer_id) {
            $this->collection->addFieldToFilter("inventorytransfer_id", $inventorytransfer_id);
        }
    }
}
