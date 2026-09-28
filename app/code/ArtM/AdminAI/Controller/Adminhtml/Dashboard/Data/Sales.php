<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Dashboard\Data;

use ArtM\AdminAI\Model\ResourceModel\Dashboard\SalesData;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Sales extends Base
{
    /** @var SalesData */
    private $salesData;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AnalyticsCacheServiceInterface $analyticsCacheService,
        ResourceConnection $resource,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        SalesData $salesData
    ) {
        parent::__construct($context, $resultJsonFactory, $analyticsCacheService, $resource, $logger, $storeManager);
        $this->salesData = $salesData;
    }

    protected function getIntentCode(): string
    {
        return 'dashboard_sales_v6';
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    protected function buildPayload(array $filters): array
    {
        return $this->salesData->fetch($filters);
    }
}