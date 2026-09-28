<?php
namespace ArtM\MsiPriority\Plugin;

use ArtM\MsiPriority\Service\SyncManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use ArtM\MsiPriority\Logger\Logger;

class SourceItemsSavePlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SyncManager
     */
    private $syncManager;
    private const ALLOWED_SOURCES = ['G5'];
    
    /**
     * @var Logger
     */
    private $logger;

    private $getSourceItemsBySku;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        SyncManager $syncManager,
        Logger $logger,
        \Magento\InventoryApi\Api\GetSourceItemsBySkuInterface $getSourceItemsBySku
    ) {
        $this->productRepository = $productRepository;
        $this->syncManager = $syncManager;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->logger = $logger;
    }

    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems)
    {
        $this->logger->debug('Skipped MSI source item due to empty SKU. before');
        foreach ($sourceItems as $sourceItem) {

            // try {
                $sku = (string) $sourceItem->getSku();
                if ($sku === '') {
                    $this->logger->debug('Skipped MSI source item due to empty SKU.');
                    continue;
                }

                $sourceCode = (string) $sourceItem->getSourceCode();

                // 🔥 FILTER HERE
                // if (!in_array($sourceCode, self::ALLOWED_SOURCES, true)) {
                //     $this->logger->debug('Skipped source (not in allowed list)', [
                //         'sku' => $sku,
                //         'source_code' => $sourceCode
                //     ]);
                //     continue;
                // }

                $this->logger->debug('Processing MSI source item for channel map.', [
                    'sku' => $sku,
                    'source_code' => $sourceCode,
                ]);

                $product = $this->productRepository->get($sku, false, null, true);

                $response = $this->syncManager->mapProductChannel(
                    $product,
                    ['source_code' => $sourceCode],
                    (int) $product->getStoreId()
                );

                if (!empty($response['success'])) {
                    $this->logger->info('Vinculum channel mapping successful.', [
                        'sku' => $sku,
                        'source_code' => $sourceCode,
                    ]);
                    continue;
                }

                if (!empty($response['skipped'])) {
                    $this->logger->debug('Vinculum mapping skipped.', [
                        'sku' => $sku,
                        'source_code' => $sourceCode,
                        'response' => $response,
                    ]);
                    continue;
                }

                $this->logger->error(
                    'Vinculum channel mapping failed.',
                    ['sku' => $sku, 'response' => $response]
                );

            // } catch (\Exception $exception) {
            //     $this->logger->error(
            //         'Vinculum source assignment map failed: ' . $exception->getMessage()
            //     );
            // }
        }

        return $result;
    }
}
