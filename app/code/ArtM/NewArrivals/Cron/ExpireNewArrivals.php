<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\NewArrivals\Cron;

use ArtM\NewArrivals\Helper\Config;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Store\Model\Store;

class ExpireNewArrivals
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly Config $config,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ProductAction $productAction,
        private readonly DateTime $dateTime
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $days = $this->config->getDurationDays();
        $threshold = $this->dateTime->gmtDate(
            'Y-m-d H:i:s',
            strtotime('-' . $days . ' days')
        );
        
        echo "threshold: " . $threshold;

        // ────────────────────────────────────────────────
        // 1. DISABLE products that are still marked as new but are too old
        // ────────────────────────────────────────────────
        $toDisable = $this->productCollectionFactory->create()
            ->addAttributeToSelect([Config::ATTRIBUTE_CODE, 'created_at'])
            ->addAttributeToFilter(Config::ATTRIBUTE_CODE, 1)
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', ProductVisibility::VISIBILITY_BOTH)
            ->addAttributeToFilter('created_at', ['lteq' => $threshold]);

        $this->updateInChunks($toDisable, [Config::ATTRIBUTE_CODE => 0]);

        // ────────────────────────────────────────────────
        // 2. ENABLE products that are young enough but currently NOT marked as new
        // ────────────────────────────────────────────────
        $toEnable = $this->productCollectionFactory->create()
            ->addAttributeToSelect([Config::ATTRIBUTE_CODE, 'created_at'])
            ->addAttributeToFilter(Config::ATTRIBUTE_CODE, ['neq' => 1]) // 0 or null
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', ProductVisibility::VISIBILITY_BOTH)
            ->addAttributeToFilter('created_at', ['gt' => $threshold]);

        $this->updateInChunks($toEnable, [Config::ATTRIBUTE_CODE => 1]);
    }

    /**
     * Update attributes in chunks to avoid memory issues & query limits
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param array $data
     * @return void
     */
    private function updateInChunks(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        array $data
    ): void {
        $ids = [];

        foreach ($collection as $product) {
            $ids[] = (int)$product->getId();

            if (count($ids) >= self::CHUNK_SIZE) {
                $this->productAction->updateAttributes(
                    $ids,
                    $data,
                    Store::DEFAULT_STORE_ID
                );
                $ids = [];
            }
        }

        if ($ids !== []) {
            $this->productAction->updateAttributes(
                $ids,
                $data,
                Store::DEFAULT_STORE_ID
            );
        }
    }
}