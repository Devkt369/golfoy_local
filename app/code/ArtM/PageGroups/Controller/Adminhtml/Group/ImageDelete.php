<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class ImageDelete extends Action
{
    const ADMIN_RESOURCE = 'ArtM_PageGroups::main';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Remove an image from a group by image_id.
     *
     * POST params:
     *   image_id  int
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData(['success' => false, 'message' => __('Invalid request method.')]);
        }

        $imageId = (int)$this->getRequest()->getParam('image_id');

        if ($imageId <= 0) {
            return $resultJson->setData(['success' => false, 'message' => __('image_id is required.')]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_group_images');

            if (!$connection->isTableExists($table)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Table %1 does not exist. Run setup:upgrade first.', $table),
                ]);
            }

            $connection->delete($table, ['image_id = ?' => $imageId]);

            return $resultJson->setData(['success' => true, 'message' => __('Image removed.')]);
        } catch (\Throwable $e) {
            return $resultJson->setData(['success' => false, 'message' => __('Error: %1', $e->getMessage())]);
        }
    }
}
