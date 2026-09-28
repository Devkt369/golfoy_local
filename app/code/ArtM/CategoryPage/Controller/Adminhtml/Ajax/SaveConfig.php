<?php
namespace ArtM\CategoryPage\Controller\Adminhtml\Ajax;

use ArtM\CategoryPage\Model\CategoryProvider;
use ArtM\CategoryPage\Model\ConfigRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class SaveConfig extends Action
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
            // echo "<pre>";
            // print_r($this->getRequest()->getParams());
            // die;
            $data = (array)$this->getRequest()->getParam('data', []);
            $this->configRepository->saveCategoryConfig($data);
            $this->categoryProvider->clearCacheByCategoryId((int)($data['category_id'] ?? 0));
            return $result->setData(['success' => true, 'message' => __('Category configuration saved.')]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
