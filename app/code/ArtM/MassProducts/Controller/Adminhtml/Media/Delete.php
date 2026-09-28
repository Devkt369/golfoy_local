<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Media;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use ArtM\MassProducts\Model\MediaTableManager;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private ResourceConnection $resource;
    private Filesystem $filesystem;
    private MediaTableManager $mediaTableManager;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        Filesystem $filesystem,
        MediaTableManager $mediaTableManager
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->filesystem = $filesystem;
        $this->mediaTableManager = $mediaTableManager;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $mediaId = (int)$this->getRequest()->getParam('media_id', 0);
            if ($mediaId <= 0) {
                return $result->setData(['success' => false, 'message' => 'Invalid media ID.']);
            }

            $connection = $this->resource->getConnection();
            if (!$this->mediaTableManager->exists()) {
                return $result->setData(['success' => false, 'message' => 'Media storage table is not initialized yet.']);
            }
            $table = $this->mediaTableManager->getTableName();

            $row = $connection->fetchRow(
                $connection->select()->from($table)->where('media_id = ?', $mediaId)
            );

            if (!$row) {
                return $result->setData(['success' => false, 'message' => 'Media record not found.']);
            }

            // Delete file from filesystem (only files under the expected temp path)
            $filePath = (string)($row['file_path'] ?? '');
            if ($filePath !== '' && strpos($filePath, 'artm_massproducts_temp/') === 0) {
                $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                if ($mediaDir->isExist($filePath)) {
                    $mediaDir->delete($filePath);
                }
            }

            $connection->delete($table, ['media_id = ?' => $mediaId]);

            return $result->setData(['success' => true]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
