<?php
namespace ArtM\MassProducts\Controller\Adminhtml\CategoryReorder;

use ArtM\MassProducts\Model\CategoryReorder\Manager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Confirm extends Action
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
            $historyId = (int)$this->getRequest()->getParam('history_id');
            if ($categoryId <= 0 || $historyId <= 0) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid request payload.'));
            }

            $user = $this->_auth->getUser();
            $username = $user ? (string)$user->getUserName() : 'system';

            $summary = $this->manager->applyHistory($historyId, $categoryId, $username);

            return $result->setData([
                'success' => true,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
