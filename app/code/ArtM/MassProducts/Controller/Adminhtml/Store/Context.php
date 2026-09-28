<?php
declare(strict_types=1);

namespace ArtM\MassProducts\Controller\Adminhtml\Store;

use ArtM\MassProducts\Model\Store\UserStorePreference;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;

class Context extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private UserStorePreference $userStorePreference;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ActionContext $context,
        UserStorePreference $userStorePreference,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->userStorePreference = $userStorePreference;
        $this->storeManager = $storeManager;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $requestedStoreId = (int)$this->getRequest()->getParam('store_id', -1);
            if ($requestedStoreId >= 0) {
                $storeId = $this->normalizeAllowedStoreId($requestedStoreId);
                $this->userStorePreference->saveSelectedStoreId($storeId);
            }

            $selectedStoreId = $this->normalizeAllowedStoreId($this->userStorePreference->getSelectedStoreId());

            return $result->setData([
                'success' => true,
                'selected_store_id' => $selectedStoreId,
                'options' => $this->buildStoreOptions(),
            ]);
        } catch (\Throwable $e) {
            $this->getResponse()->setHttpResponseCode(500);
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeAllowedStoreId(int $storeId): int
    {
        if ($storeId <= 0) {
            return 0;
        }

        try {
            $store = $this->storeManager->getStore($storeId);
            return (int)$store->getId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function buildStoreOptions(): array
    {
        $options = [
            [
                'value' => 0,
                'label' => 'All Store Views',
                'disabled' => false,
            ],
        ];

        foreach ($this->storeManager->getWebsites() as $website) {
            $options[] = [
                'value' => '',
                'label' => (string)$website->getName(),
                'disabled' => true,
            ];

            foreach ($website->getGroups() as $group) {
                $options[] = [
                    'value' => '',
                    'label' => '  ' . (string)$group->getName(),
                    'disabled' => true,
                ];

                foreach ($group->getStores() as $store) {
                    $options[] = [
                        'value' => (int)$store->getId(),
                        'label' => '    ' . (string)$store->getName(),
                        'disabled' => false,
                    ];
                }
            }
        }

        return $options;
    }
}
