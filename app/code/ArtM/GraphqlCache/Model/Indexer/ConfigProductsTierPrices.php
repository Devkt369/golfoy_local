<?php

namespace ArtM\GraphqlCache\Model\Indexer;

use DateTime;
use ArtM\GraphqlCache\Services\Indexer\ProductPricesHelper;
use Magento;

class ConfigProductsTierPrices implements Magento\Framework\Indexer\ActionInterface, Magento\Framework\Mview\ActionInterface
{
    private $productPricesHelper;

    public function __construct(
        ProductPricesHelper $productPricesHelper
    ) {
        $this->productPricesHelper = $productPricesHelper;
    }

    public function execute($ids)
    {
        return null;
    }

    public function executeFull()
    {
        return null;
        $this->productPricesHelper->addConfigurableProductTierPrices();
    }

    public function executeList(array $ids)
    {
        return null;
    }

    public function executeRow($id)
    {
        $this->productPricesHelper->addConfigurableProductTierPrices();
    }
}
