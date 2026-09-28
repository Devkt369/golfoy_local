<?php
namespace ArtM\CategoryPage\Controller\Ajax;

use ArtM\CategoryPage\Api\CategoryProviderInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Load extends Action
{
    private $categoryProvider;

    public function __construct(Context $context, CategoryProviderInterface $categoryProvider)
    {
        parent::__construct($context);
        $this->categoryProvider = $categoryProvider;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $categoryId = (int)$this->getRequest()->getParam('category_id', 0);
            $storeId = (int)$this->getRequest()->getParam('store_id', 0);
            $payload = $this->categoryProvider->getShowcasePayload($categoryId, $storeId > 0 ? $storeId : null, false);
            return $result->setData($payload);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
