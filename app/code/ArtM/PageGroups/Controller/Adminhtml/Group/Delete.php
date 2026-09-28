<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class Delete extends Action
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
     * Delete group.
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

        $groupId = (int)$this->getRequest()->getParam('group_id');
        if ($groupId <= 0) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid group_id.'),
            ]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_groups');
            $connection->delete($table, ['group_id = ?' => $groupId]);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Group deleted successfully.'),
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to delete group: %1', $e->getMessage()),
            ]);
        }
    }
}
