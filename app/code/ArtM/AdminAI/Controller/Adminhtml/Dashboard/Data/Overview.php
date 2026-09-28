<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Dashboard\Data;

use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use ArtM\AdminAI\Model\ResourceModel\Dashboard\OverviewData;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Overview extends Base
{
    /** @var OverviewData */
    private $overviewData;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AnalyticsCacheServiceInterface $analyticsCacheService,
        ResourceConnection $resource,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        OverviewData $overviewData
    ) {
        parent::__construct($context, $resultJsonFactory, $analyticsCacheService, $resource, $logger, $storeManager);
        $this->overviewData = $overviewData;
    }

    protected function getIntentCode(): string
    {
        return 'dashboard_overview';
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    protected function buildPayload(array $filters): array
    {
        return $this->overviewData->fetch($filters);
    }
}