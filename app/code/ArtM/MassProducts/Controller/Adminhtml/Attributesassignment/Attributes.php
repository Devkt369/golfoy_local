<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Attributesassignment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Attributes extends Action
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
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $setId = (int)$this->getRequest()->getParam('attribute_set_id', 0);
            $connection = $this->resource->getConnection();

            $attributeTable = $connection->getTableName('eav_attribute');
            $entityAttrTable = $connection->getTableName('eav_entity_attribute');
            $entityTypeTable = $connection->getTableName('eav_entity_type');
            $assignmentTable = $connection->getTableName('artm_massproducts_attr_assignment');
            $hasAssignmentTable = $connection->isTableExists($assignmentTable);

            $entityTypeId = (int)$connection->fetchOne(
                $connection->select()->from($entityTypeTable, ['entity_type_id'])->where('entity_type_code = ?', 'catalog_product')->limit(1)
            );

            if ($setId === 0) {
                $rows = $connection->fetchAll(
                    $connection->select()
                        ->from($attributeTable, ['attribute_code', 'frontend_label', 'is_required'])
                        ->where('entity_type_id = ?', $entityTypeId)
                        ->order('attribute_code ASC')
                );
            } else {
                $rows = $connection->fetchAll(
                    $connection->select()
                        ->from(['ea' => $entityAttrTable], [])
                        ->joinInner(
                            ['a' => $attributeTable],
                            'a.attribute_id = ea.attribute_id',
                            ['attribute_code', 'frontend_label', 'is_required']
                        )
                        ->where('ea.attribute_set_id = ?', $setId)
                        ->where('a.entity_type_id = ?', $entityTypeId)
                        ->order('ea.sort_order ASC')
                );
            }

            $globalMap = [];
            $setMap = [];
            if ($hasAssignmentTable) {
                $globalRows = $connection->fetchAll(
                    $connection->select()->from($assignmentTable, ['attribute_code', 'is_enabled'])->where('attribute_set_id = ?', 0)
                );
                foreach ($globalRows as $gr) {
                    $globalMap[(string)$gr['attribute_code']] = (int)$gr['is_enabled'];
                }

                $setRows = $connection->fetchAll(
                    $connection->select()->from($assignmentTable, ['attribute_code', 'is_enabled'])->where('attribute_set_id = ?', $setId)
                );
                foreach ($setRows as $sr) {
                    $setMap[(string)$sr['attribute_code']] = (int)$sr['is_enabled'];
                }
            }

            $items = [];
            $systemAttributes = [
                'dynamic_price',
                'dynamic_sku', 
                'dynamic_weight',
                'price_view',
                'shipment_type',
                'ship_bundle_items',
                'giftcard_amounts',
                'links_purchased_separately',
                'links_title',
                'samples_title'
            ];
            
            foreach ($rows as $row) {
                $code = trim((string)$row['attribute_code']);
                if ($code === '') {
                    continue;
                }
                
                // Skip system attributes
                if (in_array($code, $systemAttributes, true)) {
                    continue;
                }
                
                $label = trim((string)$row['frontend_label']);
                if ($label === '') {
                    $label = $code;
                }

                $globalEnabled = array_key_exists($code, $globalMap) ? ((int)$globalMap[$code] === 1) : true;
                $setEnabled = array_key_exists($code, $setMap) ? ((int)$setMap[$code] === 1) : true;
                $enabled = $setId === 0 ? $globalEnabled : ($globalEnabled && $setEnabled);

                $items[] = [
                    'attribute_code' => $code,
                    'label' => $label . ' (' . $code . ')',
                    'enabled' => $enabled,
                    'global_enabled' => $globalEnabled,
                    'is_required' => (int)($row['is_required'] ?? 0) === 1,
                ];
            }

            return $result->setData([
                'success' => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
