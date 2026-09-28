<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class ImageSort extends Action
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
     * Persist image sort order within a group.
     *
     * POST: group_id, image_ids[]
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData(['success' => false, 'message' => __('Invalid request method.')]);
        }

        $groupId = (int)$this->getRequest()->getParam('group_id');
        $imageIds = (array)$this->getRequest()->getParam('image_ids', []);

        if ($groupId <= 0 || empty($imageIds)) {
            return $resultJson->setData(['success' => false, 'message' => __('Invalid image sort payload.')]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $imageTable = $this->resourceConnection->getTableName('artm_pg_group_images');

            if (!$connection->isTableExists($imageTable)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Table %1 does not exist. Run setup:upgrade first.', $imageTable),
                ]);
            }

            $connection->beginTransaction();
            foreach ($imageIds as $index => $imageId) {
                $connection->update(
                    $imageTable,
                    ['sort_order' => ($index + 1) * 10],
                    ['image_id = ?' => (int)$imageId, 'group_id = ?' => $groupId]
                );
            }
            $connection->commit();

            return $resultJson->setData(['success' => true, 'message' => __('Image order saved.')]);
        } catch (\Throwable $e) {
            try {
                $this->resourceConnection->getConnection()->rollBack();
            } catch (\Throwable $rollbackException) {
                // no-op
            }

            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to sort images: %1', $e->getMessage()),
            ]);
        }
    }
}
