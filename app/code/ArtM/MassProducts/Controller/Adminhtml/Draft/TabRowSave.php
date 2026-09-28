<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\Import\DraftCompletenessService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class TabRowSave extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $completenessService;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DraftCompletenessService $completenessService
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->completenessService = $completenessService;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $draftId = (int)$this->getRequest()->getParam('draft_id');
            $values = (array)$this->getRequest()->getParam('values', []);
            $parentSku = trim((string)$this->getRequest()->getParam('parent_sku', ''));
            $childSkusParam = $this->getRequest()->getParam('child_skus', []);
            $variationAttributesParam = $this->getRequest()->getParam('variation_attributes', []);

            if ($draftId <= 0) {
                throw new LocalizedException(__('Invalid draft id.'));
            }

            $variationAttributes = $this->normalizeVariationAttributes($variationAttributesParam);

            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');
            $hasDraftValuesTable = $connection->isTableExists($valueTable);

            $draft = $connection->fetchRow($connection->select()->from($draftTable)->where('entity_id = ?', $draftId));
            if (!$draft) {
                throw new LocalizedException(__('Draft not found.'));
            }

            $draftSku = trim((string)($draft['sku'] ?? ''));
            $newSku = $draftSku;
            if (array_key_exists('sku', $values)) {
                $newSku = trim(is_array($values['sku']) ? implode(',', $values['sku']) : (string)$values['sku']);
            }
            $childSkus = [];
            if (is_array($childSkusParam)) {
                foreach ($childSkusParam as $childSku) {
                    $sku = trim((string)$childSku);
                    if ($sku === '' || in_array($sku, $childSkus, true)) {
                        continue;
                    }
                    $childSkus[] = $sku;
                }
            } else {
                foreach (preg_split('/[\n,]+/', (string)$childSkusParam) as $childSku) {
                    $sku = trim((string)$childSku);
                    if ($sku === '' || in_array($sku, $childSkus, true)) {
                        continue;
                    }
                    $childSkus[] = $sku;
                }
            }

            if ($newSku !== '') {
                $duplicateDraftId = (int)$connection->fetchOne(
                    $connection->select()
                        ->from($draftTable, ['entity_id'])
                        ->where('sku = ?', $newSku)
                        ->where('entity_id <> ?', $draftId)
                        ->limit(1)
                );
                if ($duplicateDraftId > 0) {
                    throw new LocalizedException(__('SKU "%1" already exists in Draft.', $newSku));
                }
            }

            if ($newSku !== '') {
                if ($parentSku !== '' && $parentSku === $newSku) {
                    throw new LocalizedException(__('A product cannot be its own parent.'));
                }
                if (in_array($newSku, $childSkus, true)) {
                    throw new LocalizedException(__('A product cannot be its own child.'));
                }
            }

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $childSkuStored = implode(',', $childSkus);

            $schema = $connection->describeTable($draftTable);
            $hasParentSkuColumn = isset($schema['parent_sku']);
            $hasChildSkuColumn = isset($schema['child_sku']);

            $draftUpdateData = [
                'updated_at' => $now,
            ];

            $resolvedProductName = '';
            if (array_key_exists('name', $values)) {
                $resolvedProductName = trim(is_array($values['name']) ? implode(',', $values['name']) : (string)$values['name']);
            }
            if ($resolvedProductName === '' && array_key_exists('product_name', $values)) {
                $resolvedProductName = trim(is_array($values['product_name']) ? implode(',', $values['product_name']) : (string)$values['product_name']);
            }

            if ($newSku !== '' && $newSku !== $draftSku) {
                $draftUpdateData['sku'] = $newSku;
            }
            if ($resolvedProductName !== '') {
                $draftUpdateData['product_name'] = $resolvedProductName;
            }
            if ($hasParentSkuColumn) {
                $draftUpdateData['parent_sku'] = $parentSku;
            }
            if ($hasChildSkuColumn) {
                $draftUpdateData['child_sku'] = $childSkuStored;
            }

            // Keep raw_data synchronized for compatibility with older logic.
            $rawData = [];
            if (!empty($draft['raw_data'])) {
                $decodedRaw = json_decode((string)$draft['raw_data'], true);
                if (is_array($decodedRaw)) {
                    $rawData = $decodedRaw;
                }
            }
            $rawData['parent'] = $parentSku;
            $rawData['child'] = $childSkuStored;
            $rawData['variation_attributes'] = $variationAttributes;
            if ($newSku !== '') {
                $rawData['sku'] = $newSku;
            }
            $draftUpdateData['raw_data'] = json_encode($rawData, JSON_UNESCAPED_UNICODE);

            $connection->update(
                $draftTable,
                $draftUpdateData,
                ['entity_id = ?' => $draftId]
            );

            if ($hasDraftValuesTable) {
                foreach ($values as $code => $value) {
                    $attributeCode = trim((string)$code);
                    if ($attributeCode === '') {
                        continue;
                    }

                    $storedValue = is_array($value) ? implode(',', $value) : (string)$value;
                    if (trim($storedValue) === '') {
                        $connection->delete(
                            $valueTable,
                            [
                                'draft_id = ?' => $draftId,
                                'attribute_code = ?' => $attributeCode,
                                'store_id = ?' => 0,
                            ]
                        );
                        continue;
                    }
                    $connection->insertOnDuplicate(
                        $valueTable,
                        [
                            'draft_id' => $draftId,
                            'attribute_code' => $attributeCode,
                            'value' => $storedValue,
                            'store_id' => 0,
                            'updated_at' => $now,
                        ],
                        ['value', 'updated_at']
                    );
                }
            } else {
                $mappedData = [];
                if (!empty($draft['mapped_data'])) {
                    $decoded = json_decode((string)$draft['mapped_data'], true);
                    if (is_array($decoded)) {
                        $mappedData = $decoded;
                    }
                }
                foreach ($values as $code => $value) {
                    $attributeCode = trim((string)$code);
                    if ($attributeCode === '') {
                        continue;
                    }
                    $storedValue = is_array($value) ? implode(',', $value) : (string)$value;
                    if (trim($storedValue) === '') {
                        unset($mappedData[$attributeCode]);
                        continue;
                    }
                    $mappedData[$attributeCode] = $storedValue;
                }
                $connection->update(
                    $draftTable,
                    [
                        'mapped_data' => json_encode($mappedData, JSON_UNESCAPED_UNICODE),
                        'updated_at' => $now,
                    ],
                    ['entity_id = ?' => $draftId]
                );
            }

            $draft = $connection->fetchRow($connection->select()->from($draftTable)->where('entity_id = ?', $draftId));
            $summary = $this->completenessService->computeDraftCompleteness((array)$draft);
            $persistedStatus = ((int)$summary['missing_count'] > 0) ? 'incomplete' : 'draft';

            $connection->update(
                $draftTable,
                [
                    'status' => $persistedStatus,
                    'missing_attributes_count' => (int)$summary['missing_count'],
                    'updated_at' => $now,
                ],
                ['entity_id = ?' => $draftId]
            );

            return $result->setData([
                'success' => true,
                'status' => $persistedStatus,
                'variation_attributes' => $variationAttributes,
                'missing_attributes_count' => (int)$summary['missing_count'],
                'filled_required' => (int)$summary['filled_required'],
                'total_required' => (int)$summary['total_required'],
                'progress_percent' => (int)$summary['progress_percent'],
                'message' => 'Draft row saved successfully.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeVariationAttributes($raw): array
    {
        $items = is_array($raw) ? $raw : preg_split('/[\n,]+/', (string)$raw);
        $normalized = [];

        foreach ((array)$items as $item) {
            $code = trim((string)$item);
            if ($code === '' || in_array($code, $normalized, true)) {
                continue;
            }
            $normalized[] = $code;
        }

        return $normalized;
    }
}
