<?php

namespace ArtM\PrintPriceTracking\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use ArtM\PrintPriceTracking\Model\ResourceModel\Report\CollectionFactory;
use Magento\Framework\App\RequestInterface;

class Report extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
        parent::__construct($context, $data);
    }

    /**
     * Get Report Collection
     *
     * @return \ArtM\PrintPriceTracking\Model\ResourceModel\Report\Collection
     */
    public function getCollection()
    {
        $page = (int)$this->getRequest()->getParam('p', 1);
        $sort = $this->getRequest()->getParam('sort', 'desc');
        $from = $this->getRequest()->getParam('from');
        $to   = $this->getRequest()->getParam('to');

        $collection = $this->collectionFactory->create();

        // Select main_table fields; increment_id and print_total are added explicitly
        $collection->addFieldToSelect([
            'item_id',
            'order_id',
            'sku',
            'name',
            'qty_ordered',
            'print_price'
        ]);

        // Only items that have been shipped at least once with a calculated print price
        $collection->addFieldToFilter('qty_shipped', ['gt' => 0]);
        $collection->addFieldToFilter('print_price', ['gt' => 0]);

        // Date Filter
        if (!empty($from)) {
            $collection->addFieldToFilter(
                'created_at',
                ['gteq' => $from . ' 00:00:00']
            );
        }

        if (!empty($to)) {
            $collection->addFieldToFilter(
                'created_at',
                ['lteq' => $to . ' 23:59:59']
            );
        }

        // Sort by latest order date
        $collection->setOrder(
            'created_at',
            ($sort === 'asc') ? 'ASC' : 'DESC'
        );

        // Pagination
        $collection->setPageSize(20);
        $collection->setCurPage($page);

        // Ensure SELECT columns use proper table aliases to avoid main_table.increment_id
        $select = $collection->getSelect();
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->columns([
            'main_table.item_id',
            'main_table.order_id',
            'order_table.increment_id AS increment_id',
            'main_table.sku',
            'main_table.name',
            'main_table.qty_ordered',
            'main_table.print_price',
            new \Zend_Db_Expr('main_table.print_price * main_table.qty_ordered AS print_total'),
            'order_table.status',
            'order_table.created_at'
        ]);

        return $collection;
    }

    /**
     * Compute total print amount for all matching records (no pagination)
     *
     * @return float
     */
    public function getTotalPrintSum()
    {
        $from = $this->getRequest()->getParam('from');
        $to   = $this->getRequest()->getParam('to');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(['qty_ordered', 'print_price']);
        $collection->addFieldToFilter('qty_shipped', ['gt' => 0]);
        $collection->addFieldToFilter('print_price', ['gt' => 0]);

        if (!empty($from)) {
            $collection->addFieldToFilter('created_at', ['gteq' => $from . ' 00:00:00']);
        }

        if (!empty($to)) {
            $collection->addFieldToFilter('created_at', ['lteq' => $to . ' 23:59:59']);
        }

        $total = 0.0;
        foreach ($collection as $item) {
            $total += (float)$item->getPrintPrice() * (float)$item->getQtyOrdered();
        }

        return $total;
    }
}
