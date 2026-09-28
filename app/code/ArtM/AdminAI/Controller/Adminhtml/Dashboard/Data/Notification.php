<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Dashboard\Data;

use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use ArtM\AdminAI\Model\ResourceModel\Dashboard\NotificationData;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Notification extends Base
{
    /** @var NotificationData */
    private $notificationData;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AnalyticsCacheServiceInterface $analyticsCacheService,
        ResourceConnection $resource,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        NotificationData $notificationData
    ) {
        parent::__construct($context, $resultJsonFactory, $analyticsCacheService, $resource, $logger, $storeManager);
        $this->notificationData = $notificationData;
    }

    protected function getIntentCode(): string
    {
        return 'dashboard_notifications';
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    protected function buildPayload(array $filters): array
    {
        return $this->notificationData->fetch($filters);
    }
}