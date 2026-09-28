<?php
namespace ArtM\CategoryPage\Controller\Adminhtml\Ajax;

use ArtM\CategoryPage\Model\CategoryProvider;
use ArtM\CategoryPage\Model\ConfigRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class DeleteManualGroup extends Action
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
            $entityId = (int)($data['entity_id'] ?? 0);
            $categoryId = (int)($data['category_id'] ?? 0);

            if ($entityId <= 0) {
                throw new \InvalidArgumentException('Invalid group.');
            }

            $this->configRepository->deleteManualGroup($entityId);
            $this->categoryProvider->clearCacheByCategoryId($categoryId);

            return $result->setData(['success' => true, 'message' => __('Group deleted.')]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
