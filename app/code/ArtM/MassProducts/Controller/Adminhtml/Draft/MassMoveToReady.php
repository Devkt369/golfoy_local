<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\Import\DraftCompletenessService;
use ArtM\MassProducts\Model\Logger\ActivityLogger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class MassMoveToReady extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';
    private const LOG_TIMEZONE = 'Asia/Kolkata';

    private $resource;
    private $completenessService;
    private $activityLogger;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DraftCompletenessService $completenessService,
        ActivityLogger $activityLogger
    )
    {
        parent::__construct($context);
        $this->resource = $resource;
        $this->completenessService = $completenessService;
        $this->activityLogger = $activityLogger;
    }

    public function execute()
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $ids = (array)$this->getRequest()->getParam('selected', []);
        if (empty($ids)) {
            $ids = (array)$this->getRequest()->getParam('ids', []);
        }
        try {
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                throw new \RuntimeException('No drafts selected.');
            }
            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');

            $draftRows = $connection->fetchAll(
                $connection->select()
                    ->from($draftTable)
                    ->where('status IN (?)', ['draft', 'incomplete', 'ready', ''])
            );
            $draftsById = [];
            $draftsBySku = [];
            $draftIds = [];
            foreach ($draftRows as $row) {
                $rowId = (int)($row['entity_id'] ?? 0);
                $sku = trim((string)($row['sku'] ?? ''));
                if ($rowId <= 0) {
                    continue;
                }
                $draftsById[$rowId] = $row;
                $draftIds[] = $rowId;
                if ($sku !== '') {
                    $draftsBySku[$sku] = $row;
                }
            }

            $valuesByDraft = [];
            if (!empty($draftIds) && $connection->isTableExists($valueTable)) {
                $valueRows = $connection->fetchAll(
                    $connection->select()
                        ->from($valueTable, ['draft_id', 'attribute_code', 'value'])
                        ->where('draft_id IN (?)', $draftIds)
                        ->where('store_id = ?', 0)
                );
                foreach ($valueRows as $valueRow) {
                    $did = (int)($valueRow['draft_id'] ?? 0);
                    if ($did <= 0) {
                        continue;
                    }
                    if (!isset($valuesByDraft[$did])) {
                        $valuesByDraft[$did] = [];
                    }
                    $valuesByDraft[$did][(string)$valueRow['attribute_code']] = (string)$valueRow['value'];
                }
            }

            foreach ($ids as $draftId) {
                if (!isset($draftsById[$draftId])) {
                    throw new \RuntimeException('Draft row not found: ' . $draftId);
                }

                $draft = $draftsById[$draftId];
                $summary = $this->completenessService->computeDraftCompleteness((array)$draft);
                if ((int)($summary['missing_count'] ?? 0) > 0) {
                    throw new \RuntimeException('Draft ' . (string)($draft['sku'] ?? $draftId) . ' is missing required attributes.');
                }

                $relation = $this->extractRelationData($draft);
                $variationAttributes = $relation['variation_attributes'];
                if (!empty($relation['children']) && empty($variationAttributes)) {
                    throw new \RuntimeException('Configurable ' . (string)($draft['sku'] ?? $draftId) . ' must choose at least one variation attribute before moving to ready.');
                }

                if (!empty($variationAttributes)) {
                    $parentSku = $relation['parent'];
                    if ($parentSku !== '') {
                        $parentRow = $draftsBySku[$parentSku] ?? null;
                        if (!$parentRow) {
                            throw new \RuntimeException('Parent draft ' . $parentSku . ' was not found for ' . (string)($draft['sku'] ?? $draftId) . '.');
                        }
                        $parentRelation = $this->extractRelationData($parentRow);
                        $this->assertVariationValuesFilled(
                            (string)($draft['sku'] ?? ''),
                            $valuesByDraft[(int)$draft['entity_id']] ?? [],
                            $parentRelation['variation_attributes']
                        );
                    }

                    foreach ($relation['children'] as $childSku) {
                        $childRow = $draftsBySku[$childSku] ?? null;
                        if (!$childRow) {
                            throw new \RuntimeException('Child draft ' . $childSku . ' was not found for configurable ' . (string)($draft['sku'] ?? $draftId) . '.');
                        }
                        $this->assertVariationValuesFilled(
                            $childSku,
                            $valuesByDraft[(int)$childRow['entity_id']] ?? [],
                            $variationAttributes
                        );
                    }
                }
            }

            $updated = $connection->update(
                $draftTable,
                [
                    'status' => 'ready',
                    'ready_by' => (($this->_auth->getUser() ? (string)$this->_auth->getUser()->getUserName() : 'system')),
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                ],
                [
                    'entity_id IN (?)' => $ids,
                    'status IN (?)' => ['draft', 'incomplete', '']
                ]
            );
            if ($updated === 0) {
                throw new \RuntimeException('Selected drafts could not be moved to ready.');
            }

            $user = $this->_auth->getUser();
            $adminUserId = $user ? (int)$user->getId() : 0;
            $adminUsername = $user ? (string)$user->getUserName() : 'system';
            $now = new \DateTime('now', new \DateTimeZone(self::LOG_TIMEZONE));

            foreach ($ids as $draftId) {
                $row = $draftsById[$draftId] ?? null;
                if (!$row) {
                    continue;
                }
                $sku = trim((string)($row['sku'] ?? ''));
                $detail = $sku . ' sent to ready by ' . $adminUsername;

                $this->activityLogger->log([
                    'action_type' => 'sent_to_ready',
                    'sku' => $sku,
                    'draft_id' => (int)$draftId,
                    'admin_user_id' => $adminUserId,
                    'admin_username' => $adminUsername,
                    'details' => $detail,
                    'comment' => 'Moved from draft to ready.',
                    'occurred_at' => $now->format('Y-m-d H:i:s'),
                ]);
            }

            return $result->setData([
                'success' => true,
                'updated' => (int)$updated,
                'message' => 'Selected drafts moved to ready.'
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function extractRelationData(array $draft): array
    {
        $parentSku = trim((string)($draft['parent_sku'] ?? ''));
        $childSkus = $this->parseSkuList((string)($draft['child_sku'] ?? ''));
        $variationAttributes = [];

        if (!empty($draft['raw_data'])) {
            $raw = json_decode((string)$draft['raw_data'], true);
            if (is_array($raw)) {
                if ($parentSku === '' && isset($raw['parent'])) {
                    $parentSku = trim((string)$raw['parent']);
                }
                if (empty($childSkus) && isset($raw['child'])) {
                    $childSkus = $this->parseSkuList((string)$raw['child']);
                }
                $variationAttributes = $this->normalizeVariationAttributes($raw['variation_attributes'] ?? []);
            }
        }

        return [
            'parent' => $parentSku,
            'children' => $childSkus,
            'variation_attributes' => $variationAttributes,
        ];
    }

    private function assertVariationValuesFilled(string $sku, array $values, array $variationAttributes): void
    {
        foreach ($variationAttributes as $code) {
            $value = array_key_exists($code, $values) ? $values[$code] : '';
            if (!trim((string)$value)) {
                throw new \RuntimeException('Draft ' . $sku . ' must fill variation attribute "' . $code . '" before moving to ready.');
            }
        }
    }

    private function parseSkuList(string $raw): array
    {
        $list = [];
        foreach (preg_split('/[\n,]+/', $raw) as $token) {
            $sku = trim((string)$token);
            if ($sku === '' || in_array($sku, $list, true)) {
                continue;
            }
            $list[] = $sku;
        }
        return $list;
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
