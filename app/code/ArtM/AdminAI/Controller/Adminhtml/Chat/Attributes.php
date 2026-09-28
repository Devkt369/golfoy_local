<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Chat;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Attributes extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $entityType = trim((string)$this->getRequest()->getParam('entity_type', 'product'));
        $source = trim((string)$this->getRequest()->getParam('source', 'attributes'));
        $attributeSetsRaw = trim((string)$this->getRequest()->getParam('attribute_sets', ''));
        $attributeSetNames = [];
        if ($attributeSetsRaw !== '') {
            $attributeSetNames = array_values(array_unique(array_filter(array_map('trim', preg_split('/[;,]/', $attributeSetsRaw) ?: []))));
        }

        $entityTypeCodeMap = [
            'product' => 'catalog_product',
            'category' => 'catalog_category',
            'customer' => 'customer'
        ];

        $entityTypeCode = $entityTypeCodeMap[$entityType] ?? 'catalog_product';

        try {
            $connection = $this->resourceConnection->getConnection();

            if ($source === 'attribute_sets') {
                $setTable = $this->resourceConnection->getTableName('eav_attribute_set');
                $typeTable = $this->resourceConnection->getTableName('eav_entity_type');

                $select = $connection->select()
                    ->from(['s' => $setTable], ['code' => 'attribute_set_name', 'label' => 'attribute_set_name'])
                    ->join(['t' => $typeTable], 's.entity_type_id = t.entity_type_id', [])
                    ->where('t.entity_type_code = ?', 'catalog_product')
                    ->order('s.attribute_set_name ASC');

                $rows = $connection->fetchAll($select);
                $options = [];
                foreach ($rows as $row) {
                    $name = trim((string)($row['code'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $options[] = ['code' => $name, 'label' => $name];
                }

                return $result->setData([
                    'success' => true,
                    'source' => $source,
                    'attributes' => $options
                ]);
            }

            if ($source === 'attribute_filters') {
                $eaTable = $this->resourceConnection->getTableName('eav_attribute');
                $etTable = $this->resourceConnection->getTableName('eav_entity_type');
                $optTable = $this->resourceConnection->getTableName('eav_attribute_option');
                $optValTable = $this->resourceConnection->getTableName('eav_attribute_option_value');
                $entityAttrTable = $this->resourceConnection->getTableName('eav_entity_attribute');
                $setTable = $this->resourceConnection->getTableName('eav_attribute_set');

                $select = $connection->select()
                    ->from(['ea' => $eaTable], ['attribute_code'])
                    ->join(['et' => $etTable], 'ea.entity_type_id = et.entity_type_id', [])
                    ->join(['o' => $optTable], 'ea.attribute_id = o.attribute_id', [])
                    ->join(['ov' => $optValTable], 'o.option_id = ov.option_id AND ov.store_id = 0', ['option_value' => 'value'])
                    ->where('et.entity_type_code = ?', 'catalog_product')
                    ->where('ea.frontend_input IN (?)', ['select', 'multiselect'])
                    ->where('ov.value IS NOT NULL')
                    ->where('ov.value <> ?', '')
                    ->order('ea.attribute_code ASC')
                    ->order('ov.value ASC');

                if (!empty($attributeSetNames)) {
                    $select->join(['eea' => $entityAttrTable], 'ea.attribute_id = eea.attribute_id', [])
                        ->join(['s' => $setTable], 'eea.attribute_set_id = s.attribute_set_id', [])
                        ->where('s.attribute_set_name IN (?)', $attributeSetNames);
                }

                $rows = $connection->fetchAll($select);
                $options = [];
                foreach ($rows as $row) {
                    $code = trim((string)($row['attribute_code'] ?? ''));
                    $value = trim((string)($row['option_value'] ?? ''));
                    if ($code === '' || $value === '') {
                        continue;
                    }

                    $token = $code . '=' . $value;
                    $options[$token] = ['code' => $token, 'label' => $token];
                }

                return $result->setData([
                    'success' => true,
                    'source' => $source,
                    'attributes' => array_values($options)
                ]);
            }

            $eaTable = $this->resourceConnection->getTableName('eav_attribute');
            $etTable = $this->resourceConnection->getTableName('eav_entity_type');

            $select = $connection->select()
                ->from(['ea' => $eaTable], ['code' => 'attribute_code', 'label' => 'frontend_label'])
                ->join(['et' => $etTable], 'ea.entity_type_id = et.entity_type_id', [])
                ->where('et.entity_type_code = ?', $entityTypeCode)
                ->order('ea.attribute_code ASC');

            $rows = $connection->fetchAll($select);
            $attributes = [];

            foreach ($rows as $row) {
                $code = (string)($row['code'] ?? '');
                if ($code === '') {
                    continue;
                }

                $attributes[] = [
                    'code' => $code,
                    'label' => trim((string)($row['label'] ?? ''))
                ];
            }

            return $result->setData([
                'success' => true,
                'entity_type' => $entityType,
                'source' => $source,
                'attributes' => $attributes
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'entity_type' => $entityType,
                'source' => $source,
                'attributes' => [],
                'message' => 'Unable to load attributes right now.'
            ]);
        }
    }
}
