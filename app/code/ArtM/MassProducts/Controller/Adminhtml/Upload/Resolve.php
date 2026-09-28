<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Upload;

use ArtM\MassProducts\Model\AttributeSetResolver;
use ArtM\MassProducts\Model\UploadFileReader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Resolve extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $directoryList;
    private $uploadFileReader;
    private $attributeSetResolver;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DirectoryList $directoryList,
        UploadFileReader $uploadFileReader,
        AttributeSetResolver $attributeSetResolver
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->uploadFileReader = $uploadFileReader;
        $this->attributeSetResolver = $attributeSetResolver;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $uploadId = (int)$this->getRequest()->getParam('upload_id');
            if ($uploadId <= 0) {
                throw new LocalizedException(__('Invalid upload ID.'));
            }

            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_intake_uploads');
            $upload = $connection->fetchRow(
                $connection->select()->from($table)->where('id = ?', $uploadId)
            );

            if (!$upload || empty($upload['filename'])) {
                throw new LocalizedException(__('Uploaded file record not found.'));
            }

            $filename = (string)$upload['filename'];
            $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
            $path = rtrim($this->directoryList->getPath(DirectoryList::VAR_DIR), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'artm_massupload' . DIRECTORY_SEPARATOR . $filename;

            if (!is_file($path)) {
                throw new LocalizedException(__('Uploaded file is missing from storage.'));
            }

            $csvColumns = $this->uploadFileReader->extractHeaderColumns($path, $extension);
            $attributeSetValues = $this->uploadFileReader->extractColumnValues($path, $extension, 'attribute_set');
            $resolved = $this->attributeSetResolver->resolve($csvColumns, $attributeSetValues);

            return $result->setData([
                'success' => true,
                'require_attribute_set' => (bool)$resolved['require_attribute_set'],
                'attribute_sets' => $resolved['attribute_sets'],
                'unknown_sets' => $resolved['unknown_sets'],
                'csv_columns' => $csvColumns,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
