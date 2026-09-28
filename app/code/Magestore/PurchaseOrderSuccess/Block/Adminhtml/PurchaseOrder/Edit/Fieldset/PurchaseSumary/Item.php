<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PurchaseOrderSuccess\Block\Adminhtml\PurchaseOrder\Edit\Fieldset\PurchaseSumary;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\PurchaseOrderInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\PurchaseOrderItemInterface;
use Magestore\PurchaseOrderSuccess\Api\PurchaseOrderRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Block\Adminhtml\Grid\Column\Renderer\Text;
use Magestore\PurchaseOrderSuccess\Model\ResourceModel\PurchaseOrder\Item\CollectionFactory as ItemCollectionFactory;
use Magestore\PurchaseOrderSuccess\Model\ResourceModel\PurchaseOrder\Item\Collection as ItemCollection;
use Magento\Backend\Helper\Data as BackendHelper;
use Magestore\PurchaseOrderSuccess\Block\Adminhtml\PurchaseOrder\Edit\Fieldset\PurchaseSumary\Item\Delete;
use Magestore\SupplierSuccess\Service\Supplier\PricingListService;

/**
 * PurchaseOrderSuccess - Purchase Summary - Item
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Item extends Extended
{
    /**
     * @var ItemCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    protected $purchaseOrderRepositoryInterface;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var BackendHelper
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
    protected $editFields = ['cost', 'tax', 'discount', 'qty_orderred'];

    /**
     * @var string
     */
    protected $originCostField = 'original_cost';

    /**
     * @var array
     */
    protected $selectedId;

    /**
     * @var array
     */
    protected $selectedProductData = [];

    protected $purchaseOrder;

    /**
     * @param Context $context
     * @param BackendHelper $backendHelper
     * @param ItemCollectionFactory $collectionFactory
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepositoryInterface
     * @param EncoderInterface $jsonEncoder
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        ItemCollectionFactory $collectionFactory,
        PurchaseOrderRepositoryInterface $purchaseOrderRepositoryInterface,
        EncoderInterface $jsonEncoder,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->collectionFactory = $collectionFactory;
        $this->purchaseOrderRepositoryInterface = $purchaseOrderRepositoryInterface;
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
        $this->setId("purchaseorder_list_item");
        $this->setDefaultSort("purchase_order_item_id");
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
     * Get origin cost for selected products
     *
     * @return string
     */
    public function getOriginCostField()
    {
        return $this->originCostField;
    }

    /**
     * Prepare collection for grid purchase order item
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
     * Get Collection for grid purchase order item
     *
     * @return ItemCollection
     */
    public function getDataColllection()
    {
        $purchaseOrderId = $this->getRequest()->getParam('id', null);
        $collection = $this->collectionFactory->create();
        $collection->setPurchaseOrderToFilter($purchaseOrderId);
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
        if (array_key_exists('in_purchase_order', $data)) {
            $condition = 'nin';
            if ($data['in_purchase_order'] == '1') {
                $condition = 'in';
            }
            $collection->addFieldToFilter('product_id', [$condition => $selectedProduct]);
        }
        $collection->getSelect()
            ->order(new \Zend_Db_Expr('FIELD(product_id, "' . implode('","', $selectedProduct) . '") DESC'));
        return $collection;
    }

    /**
     * Prepare columns for grid purchase order item
     *
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            "in_purchase_order",
            [
                "type" => "checkbox",
                "name" => "in_purchase_order",
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
        $purchaseOrder = $this->getCurrentPurchaseOrder();
        $this->addColumn(
            "original_cost",
            [
                "header" => __("Current Cost") . '('.$purchaseOrder->getCurrencyCode().')',
                "index" => "original_cost",
                'type' => 'number',
                "sortable" => true,
                'rate' => '1'
            ]
        );
        $this->addColumn(
            "cost",
            [
                "header" => __("Purchase Cost") . ' ('.$purchaseOrder->getCurrencyCode().')',
                'renderer' => Text::class,
                "index" => "cost",
                'type' => 'number',
                "editable" => true,
                "sortable" => true
            ]
        );
        $this->addColumn(
            "tax",
            [
                "header" => __("Tax (%)"),
                'renderer' => Text::class,
                "index" => "tax",
                'type' => 'number',
                "editable" => true,
                "sortable" => true
            ]
        );
        $this->addColumn(
            "discount",
            [
                "header" => __("Discount (%)"),
                'renderer' => Text::class,
                "index" => "discount",
                'type' => 'number',
                "editable" => true,
                "sortable" => true
            ]
        );
        $this->addColumn(
            "qty_orderred",
            [
                "header" => __("Qty Orderring"),
                'renderer' => Text::class,
                "index" => "qty_orderred",
                'type' => 'number',
                "editable" => true,
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
        $this->_eventManager->dispatch('prepare_purchase_order_grid_item', ['object' => $this]);
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
        return $this->getUrl("*/purchaseOrder_product/grid", ["_current" => true]);
    }

    /**
     * Get Save Url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/purchaseOrder_product/update', ["_current" => true]);
    }
    
    /**
     * Get Delete Url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/purchaseOrder_product/delete');
    }

    /**
     * Get Update Cost Url
     *
     * @return string
     */
    public function getUpdateCostUrl()
    {
        return $this->getUrl('*/purchaseOrder_product/updatecost');
    }

    /**
     * Get Reload Total Url
     *
     * @return string
     */
    public function getReloadTotalUrl()
    {
        return $this->getUrl('*/purchaseOrder_product/reloadTotal', ["_current" => true]);
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
                ->addFieldToFilter(PurchaseOrderItemInterface::QTY_ORDERRED, ['lteq' => 0]);
            /** @var PurchaseOrderItemInterface $item */
            foreach ($collection as $item) {
                $this->selectedProductData[$item->getProductId()] = [
                    PurchaseOrderItemInterface::COST => $item->getCost(),
                    PurchaseOrderItemInterface::COST . '_old' => $item->getCost(),
                    PurchaseOrderItemInterface::TAX => $item->getTax(),
                    PurchaseOrderItemInterface::TAX . '_old' => $item->getTax(),
                    PurchaseOrderItemInterface::DISCOUNT => $item->getDiscount(),
                    PurchaseOrderItemInterface::DISCOUNT . '_old' => $item->getDiscount(),
                    PurchaseOrderItemInterface::QTY_ORDERRED => $item->getQtyOrderred(),
                    PurchaseOrderItemInterface::QTY_ORDERRED . '_old' => $item->getQtyOrderred(),
                    PurchaseOrderItemInterface::ORIGINAL_COST => $item->getOriginalCost(),
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
     * Get Price List Json
     *
     * @return string
     */
    public function getPriceListJson()
    {
        if (!$this->_scopeConfig->getValue('suppliersuccess/pricelist/enable')) {
            $priceList = [];
            return $this->jsonEncoder->encode($priceList);
        }
        $puchaseOrder = $this->getCurrentPurchaseOrder();
        $time = $puchaseOrder->getPurchasedAt();
        $supplierId = $puchaseOrder->getSupplierId();
        /** @var PricingListService $supplierService */
        $supplierService = ObjectManager::getInstance()->create(
            PricingListService::class
        );
        $priceList = $supplierService->getProductCost(null, $supplierId, $time);
        $priceListJson = [];
        foreach ($priceList as $price) {
            $price['cost'] = $this->priceCurrency->convert($price['cost'] * $puchaseOrder->getCurrencyRate());
            $priceListJson[] = $price;
        }
        return $this->jsonEncoder->encode($priceListJson);
    }

    /**
     * Get Current Purchase Order
     *
     * @return PurchaseOrderInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getCurrentPurchaseOrder()
    {
        if (!$this->purchaseOrder) {
            $purchaseOrderId = $this->getRequest()->getParam('id');
            $this->purchaseOrder = $this->purchaseOrderRepositoryInterface->get($purchaseOrderId);
        }
        return $this->purchaseOrder;
    }
}
