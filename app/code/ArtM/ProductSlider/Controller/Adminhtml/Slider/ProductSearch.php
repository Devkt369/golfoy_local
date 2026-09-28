<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Slider;

use ArtM\ProductSlider\Model\ProductEngine;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class ProductSearch extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::slider_save';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly ProductEngine $productEngine
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        try {
            $q = trim((string)$this->getRequest()->getParam('q', ''));
            $page = max(1, (int)$this->getRequest()->getParam('page', 1));
            $pageSize = max(1, min(100, (int)$this->getRequest()->getParam('page_size', 20)));
            $selected = (string)$this->getRequest()->getParam('selected', '');
            $selectedIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $selected)))));

            return $result->setData([
                'success' => true,
                'data' => $this->productEngine->searchProducts($q, $page, $pageSize, $selectedIds),
            ]);
        } catch (\Throwable $exception) {
            return $result->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
