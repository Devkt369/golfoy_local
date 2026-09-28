<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\Import\DraftCreator;
use ArtM\MassProducts\Model\Logger\ActivityLogger;
use ArtM\MassProducts\Model\UploadFileReader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Create extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';
    private const LOG_TIMEZONE = 'Asia/Kolkata';

    private $resource;
    private $directoryList;
    private $fileReader;
    private $draftCreator;
    private $activityLogger;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DirectoryList $directoryList,
        UploadFileReader $fileReader,
        DraftCreator $draftCreator,
        ActivityLogger $activityLogger
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->fileReader = $fileReader;
        $this->draftCreator = $draftCreator;
        $this->activityLogger = $activityLogger;
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

            // Load upload record
            $uploadsTable = $connection->getTableName('artm_intake_uploads');
            $upload = $connection->fetchRow(
                $connection->select()->from($uploadsTable)->where('id = ?', $uploadId)
            );
            if (!$upload || empty($upload['filename'])) {
                throw new LocalizedException(__('Uploaded file record not found.'));
            }

            $filename  = (string)$upload['filename'];
            $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
            $path = rtrim($this->directoryList->getPath(DirectoryList::VAR_DIR), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'artm_massupload' . DIRECTORY_SEPARATOR . $filename;

            if (!is_file($path)) {
                throw new LocalizedException(__('Uploaded file is missing from storage.'));
            }

            // Load saved column mappings for this upload
            $mappingTable = $connection->getTableName('artm_massupload_mapping');
            $mappingColumns = $connection->describeTable($mappingTable);
            $selectCols = ['csv_column', 'attribute_code'];
            if (isset($mappingColumns['is_new'])) {
                $selectCols[] = 'is_new';
            }
            $mappingRows = $connection->fetchAll(
                $connection->select()
                    ->from($mappingTable, $selectCols)
                    ->where('upload_id = ?', $uploadId)
            );

            if (empty($mappingRows)) {
                throw new LocalizedException(__('No column mappings found for this upload. Please map columns first.'));
            }

            // Build [csv_column => attribute_code] map
            $columnMap = [];
            foreach ($mappingRows as $row) {
                $csvCol  = trim((string)$row['csv_column']);
                $attrCode = trim((string)$row['attribute_code']);
                if ($csvCol !== '' && $attrCode !== '') {
                    $columnMap[$csvCol] = $attrCode;
                }
            }

            // Read all data rows from the file
            $rows = $this->fileReader->extractRows($path, $extension);
            if (empty($rows)) {
                throw new LocalizedException(__('No data rows found in the uploaded file.'));
            }

            // Generate unique batch ID
            $batchId = uniqid('artm_', true);
            if ($batchId === '') {
                throw new LocalizedException(__('Unable to generate batch_id for this draft.'));
            }

            $user = $this->_auth->getUser();
            $adminUserId = $user ? (int)$user->getId() : 0;
            $adminUsername = $user ? (string)$user->getUserName() : 'system';

            // TODO: Required attribute validation will run during QUEUE stage, not draft stage.
            // Draft rows are saved as-is — missing/empty attributes are allowed.
            $summary = $this->draftCreator->createDraft($rows, $columnMap, $batchId, $adminUsername);

            $mappingSummary = $this->buildMappedAttributesSummary($mappingRows, $connection, true);
            $savedCount = (int)($summary['saved'] ?? 0);
            $sheetName = $filename;
            $detail = $adminUsername . ' uploaded the sheet ' . $sheetName . ' with ' . $savedCount . ' products';
            if ($mappingSummary !== '') {
                $detail .= "\n" . 'New mapped attributes in this upload: ' . $mappingSummary;
            }

            $this->activityLogger->log([
                'action_type' => 'upload_sheet',
                'upload_id' => $uploadId,
                'sheet_name' => $sheetName,
                'product_count' => $savedCount,
                'admin_user_id' => $adminUserId,
                'admin_username' => $adminUsername,
                'details' => $detail,
                'comment' => 'Upload draft creation completed.',
                'occurred_at' => (new \DateTime('now', new \DateTimeZone(self::LOG_TIMEZONE)))->format('Y-m-d H:i:s'),
            ]);

            return $result->setData([
                'success'   => true,
                'batch_id'  => $batchId,
                'saved'     => (int)($summary['saved'] ?? 0),
                'created_count' => (int)($summary['created_count'] ?? 0),
                'update_count' => (int)($summary['update_count'] ?? 0),
                'message'   => 'Draft created successfully',
            ]);

        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function buildMappedAttributesSummary(
        array $mappingRows,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        bool $onlyNew = false
    ): string
    {
        if (empty($mappingRows)) {
            return '';
        }

        $attributeCodes = [];
        foreach ($mappingRows as $row) {
            $code = trim((string)($row['attribute_code'] ?? ''));
            if ($code !== '') {
                $attributeCodes[] = $code;
            }
        }
        $attributeCodes = array_values(array_unique($attributeCodes));

        $labelsByCode = [];
        if (!empty($attributeCodes)) {
            $eavAttrTable = $connection->getTableName('eav_attribute');
            $labelRows = $connection->fetchAll(
                $connection->select()
                    ->from($eavAttrTable, ['attribute_code', 'frontend_label'])
                    ->where('attribute_code IN (?)', $attributeCodes)
            );
            foreach ($labelRows as $labelRow) {
                $code = trim((string)($labelRow['attribute_code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $label = trim((string)($labelRow['frontend_label'] ?? ''));
                $labelsByCode[$code] = $label !== '' ? $label : $code;
            }
        }

        $parts = [];
        foreach ($mappingRows as $row) {
            if ($onlyNew && array_key_exists('is_new', $row) && (int)($row['is_new'] ?? 0) !== 1) {
                continue;
            }
            $csvColumn = trim((string)($row['csv_column'] ?? ''));
            $code = trim((string)($row['attribute_code'] ?? ''));
            if ($csvColumn === '' || $code === '') {
                continue;
            }
            $label = $labelsByCode[$code] ?? $code;
            $parts[] = $csvColumn . ' => ' . $label . '(' . $code . ')';
        }

        return implode(' | ', $parts);
    }
}
