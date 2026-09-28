<?php
namespace ArtM\CategoryPage\Controller\Adminhtml\Ajax;

use ArtM\CategoryPage\Model\ConfigRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class SaveSort extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Backend::admin';

    private $configRepository;

    public function __construct(Context $context, ConfigRepository $configRepository)
    {
        parent::__construct($context);
        $this->configRepository = $configRepository;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $scope = (string)$this->getRequest()->getParam('scope', '');
            $items = (array)$this->getRequest()->getParam('items', []);
            $this->configRepository->saveSortOrders($scope, $items);
            return $result->setData(['success' => true, 'message' => __('Sorting saved.')]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
