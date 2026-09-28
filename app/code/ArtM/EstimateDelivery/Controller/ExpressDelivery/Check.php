<?php
namespace ArtM\EstimateDelivery\Controller\ExpressDelivery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;

class Check extends Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $getSourceItemsBySku;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        GetSourceItemsBySkuInterface $getSourceItemsBySku
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $request = $this->getRequest();
        $skus = [];
        // Accept JSON POST body or fallback to param
        if ($request->isPost()) {
            $rawBody = $request->getContent();
            if ($rawBody) {
                $data = json_decode($rawBody, true);
                if (isset($data['skus']) && is_array($data['skus'])) {
                    $skus = $data['skus'];
                }
            }
        }
        if (empty($skus)) {
            $sku = $request->getParam('sku');
            if ($sku) {
                $skus = [$sku];
            }
        }

        $response = [];
        if (!empty($skus)) {
            // Get own sources from config
            $ownSources = $this->scopeConfig->getValue(
                'artm_estimatedelivery/own_source/all_source',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $ownSourcesArr = $ownSources ? array_map('trim', explode(',', $ownSources)) : [];

            foreach ($skus as $sku) {
                $isExpress = false;
                try {
                    $sourceItems = $this->getSourceItemsBySku->execute($sku);
                    foreach ($sourceItems as $item) {
                        if (in_array($item->getSourceCode(), $ownSourcesArr) && $item->getQuantity() > 0) {
                            $isExpress = true;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $isExpress = false;
                }
                $response[$sku] = $isExpress;
            }
        }

        return $result->setData($response);
    }
}
