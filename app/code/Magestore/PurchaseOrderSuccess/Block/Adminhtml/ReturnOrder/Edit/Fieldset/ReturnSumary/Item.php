<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PurchaseOrderSuccess\Block\Adminhtml\ReturnOrder\Edit\Fieldset\ReturnSumary;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\ReturnOrderInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\ReturnOrderItemInterface;
use Magestore\PurchaseOrderSuccess\Api\ReturnOrderRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Block\Adminhtml\Grid\Column\Renderer\Text;
use Magestore\PurchaseOrderSuccess\Block\Adminhtml\ReturnOrder\Edit\Fieldset\ReturnSumary\Item\Delete;
use Magestore\PurchaseOrderSuccess\Model\ResourceModel\ReturnOrder\Item\CollectionFactory;
use Magestore\PurchaseOrderSuccess\Model\ResourceModel\ReturnOrder\Item\Collection;
use Zend_Db_Expr;

/**
 * Return Summary - Item
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Item extends Extended
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ReturnOrderRepositoryInterface
     */
    protected $returnOrderRepositoryInterface;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var Data
     */
    protected $backendHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var string
     */
    protected $hiddenInputField = 'selected_products';

    /**
     * @var array
     */
    protected $editFields = ['qty_returned'];

    /**
     * @var array
     */
    protected $selectedId;

    /**
     * @var array
     */
    protected $selectedProductData = [];

    protected $returnOrder;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param ReturnOrderRepositoryInterface $returnOrderRepositoryInterface
     * @param EncoderInterface $jsonEncoder
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        ReturnOrderRepositoryInterface $returnOrderRepositoryInterface,
        EncoderInterface $jsonEncoder,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->collectionFactory = $collectionFactory;
        $this->returnOrderRepositoryInterface = $returnOrderRepositoryInterface;
        $this->jsonEncoder = $jsonEncoder;
        $this->backendHelper = $backendHelper;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId("returnorder_list_item");
        $this->setDefaultSort("return_item_id");
        $this->setUseAjax(true);
    }

    /**
     * Set hidden input field name for selected products
     *
     * @param string $name
     */
    public function setHiddenInputField($name)
    {
        $this->hiddenInputField = $name;
    }

    /**
     * Get hidden input field name for selected products
     *
     * @return string
     */
    public function getHiddenInputField()
    {
        return $this->hiddenInputField;
    }

    /**
     * Prepare collection for grid return order item
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->getDataColllection();
        $collection = $this->modifyCollection($collection);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Get Collection for grid return order item
     *
     * @return Collection
     */
    public function getDataColllection()
    {
        $returnOrderId = $this->getRequest()->getParam('id', null);
        $collection = $this->collectionFactory->create();
        $collection->setReturnOrderToFilter($returnOrderId);
        return $collection;
    }

    /**
     * Function to modify collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    public function modifyCollection(AbstractCollection $collection)
    {
        $selectedProduct = $this->getSelectedProducts();
        $data = $this->backendHelper->prepareFilterString((string)$this->getRequest()->getParam('filters'));
        if (array_key_exists('in_return_order', $data)) {
            $condition = 'nin';
            if ($data['in_return_order'] == '1') {
                $condition = 'in';
            }
            $collection->addFieldToFilter('product_id', [$condition => $selectedProduct]);
        }
        $collection->getSelect()
            ->order(new Zend_Db_Expr('FIELD(product_id, "' . implode('","', $selectedProduct) . '") DESC'));
        return $collection;
    }

    /**
     * Prepare columns for grid return order item
     *
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            "in_return_order",
            [
                "type" => "checkbox",
                "name" => "in_return_order",
                "values" => $this->getSelectedProducts(),
                "index" => "product_id",
                "filter" => false,
                "use_index" => "product_id",
                "header_css_class" => "col-select col-massaction",
                "column_css_class" => "col-select col-massaction"
            ]
        );
        $this->addColumn(
            "product_sku",
            [
                "header" => __("SKU"),
                "index" => "product_sku",
                "sortable" => true,
            ]
        );
        $this->addColumn(
            "product_name",
            [
                "header" => __("Product Name"),
                "index" => "product_name",
                "sortable" => true,
            ]
        );
        $this->addColumn(
            "product_supplier_sku",
            [
                "header" => __("Supplier SKU"),
                "index" => "product_supplier_sku",
                "sortable" => true,
            ]
        );
        $this->addColumn(
            "qty_returned",
            [
                "header" => __("Qty Returned"),
                'renderer' => Text::class,
                "index" => "qty_returned",
                'type' => 'number',
                "editable" => true,
                "sortable" => true
            ]
        );
        $this->addColumn(
            "qty_transferred",
            [
                "header" => __("Qty Delivered"),
                "index" => "qty_transferred",
                'type' => 'number',
                "sortable" => true
            ]
        );
        $this->addColumn(
            "delete",
            [
                "header" => __("Action"),
                'renderer' => Delete::class,
                'filters' => false,
                'sortable' => false,
            ]
        );
        $this->modifyColumns();
        $this->_eventManager->dispatch('prepare_return_order_grid_item', ['object' => $this]);
        return parent::_prepareColumns();
    }

    /**
     * Function to add, remove or modify product grid columns
     *
     * @return $this
     */
    public function modifyColumns()
    {
        return $this;
    }

    /**
     * Get Grid Url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl("*/returnOrder_product/grid", ["_current" => true]);
    }

    /**
     * Get Save Url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/returnOrder_product/update', ["_current" => true]);
    }

    /**
     * Get Delete Url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/returnOrder_product/delete');
    }

    /**
     * Get Selected Products
     *
     * @return array
     */
    public function getSelectedProducts()
    {
        if (empty($this->selectedId)) {
            $collection = $this->getDataColllection()
                ->addFieldToFilter(ReturnOrderItemInterface::QTY_RETURNED, ['lteq' => 0]);
            /** @var ReturnOrderItemInterface $item */
            foreach ($collection as $item) {
                $this->selectedProductData[$item->getProductId()] = [
                    ReturnOrderItemInterface::QTY_RETURNED => $item->getQtyReturned(),
                    ReturnOrderItemInterface::QTY_RETURNED . '_old' => $item->getQtyReturned(),
                ];
            }
            $this->selectedId = array_keys($this->selectedProductData);
        }
        return $this->selectedId;
    }

    /**
     * Get Selected Product Data
     *
     * @return string
     */
    public function getSelectedProductData()
    {
        return $this->jsonEncoder->encode($this->selectedProductData);
    }

    /**
     * Get Edit Fields
     *
     * @return false|string
     */
    public function getEditFields()
    {
        return json_encode($this->editFields);
    }

    /**
     * Get Current Return Order
     *
     * @return ReturnOrderInterface
     * @throws NotFoundException
     */
    public function getCurrentReturnOrder()
    {
        if (!$this->returnOrder) {
            $returnOrderId = $this->getRequest()->getParam('id');
            $this->returnOrder = $this->returnOrderRepositoryInterface->get($returnOrderId);
        }
        return $this->returnOrder;
    }
}
