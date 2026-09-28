<?php
namespace ArtM\OutOfStockExport\Block\Adminhtml\Report;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $collectionFactory;
    protected $typeFactory;
    protected $visibilitySource;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \ArtM\OutOfStockExport\Model\ResourceModel\Report\CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\Product\TypeFactory $typeFactory,
        \Magento\Catalog\Model\Product\Visibility $visibilitySource,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->typeFactory       = $typeFactory;
        $this->visibilitySource  = $visibilitySource;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('artmOutOfStockGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
    }

    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->applyReportFilters();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header' => __('ID'),
            'index'  => 'entity_id',
            'type'   => 'number',
            'width'  => '80px',
        ]);

        $this->addColumn('sku', [
            'header' => __('SKU'),
            'index'  => 'sku',
        ]);

        $this->addColumn('name', [
            'header' => __('Product Name'),
            'index'  => 'name',
        ]);

        $this->addColumn('type_id', [
            'header'  => __('Type'),
            'index'   => 'type_id',
            'type'    => 'options',
            'options' => $this->typeFactory->create()->getOptionArray(),
        ]);

        $this->addColumn('visibility', [
            'header'  => __('Visibility'),
            'index'   => 'visibility',
            'type'    => 'options',
            'options' => $this->visibilitySource->getOptionArray(),
        ]);

        $this->addColumn('qty', [
            'header' => __('Qty'),
            'index'  => 'qty',
            'type'   => 'number',
        ]);

        $this->addColumn('created_at', [
            'header'  => __('Date'),
            'index'   => 'created_at',
            'type'    => 'date',
            'format'  => 'yyyy-MM-dd',
            'filter'  => \Magento\Backend\Block\Widget\Grid\Column\Filter\Date::class,
        ]);

        $this->addColumn('stock_status', [
            'header'  => __('Stock Status'),
            'index'   => 'stock_status',
            'type'    => 'options',
            'options' => [0 => __('Out of Stock'), 1 => __('In Stock')],
        ]);

        $this->addExportType('*/*/exportCsv', __('CSV'));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/index');
    }
}