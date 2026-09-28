<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class ImageAdd extends Action
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
     * Add one or more images to a group.
     *
     * POST params:
     *   group_id  int
     *   paths[]   array of relative image paths (relative to pub/media/pagegroups/)
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
        $paths = $this->getRequest()->getParam('paths');

        // Ensure paths is always an array
        if (is_string($paths)) {
            $paths = trim($paths) !== '' ? [$paths] : [];
        } elseif (!is_array($paths)) {
            $paths = [];
        }

        if (!$paths) {
            $singlePath = trim((string)$this->getRequest()->getParam('image_path'));
            $paths = $singlePath !== '' ? [$singlePath] : [];
        }

        if ($groupId <= 0 || empty($paths)) {
            return $resultJson->setData(['success' => false, 'message' => __('group_id and at least one path are required.')]);
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

            $maxSort = (int)$connection->fetchOne(
                $connection->select()
                    ->from($table, new \Zend_Db_Expr('COALESCE(MAX(sort_order), 0)'))
                    ->where('group_id = ?', $groupId)
            );

            foreach ($paths as $path) {
                $path = trim((string)$path);
                if ($path === '') {
                    continue;
                }
                // Avoid duplicate paths in the same group
                $exists = (int)$connection->fetchOne(
                    $connection->select()
                        ->from($table, new \Zend_Db_Expr('COUNT(1)'))
                        ->where('group_id = ?', $groupId)
                        ->where('image_path = ?', $path)
                );
                if ($exists) {
                    continue;
                }
                $maxSort += 10;
                $connection->insert($table, [
                    'group_id' => $groupId,
                    'image_path' => $path,
                    'sort_order' => $maxSort,
                ]);
            }

            return $resultJson->setData(['success' => true, 'message' => __('Images added.')]);
        } catch (\Throwable $e) {
            return $resultJson->setData(['success' => false, 'message' => __('Error: %1', $e->getMessage())]);
        }
    }
}
