<?php
namespace ArtM\GraphqlCache\Model\Cache\Type;

use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\App\Cache\Type\FrontendPool;

class GraphqlCache extends TagScope
{
    const TYPE_IDENTIFIER = 'artm_graphql';
    const CACHE_TAG = 'ARTM_GRAPHQL';

    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}