<?php
namespace ArtM\CategoryPage\Controller\Adminhtml\Ajax;

use ArtM\CategoryPage\Model\CategoryProvider;
use ArtM\CategoryPage\Model\ConfigRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class ResetCategory extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CategoryPage::manage_categories';

    private $configRepository;
    private $categoryProvider;

    public function __construct(
        Context $context,
        ConfigRepository $configRepository,
        CategoryProvider $categoryProvider
    ) {
        parent::__construct($context);
        $this->configRepository = $configRepository;
        $this->categoryProvider = $categoryProvider;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $categoryId = (int)$this->getRequest()->getParam('category_id', 0);
            if ($categoryId <= 0) {
                throw new \InvalidArgumentException('Invalid category id.');
            }

            $this->configRepository->resetCategoryData($categoryId);
            $this->categoryProvider->clearCacheByCategoryId($categoryId);

            return $result->setData([
                'success' => true,
                'message' => __('Category reset to default successfully.')
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
