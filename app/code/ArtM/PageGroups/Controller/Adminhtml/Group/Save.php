<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class Save extends Action
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
     * Save group.
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

        $data = $this->getRequest()->getPostValue();
        $groupId = isset($data['group_id']) ? (int)$data['group_id'] : 0;
        $pageId = isset($data['page_id']) ? (int)$data['page_id'] : 0;
        $title = trim((string)($data['title'] ?? ''));
        $sortOrder = (int)($data['sort_order'] ?? 0);

        if ($pageId <= 0 || $title === '') {
            return $resultJson->setData([
                'success' => false,
                'message' => __('page_id and title are required.'),
            ]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_groups');

            if (!$connection->isTableExists($table)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Table %1 does not exist. Run setup:upgrade first.', $table),
                ]);
            }

            $bind = [
                'page_id' => $pageId,
                'title' => $title,
                'sort_order' => $sortOrder,
            ];

            if ($groupId > 0) {
                $connection->update($table, $bind, ['group_id = ?' => $groupId]);
            } else {
                if ($sortOrder === 0) {
                    $maxSort = (int)$connection->fetchOne(
                        $connection->select()
                            ->from($table, new \Zend_Db_Expr('COALESCE(MAX(sort_order), 0)'))
                            ->where('page_id = ?', $pageId)
                    );
                    $bind['sort_order'] = $maxSort + 10;
                }
                $connection->insert($table, $bind);
                $groupId = (int)$connection->lastInsertId($table);
            }

            return $resultJson->setData([
                'success' => true,
                'group_id' => $groupId,
                'message' => __('Group saved successfully.'),
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to save group: %1', $e->getMessage()),
            ]);
        }
    }
}
