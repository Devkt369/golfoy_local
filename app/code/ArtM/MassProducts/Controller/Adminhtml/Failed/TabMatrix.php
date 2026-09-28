<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Failed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;

class TabMatrix extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;

    public function __construct(Context $context, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');
            $mediaTable = $connection->getTableName('artm_massproducts_draft_media');
            $productEntityTable = $connection->getTableName('catalog_product_entity');
            $rows = $connection->fetchAll(
                $connection->select()->from($draftTable)->where('status = ?', 'failed')->order('entity_id ASC')
            );

            $skuList = array_values(array_filter(array_map(static function ($row) {
                return trim((string)($row['sku'] ?? ''));
            }, $rows)));
            $createdAtBySku = [];
            if (!empty($skuList) && $connection->isTableExists($productEntityTable)) {
                $createdRows = $connection->fetchAll(
                    $connection->select()
                        ->from($productEntityTable, ['sku', 'created_at'])
                        ->where('sku IN (?)', $skuList)
                );
                foreach ($createdRows as $createdRow) {
                    $sku = trim((string)($createdRow['sku'] ?? ''));
                    if ($sku === '') {
                        continue;
                    }
                    $createdAtBySku[$sku] = (string)($createdRow['created_at'] ?? '');
                }
            }

            $draftIds = array_values(array_filter(array_map(static function ($row) {
                return (int)($row['entity_id'] ?? 0);
            }, $rows)));

            $valuesByDraft = [];
            if (!empty($draftIds) && $connection->isTableExists($valueTable)) {
                $valueRows = $connection->fetchAll(
                    $connection->select()
                        ->from($valueTable, ['draft_id', 'attribute_code', 'value'])
                        ->where('draft_id IN (?)', $draftIds)
                        ->where('store_id = ?', 0)
                );

                foreach ($valueRows as $valueRow) {
                    $draftId = (int)$valueRow['draft_id'];
                    if (!isset($valuesByDraft[$draftId])) {
                        $valuesByDraft[$draftId] = [];
                    }
                    $valuesByDraft[$draftId][(string)$valueRow['attribute_code']] = $valueRow['value'];
                }
            }

            $mediaByDraft = [];
            if (!empty($draftIds) && $connection->isTableExists($mediaTable)) {
                $mediaBaseUrl = rtrim(
                    $this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]),
                    '/'
                );
                $mediaRows = $connection->fetchAll(
                    $connection->select()
                        ->from($mediaTable, ['media_id', 'draft_id', 'file_path', 'roles', 'position'])
                        ->where('draft_id IN (?)', $draftIds)
                        ->order('draft_id ASC')
                        ->order('position ASC')
                );

                foreach ($mediaRows as $mediaRow) {
                    $did = (int)$mediaRow['draft_id'];
                    if (!isset($mediaByDraft[$did])) {
                        $mediaByDraft[$did] = [];
                    }
                    $rolesStr = (string)($mediaRow['roles'] ?? '');
                    $rolesArr = $rolesStr !== '' ? array_values(array_filter(explode(',', $rolesStr))) : [];
                    $filePath = (string)($mediaRow['file_path'] ?? '');
                    $mediaByDraft[$did][] = [
                        'media_id' => (int)$mediaRow['media_id'],
                        'file_path' => $filePath,
                        'url' => $mediaBaseUrl . '/' . ltrim($filePath, '/'),
                        'roles' => $rolesArr,
                        'position' => (int)$mediaRow['position'],
                    ];
                }
            }

            $msiSources = [];
            $sourceTable = $connection->getTableName('inventory_source');
            if ($connection->isTableExists($sourceTable)) {
                $sourceRows = $connection->fetchAll(
                    $connection->select()
                        ->from($sourceTable, ['source_code', 'name'])
                        ->where('source_code <> ?', 'default')
                        ->order('name ASC')
                );
                foreach ($sourceRows as $sourceRow) {
                    $msiSources[] = [
                        'source_code' => (string)$sourceRow['source_code'],
                        'name' => (string)$sourceRow['name'],
                    ];
                }
            }

            foreach ($rows as &$row) {
                $draftId = (int)($row['entity_id'] ?? 0);
                $mapped = [];
                if (!empty($row['mapped_data'])) {
                    $decoded = json_decode((string)$row['mapped_data'], true);
                    if (is_array($decoded)) {
                        $mapped = $decoded;
                    }
                }

                if (isset($valuesByDraft[$draftId])) {
                    foreach ($valuesByDraft[$draftId] as $code => $value) {
                        $mapped[(string)$code] = $value;
                    }
                }

                $rowMsiValues = [];
                if (isset($valuesByDraft[$draftId])) {
                    foreach ($valuesByDraft[$draftId] as $code => $value) {
                        $attributeCode = (string)$code;
                        if (strpos($attributeCode, 'msi_qty__') === 0 && trim((string)$value) !== '') {
                            $sourceCode = substr($attributeCode, 9);
                            if ($sourceCode !== '') {
                                $rowMsiValues[$sourceCode] = (string)$value;
                            }
                        }
                    }
                }

                $row['input_payload'] = $mapped;
                $row['input_payload_json'] = json_encode($mapped, JSON_UNESCAPED_UNICODE);
                $row['msi_values'] = $rowMsiValues;
                $row['media_items'] = $mediaByDraft[$draftId] ?? [];
                $row['product_created_at'] = $createdAtBySku[(string)($row['sku'] ?? '')] ?? '';
            }
            unset($row);

            return $result->setData(['success' => true, 'rows' => $rows, 'msi_sources' => $msiSources]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
