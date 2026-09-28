<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\ProductFeed\Cron;

use Magento\Store\Model\ScopeInterface;
use ArtM\ProductFeed\Model\FeedGenerator;

class ProductReviewFeed
{
    protected $FeedGenerator;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        FeedGenerator $FeedGenerator
        )
    {
        $this->FeedGenerator = $FeedGenerator;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if($this->getConfigValue('review/generalproductfeed/productfeed') != 1){
            return;
        }
        $this->FeedGenerator->generateFeed();
    }

    function getConfigValue($configPath, $storeId = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        return $scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId);
    }
}