<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Dashboard\Data;

use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use ArtM\AdminAI\Model\ResourceModel\Dashboard\CustomerData;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Customer extends Base
{
    /** @var CustomerData */
    private $customerData;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AnalyticsCacheServiceInterface $analyticsCacheService,
        ResourceConnection $resource,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        CustomerData $customerData
    ) {
        parent::__construct($context, $resultJsonFactory, $analyticsCacheService, $resource, $logger, $storeManager);
        $this->customerData = $customerData;
    }

    protected function getIntentCode(): string
    {
        return 'dashboard_customers_v9';
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    protected function buildPayload(array $filters): array
    {
        return $this->customerData->fetch($filters);
    }
}