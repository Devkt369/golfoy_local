<?php
namespace ArtM\CategoryPage\Controller\Adminhtml\Ajax;

use ArtM\CategoryPage\Model\CategoryProvider;
use ArtM\CategoryPage\Model\ConfigRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class SaveManual extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Backend::admin';

    private $configRepository;
    private $categoryProvider;

    public function __construct(Context $context, ConfigRepository $configRepository, CategoryProvider $categoryProvider)
    {
        parent::__construct($context);
        $this->configRepository = $configRepository;
        $this->categoryProvider = $categoryProvider;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {

            $data = (array)$this->getRequest()->getParam('data', []);
            $isManualGroup = (bool)($data['is_manual_group'] ?? false);

            if ($isManualGroup) {
                $groupId = $this->configRepository->saveManualGroup($data);
                $this->categoryProvider->clearCacheByCategoryId((int)($data['category_id'] ?? 0));
                return $result->setData(['success' => true, 'group_id' => $groupId, 'message' => __('Group created successfully.')]);
            }

            $entityId = $this->configRepository->saveManualCategory($data);
            $this->categoryProvider->clearCacheByCategoryId((int)($data['parent_category_id'] ?? 0));
            return $result->setData(['success' => true, 'entity_id' => $entityId, 'message' => __('Manual category saved.')]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
