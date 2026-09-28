<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\NewArrivals\Observer\Catalog;

use ArtM\NewArrivals\Helper\Config;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveBefore implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly EavConfig $eavConfig
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product instanceof Product || !$product->isObjectNew()) {
            return;
        }

        $storeId = (int) $product->getStoreId();
        if (!$this->config->isEnabled($storeId > 0 ? $storeId : null)) {
            return;
        }

        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, Config::ATTRIBUTE_CODE);
        if (!$attribute || !$attribute->getId()) {
            return;
        }

        $product->setData(Config::ATTRIBUTE_CODE, 1);
    }
}
