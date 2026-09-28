<?php

namespace ArtM\PageGroups\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class PageGroup
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var VisibilityResolver
     */
    private $visibilityResolver;

    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        VisibilityResolver $visibilityResolver
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->visibilityResolver = $visibilityResolver;
    }

    /**
     * Returns visible/filtered image array only.
     *
     * @param string $pageIdentifier
     * @param string $groupTitle
     * @return array
     */
    public function getImages($pageIdentifier, $groupTitle)
    {
        $group = $this->getGroupData($pageIdentifier, $groupTitle);
        return $group ? ($group['images'] ?? []) : [];
    }

    /**
     * Returns complete group payload by page + group title.
     *
     * @param string $pageIdentifier
     * @param string $groupTitle
     * @return array|null
     */
    public function getGroupData($pageIdentifier, $groupTitle)
    {
        $pageIdentifier = trim((string)$pageIdentifier);
        $groupTitle = trim((string)$groupTitle);

        if ($pageIdentifier === '' || $groupTitle === '') {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();
        $pagesTable = $this->resourceConnection->getTableName('artm_pg_pages');
        $groupsTable = $this->resourceConnection->getTableName('artm_pg_groups');

        $select = $connection->select()
            ->from(['g' => $groupsTable], ['group_id', 'page_id', 'title'])
            ->joinInner(['p' => $pagesTable], 'p.page_id = g.page_id', ['page_identifier' => 'identifier'])
            ->where('p.identifier = ?', $pageIdentifier)
            ->where('g.title = ?', $groupTitle)
            ->limit(1);

        $group = $connection->fetchRow($select);
        if (!$group) {
            return null;
        }

        return $this->buildGroupPayload($group);
    }

    /**
     * Returns complete group payload by group id.
     *
     * @param int $groupId
     * @return array|null
     */
    public function getGroupDataById($groupId)
    {
        $groupId = (int)$groupId;
        if ($groupId <= 0) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();
        $pagesTable = $this->resourceConnection->getTableName('artm_pg_pages');
        $groupsTable = $this->resourceConnection->getTableName('artm_pg_groups');

        $select = $connection->select()
            ->from(['g' => $groupsTable], ['group_id', 'page_id', 'title'])
            ->joinInner(['p' => $pagesTable], 'p.page_id = g.page_id', ['page_identifier' => 'identifier'])
            ->where('g.group_id = ?', $groupId)
            ->limit(1);

        $group = $connection->fetchRow($select);
        if (!$group) {
            return null;
        }

        return $this->buildGroupPayload($group);
    }

    /**
     * @param array $group
     * @return array|null
     */
    private function buildGroupPayload(array $group)
    {
        $connection = $this->resourceConnection->getConnection();

        $imagesTable = $this->resourceConnection->getTableName('artm_pg_group_images');
        $attributesTable = $this->resourceConnection->getTableName('artm_pg_attributes');
        $groupAttributesTable = $this->resourceConnection->getTableName('artm_pg_group_attributes');
        $valuesTable = $this->resourceConnection->getTableName('artm_pg_image_attribute_values');

        foreach ([$imagesTable, $attributesTable, $groupAttributesTable, $valuesTable] as $tableName) {
            if (!$connection->isTableExists($tableName)) {
                return null;
            }
        }

        $groupId = (int)$group['group_id'];

        $attributes = $connection->fetchAll(
            $connection->select()
                ->from(['ga' => $groupAttributesTable], ['group_attribute_id', 'sort_order'])
                ->joinInner(['a' => $attributesTable], 'a.attribute_id = ga.attribute_id', ['attribute_id', 'attribute_code', 'label'])
                ->where('ga.group_id = ?', $groupId)
                ->order('ga.sort_order ASC')
                ->order('ga.group_attribute_id ASC')
        );

        $images = $connection->fetchAll(
            $connection->select()
                ->from($imagesTable, [
                    'image_id',
                    'image_path',
                    'sort_order',
                    'visibility_mode',
                    'start_at',
                    'end_at',
                    'is_hidden'
                ])
                ->where('group_id = ?', $groupId)
                ->order('sort_order ASC')
                ->order('image_id ASC')
        );

        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $imageIds = array_map('intval', array_column($images, 'image_id'));

        $valuesByImage = [];
        if (!empty($imageIds)) {
            $valueRows = $connection->fetchAll(
                $connection->select()
                    ->from(['v' => $valuesTable], ['image_id', 'value_text'])
                    ->joinInner(['ga' => $groupAttributesTable], 'ga.group_attribute_id = v.group_attribute_id', [])
                    ->joinInner(['a' => $attributesTable], 'a.attribute_id = ga.attribute_id', ['attribute_code'])
                    ->where('v.image_id IN (?)', $imageIds)
                    ->where('ga.group_id = ?', $groupId)
            );

            foreach ($valueRows as $row) {
                $imgId = (int)$row['image_id'];
                $valuesByImage[$imgId][(string)$row['attribute_code']] = (string)$row['value_text'];
            }
        }

        $visibleImages = [];
        foreach ($images as $image) {
            if (!$this->visibilityResolver->isVisible($image)) {
                continue;
            }
            $imageId = (int)$image['image_id'];
            $image['image_url'] = $mediaBaseUrl . 'pagegroups/' . ltrim((string)$image['image_path'], '/');
            $image['values'] = $valuesByImage[$imageId] ?? [];
            $visibleImages[] = $image;
        }

        return [
            'group_id' => $groupId,
            'page_identifier' => (string)$group['page_identifier'],
            'title' => (string)$group['title'],
            'attributes' => $attributes,
            'images' => $visibleImages,
        ];
    }
}
