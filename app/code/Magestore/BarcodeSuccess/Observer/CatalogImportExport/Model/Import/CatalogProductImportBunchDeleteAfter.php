<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\BarcodeSuccess\Observer\CatalogImportExport\Model\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProductModel;
use Magestore\BarcodeSuccess\Api\Data\BarcodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Import bunch product delete after observer
 */
class CatalogProductImportBunchDeleteAfter implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $bunch = $observer->getData('bunch');
            if (empty($bunch)) {
                return;
            }
            $skus = [];
            foreach ($bunch as $rowData) {
                $sku = $rowData[ImportProductModel::COL_SKU];
                $skus[$sku] = $sku;
            }

            $connection = $this->resourceConnection->getConnection();
            $barcodeTable = $this->resourceConnection->getTableName('os_barcode');
            $connection->delete(
                $barcodeTable,
                [BarcodeInterface::PRODUCT_SKU . ' IN (?)' => $skus]
            );
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
        }
    }
}
