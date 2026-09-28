<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Singleskulayout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Attributes extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    /** @var ResourceConnection */
    private $resource;

    public function __construct(
        Context $context,
        ResourceConnection $resource
    ) {
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
            $catalogEavTable = $connection->getTableName('catalog_eav_attribute');
            $layoutTable = $connection->getTableName('artm_single_sku_layout');

            $entityTypeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($entityTypeTable, ['entity_type_id'])
                    ->where('entity_type_code = ?', 'catalog_product')
                    ->limit(1)
            );

            if ($setId > 0) {
                $rows = $connection->fetchAll(
                    $connection->select()
                        ->from(['ea' => $entityAttrTable], ['sort_order'])
                        ->joinInner(
                            ['a' => $attributeTable],
                            'a.attribute_id = ea.attribute_id',
                            [
                                'attribute_code',
                                'frontend_label',
                                'is_required',
                                'frontend_input',
                                'is_user_defined'
                            ]
                        )
                        ->joinLeft(
                            ['cea' => $catalogEavTable],
                            'cea.attribute_id = a.attribute_id',
                            ['is_visible_on_front' => 'is_visible_on_front']
                        )
                        ->where('ea.attribute_set_id = ?', $setId)
                        ->where('a.entity_type_id = ?', $entityTypeId)
                        ->order('ea.sort_order ASC')
                );
            } else {
                $rows = $connection->fetchAll(
                    $connection->select()
                        ->from(['a' => $attributeTable], [
                            'attribute_code',
                            'frontend_label',
                            'is_required',
                            'frontend_input',
                            'is_user_defined'
                        ])
                        ->joinLeft(
                            ['cea' => $catalogEavTable],
                            'cea.attribute_id = a.attribute_id',
                            ['is_visible_on_front' => 'is_visible_on_front']
                        )
                        ->where('a.entity_type_id = ?', $entityTypeId)
                        ->order('a.attribute_code ASC')
                );
            }

            $savedRows = $connection->fetchAll(
                $connection->select()
                    ->from($layoutTable, ['attribute_code', 'group_name', 'is_enabled', 'sort_order'])
                    ->where('attribute_set_id = ?', $setId)
            );

            $savedMap = [];
            foreach ($savedRows as $savedRow) {
                $savedMap[strtolower((string)$savedRow['attribute_code'])] = [
                    'group_name' => (string)$savedRow['group_name'],
                    'is_enabled' => (int)$savedRow['is_enabled'],
                    'sort_order' => (int)$savedRow['sort_order'],
                ];
            }

            $systemCodes = [
                'price',
                'qty',
                'status',
                'visibility',
                'tax_class_id',
                'category_ids',
                'media_gallery',
                'related_tgtr_position_behavior',
                'upsell_tgtr_position_behavior',
                'crosssell_tgtr_position_behavior',
                'meta_title',
                'meta_keyword',
                'meta_description',
                'custom_design',
                'options_container'
            ];

            $items = [];
            foreach ($rows as $row) {
                $code = strtolower(trim((string)($row['attribute_code'] ?? '')));
                if ($code === '') {
                    continue;
                }

                $label = trim((string)($row['frontend_label'] ?? ''));
                if ($label === '') {
                    $label = $code;
                }

                $isRequired = (int)($row['is_required'] ?? 0) === 1;
                $isUserDefined = (int)($row['is_user_defined'] ?? 0) === 1;
                $isVisibleOnFront = (int)($row['is_visible_on_front'] ?? 0) === 1;
                $isSystem = in_array($code, $systemCodes, true) || !$isUserDefined;

                $defaultGroup = 'Custom Specifications';
                if (in_array($code, ['name', 'sku', 'attribute_set_id', 'type_id'], true)) {
                    $defaultGroup = 'Basic Info';
                } elseif (in_array($code, ['price', 'special_price', 'cost', 'tier_price'], true)) {
                    $defaultGroup = 'Pricing';
                } elseif (in_array($code, ['meta_title', 'meta_keyword', 'meta_description', 'url_key'], true)) {
                    $defaultGroup = 'SEO';
                } elseif (in_array($code, ['qty', 'is_in_stock', 'manage_stock', 'stock_status'], true)) {
                    $defaultGroup = 'Inventory';
                } elseif (in_array($code, ['image', 'small_image', 'thumbnail', 'media_gallery', 'swatch_image'], true)) {
                    $defaultGroup = 'Product Media';
                } elseif (in_array($code, ['weight', 'shipment_type', 'country_of_manufacture'], true)) {
                    $defaultGroup = 'Shipping';
                }

                $saved = $savedMap[$code] ?? null;
                $items[] = [
                    'attribute_code' => $code,
                    'label' => $label,
                    'frontend_input' => (string)($row['frontend_input'] ?? 'text'),
                    'is_required' => $isRequired,
                    'is_optional' => !$isRequired,
                    'is_system' => $isSystem,
                    'is_custom' => $isUserDefined,
                    'is_visible_on_front' => $isVisibleOnFront,
                    'is_enabled' => $saved ? ((int)$saved['is_enabled'] === 1) : $isRequired,
                    'group_name' => $saved ? (string)$saved['group_name'] : $defaultGroup,
                    'sort_order' => $saved ? (int)$saved['sort_order'] : 0,
                ];
            }

            usort($items, static function (array $a, array $b): int {
                return strcasecmp((string)$a['label'], (string)$b['label']);
            });

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
