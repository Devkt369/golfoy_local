<?php

namespace ArtM\MsiPriority\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use ArtM\MsiPriority\Logger\Logger;

class SyncManager
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ApiClient
     */
    private $apiClient;
    private $logger;

    /**
     * @var PayloadBuilder
     */
    private $payloadBuilder;

    /**
     * @var SyncStateManager
     */
    private $syncStateManager;

    public function __construct(
        Config $config,
        ApiClient $apiClient,
        PayloadBuilder $payloadBuilder,
        SyncStateManager $syncStateManager,
        Logger $logger
    ) {
        $this->config = $config;
        $this->apiClient = $apiClient;
        $this->payloadBuilder = $payloadBuilder;
        $this->syncStateManager = $syncStateManager;
        $this->logger = $logger;
    }

    public function syncProduct(ProductInterface $product, $storeId = null)
    {
        // $this->logger->info('Starting product sync workflow.', [
        //     'sku' => (string) $product->getSku(),
        //     'store_id' => $storeId,
        // ]);

        // if (!$this->canSync($product, $storeId)) {
        //     $this->logger->debug('Product sync workflow skipped by canSync.', [
        //         'sku' => (string) $product->getSku(),
        //     ]);
        //     return ['success' => false, 'skipped' => true];
        // }

        if ($this->syncStateManager->isProductSynced($product)) {
            $this->logger->info('Skipping Vinculum SKU create: product is already marked synced.', [
                'sku' => (string) $product->getSku(),
                'product_id' => (int) $product->getId(),
            ]);
            return ['success' => false, 'skipped' => true, 'reason' => 'already_synced'];
        }

        $payload = $this->payloadBuilder->buildSkuPayload($product);
        $response = $this->apiClient->syncSku($payload, $storeId);

        if (!empty($response['success'])) {
            $this->syncStateManager->markProductAsSynced($product);
        }

        return $response;
    }

    public function mapProductChannel(ProductInterface $product, array $sourceItemContext, $storeId = null)
    {

        // if (!$this->canSync($product, $storeId)) {
        //     return ['success' => false, 'skipped' => true];
        // }

        // if ($this->config->getChannelCode($storeId) === '') {
        //     return ['success' => false, 'skipped' => true];
        // }

        if ($this->syncStateManager->isProductMapped($product)) {
            return ['success' => false, 'skipped' => true];
        }

        $payload = $this->payloadBuilder->buildChannelMapPayload($product, $sourceItemContext, $this->config, $storeId);
        $response = $this->apiClient->mapSkuChannel($payload, $storeId);
        if (!empty($response['success'])) {
            $this->syncStateManager->markProductAsMapped($product);
        }

        return $response;
    }

    private function canSync(ProductInterface $product, $storeId = null)
    {
        if (!$this->config->isEnabled($storeId)) {
            return false;
        }

        if ($this->config->getBaseUrl($storeId) === '' || $this->config->getApiOwner($storeId) === '' || $this->config->getApiKey($storeId) === '') {
            return false;
        }

        $selectedBrands = $this->config->getSyncBrandValues($storeId);
        if (empty($selectedBrands)) {
            $this->logger->debug('No brand filter configured, allowing sync for all brands.', [
                'sku' => (string) $product->getSku(),
            ]);
            return true;
        }

        $brandValue = (string) $product->getData('brand');
        if ($brandValue === '') {
            $this->logger->debug('Product brand value is empty, skipping due to active brand filter.', [
                'sku' => (string) $product->getSku(),
            ]);
            return false;
        }
        $isAllowed = in_array($brandValue, $selectedBrands, true);
        $this->logger->debug('Brand filter evaluated.', [
            'sku' => (string) $product->getSku(),
            'brand_value' => $brandValue,
            'allowed' => $isAllowed,
            'selected_brands' => $selectedBrands,
        ]);

        return $isAllowed;
    }
}
