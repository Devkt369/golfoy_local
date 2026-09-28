<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PurchaseOrderSuccess\Model\PurchaseOrder\Option;

use Magestore\PurchaseOrderSuccess\Model\Option\AbstractOption;
use Magestore\SupplierSuccess\Api\Data\SupplierInterface;
use Magestore\SupplierSuccess\Model\ResourceModel\Supplier\Collection;
use Magestore\SupplierSuccess\Model\ResourceModel\Supplier\CollectionFactory;
use Magestore\SupplierSuccess\Service\SupplierService;

/**
 * Class SupplierEnable
 *
 * Used to get supplier source
 */
class SupplierEnable extends AbstractOption
{
    /**
     * @var CollectionFactory
     */
    protected $supplierCollectionFactory;

    /**
     * Supplier constructor.
     *
     * @param CollectionFactory $supplierCollectionFactory
     */
    public function __construct(
        CollectionFactory $supplierCollectionFactory
    ) {
        $this->supplierCollectionFactory = $supplierCollectionFactory;
    }

    /**
     * Get supplier options for source
     *
     * @return array
     */
    public function getSupplierOptions()
    {
        $collection = $this->supplierCollectionFactory->create()
            ->addFieldToSelect(SupplierInterface::SUPPLIER_CODE)
            ->addFieldToSelect(SupplierInterface::SUPPLIER_NAME)
            ->addFieldToSelect('supplier_id')
            ->addFieldToFilter(
                SupplierInterface::STATUS,
                SupplierService::STATUS_ENABLE
            )
            ->addOrder(SupplierInterface::SUPPLIER_NAME, Collection::SORT_ORDER_ASC);
        $options = [' ' => __('Please select a supplier')];
        foreach ($collection->getItems() as $supplier) {
            $options[$supplier->getId()] = $supplier->getSupplierName();
        }
        return $options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionHash()
    {
        return $this->getSupplierOptions();
    }
}
