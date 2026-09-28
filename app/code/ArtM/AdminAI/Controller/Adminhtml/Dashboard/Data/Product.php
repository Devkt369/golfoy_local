<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Dashboard\Data;

use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use ArtM\AdminAI\Model\ResourceModel\Dashboard\ProductData;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Product extends Base
{
    /** @var ProductData */
    private $productData;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AnalyticsCacheServiceInterface $analyticsCacheService,
        ResourceConnection $resource,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ProductData $productData
    ) {
        parent::__construct($context, $resultJsonFactory, $analyticsCacheService, $resource, $logger, $storeManager);
        $this->productData = $productData;
    }

    protected function getIntentCode(): string
    {
        return 'dashboard_products_v5';
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    protected function buildPayload(array $filters): array
    {
        return $this->productData->fetch($filters);
    }
}