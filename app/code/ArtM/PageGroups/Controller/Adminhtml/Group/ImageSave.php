<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class ImageSave extends Action
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
     * Save image visibility settings.
     *
     * POST fields:
     *  image_id (required)
     *  visibility_mode (keep/show/hide)
     *  start_at (nullable)
     *  end_at (nullable)
     *  is_hidden (0|1)
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

            $row = $connection->fetchRow(
                $connection->select()
                    ->from($table, ['image_id', 'visibility_mode', 'start_at', 'end_at', 'is_hidden'])
                    ->where('image_id = ?', $imageId)
                    ->limit(1)
            );

            if (!$row) {
                return $resultJson->setData(['success' => false, 'message' => __('Image not found.')]);
            }

            $visibilityMode = strtolower((string)$this->getRequest()->getParam('visibility_mode', $row['visibility_mode']));
            if (!in_array($visibilityMode, ['keep', 'show', 'hide'], true)) {
                $visibilityMode = 'keep';
            }

            $isHidden = $this->getRequest()->getParam('is_hidden', $row['is_hidden']);
            $isHidden = (int)((string)$isHidden === '1' ? 1 : 0);

            $startAtRaw = $this->getRequest()->getParam('start_at', $row['start_at']);
            $endAtRaw = $this->getRequest()->getParam('end_at', $row['end_at']);
            $startAt = $this->normalizeDatetime($startAtRaw);
            $endAt = $this->normalizeDatetime($endAtRaw);

            if ($startAt !== null && $endAt !== null && strtotime($startAt) > strtotime($endAt)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Start At must be less than or equal to End At.'),
                ]);
            }

            $bind = [
                'visibility_mode' => $visibilityMode,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'is_hidden' => $isHidden,
            ];

            $connection->update($table, $bind, ['image_id = ?' => $imageId]);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Image visibility saved.'),
                'item' => array_merge(['image_id' => $imageId], $bind),
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to save image visibility: %1', $e->getMessage()),
            ]);
        }
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function normalizeDatetime($value)
    {
        $value = trim((string)$value);
        if ($value === '' || $value === 'null') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
