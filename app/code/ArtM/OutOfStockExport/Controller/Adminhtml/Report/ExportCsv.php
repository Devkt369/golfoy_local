<?php
namespace ArtM\OutOfStockExport\Controller\Adminhtml\Report;

class ExportCsv extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'ArtM_OutOfStockExport::report';

    protected $fileFactory;
    protected $collectionFactory;
    protected $typeFactory;
    protected $visibilitySource;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \ArtM\OutOfStockExport\Model\ResourceModel\Report\CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\Product\TypeFactory $typeFactory,
        \Magento\Catalog\Model\Product\Visibility $visibilitySource
    ) {
        parent::__construct($context);
        $this->fileFactory   = $fileFactory;
        $this->collectionFactory = $collectionFactory;
        $this->typeFactory   = $typeFactory;
        $this->visibilitySource = $visibilitySource;
    }

    public function execute()
    {
        $collection = $this->collectionFactory->create();
        $collection->applyReportFilters();

        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, ['SKU', 'Product Name', 'Type', 'Visibility', 'Qty', 'Stock Status']);

        $collection->setPageSize(500);
        $pages = $collection->getLastPageNumber();

        for ($page = 1; $page <= $pages; $page++) {
            $collection->setCurPage($page);
            foreach ($collection as $product) {
                fputcsv($fh, [
                    $product->getSku(),
                    $product->getName(),
                    $this->typeFactory->create()->getOptionText($product->getTypeId()),
                    $this->visibilitySource->getOptionText($product->getVisibility()),
                    $product->getQty(),
                    (int)$product->getStockStatus() ? 'In Stock' : 'Out of Stock',
                ]);
            }
            $collection->clear();
        }

        rewind($fh);
        $content = stream_get_contents($fh);
        fclose($fh);

        return $this->fileFactory->create(
            'out_of_stock_report.csv',
            $content,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'text/csv'
        );
    }
}