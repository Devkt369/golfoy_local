<?php
namespace ArtM\PrintPriceTracking\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use ArtM\PrintPriceTracking\Model\ResourceModel\Report\CollectionFactory;

class Export extends Action
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        FileFactory $fileFactory,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $from = $this->request->getParam('from');
        $to = $this->request->getParam('to');
        $sort = $this->request->getParam('sort', 'desc');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect([
            'item_id',
            'order_id',
            'sku',
            'name',
            'qty_ordered',
            'print_price'
        ]);
        $collection->addFieldToFilter('print_price', ['gt' => 0]);
        $collection->addFieldToFilter('qty_shipped', ['gt' => 0]);

        if (!empty($from)) {
            $collection->addFieldToFilter('created_at', ['gteq' => $from . ' 00:00:00']);
        }

        if (!empty($to)) {
            $collection->addFieldToFilter('created_at', ['lteq' => $to . ' 23:59:59']);
        }

        $collection->setOrder('created_at', ($sort === 'asc') ? 'ASC' : 'DESC');

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
            'order_table.created_at'
        ]);

        $rows = [
            ['Item ID', 'Order ID', 'Increment ID', 'SKU', 'Name', 'Qty', 'Print Price', 'Total Print', 'Order Date']
        ];

        foreach ($collection as $item) {
            $rows[] = [
                $item->getItemId(),
                $item->getOrderId(),
                $item->getIncrementId(),
                $item->getSku(),
                $item->getName(),
                $item->getQtyOrdered(),
                number_format((float)$item->getPrintPrice(), 4, '.', ''),
                number_format((float)$item->getPrintTotal(), 4, '.', ''),
                $item->getCreatedAt(),
            ];
        }

        $content = $this->buildCsv($rows);
        $fileName = 'print_price_report_' . date('Ymd_His') . '.csv';

        return $this->fileFactory->create(
            $fileName,
            $content,
            DirectoryList::VAR_DIR,
            'text/csv'
        );
    }

    private function buildCsv(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $escaped = array_map(function ($value) {
                $value = (string)$value;
                $value = str_replace('"', '""', $value);
                return '"' . $value . '"';
            }, $row);
            $lines[] = implode(',', $escaped);
        }
        return "sep=,\r\n" . implode("\r\n", $lines);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_PrintPriceTracking::report');
    }
}
