<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Page;

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

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ResourceConnection $resourceConnection
     */
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
     * Save page JSON endpoint.
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
        $title = trim((string)($data['title'] ?? ''));
        $identifier = trim((string)($data['identifier'] ?? ''));
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $pageId = isset($data['page_id']) ? (int)$data['page_id'] : 0;

        if ($title === '' || $identifier === '') {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Title and Identifier are required.'),
            ]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_pages');

            if (!$connection->isTableExists($table)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Table %1 does not exist. Run setup:upgrade first.', $table),
                ]);
            }

            $bind = [
                'title' => $title,
                'identifier' => $identifier,
                'sort_order' => $sortOrder,
            ];

            if ($pageId > 0) {
                $connection->update($table, $bind, ['page_id = ?' => $pageId]);
            } else {
                $connection->insert($table, $bind);
                $pageId = (int)$connection->lastInsertId($table);
            }

            return $resultJson->setData([
                'success' => true,
                'message' => __('Page saved successfully.'),
                'page_id' => $pageId,
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to save page: %1', $e->getMessage()),
            ]);
        }
    }
}
