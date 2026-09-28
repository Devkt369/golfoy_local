<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResourceConnection;

class SyncProduct extends Action
{
    protected $resource;
    public function __construct(
        Action\Context $context,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $productId = $this->getRequest()->getParam('product_id');
        $websiteId = $this->getRequest()->getParam('website_id');
        $selectors = $this->getRequest()->getParam('selectors'); // comma-separated
        $url = $this->getRequest()->getParam('url');
        if (!$productId || !$websiteId || !$selectors || !$url) {
            return $resultJson->setData(['success' => false, 'error' => 'Missing params']);
        }
        $selectorArr = array_map('trim', explode(',', $selectors));
        $value = '';
        foreach ($selectorArr as $selector) {
            $apiUrl = 'http://127.0.0.1:5001/get-price?url=' . urlencode($url) . '&selector=' . urlencode($selector);
            $resp = @file_get_contents($apiUrl);
            if ($resp && trim(strip_tags($resp))) {
                $value = trim(strip_tags($resp));
                break;
            }
        }
        // Save to fetched_data column (as JSON)
        $connection = $this->resource->getConnection();
        $productTable = $this->resource->getTableName('artm_pricecompare_product');
        $existing = $connection->fetchOne("SELECT fetched_data FROM $productTable WHERE product_id = ?", [$productId]);
        $fetchedData = $existing ? json_decode($existing, true) : [];
        $fetchedData[$websiteId] = $value;
        $connection->update($productTable, [
            'fetched_data' => json_encode($fetchedData)
        ], ['product_id = ?' => $productId]);
        return $resultJson->setData(['success' => true, 'value' => $value]);
    }
}
