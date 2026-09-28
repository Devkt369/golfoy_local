<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Attribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class BySet extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $collectionFactory;

    public function __construct(
        Context $context,
        ProductAttributeCollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $attributeSetId = (int)$this->getRequest()->getParam('attribute_set_id');

        if ($attributeSetId <= 0) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid attribute set.')->render(),
            ]);
        }

        $collection = $this->collectionFactory->create();
        $entityAttributeTable = $collection->getTable('eav_entity_attribute');
        $collection->getSelect()
            ->joinInner(
                ['eea' => $entityAttributeTable],
                'main_table.attribute_id = eea.attribute_id AND eea.attribute_set_id = ' . $attributeSetId,
                []
            )
            ->group('main_table.attribute_id')
            ->order('main_table.frontend_label ASC')
            ->order('main_table.attribute_code ASC');

        $attributes = [];
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
        
        foreach ($collection as $attribute) {
            $code = (string)$attribute->getAttributeCode();
            
            // Skip system attributes
            if (in_array($code, $systemAttributes, true)) {
                continue;
            }
            
            $input = (string)$attribute->getFrontendInput();
            $options = [];
            if (in_array($input, ['select', 'multiselect', 'boolean'], true)) {
                try {
                    foreach ((array)$attribute->getSource()->getAllOptions(false) as $option) {
                        if (!empty($option['value']) || $option['value'] === '0') {
                            $options[] = [
                                'value' => (string)$option['value'],
                                'label' => (string)$option['label'],
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    $options = [];
                }
            }

            $attributes[] = [
                'attribute_code' => $code,
                'frontend_label' => (string)($attribute->getFrontendLabel() ?: $attribute->getAttributeCode()),
                'frontend_input' => $input,
                'is_required' => (bool)$attribute->getIsRequired(),
                'options' => $options,
            ];
        }

        return $result->setData([
            'success' => true,
            'attributes' => $attributes,
        ]);
    }
}
