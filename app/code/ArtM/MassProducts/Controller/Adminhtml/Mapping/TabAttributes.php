<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Mapping;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class TabAttributes extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

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
            $connection = $this->resource->getConnection();
            $entityTypeTable = $connection->getTableName('eav_entity_type');
            $attributeTable = $connection->getTableName('eav_attribute');

            $entityTypeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($entityTypeTable, ['entity_type_id'])
                    ->where('entity_type_code = ?', 'catalog_product')
                    ->limit(1)
            );

            if ($entityTypeId <= 0) {
                return $result->setData([
                    'success' => true,
                    'items' => [],
                ]);
            }

            $attributes = $connection->fetchAll(
                $connection->select()
                    ->from($attributeTable, ['attribute_code', 'frontend_label'])
                    ->where('entity_type_id = ?', $entityTypeId)
                    ->order('attribute_code ASC')
            );

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
            
            foreach ($attributes as $attribute) {
                $code = trim((string)($attribute['attribute_code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                
                // Skip system attributes
                if (in_array($code, $systemAttributes, true)) {
                    continue;
                }

                $frontendLabel = trim((string)($attribute['frontend_label'] ?? ''));
                if ($frontendLabel === '') {
                    $frontendLabel = $code;
                }

                $items[] = [
                    'value' => $code,
                    'label' => $frontendLabel . ' (' . $code . ')',
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
