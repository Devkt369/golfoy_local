<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Upload;

use ArtM\MassProducts\Model\Import\AttributeSetValidator;
use ArtM\MassProducts\Model\UploadFileReader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Validate extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $directoryList;
    private $fileReader;
    private $validator;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DirectoryList $directoryList,
        UploadFileReader $fileReader,
        AttributeSetValidator $validator
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->fileReader = $fileReader;
        $this->validator = $validator;
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

            $headers = $this->fileReader->extractHeaderColumns($path, $extension);
            $headerValidation = $this->validator->validateHeaders($headers);
            if (!$headerValidation['valid']) {
                return $result->setData([
                    'success' => false,
                    'type' => (string)($headerValidation['type'] ?? 'validation_error'),
                    'message' => (string)($headerValidation['message'] ?? __('Validation failed.')),
                ]);
            }

            $rows = $this->fileReader->extractRows($path, $extension);
            $validation = $this->validator->validateRows($rows);
            $skuValidation = $this->validateSkuRows($rows, $connection);

            if (!empty($skuValidation['errors'])) {
                $validation['errors'] = array_merge((array)($validation['errors'] ?? []), $skuValidation['errors']);
            }

            $invalidRowsByNumber = [];
            foreach ((array)($validation['errors'] ?? []) as $item) {
                $rowNumber = (int)($item['row'] ?? 0);
                if ($rowNumber > 0) {
                    $invalidRowsByNumber[$rowNumber] = true;
                }
            }
            $validation['invalid_rows'] = count($invalidRowsByNumber);
            $validation['valid_rows'] = max(0, (int)$validation['total_rows'] - (int)$validation['invalid_rows']);

            $missingRows = $validation['missing_attribute_set_rows'] ?? [];
            if (!empty($missingRows)) {
                $rowList = implode(',', array_map('intval', $missingRows));
                return $result->setData([
                    'success' => false,
                    'type' => 'attribute_set_missing_rows',
                    'rows' => $missingRows,
                    'message' => 'rows ' . $rowList . ' does not have attribute_Set, please add those and try uploading again',
                ]);
            }

            return $result->setData([
                'success' => true,
                'csv_columns' => $headers,
                'attribute_set_validation' => [
                    'total_rows' => (int)$validation['total_rows'],
                    'valid_rows' => (int)$validation['valid_rows'],
                    'invalid_rows' => (int)$validation['invalid_rows'],
                    'errors' => $validation['errors'],
                ],
                'attribute_set_id' => $validation['attribute_set_id'],
                'attribute_set_name' => $validation['attribute_set_name'],
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function validateSkuRows(array $rows, \Magento\Framework\DB\Adapter\AdapterInterface $connection): array
    {
        $errors = [];
        $errorSignatureMap = [];
        $invalidRowMap = [];
        $skuRows = [];

        foreach ($rows as $row) {
            $rowNumber = (int)($row['row_number'] ?? 0);
            $data = isset($row['data']) && is_array($row['data']) ? $row['data'] : [];
            $sku = $this->extractSku($data);

            if ($sku === '') {
                $this->addRowError($errors, $errorSignatureMap, $invalidRowMap, $rowNumber, $sku, 'SKU is empty.');
                continue;
            }

            $normalized = strtolower($sku);
            if (!isset($skuRows[$normalized])) {
                $skuRows[$normalized] = [];
            }
            $skuRows[$normalized][] = [
                'row' => $rowNumber,
                'sku' => $sku,
            ];
        }

        foreach ($skuRows as $entries) {
            if (count($entries) <= 1) {
                continue;
            }
            foreach ($entries as $entry) {
                $this->addRowError(
                    $errors,
                    $errorSignatureMap,
                    $invalidRowMap,
                    (int)$entry['row'],
                    (string)$entry['sku'],
                    'Duplicate SKU in uploaded file. SKU must be unique.'
                );
            }
        }

        if (!empty($skuRows)) {
            $skuValues = [];
            foreach ($skuRows as $entries) {
                $skuValues[] = (string)$entries[0]['sku'];
            }

            $catalogSkuTable = $connection->getTableName('catalog_product_entity');
            $catalogSkus = $connection->fetchCol(
                $connection->select()
                    ->from($catalogSkuTable, ['sku'])
                    ->where('sku IN (?)', $skuValues)
            );
            $catalogMap = [];
            foreach ($catalogSkus as $catalogSku) {
                $catalogMap[strtolower(trim((string)$catalogSku))] = true;
            }

            $draftSkuMap = [];
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            if ($connection->isTableExists($draftTable)) {
                // Rows already marked 'success' are excluded: the product they
                // represent may since have been deleted from Magento, and the
                // catalogMap check above already blocks re-upload for SKUs that
                // genuinely still exist.
                $draftSkus = $connection->fetchCol(
                    $connection->select()
                        ->from($draftTable, ['sku'])
                        ->where('sku IN (?)', $skuValues)
                        ->where('status != ?', 'success')
                );
                foreach ($draftSkus as $draftSku) {
                    $draftSkuMap[strtolower(trim((string)$draftSku))] = true;
                }
            }

            foreach ($skuRows as $normalized => $entries) {
                foreach ($entries as $entry) {
                    if (isset($catalogMap[$normalized])) {
                        $this->addRowError(
                            $errors,
                            $errorSignatureMap,
                            $invalidRowMap,
                            (int)$entry['row'],
                            (string)$entry['sku'],
                            'SKU already exists in Magento products.'
                        );
                    }
                    if (isset($draftSkuMap[$normalized])) {
                        $this->addRowError(
                            $errors,
                            $errorSignatureMap,
                            $invalidRowMap,
                            (int)$entry['row'],
                            (string)$entry['sku'],
                            'SKU already exists in Draft products.'
                        );
                    }
                }
            }
        }

        usort($errors, static function (array $a, array $b): int {
            return (int)($a['row'] ?? 0) <=> (int)($b['row'] ?? 0);
        });
        ksort($invalidRowMap);

        return [
            'errors' => $errors,
            'invalid_row_numbers' => array_keys($invalidRowMap),
        ];
    }

    private function extractSku(array $data): string
    {
        foreach ($data as $key => $value) {
            $normalizedKey = strtolower(trim(preg_replace('/\s+/', ' ', (string)$key)));
            if ($normalizedKey === 'sku') {
                return trim((string)$value);
            }
        }

        return '';
    }

    private function addRowError(
        array &$errors,
        array &$errorSignatureMap,
        array &$invalidRowMap,
        int $row,
        string $sku,
        string $message
    ): void
    {
        $signature = $row . '|' . $message;
        if (isset($errorSignatureMap[$signature])) {
            return;
        }

        $errorSignatureMap[$signature] = true;
        if ($row > 0) {
            $invalidRowMap[$row] = true;
        }
        $errors[] = [
            'row' => $row,
            'sku' => $sku,
            'message' => $message,
        ];
    }
}
