<?php
namespace WeserveCommerce\AjaxSearch\Model\Index;

use WeserveCommerce\AjaxSearch\Api\ProductIndexResolverInterface;
use WeserveCommerce\AjaxSearch\Model\Config;

class ProductIndexResolver implements ProductIndexResolverInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Resolves the concrete index written by the custom quick-search indexer.
     *
     * @param int $storeId
     * @return string
     */
    public function resolve(int $storeId): string
    {
        return $this->config->getQuickSearchIndexName($storeId);
    }
}
