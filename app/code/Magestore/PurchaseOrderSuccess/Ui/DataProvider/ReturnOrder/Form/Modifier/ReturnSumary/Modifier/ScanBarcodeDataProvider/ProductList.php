<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\PurchaseOrderSuccess\Ui\DataProvider\ReturnOrder\Form\Modifier\ReturnSumary\Modifier\ScanBarcodeDataProvider; //phpcs:disable

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magestore\BarcodeSuccess\Model\ResourceModel\Barcode\Collection;
use Magestore\Core\Service\CheckExistDisableTmp;
use Magestore\PurchaseOrderSuccess\Service\ReturnOrder\Item\ItemService;
use Magestore\PurchaseOrderSuccess\Ui\DataProvider\ReturnOrder\Form\Modifier\ReturnSumary\Modifier\AbstractModifier;

/**
 * Class ProductList
 *
 * Used for product list
 * @SuppressWarnings(PHPMD)
 */
class ProductList extends AbstractModifier
{

    /**
     * @var string
     */
    protected $groupContainer = 'product_list';

    /**
     * @var string
     */
    protected $groupLabel = 'Product List';

    /**
     * @var int
     */
    protected $sortOrder = 20;

    /**
     * @var CheckExistDisableTmp
     */
    protected $checkExistDisableTmp;

    protected $children = [
        'dynamic_grid' => 'dynamic_grid',
        'returned_product_modal_select_listing' => 'os_return_order_item_listing'
    ];

    protected $mapFields = [
        'id' => 'product_id',
        'product_sku' => 'product_sku',
        'product_name' => 'product_name',
        'product_supplier_sku' => 'product_supplier_sku',
    ];

    public function __construct(
        ObjectManagerInterface $objectManager,
        Registry $registry,
        RequestInterface $request,
        UrlInterface $urlBuilder,
        CheckExistDisableTmp $checkExistDisableTmp
    ) {
        parent::__construct($objectManager, $registry, $request, $urlBuilder);
        $this->checkExistDisableTmp = $checkExistDisableTmp;
    }

    /**
     * Functipon modifyData
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Modify return order form meta
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                $this->groupContainer => [
                    'children' => $this->getProductListChildren(),
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __($this->groupLabel),
                                'collapsible' => true,
                                'visible' => $this->getVisible(),
                                'opened' => true,
                                'componentType' => Fieldset::NAME,
                                'sortOrder' => $this->getSortOrder()
                            ],
                        ],
                    ],
                ],
            ]
        );
        return $meta;
    }

    /**
     * Add general form fields
     *
     * @return array
     */
    public function getProductListChildren()
    {
        $children = [
            $this->children['dynamic_grid'] => $this->getDynamicGrid()
        ];
        $children['returned_product_barcode_scan_input'] = $this->getReturnedProductScanBarcodeInput();
        return $children;
    }

    /**
     * Function getReturnedProductScanBarcodeInput
     *
     * @return \array[][][]
     */
    public function getReturnedProductScanBarcodeInput()
    {
        /* @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Container::NAME,
                        'componentType' => Field::NAME,
                        'component' => 'Magestore_PurchaseOrderSuccess/js/form/element/barcode',
                        'label' => false,
                        'sortOrder' => 20,
                        'placeholder' => __('Scan product barcode here'),
                        'getBarcodeUrl' => $this->urlBuilder->getUrl(
                            'purchaseordersuccess/returnOrder_product/getProductJsonByBarcode',
                            [
                                'warehouse_id' => $request->getParam('warehouse_id'),
                                'supplier_id' => $request->getParam('supplier_id')
                            ]
                        ),
                        'sourceElement' => 'index = ' . $this->children['returned_product_modal_select_listing'],
                        'destinationElement' => $this->scopeName . '.' . $this->groupContainer . '.' .
                            $this->children['dynamic_grid'],
                        'selectionsProvider' =>
                            $this->children['returned_product_modal_select_listing']
                            . '.' . $this->children['returned_product_modal_select_listing']
                            . '.return_order_item_template_columns.ids',
                        'qtyElement' => $this->scopeName . '.' . $this->groupContainer . '.' .
                            $this->children['dynamic_grid'] . '.%s.returned_qty',
                        'inputElementName' => 'returned_qty',
                        'isVisibleDefault' => true
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns dynamic rows configuration
     *
     * @return array
     * @throws Exception
     */
    public function getDynamicGrid()
    {
        $grid = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__field-wide',
                        'componentType' => DynamicRows::NAME,
                        'label' => null,
                        'renderDefaultRecord' => false,
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
                        'addButton' => false,
                        'itemTemplate' => 'record',
                        'dataScope' => 'data',
                        'deleteButtonLabel' => __('Remove'),
                        'dataProvider' => $this->children['returned_product_modal_select_listing'],
                        'map' => $this->mapFields,
                        'links' => [
                            'insertData' => '${ $.provider }:${ $.dataProvider }',
                        ],
                        'sortOrder' => 30,
                        'columnsHeader' => false,
                        'columnsHeaderAfterRender' => true,
                    ],
                ],
            ],
            'children' => $this->getRows(),
        ];

        if ($this->checkExistDisableTmp->isExistDisableTmpl()) {
            $grid['arguments']['data']['config']['links']['__disableTmpl']['insertData'] = false;
        }

        return $grid;
    }

    /**
     * Returns Dynamic rows records configuration
     *
     * @return array
     */
    public function getRows()
    {
        return [
            'record' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => 'container',
                            'isTemplate' => true,
                            'is_collection' => true,
                            'component' => 'Magento_Ui/js/dynamic-rows/record',
                            'dataScope' => '',
                        ],
                    ],
                ],
                'children' => $this->fillModifierMeta(),
            ],
        ];
    }

    /**
     * Fill meta columns
     *
     * @return array
     */
    public function fillModifierMeta()
    {
        return [
            'id' => $this->getTextColumn('id', false, 'Product ID', 10),
            'product_sku' => $this->getTextColumn('product_sku', false, 'Product SKU', 20),
            'product_name' => $this->getTextColumn(
                'product_name',
                false,
                'Product Name',
                30
            ),
            'product_supplier_sku' => $this->getTextColumn(
                'product_supplier_sku',
                false,
                'Supplier SKU',
                40
            ),
            'returned_qty' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataType' => Form\Element\DataType\Number::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'componentType' => Field::NAME,
                            'dataScope' => 'returned_qty',
                            'label' => __('Returned Qty'),
                            'fit' => true,
                            'additionalClasses' => 'admin__field-small',
                            'sortOrder' => 60,
                            'validation' => [
                                'validate-number' => true,
                                'validate-greater-than-zero' => true,
                                'required-entry' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'actionDelete' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType' => 'actionDelete',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'label' => __('Actions'),
                            'sortOrder' => 70,
                            'fit' => true,
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * Get returned product barcode json
     *
     * @return mixed
     */
    public function getReturnedProductBarcodeJson()
    {
        $result = [];
        $collection = $this->getReturnedProductBarcodeCollection();
        foreach ($collection->getItems() as $item) {
            $result[$item->getBarcode()] = $item->getData();
        }
        return ObjectManager::getInstance()
            ->create(EncoderInterface::class)->encode($result);
    }

    /**
     * Get returned product barcode collection
     *
     * @return mixed
     */
    public function getReturnedProductBarcodeCollection()
    {
        $warehouseId = $this->request->getParam('warehouse_id', null);
        $supplierId = $this->request->getParam('supplier_id', null);
        $collection = ObjectManager::getInstance()
            ->create(Collection::class);
        /** @var ItemService $returnItemService */
        $returnItemService = ObjectManager::getInstance()
            ->create(ItemService::class);
        $listProductsIdOnSource = $returnItemService->getProductIdOnCurrentWarehouse($warehouseId);
        $collection->addFieldToFilter('main_table.product_id', ['in' => $listProductsIdOnSource]);
        $collection->addFieldToSelect(['barcode']);
        $collection->getSelect()->joinInner(
            ['item' => $collection->getTable('os_supplier_product')],
            'main_table.product_id = item.product_id AND item.supplier_id = ' . $supplierId,
            '*'
        );
        return $collection;
    }
}
