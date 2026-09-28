<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\SupplierSuccess\Block\Adminhtml\Supplier\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magestore\SupplierSuccess\Block\Adminhtml\Supplier\Edit\Tab\Product\Delete;
use Magestore\SupplierSuccess\Block\Adminhtml\Supplier\Grid\Column\Renderer\Text;
use Magestore\SupplierSuccess\Model\Locator\LocatorInterface;
use Magestore\SupplierSuccess\Model\ResourceModel\Supplier\Product\Collection as SupplierProductCollection;
use Zend_Db_Expr;
use Magestore\SupplierSuccess\Model\ResourceModel\Supplier\Product\CollectionFactory
    as SupplierProductCollectionFactory;
use Magento\Backend\Helper\Data as BackendHelper;
use Magestore\SupplierSuccess\Api\Data\SupplierProductInterface;

/**
 * Product list tab block
 */
class Product extends Extended
{
    protected $_hiddenInputField = 'supplier_products';
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var SupplierProductCollectionFactory
     */
    protected $_supplierProductCollectionFactory;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var null
     */
    protected $newProductIds = null;

    /**
     * @param Context $context
     * @param BackendHelper $backendHelper
     * @param SupplierProductCollectionFactory $supplierProductCollectionFactory
     * @param Registry $coreRegistry
     * @param EncoderInterface $jsonEncoder
     * @param LocatorInterface $locator
     * @param array $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        SupplierProductCollectionFactory $supplierProductCollectionFactory,
        Registry $coreRegistry,
        EncoderInterface $jsonEncoder,
        LocatorInterface $locator,
        array $data = []
    ) {
        $this->_supplierProductCollectionFactory = $supplierProductCollectionFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->jsonEncoder = $jsonEncoder;
        $this->locator = $locator;
        if ($this->locator->getSession(SupplierProductInterface::SUPPLIER_PRODUCT_ADD_NEW)) {
            $this->newProductIds = $this->locator->getSession(SupplierProductInterface::SUPPLIER_PRODUCT_ADD_NEW);
            $this->locator->unsetSession(SupplierProductInterface::SUPPLIER_PRODUCT_ADD_NEW);
        }
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('os_supplier_product_listing');
        $this->setDefaultSort('product_id');
        $this->setUseAjax(true);
    }

    /**
     * Get Category
     *
     * @return array|null
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('category');
    }

    /**
     * @inheritDoc
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filters for in category flag
        if ($column->getId() == 'in_supplier') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('product_id', ['in' => $productIds]);
            } elseif (!empty($productIds)) {
                $this->getCollection()->addFieldToFilter('product_id', ['nin' => $productIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _prepareCollection()
    {
        $supplierId = $this->getRequest()->getParam('id', null);
        /** @var SupplierProductCollection $collection */
        $collection = $this->_supplierProductCollectionFactory->create()
            ->addFieldToFilter('supplier_id', $supplierId);

        if ($this->newProductIds) {
            $collection->getSelect()->order(
                new Zend_Db_Expr('FIELD(product_id, "' . implode('","', $this->newProductIds) . '") DESC')
            );
        }
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @inheritDoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_supplier',
            [
                'type' => 'checkbox',
                'name' => 'in_supplier',
                'values' => $this->_getSelectedProducts(),
                'index' => 'product_id',
                'header_css_class' => 'col-select col-massaction',
                'filter' => false,
                'column_css_class' => 'col-select col-massaction'
            ]
        );
        $this->addColumn(
            'product_sku',
            [
                'header' => __('SKU'),
                'index' => 'product_sku'
            ]
        );
        $this->addColumn(
            'product_name',
            [
                'header' => __('Product Name'),
                'index' => 'product_name'
            ]
        );

        $this->addColumn(
            'product_supplier_sku',
            [
                'header' => __('Supplier SKU'),
                'index' => 'product_supplier_sku',
                'type' => 'text',
                'renderer' => Text::class,
                'editable' => true
            ]
        );
        $this->addColumn(
            'cost',
            [
                'header' => __('Cost'),
                'index' => 'cost',
                'renderer' => Text::class,
                'editable' => true
            ]
        );
        $this->addColumn(
            'tax',
            [
                'header' => __('Tax'),
                'index' => 'tax',
                'renderer' => Text::class,
                'editable' => true
            ]
        );

        $this->addColumn(
            'delete',
            [
                'header' => __('Action'),
                'renderer' => Delete::class,
                'filter' => false,
                'sortable' => false,
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @inheritDoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('suppliersuccess/supplier_product/grid', ['_current' => true]);
    }

    /**
     * Get Selected Products
     *
     * @return array
     */
    public function _getSelectedProducts()
    {
        if ($this->newProductIds) {
            return $this->newProductIds;
        }
        return null;
    }

    /**
     * Get hidden input field name for selected products
     *
     * @return string
     */
    public function getHiddenInputField()
    {
        return $this->_hiddenInputField;
    }

    /**
     * Get Editable Fields
     *
     * @return false|string
     */
    public function getEditableFields()
    {
        $fields = [
            ['cost', 'number'],
            ['tax', 'number'],
            ['product_supplier_sku', 'text']
        ];
        return json_encode($fields);
        //return $this->jsonEncoder->encode($fields);
    }

    /**
     * Get Delete Url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('suppliersuccess/supplier_product/deleterow');
    }

    /**
     * Get Update Url
     *
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->getUrl('suppliersuccess/supplier/updateproduct');
    }
}
