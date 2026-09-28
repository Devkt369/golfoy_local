<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class Sort extends Action
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
     * Persist group sort order for a page.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid request method.'),
            ]);
        }

        $pageId = (int)$this->getRequest()->getParam('page_id');
        $groupIds = (array)$this->getRequest()->getParam('group_ids', []);

        if ($pageId <= 0 || empty($groupIds)) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid sort payload.'),
            ]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_groups');

            $connection->beginTransaction();
            foreach ($groupIds as $index => $groupId) {
                $connection->update(
                    $table,
                    ['sort_order' => ($index + 1) * 10],
                    ['group_id = ?' => (int)$groupId, 'page_id = ?' => $pageId]
                );
            }
            $connection->commit();

            return $resultJson->setData([
                'success' => true,
                'message' => __('Sort order updated.'),
            ]);
        } catch (\Throwable $e) {
            try {
                $this->resourceConnection->getConnection()->rollBack();
            } catch (\Throwable $rollbackException) {
                // no-op
            }

            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to sort groups: %1', $e->getMessage()),
            ]);
        }
    }
}
