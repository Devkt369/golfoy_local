<?php
namespace ArtM\MassProducts\Controller\Adminhtml\CategoryReorder;

use ArtM\MassProducts\Model\CategoryReorder\Manager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class History extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private Manager $manager;

    public function __construct(Context $context, Manager $manager)
    {
        parent::__construct($context);
        $this->manager = $manager;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $categoryId = (int)$this->getRequest()->getParam('category_id');
            $page = (int)$this->getRequest()->getParam('page', 1);
            $pageSize = (int)$this->getRequest()->getParam('page_size', 20);

            return $result->setData([
                'success' => true,
                'history' => $this->manager->getHistory($categoryId, $page, $pageSize),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
