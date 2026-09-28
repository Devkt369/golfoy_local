<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Listing extends Action
{
    const ADMIN_RESOURCE = 'ArtM_PageGroups::main';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    /**
     * List groups by page_id.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $pageId = (int)$this->getRequest()->getParam('page_id');

        if ($pageId <= 0) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid page_id.'),
            ]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_groups');

            if (!$connection->isTableExists($table)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Table %1 does not exist. Run setup:upgrade first.', $table),
                ]);
            }

            $select = $connection->select()
                ->from($table, ['group_id', 'page_id', 'title', 'sort_order'])
                ->where('page_id = ?', $pageId)
                ->order('sort_order ASC')
                ->order('group_id ASC');

            $items = $connection->fetchAll($select);

            // Attach images from artm_pg_group_images
            $imageTable = $this->resourceConnection->getTableName('artm_pg_group_images');
            $groupIds = array_column($items, 'group_id');
            $imagesByGroup = [];
            $imagesById = [];
            if (!empty($groupIds) && $connection->isTableExists($imageTable)) {
                $imgSelect = $connection->select()
                    ->from($imageTable, [
                        'image_id',
                        'group_id',
                        'image_path',
                        'sort_order',
                        'visibility_mode',
                        'start_at',
                        'end_at',
                        'is_hidden'
                    ])
                    ->where('group_id IN (?)', $groupIds)
                    ->order('sort_order ASC')
                    ->order('image_id ASC');
                $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                foreach ($connection->fetchAll($imgSelect) as $img) {
                    $img['image_url'] = $mediaBaseUrl . 'pagegroups/' . ltrim($img['image_path'], '/');
                    $img['attribute_values'] = [];
                    $imagesByGroup[(int)$img['group_id']][] = $img;
                    $imagesById[(int)$img['image_id']] = (int)$img['group_id'];
                }
            }

            // Attach group attributes from catalog
            $attributesTable = $this->resourceConnection->getTableName('artm_pg_attributes');
            $groupAttributesTable = $this->resourceConnection->getTableName('artm_pg_group_attributes');
            $groupAttributesByGroup = [];
            $groupAttributeById = [];
            if (!empty($groupIds)
                && $connection->isTableExists($attributesTable)
                && $connection->isTableExists($groupAttributesTable)
            ) {
                $attrSelect = $connection->select()
                    ->from(['ga' => $groupAttributesTable], ['group_attribute_id', 'group_id', 'sort_order'])
                    ->joinInner(['a' => $attributesTable], 'a.attribute_id = ga.attribute_id', ['attribute_id', 'attribute_code', 'attribute_label' => 'label'])
                    ->where('ga.group_id IN (?)', $groupIds)
                    ->order('ga.sort_order ASC')
                    ->order('ga.group_attribute_id ASC');

                foreach ($connection->fetchAll($attrSelect) as $attr) {
                    $groupAttributesByGroup[(int)$attr['group_id']][] = $attr;
                    $groupAttributeById[(int)$attr['group_attribute_id']] = [
                        'group_id' => (int)$attr['group_id'],
                        'attribute_code' => (string)$attr['attribute_code'],
                    ];
                }
            }

            // Attach per-image values by attribute code
            $valueTable = $this->resourceConnection->getTableName('artm_pg_image_attribute_values');
            if (!empty($imagesById)
                && !empty($groupAttributeById)
                && $connection->isTableExists($valueTable)
            ) {
                $valueSelect = $connection->select()
                    ->from($valueTable, ['image_id', 'group_attribute_id', 'value_text'])
                    ->where('image_id IN (?)', array_keys($imagesById));

                foreach ($connection->fetchAll($valueSelect) as $valueRow) {
                    $imageId = (int)$valueRow['image_id'];
                    $groupAttributeId = (int)$valueRow['group_attribute_id'];
                    if (!isset($groupAttributeById[$groupAttributeId])) {
                        continue;
                    }
                    $meta = $groupAttributeById[$groupAttributeId];
                    $groupId = (int)$meta['group_id'];
                    $attributeCode = (string)$meta['attribute_code'];

                    if (!isset($imagesByGroup[$groupId])) {
                        continue;
                    }

                    foreach ($imagesByGroup[$groupId] as &$imageItem) {
                        if ((int)$imageItem['image_id'] === $imageId) {
                            $imageItem['attribute_values'][$attributeCode] = (string)$valueRow['value_text'];
                            break;
                        }
                    }
                    unset($imageItem);
                }
            }

            foreach ($items as &$item) {
                $item['attributes'] = $groupAttributesByGroup[(int)$item['group_id']] ?? [];
                $item['images'] = $imagesByGroup[(int)$item['group_id']] ?? [];
            }

            return $resultJson->setData([
                'success' => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to load groups: %1', $e->getMessage()),
            ]);
        }
    }
}
