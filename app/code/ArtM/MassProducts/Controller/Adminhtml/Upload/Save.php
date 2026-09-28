<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Upload;

use ArtM\MassProducts\Model\UploadFileReader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var UploadFileReader
     */
    private $uploadFileReader;

    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        File $fileDriver,
        ResourceConnection $resource,
        UploadFileReader $uploadFileReader
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->resource = $resource;
        $this->uploadFileReader = $uploadFileReader;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            if (empty($_FILES['upload_file']) || !isset($_FILES['upload_file']['tmp_name'])) {
                throw new LocalizedException(__('No file uploaded.'));
            }

            $file = $_FILES['upload_file'];
            if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new LocalizedException(__('File upload failed.'));
            }

            $originalName = (string)$file['name'];
            $tmpPath = (string)$file['tmp_name'];
            $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, ['csv', 'xlsx'], true)) {
                throw new LocalizedException(__('Invalid file type. Only CSV and XLSX are allowed.'));
            }

            $targetDir = rtrim($this->directoryList->getPath(DirectoryList::VAR_DIR), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'artm_massupload';

            if (!$this->fileDriver->isExists($targetDir)) {
                $this->fileDriver->createDirectory($targetDir, 0755);
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', (string)pathinfo($originalName, PATHINFO_FILENAME));
            $safeName = date('dmY_His') . '.' . $extension;
            if ($this->fileDriver->isExists($targetDir . DIRECTORY_SEPARATOR . $safeName)) {
                $safeName = date('dmY_His') . '_' . mt_rand(1000, 9999) . '.' . $extension;
            }
            $savedPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

            if (!$this->fileDriver->copy($tmpPath, $savedPath)) {
                throw new LocalizedException(__('Unable to save uploaded file.'));
            }

            $columns = $this->uploadFileReader->extractHeaderColumns($savedPath, $extension);

            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_intake_uploads');
            $tableColumns = $connection->describeTable($table);
            $user = $this->_auth->getUser();
            $username = $user ? (string)$user->getUserName() : 'system';

            $insertData = [
                'filename' => $safeName,
                'total_rows' => null,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ];
            if (isset($tableColumns['original_filename'])) {
                $insertData['original_filename'] = $originalName;
            }
            if (isset($tableColumns['uploaded_by'])) {
                $insertData['uploaded_by'] = $username;
            }

            $connection->insert($table, $insertData);

            $uploadId = (int)$connection->lastInsertId($table);

            $autoMappingMap = $this->buildAutoMappingMap($columns, $connection);
            $autoMapped = [];
            $unmapped = [];
            foreach ($columns as $column) {
                if (isset($autoMappingMap[$column])) {
                    $autoMapped[] = $column;
                } else {
                    $unmapped[] = $column;
                }
            }

            return $result->setData([
                'success' => true,
                'upload_id' => $uploadId,
                'columns' => $columns,
                'auto_mapped' => $autoMapped,
                'unmapped' => $unmapped,
                'auto_mapping_map' => $autoMappingMap,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function buildAutoMappingMap(array $headers, \Magento\Framework\DB\Adapter\AdapterInterface $connection): array
    {
        if (empty($headers)) {
            return [];
        }

        $table = $connection->getTableName('artm_massproducts_column_mapping');
        $rows = $connection->fetchAll(
            $connection->select()->from($table, ['csv_column', 'attribute_code'])
        );

        $globalMap = [];
        foreach ($rows as $row) {
            $csvColumn = trim((string)($row['csv_column'] ?? ''));
            $attributeCode = trim((string)($row['attribute_code'] ?? ''));
            if ($csvColumn === '' || $attributeCode === '') {
                continue;
            }
            $globalMap[$this->normalizeKey($csvColumn)] = $attributeCode;
        }

        $result = [];
        foreach ($headers as $header) {
            $normalized = $this->normalizeKey((string)$header);
            if (isset($globalMap[$normalized])) {
                $result[(string)$header] = $globalMap[$normalized];
            }
        }

        return $result;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

}
